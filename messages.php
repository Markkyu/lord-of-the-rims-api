<?php
// messages.php
require_once "db.php";
require_once "helpers.php";

function handleMessages($method, $id)
{
    // POST /api/messages -> create
    if ($method === "POST" && !$id)
        return createMessage();

    // GET /api/messages?rental_id={rental_id} -> list messages for rental
    if ($method === "GET" && isset($_GET["rental_id"]))
        return getMessagesByRental($_GET["rental_id"]);

    respond(["error" => "Invalid messages route"], 404);
}

function createMessage()
{
    $body = getBody();
    $required = ["sender_id", "receiver_id", "rental_id", "message"];
    foreach ($required as $r) {
        if (empty($body[$r]))
            respond(["error" => "Missing field: $r"], 400);
    }

    $db = dbConnect();
    $stmt = $db->prepare("INSERT INTO messages (rental_id, sender_id, receiver_id, message, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->execute([$body["rental_id"], $body["sender_id"], $body["receiver_id"], $body["message"]]);

    $msgId = $db->lastInsertId();
    respond(["message" => "Message sent", "message_id" => $msgId], 201);
}

function getMessagesByRental($rentalId)
{
    $db = dbConnect();
    $stmt = $db->prepare("SELECT m.*, s.username AS sender, rcv.username AS receiver
                          FROM messages m
                          LEFT JOIN users s ON m.sender_id = s.id
                          LEFT JOIN users rcv ON m.receiver_id = rcv.id
                          WHERE m.rental_id = ?
                          ORDER BY m.created_at ASC");
    $stmt->execute([$rentalId]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    respond($messages);
}