<?php

declare(strict_types=1);

namespace App\Support\Shlink;

final class ShlinkApiException extends ShlinkException
{
    public function __construct(
        string $message,
        private readonly int $statusCode,
        private readonly ?string $errorType = null,
        private readonly ?string $title = null,
        private readonly ?string $detail = null,
        private readonly ?string $responseBody = null,
        private readonly ?string $requestId = null,
        private readonly ?string $method = null,
        private readonly ?string $url = null,
        private readonly ?array $decodedResponse = null
    ) {
        parent::__construct($message, $statusCode);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getErrorType(): ?string
    {
        return $this->errorType;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getDetail(): ?string
    {
        return $this->detail;
    }

    public function getResponseBody(): ?string
    {
        return $this->responseBody;
    }

    public function getRequestId(): ?string
    {
        return $this->requestId;
    }

    public function getMethod(): ?string
    {
        return $this->method;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function getDecodedResponse(): ?array
    {
        return $this->decodedResponse;
    }
}
