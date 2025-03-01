<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include RabbitMQ dependencies
require_once('/var/www/rabbitmqphp_example/path.inc');
require_once('/var/www/rabbitmqphp_example/get_host_info.inc');
require_once('/var/www/rabbitmqphp_example/rabbitMQLib.inc');

// Function to process requests from sendMessage.php
function requestProcessor($request)
{
    echo "Received request".PHP_EOL;
    var_dump($request);

    if (!isset($request['type'])) {
        echo "Error: Unsupported message type received" . PHP_EOL;
        return array("returnCode" => '1', "message" => "ERROR: Unsupported message type");
    }

    if ($request['type'] == "login") { 
        if (!empty($request['email']) && !empty($request['password'])) {
            echo "Login successful for: " . $request['email'] . PHP_EOL; // Log success to terminal
            return array("returnCode" => '0', "message" => "Valid credentials, redirecting...");
        } else {
            echo "Login failed: Invalid credentials" . PHP_EOL;
            return array("returnCode" => '1', "message" => "Invalid credentials");
        }
    }

    echo "Unknown request type received" . PHP_EOL;
    return array("returnCode" => '1', "message" => "Unknown request type");
}

// Ensure RabbitMQ server is correctly initialized
$server = new rabbitMQServer("/var/www/rabbitmqphp_example/testRabbitMQ.ini", "testServer");

echo "testRabbitMQServer BEGIN".PHP_EOL;
$server->process_requests('requestProcessor');
echo "testRabbitMQServer END".PHP_EOL;
exit();
?>

