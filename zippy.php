<?php

require_once(__DIR__ . '/RabbitMQ/RabbitMQLib.inc');

use RabbitMQ\RabbitMQClient;

$client = new RabbitMQClient(__DIR__ . '/RabbitMQ/RabbitMQ.ini', 'Deployment-Dev');

function sendRabbitMQRequest($client, $requestData) {
    $request = json_encode($requestData);
    $response = $client->sendRequest($request);
    return $response;
}

function get_bundle_version($category)
{
    global $client;
    echo "Requesting version for: " . $category . "\n";
    try {
        $request_data = [
            "action" => "get_bundle_version",
            "category" => $category,
        ];
        $response_json = $client->sendRequest(json_encode($request_data));

        echo "Raw version data received: " . $response_json . "\n";

        $response = json_decode($response_json, true);

        if (isset($response['major']) && isset($response['minor']) && isset($response['patch'])) {
            $major = (int)$response['major'];
            $minor = (int)$response['minor'];
            $patch = (int)$response['patch'];
            echo "Parsed version data: " . $major . "." . $minor . "." . $patch . "\n";
            return [$major, $minor, $patch];
        } else {
            echo "Warning: Invalid version data format received. Defaulting to 1.0.0\n";
            return [1, 0, 0];
        }

    } catch (Exception $e) {
        echo "Error getting bundle version: " . $e->getMessage() . "\n";
        return [1, 0, 0];
    }
}


function send_to_rabbitmq($data)
{
    global $client;
    try {
        $response = sendRabbitMQRequest($client, $data);
        echo "Data sent to RabbitMQ for bundle: " . $data['bundle_name'] . "\n";
        return true;
    } catch (Exception $e) {
        echo "Error sending data to RabbitMQ: " . $e->getMessage() . "\n";
        return false;
    }
}

function create_zip($folder_path, $zip_filename)
{
    echo "Creating zip file: " . $zip_filename . "\n";
    try {
        $zip = new ZipArchive();
        if ($zip->open($zip_filename, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new Exception("Cannot open zip archive");
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($folder_path),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $name => $file) {
            if (!$file->isDir()) {
                $file_path = $file->getRealPath();
                $relative_path = substr($file_path, strlen($folder_path) + 1);
                $zip->addFile($file_path, $relative_path);
            }
        }

        $zip->close();
        echo "Zip file created successfully: " . $zip_filename . "\n";
        return true;
    } catch (Exception $e) {
        echo "Error creating zip archive: " . $e->getMessage() . "\n";
        return false;
    }
}

function main()
{
    $bundles = [
        1 => ["name" => "TheLogger", "files" => [
            ["../webserver/login.php", "Login.php"], ["../webserver/register.php", "register.php"],
            ["../webserver/styles.css", "styles.css"], ["../webserver/index.html", "index.html"]]],
        2 => ["name" => "Dashboard", "files" => [
            ["../webserver/crypto-side/home.php", "home.php"], ["../webserver/crypto-side/js/app.js", "app.js"]]],
        3 => ["name" => "Trader", "files" => [
            ["../webserver/crypto-side/trade.php", "trade.php"], ["../webserver/crypto-side/js/trade.js", "trade.js"],
            ["../webserver/crypto-side/get_balance.php", "get_balance.php"], ["../webserver/crypto-side/get_portfolio.php", "get_portfolio.php"]]],
        4 => ["name" => "Notification", "files" => [
            ["../webserver/crypto-side/notification.php", "notification.php"], ["../webserver/crypto-side/check_price.php", "chech_price.php"],
            ["../webserver/crypto-side/js/notifications.js", "notifications.js"]]],
        5 => ["name" => "Portfolio", "files" => [
            ["../webserver/crypto-side/portfolio.php", "portfolio.php"], ["../webserver/crypto-side/js/portfolio.js", "portfolio.js"]]],
        6 => ["name" => "News", "files" => [
            ["../webserver/crypto-side/rss.php", "rss.php"], ["../webserver/crypto-side/js/newsJuice.js", "newsJuice.js"],
            ["../webserver/crypto-side/js/server.js", "server.js"]]],
        7 => ["name" => "Css", "files" => [
            ["../webserver/crypto-side/css/makeEverythingPretty.css", "makeEverythingPretty.css"],
            ["../webserver/crypto-side/css/TestyDashy.css", "TestyDashy.css"]]],
        8 => ["name" => "Database", "files" => [
            ["Database/db_handler.php", "db_handler.php"], ["Database/crypto_handler.php", "crypto_handler.php"],
            ["DMZ/dmz_handler.php", "dmz_handler.php"]]],
    ];

    echo ">------------------------<\n";
    echo "Available bundles:\n";
    foreach ($bundles as $key => $bundle) {
        echo "$key: " . $bundle['name'] . "\n";
    }
    echo ">------------------------<\n";

    while (true) {
        $bundle_choice = readline("Enter the bundle number: ");
        if (array_key_exists($bundle_choice, $bundles)) {
            $selected_bundle = $bundles[$bundle_choice];
            break;
        } else {
            echo "Invalid bundle number. Please look at the list again.\n";
        }
    }

    $bundle_name = $selected_bundle["name"];
    $files_to_zip = $selected_bundle["files"];

    if (empty($files_to_zip)) {
        echo "No files selected. Exiting.\n";
        return;
    }

    $temp_folder = "temp_deploy_files";
    if (!file_exists($temp_folder)) {
        mkdir($temp_folder, 0777, true);
    }

    foreach ($files_to_zip as $file_info) {
        list($full_file_path, $file_name) = $file_info;
        if (file_exists($full_file_path)) {
            copy($full_file_path, $temp_folder . "/" . $file_name);
        } else {
            echo "Warning: File '$full_file_path' not found. Skipping.\n";
        }
    }

    list($major, $minor, $patch) = [1, 0, 0];
    $existing_version = get_bundle_version($bundle_name);

    if ($existing_version === null) {
        echo "Error retrieving bundle version. Exiting.\n";
        return;
    }

    list($major, $minor, $patch) = $existing_version;
    $patch += 1;
    if ($patch >= 10) {
        $minor += 1;
        $patch = 0;
    }

    $zip_filename = "{$bundle_name}_v{$major}.{$minor}.{$patch}.zip";
    echo "Zip filename will be: " . $zip_filename . "\n";

    if (create_zip($temp_folder, $zip_filename)) {
    } else {
        array_map('unlink', glob("$temp_folder/*"));
        rmdir($temp_folder);
        echo "Zipping failed. Exiting.\n";
        return;
    }

    $comment = readline("Enter a comment for this deployment: ");

    $data_to_send = [
        "action" => "send_bundle",
        "bundle_name" => $zip_filename,
        "category" => $bundle_name,
        "major" => $major,
        "minor" => $minor,
        "patch" => $patch,
        "comment" => $comment
    ];

    send_to_rabbitmq($data_to_send);

    array_map('unlink', glob("$temp_folder/*"));
    rmdir($temp_folder);

    $ssh_host = "10.147.19.36";
    $ssh_user = "bundler";
    $remote_path = "/home/bundler/Bundles";
    $ssh_password = "!Bundle123";

    $sshpass_command = "sshpass -p '$ssh_password' scp $zip_filename {$ssh_user}@{$ssh_host}:{$remote_path}";

    exec($sshpass_command, $output, $return_var);

    if ($return_var === 0) {
        echo "Zip file '$zip_filename' sent via SSH.\n";
    } else {
        echo "Error sending zip file via SSH: " . implode("\n", $output) . "\n";
    }

    echo "Deployment process completed.\n";
}

main();
echo "Program finished.\n";

?>



