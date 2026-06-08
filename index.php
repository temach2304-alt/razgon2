<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$is_teacher = $_SESSION['role'] === 'teacher';

if ($is_teacher) {
    header('Location: teacher_dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>РЦП - Рефлексивная Цифровая Персона</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <header class="main-header">
            <h1>🎭 Рефлексивная Цифровая Персона</h1>
            <p>Курс «Я и мой выбор»</p>
            <nav class="nav-menu">
                <a href="profile.php" class="nav-btn">👤 Профиль</a>
                <a href="logout.php" class="nav-btn">🚪 Выход</a>
            </nav>
        </header>

        <main>
            <h2 style="text-align: center; color: white; margin-bottom: 30px; font-size: 2em;">
                Выберите модуль для работы:
            </h2>
            
            <div class="cards-grid">
                <div class="module-card" onclick="location.href='module.php?type=uncertainty'">
                    <h3>📝 Модуль 1</h3>
                    <h4>Неопределенность</h4>
                    <p>Занятие №2: «Как я отношусь к неизвестности?»</p>
                    <button class="btn-primary">Начать модуль</button>
                </div>

                <div class="module-card" onclick="location.href='module.php?type=pros_cons'">
                    <h3>⚖️ Модуль 2</h3>
                    <h4>За и Против</h4>
                    <p>Занятие №5: «Как выбираю я?»</p>
                    <button class="btn-primary">Начать модуль</button>
                </div>

                <div class="module-card" onclick="location.href='module.php?type=consequences'">
                    <h3>🔮 Модуль 3</h3>
                    <h4>Последствия</h4>
                    <p>Занятие №8: «А что если я выберу ЭТО?»</p>
                    <button class="btn-primary">Начать модуль</button>
                </div>

                <div class="module-card" onclick="location.href='module.php?type=quality_choice'">
                    <h3>✅ Модуль 4</h3>
                    <h4>Качество выбора</h4>
                    <p>Занятие №9: «Любой ли выбор хорош?»</p>
                    <button class="btn-primary">Начать модуль</button>
                </div>
            </div>

            <div class="info-block">
                <h3>📌 Важная информация:</h3>
                <ul>
                    <li>Все ваши ответы конфиденциальны и защищены</li>
                    <li>РЦП не дает готовых ответов, а помогает вам разобраться</li>
                    <li>Сессия автоматически завершится через 40 минут</li>
                    <li>Вы можете удалить свои данные в любой момент</li>
                </ul>
            </div>
        </main>
    </div>
</body>
</html>