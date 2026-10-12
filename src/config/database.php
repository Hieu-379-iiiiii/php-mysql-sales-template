<?php

$host = getenv('DB_HOST') ?: 'db';
$database = getenv('MYSQL_DATABASE') ?: 'ql_banhang';
$username = getenv('MYSQL_USER') ?: 'appuser';
$password = getenv('MYSQL_PASSWORD') ?: 'apppassword';

$conn = new mysqli(
    $host,
    $username,
    $password,
    $database
);

if ($conn->connect_error) {
    die('Kết nối cơ sở dữ liệu thất bại: ' . $conn->connect_error);
}

$conn->set_charset('utf8mb4');