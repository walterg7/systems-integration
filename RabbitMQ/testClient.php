<?php
require_once 'RabbitMQLib.inc';

use RabbitMQ\RabbitMQClient;

$client = new RabbitMQClient("RabbitMQ.ini", "Logger");

$message = [
    "username" => "walter",
    "password" => "IT490",
    "message" => "Let me in!"
];

$client->publishMessage($message);
$client->close();
?>