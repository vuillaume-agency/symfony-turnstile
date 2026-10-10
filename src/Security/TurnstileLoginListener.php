<?php

declare(strict_types=1);

namespace VuillaumeAgency\TurnstileBundle\Security;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;
use VuillaumeAgency\TurnstileBundle\Http\TurnstileHttpClientInterface;

/**
 * Verifies the Turnstile token of a password login while the passport is checked.
 *
 * Symfony's form_login reads the login POST itself, outside any form type, so a TurnstileType
 * field cannot cover the login page. This listener does, for form_login and for every
 * authenticator extending AbstractLoginFormAuthenticator (the shape make:security:form-login and
 * the documentation use). It leaves alone every other way in — http_basic and json_login (API
 * clients cannot post a token), login_link, remember_me, OAuth, Security::login() — because those
 * either carry no PasswordCredentials badge or come from another authenticator class.
 *
 * Registered at priority 300 on CheckPassportEvent: after the CSRF listener (512) and before the
 * account is loaded (256), so a refused token says nothing about whether the account exists. The
 * refusal is an AuthenticationException: login_throttling counts it, the login page shows the
 * message through error.messageKey|trans(error.messageData, 'security').
 *
 * A Turnstile token is accepted once by siteverify: a project that verified it in its own
 * listener or authenticator removes that code when it turns the option on.
 */
final class TurnstileLoginListener
{
    public function __construct(
        private readonly TurnstileHttpClientInterface $turnstileHttpClient,
        private readonly RequestStack $requestStack,
        private readonly bool $enable,
        private readonly string $message,
    ) {
    }

    public function __invoke(CheckPassportEvent $event): void
    {
        if (!$this->enable) {
            return;
        }

        if (!$event->getAuthenticator() instanceof AbstractLoginFormAuthenticator) {
            return;
        }

        if (!$event->getPassport()->hasBadge(PasswordCredentials::class)) {
            return;
        }

        $request = $this->requestStack->getCurrentRequest();
        $token = null === $request ? '' : (string) $request->request->get('cf-turnstile-response', '');

        if ('' === $token || !$this->turnstileHttpClient->verifyResponse($token)) {
            throw new CustomUserMessageAuthenticationException($this->message);
        }
    }
}
