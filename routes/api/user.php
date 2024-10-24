<?php declare(strict_types=1);
use Controllers\Api\Output;
use Api\Checks;
use Controllers\Api\User;
use App\Utilities\IP;
use App\Authentication\JWT;
use App\Authentication\AuthToken;
use App\Logs\SystemLog;

// GET /api/user
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $checks = new Checks($vars, []);
    $checks->apiChecksNoCSRF();

    $user = new User();

    if (!$routeInfo[2]) {
        $allUsers = $user->get(null);
        if ($allUsers) {
            echo Output::success($allUsers, 200);
        } else {
            Output::error('No users found', 404);
        }
        return;
    }

    // This endpoint is for fetching a user's data
    if (!isset($routeInfo[2]['id'])) {
        Output::error('Missing user id', 400);
        exit();
    }

    // If the user is integer, then we will assume it's an id, otherwise we'll assume it's a username
    $userId = $routeInfo[2]['id'];
    if (!is_numeric($userId)) {
        $userId = (string) $userId;
    } else {
        $userId = (int) $userId;
    }

    $userInfoArray = $user->get($userId);

    if ($userInfoArray) {
        echo Output::success($userInfoArray, 200);
    } else {
        Output::error('User not found', 404);
    }
}

// POST /api/user
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!MANUAL_REGISTRATION) {
        Output::error('Manual registration is disabled', 400);
        exit();
    }
    // This endpoint is for creating a new local user.
    $checks = new Checks($vars, $_POST);
    $checks->apiChecksNoUser();

    // Create the user
    $user = new User();

    $data = $_POST;

    unset($data['csrf_token']);

    $requiredFields = ['username', 'password', 'confirm_password', 'email'];

    foreach ($requiredFields as $field) {
        if (!isset($data[$field])) {
            Output::error('Missing ' . $field, 400);
            exit();
        }
        if (empty($data[$field])) {
            Output::error('Empty ' . $field, 400);
            exit();
        }
    }

    $data['last_ips'] = IP::currentIP();

    $data['origin_country'] = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2) : 'EN';

    $data['role'] = 'user';

    $data['theme'] = COLOR_SCHEME;

    $data['provider'] = 'local';

    $data['enabled'] = 1;

    // Decide whether you want to sleep here or not, to slow down the brute force attacks
    // sleep(2);

    echo $user->create($data, 'local');
}


if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    // Let's catch php input stream
    $data = Checks::jsonBody();

    // Also the router info should bring us the id
    if (!isset($routeInfo[2]['id'])) {
        Output::error('Missing user id', 400);
        exit();
    }

    $userId = (int) $routeInfo[2]['id'];

    $checks = new Checks($vars, $data);
    $checks->apiChecks();

    // Get the user data based on the ID
    $user = new User();

    // Make sure that the user submitting this is the same as the user being updated. The only secure way of doing this is by checking the JWT token. This will prevent user from updating another user's data by changing the username paramter's value in the request
    if (isset($data['username']) && JWT::extractUserName(AuthToken::get()) !== $data['username']) {
        Output::error('You are not allowed to update this user', 409);
        exit();
    }

    if (isset($data['passwword'], $data['confirm_password'])) {
        if ($data['password'] !== $data['confirm_password']) {
            Output::error('Passwords do not match', 400);
            exit();
        }
    }

    if (isset($data['password'])) {
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
    }

    unset($data['confirm_password']);
    unset($data['csrf_token']);

    // If we are deleting the picutre, we need to remove it from the filesystem
    if (isset($data['picture'])) {
        if ($data['picture'] === '' || $data['picture'] === 'null' || $data['picture'] === null) {
            // Now let's find the current picture name
            $currentPicture = $user->get($userId)['picture'];
            $profilePicturePath = dirname($_SERVER['DOCUMENT_ROOT']) . '/public' . $currentPicture;
            // Now delete the file
            if (file_exists($profilePicturePath)) {
                unlink($profilePicturePath);
            } else {
                SystemLog::write('Could not delete the picture: ' . $profilePicturePath . '. Full payload was ' . json_encode($data), 'error');
            }
        }
    }

    $user->update($data, (int) $userId);
}
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {

    // Let's check if the csrf token is passed as a query string in the DELETE request
    if (!isset($_GET['csrf_token'])) {
        Output::error('Missing CSRF Token', 401);
        exit();
    }

    // Also the router info should bring us the id
    if (!isset($routeInfo[2]['id'])) {
        Output::error('Missing user id', 400);
        exit();
    }

    $userId = (int) $routeInfo[2]['id'];

    $checks = new Checks($vars, []);
    $checks->apiChecksDelete($_GET['csrf_token']);

    $user = new User();

    $user->delete($userId);
}
