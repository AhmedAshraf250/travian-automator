<?php

namespace App\Application\Accounts\Session\Data;

/**
 * Represents a normalized HTTP response returned by an account session.
 */
final readonly class SessionResponse
{
    /**
     * Create a normalized session response DTO.
     *
     * @param  array<string, list<string>>  $headers
     */
    public function __construct(
        public int $statusCode,
        public string $body,
        public string $effectiveUri,
        public array $headers,
    ) {}

    /**
     * Determine whether the response status code indicates success.
     */
    public function successful(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }
}
