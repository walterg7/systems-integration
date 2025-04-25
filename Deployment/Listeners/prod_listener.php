<?php
require_once __DIR__ . '/../../RabbitMQ/RabbitMQLib.inc';
require_once __DIR__ . '/../db_connect.php';

use RabbitMQ\RabbitMQServer;

// Handle bundle status updates and rollbacks
try {
    global $db;
    echo "Trying to connect to RabbitMQ...\n";

    $server = new RabbitMQServer(__DIR__ . '/../Deployment.ini', 'Deployment-Prod');

    // Turned rollback_bundle into a function since it is used twice
    function rollback_bundle($db, $server, $category, $replyTo) {
        // Search DB for latest 'passed' version where 'category' is the one sent in the request
        $stmt = $db->prepare("
            SELECT name FROM bundles
            WHERE status = 'passed' AND category = ?
            ORDER BY major DESC, minor DESC, patch DESC
            LIMIT 1
        ");
    
        $stmt->bind_param("s", $category);
        $stmt->execute();
        $stmt_result = $stmt->get_result();
    
        if ($stmt_result->num_rows > 0) {
            $row = $stmt_result->fetch_assoc();
            $rollback_bundle = $row['name'];
    
            // Send to appropriate queue via the routing key included in the request
            $server->publishMessage([
                'action' => 'rollback_bundle',
                'bundle_name' => $rollback_bundle
            ], $replyTo);
    
            echo "$replyTo rolled back to bundle $rollback_bundle.\n";
        } else {
            echo "No previous version to roll back to.\n"; // Should never occur
        }
    
        $stmt->close();
    }    

    $server->consume(function ($message) use ($db, $server) {
        echo "Message: $message\n";

        $data = json_decode($message, true);
        $response = ["status" => "error", "message" => "Unknown error"];

        if (!isset($data['action'])) {
            echo "Error: Action not specified.\n";
            $response["message"] = "Action not specified.";
        } else {
            $action = $data['action'];

            switch ($action) {
                // On demand bundle request from Prod
                case 'get_bundle':
                    $bundleName = $data['bundle_name'] ?? null;
                    $replyTo = $data['reply_to'] ?? null;
                
                    if (!$bundleName || !$replyTo) {
                        echo "Missing bundle_name or reply_to in get_bundle request\n";
                        break;
                    }
                
                    // Check if bundle exists
                    $stmt = $db->prepare("SELECT * FROM bundles WHERE name = ?");
                    $stmt->execute([$bundleName]);
                    $bundle = $stmt->fetch();
                
                    if ($bundle) {
                        echo "Valid bundle found! Sending bundle to $replyTo...\n";

                        $server->publishMessage([
                            'action' => 'new_bundle', //This action triggers installation on the Prod side
                            'bundle_name' => $bundleName
                        ], $replyTo);

                    } else {
                        echo "Invalid bundle. Sending error to $replyTo...\n";

                        $server->publishMessage([
                            'action' => 'error',
                            'message' => "Bundle '$bundleName' does not exist."
                        ], $replyTo);
                    }
                    break;

                // Handle bundle status updates
                case 'update_status':
                    if (!isset($data['bundle_name'], $data['status'])) {
                        echo "Missing data in request.\n";
                        break;
                    }
                
                    $bundle_name = $data['bundle_name'];
                    $status = strtolower($data['status']);

                    // Status must be 'passed' or 'failed'
                    if (!in_array($status, ['passed', 'failed'])) {
                        echo "Invalid status: $status. Must be 'passed' or 'failed'.\n";
                        break;
                    }
                
                    // Update DB
                    $stmt = $db->prepare("UPDATE bundles SET status = ? WHERE name = ?");
                    $stmt-> bind_param ("ss", $status, $bundle_name);

                    if ($stmt->execute()) {
                        echo "Bundle $bundle_name marked as $status!\n";
                    
                        // Extract category from bundle name since category is not included in 'update_status' request
                        if (preg_match('/^([A-Za-z0-9]+)_v[0-9]+\.[0-9]+\.[0-9]+\.zip$/', $bundle_name, $matches)) {
                            $category = $matches[1];
                            //echo "Extracted category: $category\n";
                    
                            // Optional: automatically roll back to latest 'passed' bundle of same category after marking a bundle as 'failed'
                            if ($status === 'failed' && isset($data['reply_to'])) {
                                rollback_bundle($db, $server, $category, $data['reply_to']); // or comment this line to disable this feature
                            }
                        } else {
                            echo "Failed to extract category from bundle name: $bundle_name\n";
                        }
                    } else {
                        echo "Failed to update bundle status: " . $stmt->error . "\n";
                    }
                    
                    $stmt->close();
                    break;

                // Rollback to latest bundle version in the category with 'passed' 
                case 'rollback_bundle':
                    rollback_bundle($db, $server, $data['category'], $data['reply_to']);
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