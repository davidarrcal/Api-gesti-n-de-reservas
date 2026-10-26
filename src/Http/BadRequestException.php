<?php
declare(strict_types=1);

namespace App\Http;

use RuntimeException;

final class BadRequestException extends RuntimeException
{
    public function __construct(string $message, public readonly array $errors = [])
    {
        parent::__construct($message);
    }
}
