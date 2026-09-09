<?php

declare(strict_types=1);

namespace RadioApi\Exceptions;

use RuntimeException;

class ApiException extends RuntimeException
{
    /**
     * @param  array<string, mixed>  $response
     * @param  array<string, string>  $headers
     */
    public function __construct(
        public readonly int $status,
        public readonly ?string $errorCode,
        string $message,
        public readonly array $response = [],
        public readonly array $headers = [],
    ) {
        parent::__construct($message, $status);
    }
}
