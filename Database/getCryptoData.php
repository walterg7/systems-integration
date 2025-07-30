<?php
require_once(__DIR__ . '/../RabbitMQ/RabbitMQLib.inc');
require_once(__DIR__ . '/../Logger/Logger.inc');
require_once('dbConnect.php');

use RabbitMQ\RabbitMQClient;

echo "Trying to connect to RabbitMQ...\n";

try {
    global $db;

    $client = new RabbitMQClient(__DIR__ . '/../RabbitMQ/RabbitMQ.ini', 'DMZ');

    $request = ['action' => 'getTop100Crypto'];
    $response = $client->sendRequest($request);

    // Decode JSON response if necessary
    if (is_string($response)) {
        $response = json_decode($response, true);
    }

    // Handle error from DMZ
    if (!is_array($response) || (isset($response['status']) && $response['status'] === 'error')) {
        $msg = isset($response['message']) ? $response['message'] : 'Unknown error';

        // msg from RabbitMQLib.inc
        if ($msg === 'No response from server.') {
            echo "FATAL: No response from DMZ.\n";
            Logger\sendLog("BACKEND", "FATAL: No response from DMZ.");
        } else {
            echo "ERROR: Failed to retrieve data from DMZ.\n";
            Logger\sendLog("BACKEND", "ERROR: Failed to retrieve data from DMZ.");
        }
        exit;
    }

    // Process and insert into DB
    foreach ($response as $coin) {
        $stmt = $db->prepare("INSERT INTO crypto (asset_id, name, symbol, price, market_cap, supply, max_supply, volume, change_percent)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            name = VALUES(name), symbol = VALUES(symbol), price = VALUES(price), market_cap = VALUES(market_cap),
            supply = VALUES(supply), max_supply = VALUES(max_supply), volume = VALUES(volume),
            change_percent = VALUES(change_percent), last_updated = CURRENT_TIMESTAMP");

        $stmt->bind_param("sssssssss", 
            $coin['id'], 
            $coin['name'], 
            $coin['symbol'], 
            $coin['priceUsd'], 
            $coin['marketCapUsd'],  
            $coin['supply'], 
            $coin['maxSupply'], 
            $coin['volumeUsd24Hr'], 
            $coin['changePercent24Hr']
        );

        if (!$stmt->execute()) {
            echo "Error saving coin '{$coin['name']}' to database: " . $stmt->error . "\n";
            Logger\sendLog("BACKEND", "ERROR: '{$coin['name']}' could not be saved to DB: " . $stmt->error);
        }
        $stmt->close();
    }

    echo "Crypto data successfully updated!\n";
    Logger\sendLog("BACKEND", "INFO: API call successful, crypto table updated.");

} catch (Exception $error) {
    $msg = "Failed to connect to DMZ: " . $error->getMessage();
    echo "$msg\n";
    Logger\sendLog("BACKEND", "FATAL: $msg");
}
