<?php

function getBody()
{
    return json_decode(file_get_contents("php://input"), true);
}

function respond($data, $code = 200)
{
    http_response_code($code);
    echo json_encode($data);
    exit;
}