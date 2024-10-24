<?php declare(strict_types=1);

namespace Api;

use Api\Response;

class Input
{
    // We have no natural way of getting json input so we will use the php://input stream
    public function getJsonInput() : array
    {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        if ($data === null) {
            Response::output('invalid JSON input', 400);
        }
        return $data;
    }
}
