<?php
session_start();

// Настройки базы данных
$host = 'localhost';
$dbname = 'video_hosting';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Проверка авторизации
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getUserID() {
    return $_SESSION['user_id'] ?? null;
}
?>