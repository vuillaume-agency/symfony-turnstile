<?php

declare(strict_types=1);

namespace VuillaumeAgency\TurnstileBundle\Tests\Twig;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Loader\ChainLoader;
use Twig\Loader\FilesystemLoader;
use VuillaumeAgency\TurnstileBundle\Twig\TurnstileExtension;

final class TurnstileExtensionTest extends TestCase
{
    public function testTheWidgetCarriesTheSiteKeyTheScriptAndTheAttributes(): void
    {
        $html = $this->render("{{ turnstile_widget({'data-action': 'login', 'data-theme': 'light'}) }}", enable: true);

        self::assertStringContainsString('<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" defer></script>', $html);
        self::assertStringContainsString('data-sitekey="site-key"', $html);
        self::assertStringContainsString('class="cf-turnstile"', $html);
        self::assertStringContainsString('data-action="login"', $html);
        self::assertStringContainsString('data-theme="light"', $html);
    }

    public function testTheClassCanBeExtendedAndIsRenderedOnce(): void
    {
        $html = $this->render("{{ turnstile_widget({'class': 'cf-turnstile my-widget'}) }}", enable: true);

        self::assertSame(1, substr_count($html, 'class="'));
        self::assertStringContainsString('class="cf-turnstile my-widget"', $html);
    }

    public function testAttributeValuesAreEscaped(): void
    {
        $html = $this->render("{{ turnstile_widget({'data-action': '\"><script>'}) }}", enable: true);

        self::assertStringNotContainsString('"><script>', $html);
        self::assertStringContainsString('data-action="&quot;&gt;&lt;script&gt;"', $html);
    }

    public function testNothingIsRenderedWhenDisabled(): void
    {
        self::assertSame('', trim($this->render('{{ turnstile_widget() }}', enable: false)));
    }

    public function testTheLoginMessageIsTranslatedInFrench(): void
    {
        $fr = Yaml::parseFile(__DIR__.'/../../src/Resources/translations/security.fr.yml');
        $en = Yaml::parseFile(__DIR__.'/../../src/Resources/translations/security.en.yml');

        self::assertSame('La vérification de sécurité a échoué. Veuillez réessayer.', $fr['The security check failed. Please try again.']);
        self::assertSame('The security check failed. Please try again.', $en['The security check failed. Please try again.']);
    }

    private function render(string $template, bool $enable): string
    {
        $views = new FilesystemLoader();
        $views->addPath(__DIR__.'/../../src/Resources/views', 'VuillaumeAgencyTurnstile');
        $twig = new Environment(new ChainLoader([new ArrayLoader(['page' => $template]), $views]));
        $twig->addExtension(new TurnstileExtension('site-key', $enable));

        return $twig->render('page');
    }
}
