<?php
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: login.php');
    exit;
}

$student_id = $_GET['id'] ?? 0;

// Получаем информацию о студенте
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'student'");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if (!$student) {
    header('Location: teacher_dashboard.php');
    exit;
}

// Получаем сессии студента
$stmt = $pdo->prepare("
    SELECT 
        ds.*,
        COUNT(dm.id) as messages_count
    FROM dialog_sessions ds
    LEFT JOIN dialog_messages dm ON ds.id = dm.session_id
    WHERE ds.user_id = ?
    ORDER BY ds.started_at DESC
");
$stmt->execute([$student_id]);
$sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

$module_names = [
    'uncertainty' => 'Неопределенность',
    'pros_cons' => 'За и Против',
    'consequences' => 'Последствия',
    'quality_choice' => 'Качество выбора'
];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($student['full_name']); ?> - РЦП</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <header class="main-header">
            <h1>👤 <?php echo htmlspecialchars($student['full_name']); ?></h1>
            <p>Детальная информация об учащемся</p>
            <nav class="nav-menu">
                <a href="teacher_dashboard.php" class="nav-btn">← Назад к дашборду</a>
            </nav>
        </header>

        <div class="dashboard-container">
            <h3 style="margin-bottom: 20px; color: #667eea;">📋 Информация</h3>
            <table class="data-table" style="max-width: 600px;">
                <tr>
                    <th>Имя пользователя</th>
                    <td><?php echo htmlspecialchars($student['username']); ?></td>
                </tr>
                <tr>
                    <th>Класс</th>
                    <td><?php echo htmlspecialchars($student['class'] ?? '-'); ?></td>
                </tr>
                <tr>
                    <th>Дата регистрации</th>
                    <td><?php echo date('d.m.Y H:i', strtotime($student['created_at'])); ?></td>
                </tr>
                <tr>
                    <th>Последний вход</th>
                    <td>
                        <?php if ($student['last_login']): ?>
                            <?php echo date('d.m.Y H:i', strtotime($student['last_login'])); ?>
                        <?php else: ?>
                            Не входил
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th>Всего сессий</th>
                    <td><?php echo count($sessions); ?></td>
                </tr>
            </table>

            <h3 style="margin: 40px 0 20px; color: #667eea;">📊 История сессий</h3>
            <?php if (count($sessions) > 0): ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Модуль</th>
                        <th>Начало</th>
                        <th>Завершение</th>
                        <th>Длительность</th>
                        <th>Сообщений</th>
                        <th>Статус</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sessions as $session): ?>
                    <tr>
                        <td><?php echo $module_names[$session['module_type']] ?? $session['module_type']; ?></td>
                        <td><?php echo date('d.m.Y H:i', strtotime($session['started_at'])); ?></td>
                        <td>
                            <?php if ($session['ended_at']): ?>
                                <?php echo date('d.m.Y H:i', strtotime($session['ended_at'])); ?>
                            <?php else: ?>
                                <span style="color: #999;">Активна</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($session['duration_seconds']): ?>
                                <?php echo round($session['duration_seconds'] / 60, 1); ?> мин
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td><?php echo $session['messages_count']; ?></td>
                        <td>
                            <?php if ($session['is_completed']): ?>
                                <span class="badge badge-success">Завершена</span>
                            <?php else: ?>
                                <span class="badge badge-warning">В процессе</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p style="text-align: center; color: #999; padding: 40px;">
                У этого учащегося пока нет сессий
            </p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>