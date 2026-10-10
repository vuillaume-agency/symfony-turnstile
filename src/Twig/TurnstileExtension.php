<?php

declare(strict_types=1);

namespace VuillaumeAgency\TurnstileBundle\Twig;

use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * {{ turnstile_widget({'data-action': 'login', 'data-theme': 'light'}) }}.
 *
 * Renders the widget in a plain HTML template, where no form type renders it for you: a login
 * page is the usual case. Same markup as the form field (the api.js script and a div.cf-turnstile
 * carrying the site key), nothing when the bundle is disabled (tests, local development).
 */
final class TurnstileExtension extends AbstractExtension
{
    public function __construct(
        private readonly string $key,
        private readonly bool $enable,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('turnstile_widget', $this->renderWidget(...), ['needs_environment' => true, 'is_safe' => ['html']]),
        ];
    }

    /**
     * @param array<string, scalar|null> $attributes HTML attributes of the widget div (data-action, data-theme, data-size, class, id…)
     */
    public function renderWidget(Environment $twig, array $attributes = []): string
    {
        return $twig->render('@VuillaumeAgencyTurnstile/widget.html.twig', [
            'key' => $this->key,
            'enable' => $this->enable,
            'attributes' => $attributes,
        ]);
    }
}
