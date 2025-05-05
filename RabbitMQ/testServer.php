<?php
require_once 'RabbitMQLib.inc';

use RabbitMQ\RabbitMQServer;

$server = new RabbitMQServer("RabbitMQ.ini", "Logger");
$server->process_messages();
$server->close();
?>