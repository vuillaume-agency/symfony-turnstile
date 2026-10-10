<?php

declare(strict_types=1);

namespace VuillaumeAgency\TurnstileBundle\Tests\Functional\App;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Twig\Environment;

final class Controller
{
    public function __construct(
        private readonly Environment $twig,
        private readonly AuthenticationUtils $authenticationUtils,
    ) {
    }

    /** GET only: the POST never reaches the controller, the firewall's authenticator takes it. */
    public function login(): Response
    {
        $error = $this->authenticationUtils->getLastAuthenticationError();

        return new Response($this->twig->render('login.html.twig', [
            'error' => $error,
        ]));
    }

    public function secured(): Response
    {
        return new Response('secured');
    }
}
