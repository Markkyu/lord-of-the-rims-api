<?php
function uploadCarImage($carId)
{
    if (!isset($_FILES["image"])) {
        respond(["error" => "No image uploaded"], 400);
    }

    $image = $_FILES["image"];

    if ($image["error"] !== 0) {
        respond(["error" => "Upload error"], 400);
    }

    $allowed = ["image/jpeg", "image/png", "image/jpg"];
    if (!in_array($image["type"], $allowed)) {
        respond(["error" => "Only JPG and PNG allowed"], 400);
    }

    $ext = pathinfo($image["name"], PATHINFO_EXTENSION);
    $filename = "car_" . $carId . "_" . uniqid() . "." . $ext;

    $target = "uploads/cars/" . $filename;

    if (!move_uploaded_file($image["tmp_name"], $target)) {
        respond(["error" => "Failed to save file"], 500);
    }

    $db = dbConnect();
    $stmt = $db->prepare("INSERT INTO car_images (car_id, image_path) VALUES (?, ?)");
    $stmt->execute([$carId, $filename]);

    respond([
        "message" => "Image uploaded",
        "file" => $filename,
        "url" => "http://localhost/api/uploads/cars/" . $filename
    ]);
}