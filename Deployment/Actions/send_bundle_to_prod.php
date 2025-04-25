<?php
require_once(__DIR__ . '/../../RabbitMQ/RabbitMQLib.inc');
use RabbitMQ\RabbitMQClient;

// This is literally the same as send_bundle_to_qa.php, too lazy to combine into 1 file
// Send bundle from deployment to Prod using get_bundle request
if ($argc != 3) {
    echo "Enter the bundle filename followed by the Prod VM's routing key.\n";
    echo "Example: php send_bundle_to_prod.php TheLogger_v1.0.4.zip prod_vm1\n";
    echo "Valid clients: prod_vm1 (WebServer), prod_vm2 (Backend), prod_vm3 (DMZ)\n";
    exit(1);
}

$bundle = $argv[1];
$prodClient = $argv[2];

try {
    echo "Connecting to Prod exchange...\n";

    // Send message to 'prod_deployment_queue' with routing key 'request'
    $client = new RabbitMQClient(__DIR__ . '/../../Deployment.ini', 'Deployment-Prod');

    $message = [
        'action' => 'get_bundle',
        'bundle_name' => $bundle,
        'reply_to' => $prodClient 
    ];
    $client->publishMessage($message);

    echo "Request to sent to deployment for $prodClient to install $bundle\n";
    echo "Check /var/log/prod_listener.log for more details.\n";

    $client->close();
} catch (Exception $e) {
    echo "Error sending message to Prod exchange: " . $e->getMessage() . "\n";
}
