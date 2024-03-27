<?php

declare(strict_types=1);

// Path: App/Exceptions/ExceptionInterface.php

namespace App\Exceptions;

interface ExceptionInterface
{
    public function genericError(string $message, int $code): self;
}
