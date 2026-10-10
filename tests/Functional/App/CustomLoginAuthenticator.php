<?php

declare(strict_types=1);

namespace VuillaumeAgency\TurnstileBundle\Tests\Functional\App;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;

/** The shape make:auth used to generate: a project login-form authenticator. */
final class CustomLoginAuthenticator extends AbstractLoginFormAuthenticator
{
    public function authenticate(Request $request): Passport
    {
        return new Passport(
            new UserBadge((string) $request->request->get('email', '')),
            new PasswordCredentials((string) $request->request->get('password', '')),
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return new RedirectResponse('/custom/secured');
    }

    protected function getLoginUrl(Request $request): string
    {
        return '/custom/login';
    }
}
