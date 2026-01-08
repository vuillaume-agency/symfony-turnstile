<?php

declare(strict_types=1);

namespace VuillaumeAgency\TurnstileBundle\Tests\Validator;

use PHPUnit\Framework\TestCase;
use VuillaumeAgency\TurnstileBundle\Validator\CloudflareTurnstile;
use VuillaumeAgency\TurnstileBundle\Validator\CloudflareTurnstileValidator;

final class CloudflareTurnstileTest extends TestCase
{
    public function testDefaultMessages(): void
    {
        $constraint = new CloudflareTurnstile();

        self::assertSame('turnstile.missing_response', $constraint->missingResponseMessage);
        self::assertSame('turnstile.verification_failed', $constraint->verificationFailedMessage);
    }

    public function testCustomMessages(): void
    {
        $constraint = new CloudflareTurnstile(
            missingResponseMessage: 'Custom missing message',
            verificationFailedMessage: 'Custom failed message',
        );

        self::assertSame('Custom missing message', $constraint->missingResponseMessage);
        self::assertSame('Custom failed message', $constraint->verificationFailedMessage);
    }

    public function testCustomGroups(): void
    {
        $constraint = new CloudflareTurnstile(
            groups: ['registration', 'login'],
        );

        self::assertContains('registration', $constraint->groups);
        self::assertContains('login', $constraint->groups);
    }

    public function testDefaultGroups(): void
    {
        $constraint = new CloudflareTurnstile();

        self::assertIsArray($constraint->groups);
        self::assertContains('Default', $constraint->groups);
    }

    public function testPayload(): void
    {
        $payload = ['severity' => 'high'];
        $constraint = new CloudflareTurnstile(payload: $payload);

        self::assertSame($payload, $constraint->payload);
    }

    public function testValidatedBy(): void
    {
        $constraint = new CloudflareTurnstile();

        self::assertSame(CloudflareTurnstileValidator::class, $constraint->validatedBy());
    }

    public function testTargets(): void
    {
        $constraint = new CloudflareTurnstile();

        // Default target is PROPERTY
        self::assertSame(CloudflareTurnstile::PROPERTY_CONSTRAINT, $constraint->getTargets());
    }
}
