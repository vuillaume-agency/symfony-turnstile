<?php

declare(strict_types=1);

namespace VuillaumeAgency\TurnstileBundle\Http;

interface TurnstileHttpClientInterface
{
    public function verifyResponse(string $turnstileResponse): bool;
}
