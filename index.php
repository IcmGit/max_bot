<?php
require_once 'db.php';
require_once 'max_api.php';
require_once 'config.php';

// Получаем входящие данные
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Логируем входящий запрос (только в режиме отладки)
if (DEBUG_MODE) {
    bot_log("Webhook received", $data);
}

// Проверяем, что это сообщение
if (!$data || !isset($data['message'])) {
    http_response_code(200);
    echo json_encode(['status' => 'ok']);
    exit;
}

$message = $data['message'];
$chatId = $message['recipient']['chat_id'] ?? null;
$text = $message['body']['text'] ?? '';
$senderId = $message['sender']['user_id'] ?? null;

if (!$chatId) {
    http_response_code(200);
    echo json_encode(['status' => 'ok']);
    exit;
}

// Обрабатываем сообщение
$db = Database::getInstance();
$maxApi = new MaxAPI();

// Команда /id
if ($text === '/id') {
    $response = "🆆 *Ваш ID чата:* `{$chatId}`\n\n";
    $response .= "Скопируйте этот ID и вставьте в config.php\n\n";
    $response .= "Текущий ADMIN_CHAT_ID: " . ADMIN_CHAT_ID;
    $maxApi->sendMessage($chatId, $response);
    
    // Если это первый запуск, обновляем ADMIN_CHAT_ID
    if (ADMIN_CHAT_ID === '0') {
        bot_log("First run - update ADMIN_CHAT_ID to: {$chatId}");
    }
    
    http_response_code(200);
    echo json_encode(['status' => 'ok']);
    exit;
}

// Команда /start
if ($text === '/start') {
    $keyboard = $maxApi->createInlineKeyboard([
        [
            $maxApi->createCallbackButton("📝 Подать новую заявку", "new_ticket")
        ],
        [
            $maxApi->createCallbackButton("📋 Мои заявки", "my_tickets"),
            $maxApi->createCallbackButton("ℹ️ Помощь", "help")
        ]
    ]);
    
    $message = "🤖 *Добро пожаловать в бот поддержки!*\n\n";
    $message .= "📌 *Что вы можете сделать:*\n";
    $message .= "• Подать новую заявку\n";
    $message .= "• Отслеживать статус заявок\n";
    $message .= "• Общаться с администратором\n\n";
    $message .= "💡 Отправьте /help для просмотра команд";
    
    $maxApi->sendMessage($chatId, $message, null, $keyboard);
    
    http_response_code(200);
    echo json_encode(['status' => 'ok']);
    exit;
}

// Команда /help
if ($text === '/help') {
    $helpMessage = "📚 *Команды бота:*\n\n";
    $helpMessage .= "/start - Главное меню\n";
    $helpMessage .= "/id - Узнать ID чата\n";
    $helpMessage .= "/help - Эта справка\n";
    $helpMessage .= "/cancel - Отменить действие\n\n";
    $helpMessage .= "📝 *Как подать заявку:*\n";
    $helpMessage .= "Нажмите кнопку 'Подать новую заявку' и следуйте инструкциям";
    
    $maxApi->sendMessage($chatId, $helpMessage);
    
    http_response_code(200);
    echo json_encode(['status' => 'ok']);
    exit;
}

// Остальные сообщения - проверяем наличие открытой заявки
$activeTicket = $db->getClientActiveTicket($chatId);

if ($activeTicket) {
    // Есть открытая заявка - сохраняем сообщение
    $db->addMessage($activeTicket['id'], 'client', $chatId, $text);
    $maxApi->sendMessage($chatId, "✅ Сообщение отправлено администратору");
    
    // Уведомляем администратора
    if (ADMIN_CHAT_ID !== '0') {
        $adminMessage = "💬 *Новое сообщение от клиента*\n";
        $adminMessage .= "📋 Заявка #{$activeTicket['ticket_number']}\n";
        $adminMessage .= "👤 Клиент: {$activeTicket['client_name']}\n";
        $adminMessage .= "📝 Сообщение: {$text}";
        $maxApi->sendMessage(ADMIN_CHAT_ID, $adminMessage);
    }
} else {
    // Нет активной заявки - предлагаем создать
    $response = "У вас нет активных заявок.\n\n";
    $response .= "Чтобы создать новую заявку, отправьте команду /start";
    $maxApi->sendMessage($chatId, $response);
}

// Всегда возвращаем 200 OK для webhook
http_response_code(200);
echo json_encode(['status' => 'ok']);
?>