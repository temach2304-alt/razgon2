<?php
// Запуск сессии только если она еще не активна
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Конфигурация БД
$host = 'localhost';
$dbname = 'u3531754_bro';
$username = 'u3531754_teacher';
$password = 'QAZzaq1234QAZ';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Ошибка подключения к БД: " . $e->getMessage());
}

// Константы
define('SITE_NAME', 'Рефлексивная Цифровая Персона');
define('SITE_URL', 'https://' . $_SERVER['HTTP_HOST']);