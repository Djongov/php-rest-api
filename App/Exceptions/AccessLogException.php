<?php declare(strict_types=1);

// Path: App/Exceptions/FirewallException.php

// Used in /Controllers/Api/Firewall.php, /Models/Api/Firewall.php

namespace App\Exceptions;

class AccessLogException extends TemplateException
{
    public function notDeleted() : self
    {
        return new self('Not deleted', 500);
    }
}
