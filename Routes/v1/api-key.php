<?php

use Controllers\Output;
use Controllers\ApiKey;

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $apiKey = new ApiKey();
    if (empty($GET) && !isset($routeInfo[2]['api-key'])) {
        $all = $apiKey->get(null);
        echo Output::success($all);
        return;
    }
    if (!isset($routeInfo[2]['api-key'])) {
        Output::error('no api key id provided', 400);
    }
    $result = $apiKey->get($routeInfo[2]['api-key']);
    echo Output::success($result);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $apiKey = new ApiKey();
    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data)) {
        Output::error('no data provided', 400);
    }

    $createdBy = $data['createdBy'] ?? Output::error('no createdBy paramter provided', 400);
    $note = $data['note'] ?? Output::error('no note paramter provided', 400);
    $access = $data['access'] ?? Output::error('no access paramter provided', 400);

    $result = $apiKey->create($createdBy, $access, $note);
    echo Output::success($result);
}

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $apiKey = new ApiKey();
    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data)) {
        Output::error('no data provided', 400);
    }

    $result = $apiKey->update($data);
    if (!$result) {
        Output::error('nothing updated', 409);
    } else {
        echo Output::success('successfully updated api key with id ' . $data['id'] . ' with ' . json_encode($data));
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $apiKey = new ApiKey();
    
    // Take the id from the path
    $id = (int) $routeInfo[2]['api-key'];

    $result = $apiKey->delete($id);
    if ($result) {
        echo Output::success('successfully deleted api key with id ' . $id);
    } else {
        Output::error($result, 400);
    }
}
