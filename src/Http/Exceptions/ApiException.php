<?php

namespace Aliff\ChipIn\Http\Exceptions;

use Exception;

class ApiException extends Exception
{
    /**
     * HTTP status code returned from CHIP API (if any).
     *
     * @var int|null
     */
    protected ?int $statusCode;

    /**
     * Response payload received from CHIP API (if any).
     *
     * @var array|null
     */
    protected ?array $payload;

    /**
     * Create a new ApiException instance.
     *
     * @param  string       $message
     * @param  int|null     $statusCode
     * @param  array|null   $payload
     * @param  \Throwable|null $previous
     */
    public function __construct(
        string $message = "Unexpected error from CHIP API",
        ?int $statusCode = null,
        ?array $payload = null,
        \Throwable $previous = null
    ) {
        parent::__construct($message, $statusCode ?? 0, $previous);

        $this->statusCode = $statusCode;
        $this->payload    = $payload;
    }

    /**
     * Get the HTTP status code (if available).
     *
     * @return int|null
     */
    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }

    /**
     * Get the response payload from the API (if available).
     *
     * @return array|null
     */
    public function getPayload(): ?array
    {
        return $this->payload;
    }

    /**
     * Get a more detailed error description combining message + payload.
     *
     * @return string
     */
    public function getDetailedMessage(): string
    {
        $details = $this->getMessage();

        if ($this->payload) {
            $details .= ' | Payload: ' . json_encode($this->payload);
        }

        if ($this->statusCode) {
            $details .= ' | HTTP status: ' . $this->statusCode;
        }

        return $details;
    }
}
