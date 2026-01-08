<?php

declare(strict_types=1);

namespace VuillaumeAgency\TurnstileBundle\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use VuillaumeAgency\TurnstileBundle\DependencyInjection\VuillaumeAgencyTurnstileExtension;
use VuillaumeAgency\TurnstileBundle\Http\TurnstileHttpClientInterface;
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
    }

    public function testLoadWithDisabledValidation(): void
    {
        $this->extension->load([
            ['enable' => false],
        ], $this->container);

        self::assertFalse($this->container->getParameter('vuillaume_agency_turnstile.enable'));
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
}
