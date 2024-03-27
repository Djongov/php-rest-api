<?php

declare(strict_types=1);

namespace App\Exceptions;

class ApiKeyException extends ExceptionsTemplate
{
    public function apiKeyNotFound() : self
    {
        return new self('api key not found', 404);
    }
    public function noApiKeyFound() : self
    {
        return new self('no api keys found', 404);
    }
    public function apiKeyNotCreated() : self
    {
        return new self('api key not created', 500);
    }
    public function apiKeyNotDeleted() : self
    {
        return new self('api key not deleted', 500);
    }
    public function apiKeyNotUpdated() : self
    {
        return new self('api key not updated', 500);
    }
}
