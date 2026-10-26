<?php
date_default_timezone_set('America/Sao_Paulo');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


$host = THX_DB_HOST;
$dbname = THX_DB_NAME;
$user = THX_DB_USERNAME;
$pass = THX_DB_PASSWORD;
$port = THX_DB_PORT;

$pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8", $user, $pass);