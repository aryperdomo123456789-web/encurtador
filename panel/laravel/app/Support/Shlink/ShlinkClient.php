<?php

declare(strict_types=1);

namespace App\Support\Shlink;

use DateTimeInterface;

final class ShlinkClient
{
    /**
     * @param callable|null $transport
     */
    public function __construct(
        private string $baseUrl,
        private string $apiKey,
        private int $apiVersion = 3,
        private int $timeoutSeconds = 20,
        private $transport = null
    ) {
        $this->baseUrl = $this->normalizeBaseUrl($baseUrl);
        $this->apiKey = trim($apiKey);

        if ($this->apiKey === '') {
            throw new InvalidShlinkConfigurationException('Shlink API key can not be empty.');
        }
    }

    public function createShortUrl(array $payload): array
    {
        return $this->request('POST', '/short-urls', [], $payload);
    }

    public function createDomain(array $payload): array
    {
        return $this->request('POST', '/domains', [], $payload);
    }

    public function listDomains(array $query = []): array
    {
        return $this->request('GET', '/domains', $query);
    }

    public function getShortUrlVisits(string $shortCode, array $query = []): array
    {
        return $this->request('GET', '/short-urls/' . rawurlencode($shortCode) . '/visits', $query);
    }

    public function deleteShortUrl(string $shortCode): void
    {
        $this->request('DELETE', '/short-urls/' . rawurlencode($shortCode));
    }

    public function getDomainVisits(string $domain, array $query = []): array
    {
        return $this->request('GET', '/domains/' . rawurlencode($domain) . '/visits', $query);
    }

    public function request(string $method, string $path, array $query = [], ?array $payload = null, array $headers = []): array
    {
        $url = $this->buildUrl($path, $query);
        $requestHeaders = array_merge(
            [
                'Accept' => 'application/json',
                'X-Api-Key' => $this->apiKey,
            ],
            $headers
        );

        $body = null;
        if ($payload !== null) {
            $requestHeaders['Content-Type'] = 'application/json';
            $body = $this->encodeJson($payload);
        }

        [$statusCode, $responseHeaders, $responseBody] = $this->sendHttpRequest($method, $url, $requestHeaders, $body);
        $decoded = $this->decodeResponseBody($responseBody, $responseHeaders);

        if ($statusCode >= 400) {
            throw $this->buildApiException($statusCode, $decoded, $responseBody, $responseHeaders, $method, $url);
        }

        if ($responseBody === '' || $statusCode === 204) {
            return [];
        }

        if (!is_array($decoded)) {
            throw new ShlinkUnexpectedResponseException(
                $method . ' ' . $url . ' returned a non-JSON response.',
                $statusCode,
                $responseBody
            );
        }

        return $decoded;
    }

    public function normalizeDateTime(DateTimeInterface $dateTime): string
    {
        return $dateTime->format(DATE_ATOM);
    }

    private function buildUrl(string $path, array $query = []): string
    {
        $path = '/' . ltrim($path, '/');
        $url = rtrim($this->baseUrl, '/') . '/rest/v' . $this->apiVersion . $path;

        if ($query === []) {
            return $url;
        }

        $query = $this->normalizeArrayForQuery($query);
        $queryString = http_build_query($query, '', '&', PHP_QUERY_RFC3986);

        return $queryString === '' ? $url : $url . '?' . $queryString;
    }

    private function normalizeBaseUrl(string $baseUrl): string
    {
        $baseUrl = trim($baseUrl);
        $baseUrl = preg_replace('~/rest/v\d+/?$~', '', $baseUrl) ?? $baseUrl;
        $baseUrl = preg_replace('~/rest/?$~', '', $baseUrl) ?? $baseUrl;

        return rtrim($baseUrl, '/');
    }

    private function normalizeArrayForQuery(array $value): array
    {
        $normalized = [];

        foreach ($value as $key => $item) {
            if ($item === null) {
                continue;
            }

            if (is_array($item)) {
                $normalized[$key] = $this->normalizeArrayForQuery($item);
                continue;
            }

            if ($item instanceof DateTimeInterface) {
                $normalized[$key] = $this->normalizeDateTime($item);
                continue;
            }

            if (is_bool($item)) {
                $normalized[$key] = $item ? 'true' : 'false';
                continue;
            }

            $normalized[$key] = $item;
        }

        return $normalized;
    }

    private function normalizeArrayForJson(array $value): array
    {
        $normalized = [];

        foreach ($value as $key => $item) {
            if ($item === null) {
                continue;
            }

            if (is_array($item)) {
                $normalized[$key] = $this->normalizeArrayForJson($item);
                continue;
            }

            if ($item instanceof DateTimeInterface) {
                $normalized[$key] = $this->normalizeDateTime($item);
                continue;
            }

            $normalized[$key] = $item;
        }

        return $normalized;
    }

    private function encodeJson(array $payload): string
    {
        $json = json_encode($this->normalizeArrayForJson($payload), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($json === false) {
            throw new InvalidShlinkConfigurationException('Failed to encode request payload as JSON.');
        }

        return $json;
    }

    private function sendHttpRequest(string $method, string $url, array $headers, ?string $body): array
    {
        if (is_callable($this->transport)) {
            $result = ($this->transport)($method, $url, $headers, $body, $this->timeoutSeconds);

            $statusCode = (int) ($result['status'] ?? 0);
            $responseHeaders = $this->normalizeResponseHeaders($result['headers'] ?? $result['responseHeaders'] ?? []);
            $responseBody = (string) ($result['body'] ?? '');

            return [$statusCode, $responseHeaders, $responseBody];
        }

        if (function_exists('curl_init')) {
            return $this->sendWithCurl($method, $url, $headers, $body);
        }

        return $this->sendWithStreams($method, $url, $headers, $body);
    }

    private function sendWithCurl(string $method, string $url, array $headers, ?string $body): array
    {
        $curl = curl_init($url);
        if ($curl === false) {
            throw new ShlinkTransportException('Unable to initialize a cURL request.');
        }

        $headerLines = [];
        $formattedHeaders = $this->formatHeaders($headers);

        curl_setopt_array($curl, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeoutSeconds,
            CURLOPT_HTTPHEADER => $formattedHeaders,
            CURLOPT_HEADERFUNCTION => static function ($ch, string $headerLine) use (&$headerLines): int {
                $headerLines[] = $headerLine;

                return strlen($headerLine);
            },
        ]);

        if ($body !== null) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
        }

