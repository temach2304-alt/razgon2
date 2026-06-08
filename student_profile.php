<?php
$page_title = 'Профиль ученика - РЦП';
$header_title = '👨‍🎓 Профиль ученика';
$header_subtitle = 'Подробная информация об успеваемости';
require_once 'config.php';

// Проверка авторизации и прав педагога
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: login.php');
    exit;
}

$student_id = $_GET['id'] ?? null;

if (!$student_id) {
    header('Location: teacher_dashboard.php');
    exit;
}

// Получение данных ученика
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'student'");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if (!$student) {
    header('Location: teacher_dashboard.php');
    exit;
}

// Статистика ученика
$stmt = $pdo->prepare("
    SELECT 
        COUNT(DISTINCT module_id) as completed_modules,
        COUNT(DISTINCT m.id) as total_messages,
        SUM(TIMESTAMPDIFF(MINUTE, s.session_start, s.session_end)) as total_minutes
    FROM users u
    LEFT JOIN reflections r ON u.id = r.student_id AND r.completed = 1
    LEFT JOIN messages m ON r.id = m.reflection_id
    LEFT JOIN sessions s ON u.id = s.student_id AND s.session_end IS NOT NULL
    WHERE u.id = ?
");
$stmt->execute([$student_id]);
$stats = $stmt->fetch();

// Последние сессии
$stmt = $pdo->prepare("
    SELECT 
        s.session_start,
        s.session_end,
        TIMESTAMPDIFF(MINUTE, s.session_start, s.session_end) as duration
    FROM sessions s
    WHERE s.student_id = ? AND s.session_end IS NOT NULL
    ORDER BY s.session_start DESC
    LIMIT 5
");
$stmt->execute([$student_id]);
$recent_sessions = $stmt->fetchAll();

// Прогресс по модулям
$stmt = $pdo->prepare("
    SELECT 
        m.id as module_id,
        m.title,
        r.completed,
        r.created_at as completed_at
    FROM modules m
    LEFT JOIN reflections r ON m.id = r.module_id AND r.student_id = ?
    ORDER BY m.id
");
$stmt->execute([$student_id]);
$modules_progress = $stmt->fetchAll();

// Результаты диагностики
$stmt = $pdo->prepare("
    SELECT * FROM dtr_results 
    WHERE student_id = ? 
    ORDER BY test_date
");
$stmt->execute([$student_id]);
$dtr_results = $stmt->fetchAll();
?>

<?php include 'includes/header.php'; ?>

<div class="profile-card">
    <h2 style="color: #667eea; margin-bottom: 30px;">📋 Основная информация</h2>
    
    <div class="profile-info">
        <div class="profile-info-item">
            <label>ФИО:</label>
            <span><?php echo htmlspecialchars($student['full_name']); ?></span>
        </div>
        
        <div class="profile-info-item">
            <label>Имя пользователя:</label>
            <span><?php echo htmlspecialchars($student['username']); ?></span>
        </div>
        
        <div class="profile-info-item">
            <label>Класс:</label>
            <span><?php echo htmlspecialchars($student['class_name'] ?? 'Не указан'); ?></span>
        </div>
        
        <div class="profile-info-item">
            <label>Дата регистрации:</label>
            <span><?php echo date('d.m.Y', strtotime($student['created_at'])); ?></span>
        </div>
        
        <div class="profile-info-item">
            <label>Последний вход:</label>
            <span><?php echo $student['last_login'] ? date('d.m.Y H:i', strtotime($student['last_login'])) : 'Никогда'; ?></span>
        </div>
        
        <div class="profile-info-item">
            <label>Статус:</label>
            <span style="color: <?php echo $student['is_active'] ? '#4CAF50' : '#f44336'; ?>;">
                <?php echo $student['is_active'] ? '✅ Активен' : '❌ Неактивен'; ?>
            </span>
        </div>
    </div>
</div>

<div class="profile-card">
    <h2 style="color: #667eea; margin-bottom: 30px;">📊 Общая статистика</h2>
    
    <div class="profile-info">
        <div class="profile-info-item" style="border-left-color: #4CAF50;">
            <label>Завершено модулей:</label>
            <span style="color: #4CAF50; font-size: 1.5em;"><?php echo $stats['completed_modules']; ?> / 4</span>
        </div>
        
        <div class="profile-info-item" style="border-left-color: #2196F3;">
            <label>Сообщений в диалогах:</label>
            <span style="color: #2196F3; font-size: 1.5em;"><?php echo $stats['total_messages']; ?></span>
        </div>
        
        <div class="profile-info-item" style="border-left-color: #FF9800;">
            <label>Время в системе:</label>
            <span style="color: #FF9800; font-size: 1.5em;"><?php echo round($stats['total_minutes'] / 60, 1); ?> ч.</span>
        </div>
    </div>
</div>

<div class="profile-card">
    <h2 style="color: #667eea; margin-bottom: 30px;">🎯 Прогресс по модулям</h2>
    
    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                <th style="padding: 15px; text-align: left;">Модуль</th>
                <th style="padding: 15px; text-align: center;">Статус</th>
                <th style="padding: 15px; text-align: center;">Дата завершения</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($modules_progress as $module): ?>
            <tr style="border-bottom: 1px solid #e0e0e0; transition: background 0.3s;" 
                onmouseover="this.style.background='#f8f9fa'" 
                onmouseout="this.style.background='white'">
                <td style="padding: 15px;"><?php echo htmlspecialchars($module['title']); ?></td>
                <td style="padding: 15px; text-align: center;">
                    <?php if ($module['completed']): ?>
                        <span style="color: #4CAF50; font-weight: 600;">✅ Завершен</span>
                    <?php else: ?>
                        <span style="color: #999;">⏳ В процессе</span>
                    <?php endif; ?>
                </td>
                <td style="padding: 15px; text-align: center;">
                    <?php echo $module['completed_at'] ? date('d.m.Y H:i', strtotime($module['completed_at'])) : '—'; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if (!empty($dtr_results)): ?>
<div class="profile-card">
    <h2 style="color: #667eea; margin-bottom: 30px;">📈 Результаты диагностики ДТР</h2>
    
    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                <th style="padding: 15px; text-align: left;">Тип теста</th>
                <th style="padding: 15px; text-align: center;">Дата</th>
                <th style="padding: 15px; text-align: center;">Результат</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($dtr_results as $result): ?>
            <tr style="border-bottom: 1px solid #e0e0e0;">
                <td style="padding: 15px;">
                    <?php echo $result['test_type'] === 'pre' ? '🔵 Входная диагностика' : '🟢 Выходная диагностика'; ?>
                </td>
                <td style="padding: 15px; text-align: center;">
                    <?php echo date('d.m.Y', strtotime($result['test_date'])); ?>
                </td>
                <td style="padding: 15px; text-align: center;">
                    <strong style="color: #667eea;"><?php echo $result['score']; ?> баллов</strong>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php if (!empty($recent_sessions)): ?>
<div class="profile-card">
    <h2 style="color: #667eea; margin-bottom: 30px;">🕐 Последние сессии</h2>
    
    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                <th style="padding: 15px; text-align: left;">Дата</th>
                <th style="padding: 15px; text-align: center;">Время начала</th>
                <th style="padding: 15px; text-align: center;">Длительность</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($recent_sessions as $session): ?>
            <tr style="border-bottom: 1px solid #e0e0e0;">
                <td style="padding: 15px;"><?php echo date('d.m.Y', strtotime($session['session_start'])); ?></td>
                <td style="padding: 15px; text-align: center;"><?php echo date('H:i', strtotime($session['session_start'])); ?></td>
                <td style="padding: 15px; text-align: center;"><?php echo $session['duration']; ?> мин.</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<div class="profile-card" style="text-align: center;">
    <a href="teacher_dashboard.php" class="btn-secondary">← Назад к панели педагога</a>
</div>

<?php include 'includes/footer.php'; ?>