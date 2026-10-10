<?php

declare(strict_types=1);

namespace VuillaumeAgency\TurnstileBundle\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;
use VuillaumeAgency\TurnstileBundle\DependencyInjection\VuillaumeAgencyTurnstileExtension;
use VuillaumeAgency\TurnstileBundle\Http\TurnstileHttpClientInterface;
use VuillaumeAgency\TurnstileBundle\Security\TurnstileLoginListener;
use VuillaumeAgency\TurnstileBundle\Twig\TurnstileExtension;
use VuillaumeAgency\TurnstileBundle\Type\TurnstileType;
use VuillaumeAgency\TurnstileBundle\Validator\CloudflareTurnstileValidator;

final class VuillaumeAgencyTurnstileExtensionTest extends TestCase
{
    private ContainerBuilder $container;
    private VuillaumeAgencyTurnstileExtension $extension;

    protected function setUp(): void
    {
        $this->container = new ContainerBuilder();
        $this->extension = new VuillaumeAgencyTurnstileExtension();
    }

    public function testLoadSetsParameters(): void
    {
        $this->extension->load([
            [
                'key' => 'test-key',
                'secret' => 'test-secret',
                'enable' => true,
            ],
        ], $this->container);

        self::assertTrue($this->container->hasParameter('vuillaume_agency_turnstile.key'));
        self::assertTrue($this->container->hasParameter('vuillaume_agency_turnstile.secret'));
        self::assertTrue($this->container->hasParameter('vuillaume_agency_turnstile.enable'));

        self::assertSame('test-key', $this->container->getParameter('vuillaume_agency_turnstile.key'));
        self::assertSame('test-secret', $this->container->getParameter('vuillaume_agency_turnstile.secret'));
        self::assertTrue($this->container->getParameter('vuillaume_agency_turnstile.enable'));
    }

    public function testLoadWithDefaultValues(): void
    {
        $this->extension->load([], $this->container);

        self::assertSame('%env(TURNSTILE_KEY)%', $this->container->getParameter('vuillaume_agency_turnstile.key'));
        self::assertSame('%env(TURNSTILE_SECRET)%', $this->container->getParameter('vuillaume_agency_turnstile.secret'));
        self::assertTrue($this->container->getParameter('vuillaume_agency_turnstile.enable'));
        self::assertFalse($this->container->getParameter('vuillaume_agency_turnstile.disable_submit_until_verified'));
    }

    public function testLoadWithDisabledValidation(): void
    {
        $this->extension->load([
            ['enable' => false],
        ], $this->container);

        self::assertFalse($this->container->getParameter('vuillaume_agency_turnstile.enable'));
    }

    public function testLoadWithDisableSubmitUntilVerified(): void
    {
        $this->extension->load([
            ['disable_submit_until_verified' => true],
        ], $this->container);

        self::assertTrue($this->container->getParameter('vuillaume_agency_turnstile.disable_submit_until_verified'));
    }

    public function testServicesAreRegistered(): void
    {
        $this->extension->load([], $this->container);

        self::assertTrue($this->container->hasDefinition('turnstile.type'));
        self::assertTrue($this->container->hasDefinition('turnstile.validator'));
        self::assertTrue($this->container->hasDefinition('turnstile.http_client'));
        self::assertTrue($this->container->hasAlias(TurnstileHttpClientInterface::class));
    }

    public function testTurnstileTypeServiceDefinition(): void
    {
        $this->extension->load([], $this->container);

        $definition = $this->container->getDefinition('turnstile.type');

        self::assertSame(TurnstileType::class, $definition->getClass());
        self::assertTrue($definition->hasTag('form.type'));
    }

    public function testValidatorServiceDefinition(): void
    {
        $this->extension->load([], $this->container);

        $definition = $this->container->getDefinition('turnstile.validator');

        self::assertSame(CloudflareTurnstileValidator::class, $definition->getClass());
        self::assertTrue($definition->hasTag('validator.constraint_validator'));
    }

    public function testExtensionAlias(): void
    {
        self::assertSame('vuillaume_agency_turnstile', $this->extension->getAlias());
    }

    public function testTheTwigExtensionIsRegistered(): void
    {
        $this->extension->load([], $this->container);

        $definition = $this->container->getDefinition('turnstile.twig_extension');

        self::assertSame(TurnstileExtension::class, $definition->getClass());
        self::assertTrue($definition->hasTag('twig.extension'));
    }

    public function testTheLoginListenerIsAbsentByDefault(): void
    {
        $this->extension->load([], $this->container);

        self::assertFalse($this->container->hasDefinition('turnstile.login_listener'));
        self::assertFalse($this->container->getParameter('vuillaume_agency_turnstile.protect_password_login'));
    }

    public function testTheLoginListenerIsRegisteredAtPriority300WhenAskedFor(): void
    {
        $this->extension->load([['protect_password_login' => true]], $this->container);

        $definition = $this->container->getDefinition('turnstile.login_listener');

        self::assertSame(TurnstileLoginListener::class, $definition->getClass());
        $tags = $definition->getTag('kernel.event_listener');
        self::assertCount(1, $tags);
        self::assertSame(CheckPassportEvent::class, $tags[0]['event']);
        // After CsrfProtectionListener (512), before UserCheckerListener loads the account (256).
        self::assertSame(300, $tags[0]['priority']);
        self::assertSame('The security check failed. Please try again.', $this->container->getParameter('vuillaume_agency_turnstile.password_login_message'));
    }
}
