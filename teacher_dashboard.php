<?php
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: login.php');
    exit;
}

// Получаем общую статистику
$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'");
$total_students = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM dialog_sessions");
$total_sessions = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM dialog_sessions WHERE is_completed = 1");
$completed_sessions = $stmt->fetchColumn();

// Получаем список студентов с их прогрессом
$query = "
    SELECT 
        u.id,
        u.username,
        u.full_name,
        u.class,
        u.last_login,
        u.created_at,
        COUNT(DISTINCT ds.id) as total_sessions,
        SUM(ds.duration_seconds) as total_time,
        MAX(ds.started_at) as last_activity,
        COUNT(DISTINCT CASE WHEN ds.is_completed = 1 THEN ds.module_type END) as completed_modules
    FROM users u
    LEFT JOIN dialog_sessions ds ON u.id = ds.user_id
    WHERE u.role = 'student'
    GROUP BY u.id 
    ORDER BY u.last_login DESC
";

$stmt = $pdo->query($query);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

$module_names = [
    'uncertainty' => 'Занятие №2: Неопределённость',
    'pros_cons' => 'Занятие №5: За и Против',
    'consequences' => 'Занятие №8: Последствия',
    'quality_choice' => 'Занятие №9: Качество выбора'
];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель педагога - РЦП</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
</head>
<body>
    <div class="container">
        <div class="dashboard-header">
            <div>
                <h1>👨‍🏫 Панель педагога</h1>
                <p>Рефлексивная Цифровая Персона • Курс «Я и мой выбор»</p>
            </div>
            <div class="nav-buttons">
                <a href="profile.php" class="nav-btn">👤 Профиль</a>
                <a href="logout.php" class="nav-btn">🚪 Выход</a>
            </div>
        </div>

        <!-- Общая статистика -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3><?php echo $total_students; ?></h3>
                <p>Учащихся в курсе</p>
                <span class="trend">7А: <?php echo $pdo->query("SELECT COUNT(*) FROM users WHERE role='student' AND class='7А'")->fetchColumn(); ?> | 7Б: <?php echo $pdo->query("SELECT COUNT(*) FROM users WHERE role='student' AND class='7Б'")->fetchColumn(); ?></span>
            </div>
            <div class="stat-card">
                <h3><?php echo $total_sessions; ?></h3>
                <p>Проведено сессий</p>
                <span class="trend">Завершено: <?php echo $completed_sessions; ?></span>
            </div>
            <div class="stat-card">
                <h3><?php echo count(array_filter($students, fn($s) => $s['completed_modules'] == 4)); ?></h3>
                <p>Завершили курс (100%)</p>
                <span class="trend">↑ <?php echo $total_students > 0 ? round(count(array_filter($students, fn($s) => $s['completed_modules'] == 4)) / $total_students * 100) : 0; ?>% от группы</span>
            </div>
            <div class="stat-card">
                <h3><?php echo $total_students > 0 ? round($completed_sessions / $total_students, 1) : 0; ?></h3>
                <p>Сессий на учащегося</p>
                <span class="trend">Среднее время: <?php echo $pdo->query("SELECT AVG(duration_seconds)/60 FROM dialog_sessions WHERE duration_seconds > 0")->fetchColumn() ? round($pdo->query("SELECT AVG(duration_seconds)/60 FROM dialog_sessions WHERE duration_seconds > 0")->fetchColumn()) : 0; ?> мин</span>
            </div>
        </div>

        <!-- Таблица учеников -->
        <div class="students-table-container" style="margin-top: 30px;">
            <h3 style="color: #00c896; margin-bottom: 20px; font-size: 1.3em;">👥 Успеваемость учащихся</h3>
            <table class="students-table">
                <thead>
                    <tr>
                        <th>ФИО</th>
                        <th>Класс</th>
                        <th>Последний вход в систему</th>
                        <th>Всего сессий</th>
                        <th>Общее время</th>
                        <th>Прогресс</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $student): 
                        // Расчёт процента: (пройдено модулей / 4) * 100
                        $progress_percent = ($student['completed_modules'] / 4) * 100;
                        
                        // Определяем статус
                        if ($student['completed_modules'] == 4) {
                            $status_class = 'badge-success';
                        } elseif ($student['completed_modules'] >= 2) {
                            $status_class = 'badge-warning';
                        } else {
                            $status_class = 'badge-danger';
                        }
                    ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($student['full_name']); ?></strong><br>
                            <small style="color: #8b9dc3;">@<?php echo htmlspecialchars($student['username']); ?></small>
                        </td>
                        <td>
                            <span class="badge" style="background: rgba(0, 200, 150, 0.2); color: #00c896;">
                                <?php echo htmlspecialchars($student['class'] ?? '-'); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($student['last_login']): ?>
                                <?php echo date('d.m.Y', strtotime($student['last_login'])); ?>
                                <br><small style="color: #8b9dc3;"><?php echo date('H:i', strtotime($student['last_login'])); ?></small>
                            <?php else: ?>
                                <span style="color: #8b9dc3;">Не входил</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $student['total_sessions']; ?></td>
                        <td>
                            <?php 
                            $total_minutes = $student['total_time'] ? round($student['total_time'] / 60) : 0;
                            echo $total_minutes . ' мин';
                            ?>
                        </td>
                        
                        <!-- КОЛОНКА ПРОГРЕССА -->
                        <td style="min-width: 180px;">
                            <div style="font-weight: bold; font-size: 1.1em;">
                                <?php echo round($progress_percent); ?>%
                            </div>
                            <small style="color: #8b9dc3; display: block; margin-bottom: 5px;">
                                Пройдено: <?php echo $student['completed_modules']; ?> из 4 модулей
                            </small>
                            
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $progress_percent; ?>%"></div>
                            </div>
                        </td>

                        <td>
                            <a href="student_profile.php?id=<?php echo $student['id']; ?>" class="btn-primary" style="padding: 8px 16px; font-size: 0.9em;">
                                Профиль
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>