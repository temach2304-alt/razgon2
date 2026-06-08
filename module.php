<?php
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: login.php');
    exit;
}

$module_type = $_GET['type'] ?? 'uncertainty';
$user_id = $_SESSION['user_id'];

// Создаём сессию
try {
    $stmt = $pdo->prepare("INSERT INTO dialog_sessions (user_id, module_type) VALUES (?, ?)");
    $stmt->execute([$user_id, $module_type]);
    $session_id = $pdo->lastInsertId();
} catch (Exception $e) {
    die("Ошибка создания сессии: " . $e->getMessage());
}

// Загружаем сценарий
require_once 'rcp_scenarios.php';
$scenario = getScenario($module_type);

$module_names = [
    'uncertainty' => 'Занятие №2: Неопределённость',
    'pros_cons' => 'Занятие №5: За и Против',
    'consequences' => 'Занятие №8: Последствия',
    'quality_choice' => 'Занятие №9: Качество выбора'
];

$module_titles = [
    'uncertainty' => 'Как я отношусь к неизвестности?',
    'pros_cons' => 'Техника «За и Против»',
    'consequences' => 'Анализ последствий',
    'quality_choice' => 'Критерии качественного выбора'
];

$module_title = $module_names[$module_type] ?? 'Диалог с РЦП';
$module_subtitle = $module_titles[$module_type] ?? '';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $module_title; ?> - РЦП</title>
    <link rel="stylesheet" href="assets/css/dialog.css">
</head>
<body>
    <div class="dialog-wrapper">
        <header class="dialog-header">
            <div class="header-left">
                <a href="index.php" class="back-btn">← Назад</a>
                <div class="module-info">
                    <h1><?php echo $module_title; ?></h1>
                    <p class="module-subtitle"><?php echo $module_subtitle; ?></p>
                </div>
            </div>
        </header>

        <main class="chat-container glass-card">
            <div class="chat-messages" id="chatMessages">
                <!-- Приветствие РЦП -->
                <div class="message rcp-message">
                    <div class="message-avatar">🤖</div>
                    <div class="message-content">
                        <div class="message-text"><?php echo htmlspecialchars($scenario['greeting']); ?></div>
                        <div class="message-time"><?php echo date('H:i'); ?></div>
                    </div>
                </div>
            </div>

            <div class="chat-input-container">
                <div class="input-wrapper glass-card">
                    <textarea id="userInput" placeholder="Напишите ваш ответ..." rows="3" maxlength="1000"></textarea>
                    <div class="input-footer">
                        <span class="char-count"><span id="charCount">0</span>/1000</span>
                        <button onclick="sendMessage()" class="btn-send" id="sendBtn">
                            Отправить →
                        </button>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        const sessionId = <?php echo intval($session_id); ?>;
        const moduleType = '<?php echo htmlspecialchars($module_type); ?>';

        function sendMessage() {
            const input = document.getElementById('userInput');
            const sendBtn = document.getElementById('sendBtn');
            const message = input.value.trim();
            
            if (!message) return;

            // Блокируем кнопку на время отправки
            sendBtn.disabled = true;
            sendBtn.textContent = 'Отправка...';

            // Добавляем сообщение пользователя в чат
            addMessage(message, 'user');
            input.value = '';
            document.getElementById('charCount').textContent = '0';

            // Отправляем на сервер через AJAX
            fetch('ajax/dialog.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    session_id: sessionId,
                    message: message,
                    module_type: moduleType
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    addMessage('⚠️ Ошибка: ' + data.error, 'rcp');
                } else if (data.rcp_response) {
                    setTimeout(() => {
                        addMessage(data.rcp_response, 'rcp');
                    }, 500);
                } else {
                    addMessage('Не удалось получить ответ. Попробуйте ещё раз.', 'rcp');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                addMessage('⚠️ Произошла ошибка соединения. Проверьте подключение.', 'rcp');
            })
            .finally(() => {
                sendBtn.disabled = false;
                sendBtn.textContent = 'Отправить →';
            });
        }

        function addMessage(text, sender) {
            const chatMessages = document.getElementById('chatMessages');
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${sender}-message`;
            
            const time = new Date().toLocaleTimeString('ru-RU', {hour: '2-digit', minute:'2-digit'});
            const avatar = sender === 'user' ? '👤' : '🤖';
            
            messageDiv.innerHTML = `
                <div class="message-avatar">${avatar}</div>
                <div class="message-content">
                    <div class="message-text">${escapeHtml(text)}</div>
                    <div class="message-time">${time}</div>
                </div>
            `;
            
            chatMessages.appendChild(messageDiv);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Счётчик символов
        document.getElementById('userInput').addEventListener('input', function() {
            document.getElementById('charCount').textContent = this.value.length;
        });

        // Отправка по Enter (без Shift)
        document.getElementById('userInput').addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });
    </script>
</body>
</html>