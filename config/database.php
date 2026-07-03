<?php

$host = "localhost";
$port = "5432";
$dbname = "db_sipark_gbk";
$user = "postgres";
$password = "root";

try {
    $conn = new PDO(
        "pgsql:host=$host;port=$port;dbname=$dbname",
        $user,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} 

catch (PDOException $e) {
    exit("Koneksi database gagal: " . $e->getMessage());
}