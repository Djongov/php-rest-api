<?php declare(strict_types=1);

namespace App\Exceptions;

class ExceptionsTemplate extends \Exception implements ExceptionInterface
{
    public function genericError(string $message, int $code) : self
    {
        return new self($message, $code);
    }
    /* API onness */
    public function emptyData() : self
    {
        return new self('no data provided', 400);
    }
    public function noParamter($paramter): self
    {
        return new self('no ' . $paramter . ' provided', 400);
    }
    public function emptyParameter($paramter) : self
    {
        return new self($paramter . ' value is empty', 400);
    }
    public function parameterNoInt($paramter) : self
    {
        return new self($paramter . ' is not an integer', 400);
    }
    public function parameterNoString($paramter) : self
    {
        return new self($paramter . ' is not a string', 400);
    }
    public function parameterNoBool($paramter) : self
    {
        return new self($paramter . ' is not a boolean', 400);
    }
    public function notSaved() : self
    {
        return new self('data not saved', 500);
    }
}
