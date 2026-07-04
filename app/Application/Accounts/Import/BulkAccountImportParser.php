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
     * Parse one line for live preview and validation.
     */
    public function parsePreviewLine(string $line, int $lineNumber): AccountImportRecord
    {
        return $this->parseLine($line, $lineNumber);
    }

    /**
     * Parse a single import line.
     */
    protected function parseLine(string $line, int $lineNumber): AccountImportRecord
    {
        $normalizedLine = trim($line, " \t\n\r\0\x0B|");

        if (str_starts_with($normalizedLine, '!')) {
            return $this->parseBangLine($normalizedLine, $lineNumber);
        }

        return $this->parseWhitespaceLine($normalizedLine, $lineNumber);
    }

    /**
     * Parse the legacy !server!username!password format.
     */
    protected function parseBangLine(string $line, int $lineNumber): AccountImportRecord
    {
        $parts = explode('!', $line);

        if (count($parts) < 4 || $parts[0] !== '') {
            throw new InvalidArgumentException("Line {$lineNumber} has an invalid format.");
        }

        $serverUrl = $this->normalizeServerUrl($parts[1], $lineNumber);
        $username = trim($parts[2]);
        $password = trim($parts[3]);
        $proxy = $this->parseProxyParts($parts[4] ?? null, $parts[5] ?? null, $lineNumber);
        $userAgentOffset = $proxy['consumed_port_part'] ? 6 : 5;
        $userAgent = $this->normalizeNullableString(implode('!', array_slice($parts, $userAgentOffset)) ?: null);

        if ($username === '' || $password === '') {
            throw new InvalidArgumentException("Line {$lineNumber} must include username and password.");
        }

        if (($proxy['ip'] === null) xor ($proxy['port'] === null)) {
            throw new InvalidArgumentException("Line {$lineNumber} must provide proxy as host:port or protocol://host:port.");
        }

        return new AccountImportRecord(
            serverUrl: $serverUrl,
            username: $username,
            password: $password,
            proxyScheme: $proxy['scheme'],
            proxyIp: $proxy['ip'],
            proxyPort: $proxy['port'],
            proxyUsername: $proxy['username'],
            proxyPassword: $proxy['password'],
            userAgent: $userAgent,
        );
    }

    /**
     * Parse server username password proxy_url user_agent.
     */
    protected function parseWhitespaceLine(string $line, int $lineNumber): AccountImportRecord
    {
        $parts = preg_split('/\s+/u', trim($line)) ?: [];

        if (count($parts) < 3) {
            throw new InvalidArgumentException("Line {$lineNumber} has an invalid format.");
        }

        $serverUrl = $this->normalizeServerUrl($parts[0], $lineNumber);
        $username = trim($parts[1]);
        $password = trim($parts[2]);
        $proxy = $this->parseProxyParts($parts[3] ?? null, $parts[4] ?? null, $lineNumber);
        $userAgentOffset = $proxy['consumed_port_part'] ? 5 : 4;
        $userAgent = $this->normalizeNullableString(implode(' ', array_slice($parts, $userAgentOffset)) ?: null);

        if ($username === '' || $password === '') {
            throw new InvalidArgumentException("Line {$lineNumber} must include username and password.");
        }

        if (($proxy['ip'] === null) xor ($proxy['port'] === null)) {
            throw new InvalidArgumentException("Line {$lineNumber} must provide proxy as host:port or protocol://host:port.");
        }

        return new AccountImportRecord(
            serverUrl: $serverUrl,
            username: $username,
            password: $password,
            proxyScheme: $proxy['scheme'],
            proxyIp: $proxy['ip'],
            proxyPort: $proxy['port'],
            proxyUsername: $proxy['username'],
            proxyPassword: $proxy['password'],
            userAgent: $userAgent,
        );
    }

    /**
     * @return array{
     *     scheme: string,
     *     ip: ?string,
     *     port: ?int,
     *     username: ?string,
     *     password: ?string,
     *     consumed_port_part: bool
     * }
     */
    protected function parseProxyParts(?string $proxyHostOrUri, ?string $proxyPort, int $lineNumber): array
    {
        $proxyValue = $this->normalizeNullableString($proxyHostOrUri);

        if ($proxyValue === null) {
            $parsedPort = $this->normalizeNullablePort($proxyPort, $lineNumber);

            return [
                'scheme' => 'http',
                'ip' => null,
                'port' => $parsedPort,
                'username' => null,
                'password' => null,
                'consumed_port_part' => $parsedPort !== null,
            ];
        }

        if ($this->isProxyUri($proxyValue)) {
            return [
                ...$this->parseProxyUri($proxyValue, $lineNumber),
                'consumed_port_part' => false,
            ];
        }

        if ($this->looksLikeHostPort($proxyValue)) {
            return [
                ...$this->parseProxyUri('http://'.$proxyValue, $lineNumber),
                'consumed_port_part' => false,
            ];
        }

        return [
            'scheme' => 'http',
            'ip' => $proxyValue,
            'port' => $this->normalizeNullablePort($proxyPort, $lineNumber),
            'username' => null,
            'password' => null,
            'consumed_port_part' => true,
        ];
    }

    /**
     * Determine whether the imported proxy contains a protocol prefix.
     */
    protected function isProxyUri(string $proxyValue): bool
    {
        return preg_match('/^[a-z][a-z0-9+.-]*:\/\//i', $proxyValue) === 1;
    }

    /**
     * Determine whether a proxy value is already a complete host:port endpoint.
     */
    protected function looksLikeHostPort(string $proxyValue): bool
    {
        $parts = parse_url('http://'.$proxyValue);

        return is_array($parts)
            && $this->normalizeNullableString($parts['host'] ?? null) !== null
            && isset($parts['port'])
            && is_int($parts['port']);
    }

    /**
     * @return array{scheme: string, ip: string, port: int, username: ?string, password: ?string}
     */
    protected function parseProxyUri(string $proxyUri, int $lineNumber): array
    {
        $parts = parse_url($proxyUri);

        if (! is_array($parts)) {
            throw new InvalidArgumentException("Line {$lineNumber} contains an invalid proxy URL.");
        }

        $scheme = Str::lower((string) ($parts['scheme'] ?? ''));
        $allowedSchemes = ['http', 'https', 'socks4', 'socks4a', 'socks5', 'socks5h'];

        if (! in_array($scheme, $allowedSchemes, true)) {
            throw new InvalidArgumentException("Line {$lineNumber} contains an unsupported proxy protocol.");
        }

        $host = $this->normalizeNullableString($parts['host'] ?? null);
        $port = $parts['port'] ?? null;

        if ($host === null || ! is_int($port)) {
            throw new InvalidArgumentException("Line {$lineNumber} proxy URL must include host and port.");
        }

        return [
            'scheme' => $scheme,
            'ip' => $host,
            'port' => $this->normalizeNullablePort((string) $port, $lineNumber),
            'username' => isset($parts['user']) ? rawurldecode((string) $parts['user']) : null,
            'password' => isset($parts['pass']) ? rawurldecode((string) $parts['pass']) : null,
        ];
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
