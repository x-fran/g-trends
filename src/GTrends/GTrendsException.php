<?php

declare(strict_types=1);

namespace XFran\GTrends;

use RuntimeException;

class GTrendsException extends RuntimeException
{
    public static function requestFailed(string $uri, int $status): self
    {
        return new self("Google Trends request to $uri failed with HTTP $status");
    }

    public static function unexpectedResponse(string $uri, string $reason): self
    {
        return new self("Unexpected response from $uri: $reason");
    }
}
