<?php

declare(strict_types=1);

namespace Tests\Unit\Shlink;

use App\Support\Shlink\ShlinkApiException;
use App\Support\Shlink\ShlinkClient;
use PHPUnit\Framework\TestCase;

final class ShlinkClientIntegrationTest extends TestCase
{
    public function test_request_sends_api_key_and_accept_json_headers(): void
    {
        $captured = [];

        $transport = function (string $method, string $url, array $headers, ?string $body, int $timeout) use (&$captured): array {
            $captured = compact("method", "url", "headers", "body", "timeout");

            return [
                "status" => 200,
                "headers" => ["content-type" => "application/json"],
                "body" => json_encode(["shortUrls" => ["pagination" => ["totalItems" => 0]]]),
            ];
        };

        $client = new ShlinkClient(
            baseUrl: "https://api-shlink.vr766.com",
            apiKey: "secret-key",
            apiVersion: 3,
            timeoutSeconds: 15,
            transport: $transport,
        );

        $response = $client->request("GET", "/short-urls", ["itemsPerPage" => 1]);

        $this->assertSame("GET", $captured["method"]);
        $this->assertSame(
            "https://api-shlink.vr766.com/rest/v3/short-urls?itemsPerPage=1",
            $captured["url"],
        );
        $this->assertSame("application/json", $captured["headers"]["Accept"] ?? null);
        $this->assertSame("secret-key", $captured["headers"]["X-Api-Key"] ?? null);
        $this->assertSame(15, $captured["timeout"]);
        $this->assertNull($captured["body"]);
        $this->assertSame(0, $response["shortUrls"]["pagination"]["totalItems"]);
    }

    public function test_request_strips_legacy_rest_suffix_from_base_url(): void
    {
        $captured = [];

        $transport = function (string $method, string $url, array $headers, ?string $body, int $timeout) use (&$captured): array {
            $captured["url"] = $url;

            return ["status" => 200, "headers" => ["content-type" => "application/json"], "body" => "{}"];
        };

        $client = new ShlinkClient(
            baseUrl: "https://api-shlink.vr766.com/rest/v3/",
            apiKey: "secret-key",
            apiVersion: 3,
            timeoutSeconds: 20,
            transport: $transport,
        );

        $client->request("GET", "/short-urls");

        $this->assertSame("https://api-shlink.vr766.com/rest/v3/short-urls", $captured["url"]);
    }

    public function test_request_propagates_api_exception_on_unauthorized(): void
    {
        $transport = static function (): array {
            return [
                "status" => 401,
                "headers" => ["content-type" => "application/problem+json", "x-request-id" => "req-123"],
                "body" => json_encode([
                    "type" => "INVALID_API_KEY",
                    "title" => "Invalid API key",
                    "detail" => "The provided API key does not exist.",
                ]),
            ];
        };

        $client = new ShlinkClient(
            baseUrl: "https://api-shlink.vr766.com",
            apiKey: "wrong-key",
            apiVersion: 3,
            timeoutSeconds: 20,
            transport: $transport,
        );

        try {
            $client->request("GET", "/short-urls");
            $this->fail("Expected ShlinkApiException for HTTP 401.");
        } catch (ShlinkApiException $e) {
            $this->assertSame(401, $e->getStatusCode());
            $this->assertStringContainsString("The provided API key does not exist.", $e->getMessage());
            $this->assertSame("Invalid API key", $e->getTitle());
            $this->assertSame("The provided API key does not exist.", $e->getDetail());
            $this->assertSame("req-123", $e->getRequestId());
        }
    }

    public function test_request_propagates_api_exception_on_forbidden(): void
    {
        $transport = static function (): array {
            return [
                "status" => 403,
                "headers" => ["content-type" => "application/problem+json"],
                "body" => json_encode(["title" => "Forbidden", "detail" => "Domain not allowed."]),
            ];
        };

        $client = new ShlinkClient(
            baseUrl: "https://api-shlink.vr766.com",
            apiKey: "secret-key",
            apiVersion: 3,
            timeoutSeconds: 20,
            transport: $transport,
        );

        $this->expectException(ShlinkApiException::class);
        $this->expectExceptionCode(403);

        $client->request("GET", "/short-urls");
    }
}
