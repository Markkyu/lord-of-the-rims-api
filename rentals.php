<?php
// rentals.php
require_once "db.php";
require_once "helpers.php";

function handleRentals($method, $id)
{
    // /api/rentals
    if ($method === "POST" && !$id)
        return createRental();
    if ($method === "GET" && !$id)
        return listRentals();
    if ($method === "GET" && $id)
        return getRental($id);

    // custom: GET /api/rentals/user/{id} -> list rentals for user
    // Note: index.php currently passes id for /api/rentals/{id}, so we expect callers to use /api/rentals/user/{id}
    // If index.php isn't forwarding that, you'd call a dedicated route. We'll support query param user_id as well.
    if ($method === "GET" && isset($_GET["user_id"]))
        return listRentalsByUser($_GET["user_id"]);

    // Update status: PUT /api/rentals/{id}/status  with body { "status": "approved" }
    if ($method === "PUT" && $id && isset($GLOBALS['routeSegment3']) && $GLOBALS['routeSegment3'] === "status") {
        return updateRentalStatus($id);
    }

    // fallback for update full rental: PUT /api/rentals/{id}
    if ($method === "PUT" && $id)
        return updateRental($id);

    respond(["error" => "Invalid rentals route"], 404);
}

function createRental()
{
    $body = getBody();
    $required = ["user_id", "car_id", "start_date", "end_date"];
    foreach ($required as $r) {
        if (empty($body[$r]))
            respond(["error" => "Missing field: $r"], 400);
    }

    // basic date validation (YYYY-MM-DD)
    if (!strtotime($body["start_date"]) || !strtotime($body["end_date"])) {
        respond(["error" => "Invalid date format"], 400);
    }

    $db = dbConnect();

    // Optionally check availability here (simple overlap check)
    $stmt = $db->prepare("
        SELECT COUNT(*) AS cnt FROM rentals
        WHERE car_id = ? AND status IN ('pending','approved')
        AND NOT (end_date < ? OR start_date > ?)
    ");
    $stmt->execute([$body["car_id"], $body["start_date"], $body["end_date"]]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row && $row["cnt"] > 0) {
        respond(["error" => "Car is not available for the selected dates"], 409);
    }

    $stmt = $db->prepare("
        INSERT INTO rentals (user_id, car_id, start_date, end_date, status, created_at)
        VALUES (?, ?, ?, ?, 'pending', NOW())
    ");
    $stmt->execute([$body["user_id"], $body["car_id"], $body["start_date"], $body["end_date"]]);

    $rentalId = $db->lastInsertId();
    respond(["message" => "Rental created", "rental_id" => $rentalId], 201);
}

function listRentals()
{
    $db = dbConnect();
    $stmt = $db->query("SELECT r.*, u.username, c.brand, c.model FROM rentals r
                        LEFT JOIN users u ON r.user_id = u.id
                        LEFT JOIN cars c ON r.car_id = c.car_id
                        ORDER BY r.created_at DESC");
    $rentals = $stmt->fetchAll(PDO::FETCH_ASSOC);
    respond($rentals);
}

function listRentalsByUser($userId)
{
    $db = dbConnect();
    $stmt = $db->prepare("SELECT r.*, c.brand, c.model FROM rentals r
                          LEFT JOIN cars c ON r.car_id = c.car_id
                          WHERE r.user_id = ?
                          ORDER BY r.created_at DESC");
    $stmt->execute([$userId]);
    $rentals = $stmt->fetchAll(PDO::FETCH_ASSOC);
    respond($rentals);
}

function getRental($id)
{
    $db = dbConnect();
    $stmt = $db->prepare("SELECT r.*, u.username, c.brand, c.model FROM rentals r
                          LEFT JOIN users u ON r.user_id = u.id
                          LEFT JOIN cars c ON r.car_id = c.car_id
                          WHERE r.rental_id = ?");
    $stmt->execute([$id]);
    $rental = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$rental)
        respond(["error" => "Rental not found"], 404);
    respond($rental);
}

function updateRental($id)
{
    $body = getBody();
    $db = dbConnect();

    // Allow updating start_date and end_date (and nothing else for simplicity)
    if (empty($body["start_date"]) || empty($body["end_date"])) {
        respond(["error" => "start_date and end_date required"], 400);
    }

    $stmt = $db->prepare("UPDATE rentals SET start_date = ?, end_date = ? WHERE rental_id = ?");
    $stmt->execute([$body["start_date"], $body["end_date"], $id]);

    if ($stmt->rowCount() === 0)
        respond(["error" => "Rental not found or no change made"], 404);
    respond(["message" => "Rental updated"]);
}

function updateRentalStatus($id)
{
    $body = getBody();
    if (empty($body["status"]))
        respond(["error" => "Missing status"], 400);

    $allowed = ["pending", "approved", "rejected", "returned"];
    if (!in_array($body["status"], $allowed))
        respond(["error" => "Invalid status"], 400);

    $db = dbConnect();
    $stmt = $db->prepare("UPDATE rentals SET status = ? WHERE rental_id = ?");
    $stmt->execute([$body["status"], $id]);

    if ($stmt->rowCount() === 0)
        respond(["error" => "Rental not found or nothing changed"], 404);
    respond(["message" => "Rental status updated"]);
}