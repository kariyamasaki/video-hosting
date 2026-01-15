<?php
// Включить вывод ошибок для отладки
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Установить кодировку UTF-8
header('Content-Type: text/html; charset=utf-8');
mb_internal_encoding('UTF-8');

session_start();

// Настройки базы данных
$host = 'localhost';
$dbname = 'video_hosting';
$username = 'root';
$password = ''; // Оставьте пустым для XAMPP

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
} catch(PDOException $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}

// Проверка авторизации
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getUserID() {
    return $_SESSION['user_id'] ?? null;
}

// Безопасный вывод
function safe($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}
?>