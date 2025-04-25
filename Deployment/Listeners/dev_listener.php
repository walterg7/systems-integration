<?php
require_once __DIR__ . '/../../RabbitMQ/RabbitMQLib.inc';
require_once __DIR__ . '/../db_connect.php';

use RabbitMQ\RabbitMQServer;

// Receives bundles from dev and sends latest bundle version

try {
    global $db;
    echo "Trying to connect to RabbitMQ...\n";

    $server = new RabbitMQServer(__DIR__ . '/../Deployment.ini', 'Development');

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
                // Handle bundles sent from dev
                case "send_bundle":
                    $bundle = $data['bundle_name'];
                    $category = $data['category'];
                    $major = intval($data['major']);
                    $minor = intval($data['minor']);
                    $patch = intval($data['patch']);
                    $status = 'new';
                    $comment = $data['comment'];
                
                    $submitted_version = "$major.$minor.$patch";
                
                    // Check if exact version exists
                    $stmt = $db->prepare("SELECT 1 FROM bundles WHERE category = ? AND major = ? AND minor = ? AND patch = ? LIMIT 1");
                    $stmt->bind_param("siii", $category, $major, $minor, $patch);
                    $stmt->execute();
                    $stmt->store_result();
                
                    $version_exists = $stmt->num_rows > 0;
                    $stmt->close();

                    if ($version_exists) {
                        echo "Error: Version $submitted_version for $category already exists.\n";
                        // STOP SCP TRANSFER TO PREVENT OLD BUNDLES FROM BEING OVERWRITTEN; DEV SHOULD ABORT SCP TRANSFER WHEN RECEIVING 'error'
                        $response = [
                            "status" => "error",
                            "message" => "Version $submitted_version already exists for $category.",
                            "submitted_version" => $submitted_version,
                        ];
                    } else {
                        // Insert bundle into the database if it is new
                        $stmt = $db->prepare("INSERT INTO bundles (name, category, major, minor, patch, status, comment) VALUES (?, ?, ?, ?, ?, ?, ?)");
                        $stmt->bind_param("ssiiiss", $bundle, $category, $major, $minor, $patch, $status, $comment);
                    
                        if ($stmt->execute()) {
                            echo "Bundle $bundle v$major.$minor.$patch added to database!\n";
                            $response = ["status" => "success", "message" => "Bundle successfully sent to deployment processor!"];
                    
                        } else {
                            echo "Error: " . $stmt->error . "\n";
                            $response = ["status" => "error", "message" => "Failed to send bundle to deployment processor."];
                        }
                    }
                    break;
                
                // Get latest bundle version by checking category and send to dev
                case "get_bundle_version":
                    $category = $data['category'];
                
                    $stmt = $db->prepare("SELECT major, minor, patch FROM bundles WHERE category = ? ORDER BY major DESC, minor DESC, patch DESC LIMIT 1");
                    $stmt->bind_param("s", $category);
                    $stmt->execute();
                    $stmt->bind_result($latest_major, $latest_minor, $latest_patch);
                
                    if ($stmt->fetch()) {
                        $latest_version = "$latest_major.$latest_minor.$latest_patch";

                        $response = [
                            //"message" => "Latest version for $category is $latest_version"
                            "major" => $latest_major,
                            "minor" => $latest_minor,
                            "patch" => $latest_patch
                        ];

                        echo "Latest version for $category is $latest_version\n";
                    } else {
                        $latest_version = null;
                        $response = [
                            "message" => "No version exists for $category"
                        ];

                        echo "No version exists for $category\n";
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