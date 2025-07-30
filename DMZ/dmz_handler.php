<?php
require_once(__DIR__ . '/../RabbitMQ/RabbitMQLib.inc');
require_once(__DIR__ . '/../Logger/Logger.inc');

use RabbitMQ\RabbitMQServer;
use Dotenv\Dotenv;

$dotenvPath = '/home/db/systems-integration'; 
$dotenv = Dotenv::createImmutable($dotenvPath);
$dotenv->safeLoad();

$apiKey = getenv('COINCAP_API_KEY');

if (!$apiKey) {
    echo "FATAL: COINCAP_API_KEY is missing.\n";
    Logger\sendLog("DMZ", "FATAL: COINCAP_API_KEY is missing.");
    exit(1);
}

// better way to make API calls
function apiCall($url, $apiKey) {
    $opts = [
        "http" => [
            "method" => "GET",
            "header" => "Authorization: Bearer $apiKey\r\n"
        ]
    ];
    $context = stream_context_create($opts);

    $response = @file_get_contents($url, false, $context);

    if ($response === false) {
        $warningMessage = "WARNING: HTTP request failed! (Check API key and URL)";
        echo $warningMessage . "\n";
        Logger\sendLog("DMZ", $warningMessage);
        return ["status" => "error", "message" => $warningMessage];
    }

    $data = json_decode($response, true);

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

    $server->consume(function($body, $properties, $channel) use ($apiKey) { // import $apiKey into the function’s local scope
        $request = json_decode($body, true);

        $baseUrl = "https://rest.coincap.io/v3"; //make sure to set your API key as an environment variable

        switch ($request['action']) {
            case "getTop100Crypto":
                echo "Received Top 100 Crypto request:\n";

                $fullUrl = "$baseUrl/assets";
                $data = apiCall($fullUrl, $apiKey); 
        
                return array_slice($data['data'], 0, 100);
        
            case "getCoinDetails":
                echo "Received coin details request:\n";

                $slug = $request['coinId'];
                $fullUrl = "$baseUrl/assets/{$slug}";

                return apiCall($fullUrl, $apiKey);

            case "getCoinHistory":
                echo "Received coin history request:\n";
                
                $slug = $request['coinId'];
                $interval = $request['interval'];
                $fullUrl = "$baseUrl/assets/{$slug}/history?interval={$interval}";
                
                return apiCall($fullUrl, $apiKey);

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