        $responseBody = curl_exec($curl);

        if ($responseBody === false) {
            $error = curl_error($curl) ?: 'Unknown cURL error.';
            curl_close($curl);

            throw new ShlinkTransportException('Shlink request failed: ' . $error);
        }

        $statusCode = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        curl_close($curl);

        return [$statusCode, $this->parseHeaderLines($headerLines), (string) $responseBody];
    }

    private function sendWithStreams(string $method, string $url, array $headers, ?string $body): array
    {
        $context = stream_context_create([
            'http' => [
                'method' => $method,
                'header' => implode("\r\n", $this->formatHeaders($headers)),
                'content' => $body,
                'ignore_errors' => true,
                'timeout' => $this->timeoutSeconds,
            ],
        ]);

        $responseBody = @file_get_contents($url, false, $context);
        $responseBody = $responseBody === false ? '' : $responseBody;

        $responseHeaders = [];
        $statusCode = 0;

        if (isset($http_response_header) && is_array($http_response_header)) {
            $responseHeaders = $this->parseHeaderLines($http_response_header);

            if (isset($http_response_header[0]) && preg_match('/^HTTP\/\d(?:\.\d)?\s+(\d{3})/', $http_response_header[0], $matches) === 1) {
                $statusCode = (int) $matches[1];
            }
        }

        if ($statusCode === 0) {
            throw new ShlinkTransportException('Unable to determine the HTTP status code for the Shlink response.');
        }

        return [$statusCode, $responseHeaders, $responseBody];
    }

    private function parseHeaderLines(array $headerLines): array
    {
        $headers = [];

        foreach ($headerLines as $line) {
            $line = trim((string) $line);
            if ($line === '' || stripos($line, 'HTTP/') === 0) {
                continue;
            }

            $parts = explode(':', $line, 2);
            if (count($parts) !== 2) {
                continue;
            }

            $name = strtolower(trim($parts[0]));
            $value = trim($parts[1]);

            if (array_key_exists($name, $headers)) {
                $headers[$name] .= ', ' . $value;
                continue;
            }

            $headers[$name] = $value;
        }

        return $headers;
    }

    private function normalizeResponseHeaders(array $headers): array
    {
        if ($headers === []) {
            return [];
        }

        $assoc = [];
        $isAssoc = array_keys($headers) !== range(0, count($headers) - 1);

        if ($isAssoc) {
            foreach ($headers as $name => $value) {
                $assoc[strtolower((string) $name)] = trim((string) $value);
            }

            return $assoc;
        }

        return $this->parseHeaderLines($headers);
    }

    private function formatHeaders(array $headers): array
    {
        $formatted = [];

        foreach ($headers as $name => $value) {
            if ($value === null) {
                continue;
            }

            $formatted[] = $name . ': ' . $value;
        }

        return $formatted;
    }

    private function decodeResponseBody(string $responseBody, array $responseHeaders): ?array
    {
        $responseBody = trim($responseBody);

        if ($responseBody === '') {
            return null;
        }

        $contentType = strtolower($responseHeaders['content-type'] ?? '');
        $looksJson = str_contains($contentType, 'json') || str_starts_with($responseBody, '{') || str_starts_with($responseBody, '[');

        if (!$looksJson) {
            return null;
        }

        $decoded = json_decode($responseBody, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        return is_array($decoded) ? $decoded : null;
    }

    private function buildApiException(
        int $statusCode,
        ?array $decoded,
        string $responseBody,
        array $responseHeaders,
        string $method,
        string $url
    ): ShlinkApiException {
        $title = is_array($decoded) ? (string) ($decoded['title'] ?? '') : '';
        $detail = is_array($decoded) ? (string) ($decoded['detail'] ?? '') : '';
        $type = is_array($decoded) ? (string) ($decoded['type'] ?? '') : '';
        $requestId = $responseHeaders['x-request-id'] ?? null;
        $message = $this->buildStatusMessage($statusCode, $title, $detail, $responseBody);

        return new ShlinkApiException(
            $message,
            $statusCode,
            $type !== '' ? $type : null,
            $title !== '' ? $title : null,
            $detail !== '' ? $detail : null,
            $responseBody !== '' ? $responseBody : null,
            $requestId !== '' ? $requestId : null,
            $method,
            $url,
            $decoded
        );
    }

    private function buildStatusMessage(int $statusCode, string $title, string $detail, string $responseBody): string
    {
        $prefix = 'HTTP ' . $statusCode . ' ' . $this->statusText($statusCode);
        $message = $detail !== '' ? $detail : ($title !== '' ? $title : trim($responseBody));

        if ($message === '') {
            return $prefix;
        }

        return $prefix . ': ' . $message;
    }

    private function statusText(int $statusCode): string
    {
        return match ($statusCode) {
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            409 => 'Conflict',
            410 => 'Gone',
            422 => 'Unprocessable Entity',
            429 => 'Too Many Requests',
            500 => 'Internal Server Error',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            default => 'HTTP Error',
        };
    }
}
