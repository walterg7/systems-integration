<?php
session_start();
require_once 'vendor/autoload.php'; // Ensure you have php-amqplib installed

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Cheat code to bypass authentication
    if ($email === "admin@cheat.com" && $password === "letmein") {
        $_SESSION['email'] = $email;
        header("Location: dashboard.php");
        exit();
    }

    // RabbitMQ Authentication
    $connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
    $channel = $connection->channel();

    $queue_name = 'auth_request';
    $channel->queue_declare($queue_name, false, false, false, false);

    // Create message (JSON)
    $request_data = json_encode(['email' => $email, 'password' => $password]);
    $msg = new AMQPMessage($request_data, ['reply_to' => 'auth_response']);

    // Send login request to RabbitMQ
    $channel->basic_publish($msg, '', $queue_name);

    // Declare response queue
    list($response_queue, ,) = $channel->queue_declare('', false, false, true, false);
    
    // Create a consumer to listen for authentication response
    $callback = function ($response) {
        $auth_response = json_decode($response->body, true);
        if ($auth_response['status'] === 'success') {
            $_SESSION['email'] = $auth_response['email']; // Store email from RabbitMQ response
            header("Location: dashboard.php");
            exit();
        } else {
            echo "Invalid email or password.";
        }
    };

    $channel->basic_consume($response_queue, '', false, true, false, false, $callback);

    // Wait for a response
    while ($channel->is_consuming()) {
        $channel->wait();
    }

    // Close connections
    $channel->close();
    $connection->close();
}
?>

