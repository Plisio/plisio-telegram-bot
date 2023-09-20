<?php
// Load composer
require __DIR__ . '/vendor/autoload.php';

$bot_api_key  = 'your_api_key';
$bot_username = 'your_bot_username';
$hook_url = 'http://yoursite.com/getWebhookMethod.php';

Longman\TelegramBot\Request::setCustomBotApiUri(
    $api_base_uri          = 'http://localhost:8081', // Default: https://api.telegram.org
    $api_base_download_uri = __DIR__ . '/files/your_api_key'     // Default: /file/bot{API_KEY}
);

try {
    // Create Telegram API object
    $telegram = new Longman\TelegramBot\Telegram($bot_api_key, $bot_username);

    // Set webhook
    $result = $telegram->setWebhook($hook_url);
    if ($result->isOk()) {
        echo $result->getDescription();
    }
} catch (Longman\TelegramBot\Exception\TelegramException $e) {
    // log telegram errors
    // echo $e->getMessage();
}