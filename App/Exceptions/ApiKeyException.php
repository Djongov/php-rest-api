<?php declare(strict_types=1);

// Path: App/Exceptions/FirewallException.php

// Used in /Controllers/Api/Firewall.php, /Models/Api/Firewall.php

namespace App\Exceptions;

class ApiKeyException extends TemplateException
{
    public function apiKeyAlreadyExists() : self
    {
        return new self('Api Key already exists', 409);
    }
    public function apiKeyDoesNotExist() : self
    {
        return new self('Api Key does not exist', 404);
    }
    public function apiKeyNotUpdated() : self
    {
        return new self('Api Key not updated', 500);
    }
    public function invalidApiKey() : self
    {
        return new self('invalid Api Key', 400);
    }
    public function missingApiKey() : self
    {
        return new self('missing Api Key', 401);
    }
}
