<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ob_start();


session_start();


require_once(__DIR__ . '/../rabbitmqphp_example/RabbitMQ/RabbitMQLib.inc');

use RabbitMQ\RabbitMQClient;

$email = isset($_POST['email']) ? $_POST['email'] : "";
$password = isset($_POST['password']) ? $_POST['password'] : "";


$hashed_password = hash("sha256", $password);


$message = json_encode([
    'action' => 'login',
    'email' => $email,
    'password' => $password
]);


$client = new RabbitMQClient(__DIR__ . '/../rabbitmqphp_example/RabbitMQ/RabbitMQ.ini', 'Database');


if (!empty($email) && !empty($password)) {
    $response = json_decode($client->sendRequest($message), true);

    
    error_log("Login Response: " . json_encode($response));
    error_log("Received Response: " . json_encode($response));

    if ($response['status'] === "success") {
    $_SESSION['email'] = $email;
    $_SESSION['username'] = $response['username'];
    $_SESSION['pending_2fa'] = true;

    header("Location: verify.php");
 
        exit();
    } else {
        
        session_destroy();
        header("Location: index.html?error=" . urlencode($response['message']));
        exit();
    }
} else {
    
    session_destroy();
    header("Location: index.html?error=" . urlencode("Email and password are required."));
    exit();
}
?>

