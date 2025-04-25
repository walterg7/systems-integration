<?php
require_once(__DIR__ . '/../../RabbitMQ/RabbitMQLib.inc');
use RabbitMQ\RabbitMQClient;

// Send bundle from deployment to QA using get_bundle request
if ($argc != 3) {
    echo "Enter the bundle category followed by the QA VM's routing key.\n";
    echo "Example: php rollback_bundle_to_qa.php TheLogger qa_vm1\n";
    echo "Valid clients: qa_vm1 (WebServer), qa_vm2 (Backend), qa_vm3 (DMZ)\n";
    exit(1);
}

$category = $argv[1];
$qaClient = $argv[2];

try {
    echo "Connecting to QA exchange...\n";

    // Send message to 'qa_deployment_queue' with routing key 'request'
    $client = new RabbitMQClient(__DIR__ . '/../Deployment.ini', 'Deployment-QA');

    $message = [
        'action' => 'rollback_bundle',
        'category' => $category,
        'reply_to' => $qaClient 
    ];
    $client->publishMessage($message);

    echo "Request to sent to deployment for $qaClient to rollback to latest passed version for $category\n";
    echo "Check /var/log/qa_listener.log for more details.\n";

    $client->close();
} catch (Exception $e) {
    echo "Error sending message to QA exchange: " . $e->getMessage() . "\n";
}
