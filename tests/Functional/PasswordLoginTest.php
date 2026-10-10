<?php

declare(strict_types=1);

namespace VuillaumeAgency\TurnstileBundle\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use VuillaumeAgency\TurnstileBundle\Http\TurnstileHttpClientInterface;
use VuillaumeAgency\TurnstileBundle\Tests\Functional\App\Kernel;
use VuillaumeAgency\TurnstileBundle\Tests\Functional\App\TurnstileHttpClientStub;

/**
 * protect_password_login through Symfony's real security system, on every Symfony minor the CI
 * pins: the listener's priority, the authenticator classes it acts on and the ones it leaves
 * alone are facts of the installed security-http, not of this bundle.
 */
final class PasswordLoginTest extends WebTestCase
{
    private const MESSAGE = 'The security check failed. Please try again.';

    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    public function testAMissingTokenIsRefusedAndNoPasswordIsChecked(): void
    {
        $client = static::createClient();

        $client->request('POST', '/login', ['_username' => 'alice', '_password' => 'secret']);

        self::assertResponseRedirects('/login');
        $client->followRedirect();
        self::assertStringContainsString(self::MESSAGE, (string) $client->getResponse()->getContent());
        self::assertSame(0, $this->stub($client)->calls, 'no token: Cloudflare is not asked');
        $this->assertAnonymous($client, '/secured', '/login');
    }

    public function testAnUnknownAndAKnownAccountGetTheSameAnswerWithoutAToken(): void
    {
        // The token is checked before the account is loaded: the answer says nothing about the account.
        $client = static::createClient();

        $client->request('POST', '/login', ['_username' => 'nobody', '_password' => 'x']);
        $client->followRedirect();
        $unknown = (string) $client->getResponse()->getContent();

        $client->request('POST', '/login', ['_username' => 'alice', '_password' => 'x']);
        $client->followRedirect();
        $known = (string) $client->getResponse()->getContent();

        self::assertStringContainsString(self::MESSAGE, $unknown);
        self::assertStringContainsString(self::MESSAGE, $known);
    }

    public function testARefusedTokenIsRefused(): void
    {
        $client = static::createClient();

        $client->request('POST', '/login', ['_username' => 'alice', '_password' => 'secret', 'cf-turnstile-response' => 'bad-token']);

        self::assertResponseRedirects('/login');
        self::assertSame(1, $this->stub($client)->calls);
        $this->assertAnonymous($client, '/secured', '/login');
    }

    public function testAnAcceptedTokenSignsIn(): void
    {
        $client = static::createClient();

        $client->request('POST', '/login', ['_username' => 'alice', '_password' => 'secret', 'cf-turnstile-response' => TurnstileHttpClientStub::VALID_TOKEN]);

        self::assertResponseRedirects('/secured');
        $client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertSame('secured', $client->getResponse()->getContent());
    }

    public function testAWrongPasswordWithAnAcceptedTokenIsStillRefused(): void
    {
        $client = static::createClient();

        $client->request('POST', '/login', ['_username' => 'alice', '_password' => 'wrong', 'cf-turnstile-response' => TurnstileHttpClientStub::VALID_TOKEN]);

        self::assertResponseRedirects('/login');
        $this->assertAnonymous($client, '/secured', '/login');
    }

    public function testACustomLoginFormAuthenticatorIsCovered(): void
    {
        $client = static::createClient();

        $client->request('POST', '/custom/login', ['email' => 'alice', 'password' => 'secret']);
        self::assertResponseRedirects('/custom/login');
        $this->assertAnonymous($client, '/custom/secured', '/custom/login');

        $client->request('POST', '/custom/login', ['email' => 'alice', 'password' => 'secret', 'cf-turnstile-response' => TurnstileHttpClientStub::VALID_TOKEN]);
        self::assertResponseRedirects('/custom/secured');
        $client->followRedirect();
        self::assertSame('secured', $client->getResponse()->getContent());
    }

    public function testHttpBasicIsLeftAlone(): void
    {
        // An API client cannot post a token: the http_basic firewall authenticates without one.
        $client = static::createClient();

        $client->request('GET', '/api/ping', server: ['PHP_AUTH_USER' => 'alice', 'PHP_AUTH_PW' => 'secret']);

        self::assertResponseIsSuccessful();
        self::assertSame('secured', $client->getResponse()->getContent());
        self::assertSame(0, $this->stub($client)->calls);
    }

    public function testTheTwigFunctionRendersTheWidgetOnTheLoginPage(): void
    {
        $client = static::createClient();

        $client->request('GET', '/login');

        self::assertResponseIsSuccessful();
        $html = (string) $client->getResponse()->getContent();
        self::assertStringContainsString('challenges.cloudflare.com/turnstile/v0/api.js', $html);
        self::assertStringContainsString('class="cf-turnstile"', $html);
        self::assertStringContainsString('data-sitekey="site-key"', $html);
        self::assertStringContainsString('data-action="login"', $html);
    }

    private function stub(KernelBrowser $client): TurnstileHttpClientStub
    {
        // Registered under the interface id: the application overrides the bundle's alias.
        $stub = $client->getContainer()->get(TurnstileHttpClientInterface::class);
        \assert($stub instanceof TurnstileHttpClientStub);

        return $stub;
    }

    private function assertAnonymous(KernelBrowser $client, string $securedPath, string $loginPath): void
    {
        $client->request('GET', $securedPath);
        self::assertResponseRedirects($loginPath, message: 'still anonymous');
    }
}
