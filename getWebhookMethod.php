<?php
// Load composer
require __DIR__ . '/vendor/autoload.php';

$bot_api_key  = 'your_api_key';
$bot_username = 'your_bot_username';

// Define all paths for your custom commands in this array (leave as empty array if not used)
$commands_paths = [
    __DIR__ . '/commands',
];

$mysql_credentials = [
    'host'     => 'localhost',
    'port'     => 3306, // optional
    'user'     => 'root',
    'password' => '',
    'database' => 'tgbot',
];

try {
    // Create Telegram API object
    $telegram = new Longman\TelegramBot\Telegram($bot_api_key, $bot_username);
    $telegram->addCommandsPaths($commands_paths);
    $telegram->enableMySql($mysql_credentials);

    // Handle telegram webhook request
    $telegram->handle();

} catch (Longman\TelegramBot\Exception\TelegramException $e) {
    // Silence is golden!
    // log telegram errors
    // echo $e->getMessage();
}