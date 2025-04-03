<?php
require_once __DIR__ . '/../RabbitMQ/RabbitMQLib.inc';
require_once 'db_connect.php';

use RabbitMQ\RabbitMQServer;
use PhpAmqpLib\Message\AMQPMessage;

try {
    global $db;
    echo "Trying to connect to RabbitMQ...\n";

    // Initialize RabbitMQServer (using "Deployment" from RabbitMQ.ini)
    $server = new RabbitMQServer(__DIR__ . '/../RabbitMQ/RabbitMQ.ini', 'Deployment');

    $server->consume(function ($message) use ($db) {
        echo "Message: $message\n";

        $data = json_decode($message, true);
        $response = ["status" => "error", "message" => "Unknown error"];

        if (!isset($data['action'])) {
            echo "Error: Action not specified.\n";
            $response["message"] = "Action not specified.";
        } else {
            $action = $data['action'];

            switch ($action) {
                // Handles bundle records in DB
                case "send_bundle":
                    // Process data from client
                    $name = $data['bundle_name'];
                    $version = $data['version_number'];
                    $status = 'new';
                    $comment = $data['bundle_comment'];
                    $bundle = $data['bundle_blob'];

                    // Insert new bundle into DB
                    $stmt = $db->prepare("INSERT INTO bundles (name, version, status, comment, bundle) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param("sssss", $name, $version, $status, $comment, $bundle);

                    if ($stmt->execute()) {
                        echo "Bundle $name added to database!\n";
                        $response = ["status" => "success", "message" => "Bundle successfully registered to database!"];

                        
                    } else {
                        echo "Error: " . $stmt->error . "\n";
                        $response = ["status" => "error", "message" => "Failed to register bundle to database."];
                    }
                    $stmt->close();
                    break; 
                    
                default:
                    echo "Error: Unknown action '$action' \n";
            }
        }
        return $response;
    });
    $server->close();
} catch (Exception $error) {
    echo "Error: " . $error->getMessage() . "\n\n";
}
?>