<?php

declare(strict_types=1);

namespace VuillaumeAgency\TurnstileBundle\Tests\Security;

use LogicException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;
use VuillaumeAgency\TurnstileBundle\Http\TurnstileHttpClientInterface;
use VuillaumeAgency\TurnstileBundle\Security\TurnstileLoginListener;

final class TurnstileLoginListenerTest extends TestCase
{
    private const MESSAGE = 'The security check failed. Please try again.';

    public function testAMissingTokenIsRefusedWithoutCallingCloudflare(): void
    {
        $client = $this->client(expectedCalls: 0);
        $listener = new TurnstileLoginListener($client, $this->requestStack(null), true, self::MESSAGE);

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage(self::MESSAGE);

        $listener($this->event($this->loginFormAuthenticator(), $this->passwordPassport()));
    }

    public function testARefusedTokenIsRefused(): void
    {
        $client = $this->client(expectedCalls: 1, accepts: false);
        $listener = new TurnstileLoginListener($client, $this->requestStack('bad-token'), true, self::MESSAGE);

        $this->expectException(AuthenticationException::class);

        $listener($this->event($this->loginFormAuthenticator(), $this->passwordPassport()));
    }

    public function testAnAcceptedTokenLetsThePassportThrough(): void
    {
        $client = $this->client(expectedCalls: 1, accepts: true);
        $listener = new TurnstileLoginListener($client, $this->requestStack('good-token'), true, self::MESSAGE);

        $listener($this->event($this->loginFormAuthenticator(), $this->passwordPassport()));

        $this->addToAssertionCount(1); // no exception
    }

    public function testThePassportOfAnotherAuthenticatorIsLeftAlone(): void
    {
        // http_basic and json_login also carry PasswordCredentials, but their clients cannot post
        // a token: only login-form authenticators are concerned.
        $client = $this->client(expectedCalls: 0);
        $listener = new TurnstileLoginListener($client, $this->requestStack(null), true, self::MESSAGE);

        $listener($this->event($this->otherAuthenticator(), $this->passwordPassport()));

        $this->addToAssertionCount(1);
    }

    public function testAPassportWithoutPasswordCredentialsIsLeftAlone(): void
    {
        $client = $this->client(expectedCalls: 0);
        $listener = new TurnstileLoginListener($client, $this->requestStack(null), true, self::MESSAGE);

        $passport = new SelfValidatingPassport(new UserBadge('alice', fn () => new InMemoryUser('alice', null)));
        $listener($this->event($this->loginFormAuthenticator(), $passport));

        $this->addToAssertionCount(1);
    }

    public function testNothingHappensWhenTheBundleIsDisabled(): void
    {
        $client = $this->client(expectedCalls: 0);
        $listener = new TurnstileLoginListener($client, $this->requestStack(null), false, self::MESSAGE);

        $listener($this->event($this->loginFormAuthenticator(), $this->passwordPassport()));

        $this->addToAssertionCount(1);
    }

    public function testTheConfiguredMessageIsTheOneThrown(): void
    {
        $listener = new TurnstileLoginListener($this->client(expectedCalls: 0), $this->requestStack(null), true, 'Custom sentence.');

        try {
            $listener($this->event($this->loginFormAuthenticator(), $this->passwordPassport()));
            self::fail('A missing token must be refused.');
        } catch (CustomUserMessageAuthenticationException $e) {
            self::assertSame('Custom sentence.', $e->getMessageKey());
        }
    }

    private function client(int $expectedCalls, bool $accepts = false): TurnstileHttpClientInterface
    {
        $client = $this->createMock(TurnstileHttpClientInterface::class);
        $client->expects(self::exactly($expectedCalls))->method('verifyResponse')->willReturn($accepts);

        return $client;
    }

    private function requestStack(?string $token): RequestStack
    {
        $stack = new RequestStack();
        $request = Request::create('/login', 'POST', null === $token ? [] : ['cf-turnstile-response' => $token]);
        $stack->push($request);

        return $stack;
    }

    private function passwordPassport(): Passport
    {
        return new Passport(new UserBadge('alice', fn () => new InMemoryUser('alice', 'secret')), new PasswordCredentials('secret'));
    }

    private function event(AuthenticatorInterface $authenticator, Passport $passport): CheckPassportEvent
    {
        return new CheckPassportEvent($authenticator, $passport);
    }

    private function loginFormAuthenticator(): AuthenticatorInterface
    {
        return new class extends AbstractLoginFormAuthenticator {
            public function authenticate(Request $request): Passport
            {
                throw new LogicException('not called');
            }

            public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
            {
                return null;
            }

            protected function getLoginUrl(Request $request): string
            {
                return '/login';
            }
        };
    }

    private function otherAuthenticator(): AuthenticatorInterface
    {
        return new class implements AuthenticatorInterface {
            public function supports(Request $request): ?bool
            {
                return true;
            }

            public function authenticate(Request $request): Passport
            {
                throw new LogicException('not called');
            }

            public function createToken(Passport $passport, string $firewallName): TokenInterface
            {
                throw new LogicException('not called');
            }

            public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
            {
                return null;
            }

            public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
            {
                return null;
            }
        };
    }
}
