<?php
header("Content-Type: application/json");

require_once "db.php";
require_once "helpers.php";

// routes
require_once "auth.php";
require_once "cars.php";
require_once "rentals.php";
require_once "messages.php";
require_once "users.php";
require_once "upload.php"; // <-- IMPORTANT: load upload functions here

$method = $_SERVER["REQUEST_METHOD"];
$uri = explode("/", trim($_SERVER["REQUEST_URI"], "/"));

$route = $uri[1] ?? "";
$id = $uri[2] ?? null;

/*
|--------------------------------------------------------------------------
| SPECIAL ROUTE FOR IMAGE UPLOAD
|--------------------------------------------------------------------------
| POST /api/cars/upload-image/{carId}
|--------------------------------------------------------------------------
*/
if ($route === "cars" && isset($uri[2]) && $uri[2] === "upload-image") {
    $carId = intval($uri[3] ?? 0);

    if ($method === "POST") {
        uploadCarImage($carId);  // <-- function is now loaded
        exit;
    }

    respond(["error" => "Method not allowed"], 405);
}


/*
|--------------------------------------------------------------------------
| MAIN SWITCH ROUTING
|--------------------------------------------------------------------------
*/
switch ($route) {

    case "auth":
        handleAuth($method);
        break;

    case "cars":
        handleCars($method, $id);
        break;

    case "rentals":
        handleRentals($method, $id);
        break;

    case "messages":
        handleMessages($method, $id);
        break;

    case "users":
        handleUsers($method, $id);
        break;

    default:
        http_response_code(404);
        echo json_encode(["error" => "Route not found"]);
}