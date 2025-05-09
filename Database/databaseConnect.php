#!/usr/bin/php
<?php
require_once(__DIR__ . '/../Logger/Logger.inc');

mysqli_report(MYSQLI_REPORT_STRICT);

try {
        $db = new mysqli("localhost", "tester_user", "testMe", "IT490");
        echo "Successfully connected to database" . PHP_EOL;
        return $db;
} catch (mysqli_sql_exception) {
        $errorMsg = "Failed to connect to database.";
        echo $errorMsg . PHP_EOL;
        Logger\sendLog("BACKEND", "FATAL: $errorMsg"); 
        exit(1);
}
?>