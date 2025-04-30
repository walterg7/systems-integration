<?php
namespace Logger;

require_once(__DIR__ . '/../RabbitMQ/RabbitMQLib.inc');

use RabbitMQ\RabbitMQClient;

// just so we don't have to constantly declare the RabbitMQ ciient for Logger in every handler/listener
function sendLog($category, $logMessage) {
    $client = new RabbitMQClient(__DIR__ . '/../RabbitMQ/RabbitMQ.ini', 'Logger');

    $message = [
        'category' => $category,
        'logMessage' => $logMessage
    ];

    $client->publishMessage($message, '', 'logger'); // publish message to the fanout exchange
}
?>