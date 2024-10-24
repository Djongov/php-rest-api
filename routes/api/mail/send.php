<?php declare(strict_types=1);
use App\Mail\Send;
use Api\Checks;

$checks = new Checks($vars, $_POST);

$checks->checkParams(['recipients', 'subject', 'body'], $_POST);

// We need the most strict checks for this endpoint
$checks->apiAdminChecks();

// First let's check the recipients, they should be comma separated
$recipients = explode(';', $_POST['recipients']);

// Now we must prepare the recipients array
$recipients = array_map(function ($recipient) {
    return [
        'email' => $recipient,
    ];
}, $recipients);

// Now let's send the email
echo Send::send($recipients, $_POST['subject'], $_POST['body']);
