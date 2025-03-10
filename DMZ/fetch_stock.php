<?php

require_once __DIR__ . '/../RabbitMQ/RabbitMQLib.inc';

function fetchData($assetID){
        $url = "https://api.coincap.io/v2/assets/" . urlencode($assetID);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Accept: application/json"
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode == 200) {
                return json_decode($response, true);
        } else {
                return ["error" => "Failed", "code" => $httpCode];
        }
}
//example test call
$asset = "bitcoin";
$data = fetchData($asset);
print_r($data);

?>

