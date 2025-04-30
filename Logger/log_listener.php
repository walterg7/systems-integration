<?php
require_once(__DIR__ . '/../RabbitMQ/RabbitMQLib.inc');

use RabbitMQ\RabbitMQServer;

function logMessage($category, $message) {
    $timestamp = new DateTime("now", new DateTimeZone("America/New_York")); // use EST time
    $entry = "[" . $timestamp->format("Y-m-d H:i:s") . "] $message\n";

    // Separate log files for each server
    $logFiles = [
        'WEBSERVER' => __DIR__ . '/webserver.log',
        'BACKEND' => __DIR__ . '/backend.log',
        'DMZ' => __DIR__ . '/dmz.log'
    ];

    // The file to write to depends on the 'category' given in the message (if 'category' => 'DMZ', write to dmz.log)
    file_put_contents($logFiles[$category] ?? __DIR__ . '/unknown.log', $entry, FILE_APPEND);
}

$server = new RabbitMQServer(__DIR__ . '/../RabbitMQ/RabbitMQ.ini', 'Logger');

$server->consume(function($body) {
    $message = json_decode($body, true);
    if (isset($message['category'], $message['logMessage'])) {
        logMessage($message['category'], $message['logMessage']);
    }
});