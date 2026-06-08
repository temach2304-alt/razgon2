<?php
$page_title = 'Мой профиль - РЦП';
$header_title = '👤 Мой профиль';
$header_subtitle = 'Управление личной информацией';
require_once 'config.php';

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';
$message_type = '';

// Обработка обновления профиля
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $full_name = trim($_POST['full_name'] ?? '');
    $class_name = trim($_POST['class_name'] ?? '');
    
    if ($full_name) {
        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, class_name = ? WHERE id = ?");
        $stmt->execute([$full_name, $class_name ?: null, $user_id]);
        
        $message = 'Профиль успешно обновлен!';
        $message_type = 'success';
    }
}

// Обработка смены пароля
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Получение текущего пароля
    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!password_verify($current_password, $user['password_hash'])) {
        $message = 'Текущий пароль введен неверно';
        $message_type = 'error';
    } elseif ($new_password !== $confirm_password) {
        $message = 'Новые пароли не совпадают';
        $message_type = 'error';
    } elseif (strlen($new_password) < 6) {
        $message = 'Пароль должен содержать минимум 6 символов';
        $message_type = 'error';
    } else {
        $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $stmt->execute([$new_password_hash, $user_id]);
        
        $message = 'Пароль успешно изменен!';
        $message_type = 'success';
    }
}

// Получение данных пользователя
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Статистика для ученика
$student_stats = null;
if ($user['role'] === 'student') {
    // Количество завершенных модулей
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT module_id) as completed_modules 
        FROM reflections 
        WHERE student_id = ? AND completed = 1
    ");
    $stmt->execute([$user_id]);
    $completed = $stmt->fetch()['completed_modules'];
    
    // Общее время в системе
    $stmt = $pdo->prepare("
        SELECT SUM(TIMESTAMPDIFF(MINUTE, session_start, session_end)) as total_minutes
        FROM sessions 
        WHERE student_id = ? AND session_end IS NOT NULL
    ");
    $stmt->execute([$user_id]);
    $total_minutes = $stmt->fetch()['total_minutes'] ?? 0;
    
    $student_stats = [
        'completed_modules' => $completed,
        'total_minutes' => $total_minutes
    ];
}

// Статистика для педагога
$teacher_stats = null;
if ($user['role'] === 'teacher') {
    // Количество учеников
    $stmt = $pdo->prepare("SELECT COUNT(*) as students_count FROM users WHERE role = 'student'");
    $stmt->execute();
    $students_count = $stmt->fetch()['students_count'];
    
    // Количество проведенных сессий
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT s.id) as sessions_count
        FROM sessions s
        JOIN users u ON s.student_id = u.id
        WHERE u.class_name IS NOT NULL
    ");
    $stmt->execute();
    $sessions_count = $stmt->fetch()['sessions_count'];
    
    $teacher_stats = [
        'students_count' => $students_count,
        'sessions_count' => $sessions_count
    ];
}
?>

<?php include 'includes/header.php'; ?>

<?php if ($message): ?>
<div class="profile-card" style="margin-bottom: 20px;">
    <div class="<?php echo $message_type === 'success' ? 'success-message' : 'error-message'; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
</div>
<?php endif; ?>

<div class="profile-card">
    <h2 style="color: #667eea; margin-bottom: 30px;">📋 Основная информация</h2>
    
    <div class="profile-info">
        <div class="profile-info-item">
            <label>Имя пользователя:</label>
            <span><?php echo htmlspecialchars($user['username']); ?></span>
        </div>
        
        <div class="profile-info-item">
            <label>Роль:</label>
            <span><?php echo $user['role'] === 'teacher' ? '👨‍🏫 Педагог' : '👨‍🎓 Ученик'; ?></span>
        </div>
        
        <div class="profile-info-item">
            <label>Дата регистрации:</label>
            <span><?php echo date('d.m.Y', strtotime($user['created_at'])); ?></span>
        </div>
        
        <?php if ($user['role'] === 'student'): ?>
        <div class="profile-info-item">
            <label>Класс:</label>
            <span><?php echo htmlspecialchars($user['class_name'] ?? 'Не указан'); ?></span>
        </div>
        <?php endif; ?>
        
        <div class="profile-info-item">
            <label>Последний вход:</label>
            <span><?php echo $user['last_login'] ? date('d.m.Y H:i', strtotime($user['last_login'])) : 'Никогда'; ?></span>
        </div>
    </div>
</div>

<?php if ($student_stats): ?>
<div class="profile-card">
    <h2 style="color: #667eea; margin-bottom: 30px;">📊 Моя статистика</h2>
    
    <div class="profile-info">
        <div class="profile-info-item" style="border-left-color: #4CAF50;">
            <label>Завершено модулей:</label>
            <span style="color: #4CAF50;"><?php echo $student_stats['completed_modules']; ?> из 4</span>
        </div>
        
        <div class="profile-info-item" style="border-left-color: #2196F3;">
            <label>Время в системе:</label>
            <span style="color: #2196F3;"><?php echo round($student_stats['total_minutes'] / 60, 1); ?> ч.</span>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($teacher_stats): ?>
<div class="profile-card">
    <h2 style="color: #667eea; margin-bottom: 30px;">📊 Статистика педагога</h2>
    
    <div class="profile-info">
        <div class="profile-info-item" style="border-left-color: #4CAF50;">
            <label>Всего учеников:</label>
            <span style="color: #4CAF50;"><?php echo $teacher_stats['students_count']; ?></span>
        </div>
        
        <div class="profile-info-item" style="border-left-color: #2196F3;">
            <label>Проведено сессий:</label>
            <span style="color: #2196F3;"><?php echo $teacher_stats['sessions_count']; ?></span>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="profile-card">
    <h2 style="color: #667eea; margin-bottom: 30px;">✏️ Редактировать профиль</h2>
    
    <form method="POST">
        <input type="hidden" name="update_profile" value="1">
        
        <div class="form-group">
            <label>ФИО:</label>
            <input type="text" name="full_name" required value="<?php echo htmlspecialchars($user['full_name']); ?>">
        </div>
        
        <?php if ($user['role'] === 'student'): ?>
        <div class="form-group">
            <label>Класс:</label>
            <input type="text" name="class_name" value="<?php echo htmlspecialchars($user['class_name'] ?? ''); ?>">
        </div>
        <?php endif; ?>
        
        <button type="submit" class="btn-primary">Сохранить изменения</button>
    </form>
</div>

<div class="profile-card">
    <h2 style="color: #667eea; margin-bottom: 30px;">🔒 Сменить пароль</h2>
    
    <form method="POST">
        <input type="hidden" name="change_password" value="1">
        
        <div class="form-group">
            <label>Текущий пароль:</label>
            <input type="password" name="current_password" required>
        </div>
        
        <div class="form-group">
            <label>Новый пароль (минимум 6 символов):</label>
            <input type="password" name="new_password" required>
        </div>
        
        <div class="form-group">
            <label>Подтвердите новый пароль:</label>
            <input type="password" name="confirm_password" required>
        </div>
        
        <button type="submit" class="btn-primary">Изменить пароль</button>
    </form>
</div>

<div class="profile-card" style="text-align: center;">
    <a href="logout.php" class="btn-danger">🚪 Выйти из системы</a>
</div>

<?php include 'includes/footer.php'; ?>