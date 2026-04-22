<?php
// setup_webhook.php - запустить один раз после загрузки на хостинг
require_once 'config.php';
require_once 'max_api.php';

$api = new MaxAPI();

echo "=== НАСТРОЙКА WEBHOOK ДЛЯ MAX БОТА ===\n\n";

// 1. Проверяем соединение
echo "1. Проверяем соединение с API MAX...\n";
$me = $api->getMe();
if ($me) {
    echo "   ✅ Бот найден: " . ($me['name'] ?? 'Unknown') . "\n";
} else {
    echo "   ❌ Ошибка: не удалось подключиться к API\n";
    echo "   Проверьте токен в config.php\n";
    exit(1);
}

// 2. Удаляем старый webhook
echo "\n2. Удаляем старый webhook...\n";
$result = $api->deleteWebhook();
echo "   " . ($result ? "✅ Удален" : "⚠️ Не найден") . "\n";

// 3. Устанавливаем новый webhook
echo "\n3. Устанавливаем новый webhook...\n";
echo "   URL: " . BOT_WEBHOOK_URL . "\n";

$result = $api->setWebhook(BOT_WEBHOOK_URL);
if ($result) {
    echo "   ✅ Webhook успешно установлен!\n";
} else {
    echo "   ❌ Ошибка установки webhook\n";
    echo "   Проверьте:\n";
    echo "   - Доступность URL по HTTPS\n";
    echo "   - Права на запись в папке logs/\n";
    exit(1);
}

// 4. Проверяем статус webhook
echo "\n4. Проверяем статус webhook...\n";
$info = $api->getWebhookInfo();
if ($info) {
    echo "   URL: " . ($info['url'] ?? 'не установлен') . "\n";
    echo "   Активен: " . (($info['is_active'] ?? false) ? 'Да' : 'Нет') . "\n";
}

echo "\n=== НАСТРОЙКА ЗАВЕРШЕНА ===\n";
echo "\nДалее:\n";
echo "1. Напишите боту команду /id\n";
echo "2. Скопируйте полученный ID\n";
echo "3. Обновите ADMIN_CHAT_ID в config.php\n";
echo "4. Перезапустите настройку webhook\n";
?>