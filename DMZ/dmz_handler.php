<?php
require_once(__DIR__ . '/../RabbitMQ/RabbitMQLib.inc');
require_once(__DIR__ . '/../Logger/Logger.inc');

use RabbitMQ\RabbitMQServer;

function apiCall($url, $context = null) {
    $response = @file_get_contents($url, false, $context);

    // triggered when API key is invalid or not set
    if ($response === false) {
        $warningMessage = "WARNING: HTTP request failed! HTTP/1.1 401 Unauthorized (Make sure the API key is valid and set)";
        echo $warningMessage . "\n";
        Logger\sendLog("DMZ", $warningMessage);
        return ["status" => "error", "message" => $warningMessage];
    }

    $data = json_decode($response, true);

    // bad API response
    if ($data === null) {
        $errorMessage = "ERROR: Failed to parse API response.";
        echo $errorMessage . "\n";
        Logger\sendLog("DMZ", $errorMessage);
        return ["status" => "error", "message" => $errorMessage];
    }

    return $data;
}

try {
    $server = new RabbitMQServer(__DIR__ . '/../RabbitMQ/RabbitMQ.ini', 'DMZ');

    $server->consume(function($body, $properties, $channel) {
        $request = json_decode($body, true);

        $apiKey = getenv('COINCAP_API_KEY');
        $baseUrl = "https://rest.coincap.io/v3"; //make sure to set your API key as an environment variable

        switch ($request['action']) {
            case "getTop100Crypto":
                echo "Received Top 100 Crypto request:\n";
                $fullUrl = "$baseUrl/assets/?apiKey=$apiKey";
        
                $data = apiCall($fullUrl);
                if (isset($data['status']) && $data['status'] === 'error') {
                    return $data;
                }
        
                return array_slice($data['data'], 0, 100);
        
            case "getCoinDetails":
                echo "Received coin details request:\n";
                $slug = $request['coinId'];
                $fullUrl = "$baseUrl/assets/{$slug}?apiKey=$apiKey";
        
                return apiCall($fullUrl);
        
            case "getCoinHistory":
                echo "Received coin history request:\n";
                $slug = $request['coinId'];
                $interval = $request['interval'];
                $fullUrl = "$baseUrl/assets/{$slug}/history?interval={$interval}&apiKey=$apiKey";
        
                return apiCall($fullUrl);
        
            default:
                return ["status" => "error", "message" => "Invalid request type"];
        }});
    $server->close();
} catch (Exception $error) {
    $msg = "FATAL: " . $error->getMessage();
    echo "$msg\n";
    Logger\sendLog("DMZ", $msg);
}
?>