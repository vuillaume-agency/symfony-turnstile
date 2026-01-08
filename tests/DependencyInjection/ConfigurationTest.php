<?php

declare(strict_types=1);

namespace VuillaumeAgency\TurnstileBundle\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;
use VuillaumeAgency\TurnstileBundle\DependencyInjection\Configuration;

final class ConfigurationTest extends TestCase
{
    private Processor $processor;
    private Configuration $configuration;

    protected function setUp(): void
    {
        $this->processor = new Processor();
        $this->configuration = new Configuration();
    }

    public function testDefaultValues(): void
    {
        $config = $this->processor->processConfiguration($this->configuration, []);

        self::assertTrue($config['enable']);
        self::assertSame('%env(TURNSTILE_KEY)%', $config['key']);
        self::assertSame('%env(TURNSTILE_SECRET)%', $config['secret']);
    }

    public function testCustomValues(): void
    {
        $config = $this->processor->processConfiguration($this->configuration, [
            [
                'enable' => false,
                'key' => 'custom-key',
                'secret' => 'custom-secret',
            ],
        ]);

        self::assertFalse($config['enable']);
        self::assertSame('custom-key', $config['key']);
        self::assertSame('custom-secret', $config['secret']);
    }

    public function testPartialConfiguration(): void
    {
        $config = $this->processor->processConfiguration($this->configuration, [
            [
                'key' => 'my-site-key',
            ],
        ]);

        self::assertTrue($config['enable']);
        self::assertSame('my-site-key', $config['key']);
        self::assertSame('%env(TURNSTILE_SECRET)%', $config['secret']);
    }

    public function testEnableCanBeTrue(): void
    {
        $config = $this->processor->processConfiguration($this->configuration, [
            ['enable' => true],
        ]);

        self::assertTrue($config['enable']);
    }

    public function testEnableCanBeFalse(): void
    {
        $config = $this->processor->processConfiguration($this->configuration, [
            ['enable' => false],
        ]);

        self::assertFalse($config['enable']);
    }

    public function testConfigurationMerging(): void
    {
        $config = $this->processor->processConfiguration($this->configuration, [
            ['key' => 'first-key'],
            ['key' => 'second-key'],
        ]);

        self::assertSame('second-key', $config['key']);
    }

    public function testTreeBuilderName(): void
    {
        $treeBuilder = $this->configuration->getConfigTreeBuilder();

        self::assertSame('vuillaume_agency_turnstile', $treeBuilder->buildTree()->getName());
    }
}
