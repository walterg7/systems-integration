<?php
require_once(__DIR__ . '/../RabbitMQ/RabbitMQLib.inc');

use RabbitMQ\RabbitMQServer;

$server = new RabbitMQServer(__DIR__ . '/../RabbitMQ/RabbitMQ.ini', 'DMZ');

$server->consume(function($body, $properties, $channel) {
    $request = json_decode($body, true);

    $apiKey = getenv('COINCAP_API_KEY');
    $baseUrl = "https://rest.coincap.io/v3";

    switch ($request['action']) {
        case "getTop100Crypto":
            echo "Received Top 100 Crypto request:\n";
            
            $fullUrl = "$baseUrl/assets/?apiKey=$apiKey";
            $response = file_get_contents($fullUrl);
            $data = json_decode($response, true);

            //Troubleshooting
            if ($data === null) {
                echo "Failed to parse API response.\n";
            } else {
                //echo "API response: " . print_r($data, true) . "\n";
            }

            $top100Crypto = array_slice($data['data'], 0, 100);
            return $top100Crypto;

        case "getCoinDetails":
            echo "Received coin details request:\n";

            $slug = $request['coinId'];                
            $fullUrl = "$baseUrl/assets/{$slug}?apiKey=$apiKey";
        
            $response = file_get_contents($fullUrl);
            $data = json_decode($response, true);

            return $data;

        case "getCoinHistory":
            echo "Received coin history request:\n";

            $slug = $request['coinId'];
            $interval = $request['interval'];
            $fullUrl = "$baseUrl/assets/{$slug}/history?interval={$interval}&apiKey=$apiKey";
    
            $response = file_get_contents($fullUrl);
            $data = json_decode($response, true);
    
            return $data;
        
        default:
            return ["status" => "error", "message" => "Invalid request type"];
    }
});
?>