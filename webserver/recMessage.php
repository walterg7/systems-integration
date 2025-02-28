<?php
// error handling
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once('/var/www/rabbitmqphp_example/path.inc');
require_once('/var/www/rabbitmqphp_example/get_host_info.inc');
require_once('/var/www/rabbitmqphp_example/rabbitMQLib.inc');

// Function to process requests from sendMessage.php
function requestProcessor($request)
{
    echo "Received request".PHP_EOL;
    var_dump($request);

    // Check the message type and process it
    if (!isset($request['type'])) {
        return array("returnCode" => '1', "message" => "ERROR: Unsupported message type");
    }

    if ($request['type'] == "confirm") {
        // If email and password are received, confirm and send response back
        if (!empty($request['email']) && !empty($request['password'])) {
            // Message confirming the credentials are valid
            return array("returnCode" => '0', "message" => "Valid credentials, redirecting...");
        } else {
            return array("returnCode" => '1', "message" => "Invalid credentials");
        }
    }

    return array("returnCode" => '1', "message" => "Unknown request type");
}

// Start the RabbitMQ server
$server = new rabbitMQServer("/var/www/rabbitmqphp_example/testRabbitMQ.ini", "testServer");
echo "testRabbitMQServer BEGIN".PHP_EOL;
$server->process_requests('requestProcessor');
echo "testRabbitMQServer END".PHP_EOL;
exit();
?>

