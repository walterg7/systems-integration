#!/usr/bin/php
<?php
// deployment DB where all bundles are stored
$db = new mysqli("localhost", "deployment", "!Deploy123", "IT490");

if ($db->errno != 0)
{
        echo "Failed to connect to database: ". $db->error . PHP_EOL;
        exit(0);
}

echo "Successfully connected to database" . PHP_EOL;
return $db;
?>