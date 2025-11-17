<?php
// users.php
require_once "db.php";
require_once "helpers.php";

function handleUsers($method, $id)
{
    if ($method === "GET" && !$id)
        return listUsers();
    if ($method === "GET" && $id)
        return getUser($id);
    if ($method === "PUT" && $id)
        return updateUser($id);
    if ($method === "DELETE" && $id)
        return deleteUser($id);

    respond(["error" => "Invalid users route"], 404);
}

function listUsers()
{
    $db = dbConnect();
    $stmt = $db->query("SELECT id, username, created_at FROM users ORDER BY created_at DESC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    respond($users);
}

function getUser($id)
{
    $db = dbConnect();
    $stmt = $db->prepare("SELECT id, username, created_at FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user)
        respond(["error" => "User not found"], 404);
    respond($user);
}

function updateUser($id)
{
    $body = getBody();
    $db = dbConnect();

    // allow only username change for simplicity (don't allow direct password change here)
    if (empty($body["username"]))
        respond(["error" => "username required"], 400);

    $stmt = $db->prepare("UPDATE users SET username = ? WHERE id = ?");
    $stmt->execute([$body["username"], $id]);

    if ($stmt->rowCount() === 0)
        respond(["error" => "User not found or no change made"], 404);
    respond(["message" => "User updated"]);
}

function deleteUser($id)
{
    $db = dbConnect();
    $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$id]);

    if ($stmt->rowCount() === 0)
        respond(["error" => "User not found"], 404);
    respond(["message" => "User deleted"]);
}