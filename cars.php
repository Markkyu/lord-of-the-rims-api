<?php

function handleCars($method, $id)
{
    if ($method === "GET" && !$id)
        return getCars();
    if ($method === "GET" && $id)
        return getCar($id);
    if ($method === "POST")
        return addCar();
    if ($method === "PUT" && $id)
        return updateCar($id);
    if ($method === "DELETE" && $id)
        return deleteCar($id);

    respond(["error" => "Invalid car route"], 404);
}

function getCars()
{
    $db = dbConnect();
    $cars = $db->query("SELECT * FROM cars")->fetchAll(PDO::FETCH_ASSOC);
    respond($cars);
}

function getCar($id)
{
    $db = dbConnect();
    $stmt = $db->prepare("SELECT * FROM cars WHERE car_id = ?");
    $stmt->execute([$id]);
    $car = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$car)
        respond(["error" => "Car not found"], 404);

    respond($car);
}

function addCar()
{
    $body = getBody();
    $db = dbConnect();

    $stmt = $db->prepare("INSERT INTO cars (brand, model, type, rate_per_day, transmission_type, gasoline_type, seats) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $body["brand"],
        $body["model"],
        $body["type"],
        $body["rate_per_day"],
        $body["transmission_type"],
        $body["gasoline_type"],
        $body["seats"]
    ]);

    respond(["message" => "Car added"]);
}

function updateCar($id)
{
    $body = getBody();
    $db = dbConnect();

    $stmt = $db->prepare("UPDATE cars SET brand=?, model=?, type=?, rate_per_day=?, transmission_type=?, gasoline_type=?, seats=? WHERE car_id=?");
    $stmt->execute([
        $body["brand"],
        $body["model"],
        $body["type"],
        $body["rate_per_day"],
        $body["transmission_type"],
        $body["gasoline_type"],
        $body["seats"],
        $id
    ]);

    if ($stmt->rowCount() === 0) {
        respond(["error" => "Car not found"], 404);
    }

    respond(["message" => "Car updated"]);
}

function deleteCar($id)
{
    $db = dbConnect();

    $stmt = $db->prepare("DELETE FROM cars WHERE car_id = ?");
    $stmt->execute([$id]);

    if ($stmt->rowCount() === 0) {
        respond(["error" => "Car not found"], 404);
    }

    respond(["message" => "Car deleted"]);
}