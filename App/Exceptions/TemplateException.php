<?php declare(strict_types=1);

namespace App\Exceptions;

class TemplateException extends \Exception
{
    public function notSaved($message = 'Not saved', $code = 500) : self
    {
        return new self($message, $code);
    }
    public function generic($message, $code) : self
    {
        return new self($message, $code);
    }
}
