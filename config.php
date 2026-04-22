<?php
// Конфигурация для production (Beget)
define('MAX_BOT_TOKEN', 'f9LHodD0cOKxWbSqNENEeY5_OT5L9MQ2i5UAMO_fXTEJukKdGscfy60yiIUKJJIEAiD0VoPk8XfTl3TVASS6'); // Токен из платформы MAX
define('MAX_API_URL', 'https://platform-api.max.ru');
define('BOT_MODE', 'webhook'); // Режим webhook для production

// Настройки БД (Beget)
define('DB_HOST', 'localhost');
define('DB_NAME', 'ваша_база_данных');
define('DB_USER', 'ваш_пользователь');
define('DB_PASS', 'ваш_пароль');

// URL бота (ваш домен)
define('BOT_URL', 'https://mbgorod.ru/max_bot');
define('BOT_WEBHOOK_URL', BOT_URL . '/index.php');

// ID администратора (замените после первого запуска)
define('ADMIN_CHAT_ID', '0'); // Временно 0, получите через команду /id

// Папки для загрузок
define('UPLOAD_DIR', __DIR__ . '/uploads/');

// Настройки БД
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATE', 'utf8mb4_unicode_ci');

// Режим отладки (отключить в production)
define('DEBUG_MODE', false);

// Логирование
define('LOG_FILE', __DIR__ . '/logs/bot.log');

// Функция для логирования
function bot_log($message, $data = null) {
    if (!DEBUG_MODE) return;
    
    $log = date('Y-m-d H:i:s') . ' - ' . $message;
    if ($data) {
        $log .= ' - ' . json_encode($data, JSON_UNESCAPED_UNICODE);
    }
    $log .= PHP_EOL;
    file_put_contents(LOG_FILE, $log, FILE_APPEND);
}
?>