<?php
require_once(__DIR__ . '/../../RabbitMQ/RabbitMQLib.inc');
use RabbitMQ\RabbitMQClient;

// This is literally the same as rollback_bundle_to_qa.php, too lazy to combine into 1 file

// Send bundle from deployment to Prod using get_bundle request
if ($argc != 3) {
    echo "Enter the bundle category followed by the Prod VM's routing key.\n";
    echo "Example: php rollback_bundle_to_prod.php TheLogger prod_vm1\n";
    echo "Valid clients: prod_vm1 (WebServer), prod_vm2 (Backend), prod_vm3 (DMZ)\n";
    exit(1);
}

$category = $argv[1];
$prodClient = $argv[2];

try {
    echo "Connecting to Prod exchange...\n";

    // Send message to 'prod_deployment_queue' with routing key 'request'
    $client = new RabbitMQClient(__DIR__ . '/../Deployment.ini', 'Deployment-Prod');

    $message = [
        'action' => 'rollback_bundle',
        'category' => $category,
        'reply_to' => $prodClient 
    ];
    $client->publishMessage($message);

    echo "Request to sent to deployment for $prodClient to rollback to latest passed version for $category\n";
    echo "Check /var/log/prod_listener.log for more details.\n";

    $client->close();
} catch (Exception $e) {
    echo "Error sending message to Prod exchange: " . $e->getMessage() . "\n";
}
