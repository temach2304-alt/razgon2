<?php
require_once 'config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $full_name = trim($_POST['full_name'] ?? '');
    $role = $_POST['role'] ?? 'student';
    $class_name = trim($_POST['class_name'] ?? '');
    
    // Валидация
    if (!$username || !$password || !$full_name) {
        $error = 'Пожалуйста, заполните все обязательные поля';
    } elseif ($password !== $password_confirm) {
        $error = 'Пароли не совпадают';
    } elseif (strlen($password) < 6) {
        $error = 'Пароль должен содержать минимум 6 символов';
    } else {
        // Проверка существующего пользователя
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        
        if ($stmt->fetch()) {
            $error = 'Пользователь с таким именем уже существует';
        } else {
            try {
                // Создание пользователя
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                
                $stmt = $pdo->prepare("
                    INSERT INTO users (username, password_hash, full_name, role, class_name, created_at) 
                    VALUES (?, ?, ?, ?, ?, NOW())
                ");
                
                $stmt->execute([$username, $password_hash, $full_name, $role, $class_name ?: null]);
                
                $success = 'Регистрация успешна! Теперь вы можете войти в систему.';
                
            } catch (PDOException $e) {
                $error = 'Ошибка при регистрации: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация - РЦП</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="auth-container">
        <h2>📝 Регистрация</h2>
        
        <?php if ($error): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success-message">
                <?php echo htmlspecialchars($success); ?><br>
                <a href="login.php" style="color: #667eea; font-weight: 600;">Перейти к входу →</a>
            </div>
        <?php endif; ?>
        
        <?php if (!$success): ?>
        <form method="POST">
            <div class="form-group">
                <label>ФИО: *</label>
                <input type="text" name="full_name" required placeholder="Иванов Иван Иванович" value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label>Имя пользователя: *</label>
                <input type="text" name="username" required placeholder="ivanov" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label>Пароль: * (минимум 6 символов)</label>
                <input type="password" name="password" required>
            </div>
            
            <div class="form-group">
                <label>Подтвердите пароль: *</label>
                <input type="password" name="password_confirm" required>
            </div>
            
            <div class="form-group">
                <label>Роль: *</label>
                <select name="role" required onchange="toggleClassField(this.value)">
                    <option value="student" <?php echo ($_POST['role'] ?? 'student') === 'student' ? 'selected' : ''; ?>>Ученик</option>
                    <option value="teacher" <?php echo ($_POST['role'] ?? '') === 'teacher' ? 'selected' : ''; ?>>Педагог</option>
                </select>
            </div>
            
            <div class="form-group" id="class-field" style="<?php echo ($_POST['role'] ?? 'student') === 'student' ? '' : 'display: none;'; ?>">
                <label>Класс:</label>
                <input type="text" name="class_name" placeholder="9А" value="<?php echo htmlspecialchars($_POST['class_name'] ?? ''); ?>">
            </div>
            
            <button type="submit" class="btn-primary" style="width: 100%;">Зарегистрироваться</button>
        </form>
        
        <div class="auth-footer">
            Уже есть аккаунт? <a href="login.php">Войти</a>
        </div>
        <?php endif; ?>
    </div>

    <script>
    function toggleClassField(role) {
        const classField = document.getElementById('class-field');
        if (role === 'student') {
            classField.style.display = 'block';
        } else {
            classField.style.display = 'none';
        }
    }
    </script>
</body>
</html>