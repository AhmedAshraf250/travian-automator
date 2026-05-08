<?php

namespace App\Application\Accounts\Import;

use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Parses the legacy multi-line import format into normalized account records.
 */
class BulkAccountImportParser
{
    /**
     * Parse textarea content into normalized import records.
     *
     * @return list<AccountImportRecord>
     */
    public function parse(string $contents): array
    {
        $records = [];

        foreach (preg_split('/\R/u', $contents) ?: [] as $lineNumber => $line) {
            $trimmedLine = trim($line);

            if ($trimmedLine === '') {
                continue;
            }

            $records[] = $this->parseLine($trimmedLine, $lineNumber + 1);
        }

        return $records;
    }

    /**
     * Parse a single import line.
     */
    protected function parseLine(string $line, int $lineNumber): AccountImportRecord
    {
        $parts = explode('!', $line);

        if (count($parts) < 4 || $parts[0] !== '') {
            throw new InvalidArgumentException("Line {$lineNumber} has an invalid format.");
        }

        $serverUrl = $this->normalizeServerUrl($parts[1], $lineNumber);
        $username = trim($parts[2]);
        $password = trim($parts[3]);
        $proxyIp = $this->normalizeNullableString($parts[4] ?? null);
        $proxyPort = $this->normalizeNullablePort($parts[5] ?? null, $lineNumber);
        $userAgent = $this->normalizeNullableString(implode('!', array_slice($parts, 6)) ?: null);

        if ($username === '' || $password === '') {
            throw new InvalidArgumentException("Line {$lineNumber} must include username and password.");
        }

        if (($proxyIp === null) xor ($proxyPort === null)) {
            throw new InvalidArgumentException("Line {$lineNumber} must provide both proxy IP and proxy port together.");
        }

        return new AccountImportRecord(
            serverUrl: $serverUrl,
            username: $username,
            password: $password,
            proxyIp: $proxyIp,
            proxyPort: $proxyPort,
            userAgent: $userAgent,
        );
    }

    /**
     * Normalize the imported server URL.
     */
    protected function normalizeServerUrl(string $serverUrl, int $lineNumber): string
    {
        $trimmedServerUrl = trim($serverUrl);

        if ($trimmedServerUrl === '' || ! filter_var($trimmedServerUrl, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException("Line {$lineNumber} contains an invalid server URL.");
        }

        return rtrim(Str::lower($trimmedServerUrl), '/').'/';
    }

    /**
     * Normalize a nullable port value.
     */
    protected function normalizeNullablePort(?string $port, int $lineNumber): ?int
    {
        $normalizedPort = $this->normalizeNullableString($port);

        if ($normalizedPort === null) {
            return null;
        }

        if (! ctype_digit($normalizedPort)) {
            throw new InvalidArgumentException("Line {$lineNumber} contains an invalid proxy port.");
        }

        $integerPort = (int) $normalizedPort;

        if ($integerPort < 1 || $integerPort > 65535) {
            throw new InvalidArgumentException("Line {$lineNumber} contains an out-of-range proxy port.");
        }

        return $integerPort;
    }

    /**
     * Normalize nullable free-form string values.
     */
    protected function normalizeNullableString(?string $value): ?string
    {
        $trimmedValue = trim((string) $value);

        return $trimmedValue === '' ? null : $trimmedValue;
    }
}
