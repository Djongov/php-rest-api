<?php declare(strict_types=1);

namespace App\Exceptions;

class ContentSecurityPolicyExceptions extends ExceptionsTemplate
{
    public function CSPNotFound() : self
    {
        return new self('api key not found', 404);
    }
    public function noCSPFound() : self
    {
        return new self('no api keys found', 404);
    }
    public function CSPNotCreated() : self
    {
        return new self('api key not created', 500);
    }
    public function CSPNotDeleted() : self
    {
        return new self('api key not deleted', 500);
    }
    public function CSPNotUpdated() : self
    {
        return new self('api key not updated', 500);
    }
    public function missingColumn(string $column) : self
    {
        return new self('missing column: ' . $column, 400);
    }
}
