<?php

namespace App\Application\Accounts\Import;

/**
 * Represents one parsed account row from the bulk import textarea.
 */
final readonly class AccountImportRecord
{
    /**
     * Create a new parsed import record.
     */
    public function __construct(
        public string $serverUrl,
        public string $username,
        public string $password,
        public string $proxyScheme,
        public ?string $proxyIp,
        public ?int $proxyPort,
        public ?string $proxyUsername,
        public ?string $proxyPassword,
        public ?string $userAgent,
    ) {}
}
