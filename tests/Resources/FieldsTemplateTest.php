<?php

declare(strict_types=1);

namespace VuillaumeAgency\TurnstileBundle\Tests\Resources;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

final class FieldsTemplateTest extends TestCase
{
    public function testCallbacksAreDefinedBeforeTheWidgetCanRender(): void
    {
        $html = $this->render(['disable_submit_until_verified' => true]);

        self::assertStringContainsString('data-callback="turnstile_form_captcha_onSuccess"', $html);
        self::assertStringContainsString('data-error-callback="turnstile_form_captcha_onError"', $html);
        self::assertStringContainsString('data-expired-callback="turnstile_form_captcha_onExpired"', $html);
        self::assertStringContainsString("var widgetId = 'turnstile_form_captcha';", $html);
        self::assertStringContainsString("window[widgetId + '_onSuccess'] = function", $html);
        self::assertStringContainsString("window[widgetId + '_onError'] = function", $html);
        self::assertStringContainsString("window[widgetId + '_onExpired'] = function", $html);

        // Turnstile resolves the callback names once, when it renders the widget, which happens before
        // DOMContentLoaded handlers in Firefox: the globals must exist as soon as the inline script runs.
        self::assertStringNotContainsString("addEventListener('DOMContentLoaded'", $html);
        self::assertStringNotContainsString('document.readyState', $html);
    }

    public function testNoCallbacksWhenSubmitDisablingIsOff(): void
    {
        $html = $this->render(['disable_submit_until_verified' => false]);

        self::assertStringContainsString('data-sitekey="site-key"', $html);
        self::assertStringNotContainsString('data-callback', $html);
        self::assertStringNotContainsString('_onSuccess', $html);
    }

    public function testNothingIsRenderedWhenDisabled(): void
    {
        $html = $this->render(['enable' => false, 'disable_submit_until_verified' => true]);

        self::assertSame('', trim($html));
    }

    public function testClassAttributeIsRenderedOnce(): void
    {
        $html = $this->render(['attr' => ['class' => 'cf-turnstile my-widget', 'data-theme' => 'light']]);

        self::assertSame(1, substr_count($html, ' class='));
        self::assertStringContainsString('class="cf-turnstile my-widget"', $html);
        self::assertStringContainsString('data-theme="light"', $html);
    }

    public function testDefaultClassIsCfTurnstile(): void
    {
        $html = $this->render([]);

        self::assertSame(1, substr_count($html, ' class='));
        self::assertStringContainsString('class="cf-turnstile"', $html);
    }

    /**
     * @param array<string, mixed> $vars
     */
    private function render(array $vars): string
    {
        $twig = new Environment(new FilesystemLoader(\dirname(__DIR__, 2).'/src/Resources/views'));

        return $twig->load('fields.html.twig')->renderBlock('turnstile_widget', $vars + [
            'id' => 'form_captcha',
            'attr' => [],
            'key' => 'site-key',
            'enable' => true,
            'disable_submit_until_verified' => false,
        ]);
    }
}
