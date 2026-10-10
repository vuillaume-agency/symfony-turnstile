<?php

declare(strict_types=1);

namespace VuillaumeAgency\TurnstileBundle\Tests\Functional\App;

use VuillaumeAgency\TurnstileBundle\Http\TurnstileHttpClientInterface;

/** Only VALID_TOKEN verifies; every other token is a challenge Cloudflare rejects. */
final class TurnstileHttpClientStub implements TurnstileHttpClientInterface
{
    public const VALID_TOKEN = 'valid-token';

    public int $calls = 0;

    public function verifyResponse(string $turnstileResponse): bool
    {
        ++$this->calls;

        return self::VALID_TOKEN === $turnstileResponse;
    }
}
