<?php
require_once 'db.php';
require_once 'config.php';

session_start();

// Проверка авторизации
if (!isset($_SESSION['admin_logged'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
        // Смените пароль на более сложный
        if ($_POST['password'] === 'admin123') {
            $_SESSION['admin_logged'] = true;
        } else {
            $error = "Неверный пароль";
        }
    }
    
    if (!isset($_SESSION['admin_logged'])) {
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Вход в панель администратора</title>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <style>
                body { font-family: Arial, sans-serif; padding: 50px; background: #f0f2f5; }
                .login-form { max-width: 300px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                h2 { margin-top: 0; }
                input { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 5px; }
                button { width: 100%; padding: 10px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; }
                button:hover { background: #0056b3; }
                .error { color: red; margin-bottom: 10px; }
            </style>
        </head>
        <body>
            <div class="login-form">
                <h2>Вход в панель администратора</h2>
                <?php if (isset($error)) echo "<div class='error'>$error</div>"; ?>
                <form method="POST">
                    <input type="password" name="password" placeholder="Пароль" required>
                    <button type="submit">Войти</button>
                </form>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}

$db = Database::getInstance();
$tickets = $db->getAllOpenTickets();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Панель администратора MAX бота</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f0f2f5; }
        .container { display: flex; height: 100vh; }
        .sidebar { width: 350px; background: white; border-right: 1px solid #e0e0e0; overflow-y: auto; }
        .chat-area { flex: 1; display: flex; flex-direction: column; }
        .ticket-item { padding: 15px; border-bottom: 1px solid #e0e0e0; cursor: pointer; transition: background 0.3s; }
        .ticket-item:hover { background: #f5f5f5; }
        .ticket-item.active { background: #e3f2fd; border-left: 3px solid #2196f3; }
        .ticket-number { font-weight: bold; font-size: 16px; }
        .ticket-info { font-size: 12px; color: #666; margin-top: 5px; }
        .unread-badge { background: #f44336; color: white; border-radius: 10px; padding: 2px 8px; font-size: 12px; float: right; }
        .messages { flex: 1; overflow-y: auto; padding: 20px; background: #fafafa; }
        .message { margin-bottom: 20px; display: flex; }
        .message.client { justify-content: flex-start; }
        .message.admin { justify-content: flex-end; }
        .message-content { max-width: 60%; padding: 10px 15px; border-radius: 10px; }
        .client .message-content { background: white; border: 1px solid #e0e0e0; }
        .admin .message-content { background: #007bff; color: white; }
        .message-text { word-wrap: break-word; }
        .message-time { font-size: 11px; margin-top: 5px; opacity: 0.7; }
        .media-preview { max-width: 200px; max-height: 200px; margin-top: 10px; cursor: pointer; }
        .input-area { padding: 20px; background: white; border-top: 1px solid #e0e0e0; }
        .input-group { display: flex; gap: 10px; }
        textarea { flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 5px; resize: vertical; font-family: inherit; }
        button { padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; }
        button:hover { background: #0056b3; }
        .media-buttons { display: flex; gap: 10px; margin-top: 10px; }
        .media-btn { background: #6c757d; padding: 8px 15px; font-size: 14px; }
        .close-ticket-btn { background: #dc3545; margin-left: 10px; }
        .close-ticket-btn:hover { background: #c82333; }
        .header { background: white; padding: 15px 20px; border-bottom: 1px solid #e0e0e0; display: flex; justify-content: space-between; align-items: center; }
        h2 { font-size: 18px; }
        @media (max-width: 768px) {
            .sidebar { width: 250px; }
            .message-content { max-width: 80%; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="sidebar">
            <div style="padding: 15px; background: #007bff; color: white;">
                <h2>Активные заявки</h2>
                <small>Всего: <?= count($tickets) ?></small>
            </div>
            <div id="tickets-list">
                <?php foreach ($tickets as $ticket): ?>
                <div class="ticket-item" data-ticket-id="<?= $ticket['id'] ?>">
                    <div class="ticket-number">
                        Заявка #<?= $ticket['ticket_number'] ?>
                        <?php if ($ticket['unread_count'] > 0): ?>
                        <span class="unread-badge"><?= $ticket['unread_count'] ?> new</span>
                        <?php endif; ?>
                    </div>
                    <div class="ticket-info">
                        <?= htmlspecialchars($ticket['client_name']) ?> | <?= htmlspecialchars($ticket['client_phone']) ?>
                    </div>
                    <div class="ticket-info">
                        Создана: <?= date('d.m.Y H:i', strtotime($ticket['created_at'])) ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="chat-area">
            <div class="header" id="chat-header">
                <h2>Выберите заявку</h2>
                <button class="close-ticket-btn" onclick="closeTicket()" style="display:none" id="closeBtn">Закрыть заявку</button>
            </div>
            <div class="messages" id="messages">
                <div style="text-align: center; color: #999; padding: 50px;">
                    Выберите заявку из списка слева
                </div>
            </div>
            <div class="input-area" style="display:none" id="inputArea">
                <div class="input-group">
                    <textarea id="messageText" rows="3" placeholder="Введите сообщение..."></textarea>
                    <button onclick="sendMessage()">Отправить</button>
                </div>
                <div class="media-buttons">
                    <button class="media-btn" onclick="document.getElementById('photoInput').click()">📷 Добавить фото</button>
                    <button class="media-btn" onclick="document.getElementById('videoInput').click()">🎥 Добавить видео</button>
                </div>
                <input type="file" id="photoInput" accept="image/*" style="display:none" onchange="uploadMedia('photo')">
                <input type="file" id="videoInput" accept="video/*" style="display:none" onchange="uploadMedia('video')">
            </div>
        </div>
    </div>
    
    <script>
    let currentTicketId = null;
    let pendingMedia = null;
    
    function loadMessages(ticketId) {
        fetch(`admin_api.php?action=get_messages&ticket_id=${ticketId}`)
            .then(response => response.json())
            .then(data => {
                const messagesDiv = document.getElementById('messages');
                messagesDiv.innerHTML = '';
                
                if (data.messages) {
                    data.messages.forEach(msg => {
                        const messageDiv = document.createElement('div');
                        messageDiv.className = `message ${msg.sender_type}`;
                        
                        let content = `<div class="message-content">`;
                        if (msg.message_text) {
                            content += `<div class="message-text">${escapeHtml(msg.message_text)}</div>`;
                        }
                        if (msg.media_url) {
                            if (msg.media_type === 'photo') {
                                content += `<img src="${msg.media_url}" class="media-preview" onclick="window.open('${msg.media_url}')">`;
                            } else if (msg.media_type === 'video') {
                                content += `<video src="${msg.media_url}" class="media-preview" controls></video>`;
                            }
                        }
                        content += `<div class="message-time">${new Date(msg.created_at).toLocaleString()}</div>`;
                        content += `</div>`;
                        messageDiv.innerHTML = content;
                        messagesDiv.appendChild(messageDiv);
                    });
                }
                
                messagesDiv.scrollTop = messagesDiv.scrollHeight;
            });
    }
    
    function sendMessage() {
        const text = document.getElementById('messageText').value;
        if (!text.trim()) return;
        
        const formData = new FormData();
        formData.append('action', 'send_message');
        formData.append('ticket_id', currentTicketId);
        formData.append('message', text);
        
        fetch('admin_api.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('messageText').value = '';
                loadMessages(currentTicketId);
            } else {
                alert('Ошибка отправки: ' + (data.error || 'Неизвестная ошибка'));
            }
        });
    }
    
    function closeTicket() {
        if (confirm('Закрыть заявку? Клиент не сможет отправлять сообщения в эту заявку.')) {
            fetch(`admin_api.php?action=close_ticket&ticket_id=${currentTicketId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Заявка закрыта');
                        location.reload();
                    }
                });
        }
    }
    
    function uploadMedia(type) {
        const input = document.getElementById(type === 'photo' ? 'photoInput' : 'videoInput');
        const file = input.files[0];
        if (!file) return;
        
        const formData = new FormData();
        formData.append('action', 'send_media');
        formData.append('ticket_id', currentTicketId);
        formData.append('media_type', type);
        formData.append('media', file);
        
        fetch('admin_api.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadMessages(currentTicketId);
            } else {
                alert('Ошибка загрузки: ' + (data.error || 'Неизвестная ошибка'));
            }
        });
        
        input.value = '';
    }
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Обработка выбора заявки
    document.querySelectorAll('.ticket-item').forEach(item => {
        item.addEventListener('click', function() {
            document.querySelectorAll('.ticket-item').forEach(i => i.classList.remove('active'));
            this.classList.add('active');
            currentTicketId = this.dataset.ticketId;
            
            document.getElementById('chat-header').querySelector('h2').textContent = this.querySelector('.ticket-number').textContent;
            document.getElementById('closeBtn').style.display = 'block';
            document.getElementById('inputArea').style.display = 'block';
            
            loadMessages(currentTicketId);
            
            // Отмечаем сообщения как прочитанные
            fetch(`admin_api.php?action=mark_read&ticket_id=${currentTicketId}`);
        });
    });
    
    // Автообновление
    setInterval(() => {
        if (currentTicketId) {
            loadMessages(currentTicketId);
        }
    }, 5000);
    </script>
</body>
</html>