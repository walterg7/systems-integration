<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once('/var/www/rabbitmqphp_example/path.inc');
require_once('/var/www/rabbitmqphp_example/get_host_info.inc');
require_once('/var/www/rabbitmqphp_example/rabbitMQLib.inc');

$email = isset($_POST['email']) ? $_POST['email'] : "";
$password = isset($_POST['password']) ? $_POST['password'] : "";

// Prepare request
$request = array(
    'type' => 'login',
    'email' => $email,
    'password' => $password
);

// Connect to RabbitMQ
$client = new rabbitMQClient("/var/www/rabbitmqphp_example/testRabbitMQ.ini", "testServer");

// Only send request if email and password are set
if (!empty($email) && !empty($password)) {
    $response = $client->send_request($request);

    // Debugging: Check what the server responds with
    error_log("Login Response: " . json_encode($response));

    if ($response['returnCode'] == 0) {
        // Success: Redirect to dashboard.html
        header("Location: dashboard.html");
        exit();
    } else {
        // Failure: Redirect back to index.html with an error
        header("Location: index.html?error=" . urlencode($response['message']));
        exit();
    }
} else {
    // Missing email/password, redirect back with an error
    header("Location: index.html?error=" . urlencode("Email and password are required."));
    exit();
}
?>

