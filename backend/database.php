<?php
$env = parse_ini_file(__DIR__ . '/../.env');

$host = $env['DB_HOST'] ?? 'casestudy';
$port = $env['DB_PORT'] ?? 3306;
$user = $env['DB_USER'] ?? 'root';
$pass = $env['DB_PASS'] ?? 'neust123';
$db   = $env['DB_NAME'] ?? 'cs_pos';

$conn = new mysqli($host, $user, $pass, $db, $port);

if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
?>