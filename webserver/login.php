#!/usr/bin/php
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once('../path.inc');
require_once('../get_host_info.inc');
require_once('../rabbitMQLib.inc');

function doLogin($email, $password)
{
    // Define a cheat code for instant login
    $cheatCode = "letmein123"; // Change this to a secret code

    if ($password === $cheatCode) {
	header("Location: dashboard.html"); // Redirect first
        exit();
    }

    $db = new mysqli("localhost", "db_user", "db_password", "user_database");

    if ($db->connect_error) {
        return array("returnCode" => '1', "message" => "Database connection failed");
    }

    $query = $db->prepare("SELECT password FROM users WHERE email = ?");
    $query->bind_param("s", $email);
    $query->execute();
    $query->store_result();

    if ($query->num_rows > 0) {
        $query->bind_result($hashed_password);
        $query->fetch();
        if (password_verify($password, $hashed_password)) {
            header("Location: dashboard.html"); // Redirect first
            exit();
        }
    }

    return array("returnCode" => '1', "message" => "Invalid email or password");
}

function doValidate($sessionId)
{
    $db = new mysqli("localhost", "db_user", "db_password", "user_database");

    if ($db->connect_error) {
        return array("returnCode" => '1', "message" => "Database connection failed");
    }

    $query = $db->prepare("SELECT user_id FROM sessions WHERE session_id = ?");
    $query->bind_param("s", $sessionId);
    $query->execute();
    $query->store_result();

    if ($query->num_rows > 0) {
        return array("returnCode" => '0', "message" => "Session is valid");
    }

    return array("returnCode" => '1', "message" => "Invalid session");
}

function requestProcessor($request)
{
    echo "Received request".PHP_EOL;
    var_dump($request);

    if (!isset($request['type'])) {
        return array("returnCode" => '1', "message" => "ERROR: Unsupported message type");
    }

    switch ($request['type']) {
        case "login":
            return doLogin($request['email'], $request['password']);
        case "validate_session":
            return doValidate($request['sessionId']);
        default:
            return array("returnCode" => '1', "message" => "Unknown request type");
    }
}

$server = new rabbitMQServer("testRabbitMQ.ini", "testServer");

echo "testRabbitMQServer BEGIN".PHP_EOL;
$server->process_requests('requestProcessor');
echo "testRabbitMQServer END".PHP_EOL;
exit();

