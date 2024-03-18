<?php

declare(strict_types=1);

namespace App\Exceptions;

class ExceptionsTemplate extends \Exception implements ExceptionInterface
{
    public function genericError(string $message, int $code) : self
    {
        return new self($message, $code);
    }
}
