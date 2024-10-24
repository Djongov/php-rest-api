<?php declare(strict_types=1);

namespace App\Exceptions;

// This trait is used to handle user-specific exceptions
class UserExceptions extends TemplateException
{
    public function userAlreadyExists() : self
    {
        return new self('User already exists', 409);
    }

    public function userNotFound() : self
    {
        return new self('User not found', 404);
    }

    public function userNotCreated() : self
    {
        return new self('User not created', 500);
    }

    public function userNotDeleted() : self
    {
        return new self('User not deleted', 500);
    }
    public function userNotUpdated() : self
    {
        return new self('Nothing to update', 409);
    }
}
