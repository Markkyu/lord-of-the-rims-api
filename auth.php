<?php

function handleAuth($method)
{
    if ($method === "POST" && $_GET["action"] === "register")
        registerUser();
    if ($method === "POST" && $_GET["action"] === "login")
        loginUser();

    respond(["error" => "Invalid auth route"], 404);
}

function registerUser()
{
    $body = getBody();
    $db = dbConnect();

    if (!isset($body["username"], $body["password"])) {
        respond(["error" => "Missing fields"], 400);
    }

    $hash = password_hash($body["password"], PASSWORD_DEFAULT);

    $stmt = $db->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
    $stmt->execute([$body["username"], $hash]);

    respond(["message" => "User registered"]);
}

function loginUser()
{
    $body = getBody();
    $db = dbConnect();

    $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$body["username"]]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($body["password"], $user["password"])) {
        respond(["error" => "Invalid login"], 401);
    }

    respond(["message" => "Login successful", "user" => $user]);
}