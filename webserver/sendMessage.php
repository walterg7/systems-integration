<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once('/var/www/rabbitmqphp_example/path.inc');
require_once('/var/www/rabbitmqphp_example/get_host_info.inc');
require_once('/var/www/rabbitmqphp_example/rabbitMQLib.inc');

$email = $_POST['email'];
$password = $_POST['password'];

// Send the data to recMessage.php via RabbitMQ
$request = array(
    'type' => 'login',
    'email' => $email,
    'password' => $password
);

$client = new rabbitMQClient("/var/www/rabbitmqphp_example/testRabbitMQ.ini", "testServer");

$response = $client->send_request($request);

// Debug the response
var_dump($response);

// If the login was successful, redirect to the dashboard
if ($response['returnCode'] == 0) {
    header("Location: dashboard.html");
    exit();
} else {
    echo "Login failed: " . $response['message'];
}
?>

