<?php

namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\InlineKeyboard;

require __DIR__ . '/../shopItems/Notebook.php';
require __DIR__ . '/../shopItems/PC.php';
require __DIR__ . '/../shopItems/Phone.php';

class ShopCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'shop';

    /**
     * @var string
     */
    protected $description = 'Shop dialog';

    /**
     * @var string
     */
    protected $usage = '/shop';

    /**
     * @var string
     */
    protected $version = '1.0.0';

    /**
     * @var bool
     */
    protected $need_mysql = true;

    /**
     * @var bool
     */
    protected $private_only = true;

    /**
     * Conversation Object
     *
     * @var Conversation
     */
    protected $conversation;

    /**
     * Main command execution
     *
     * @return ServerResponse
     * @throws TelegramException
     */
    public function execute(): ServerResponse
    {
        $message = $this->getMessage();

        $chat    = $message->getChat();
        $user    = $message->getFrom();
        $text    = trim($message->getText(true));
        $chat_id = $chat->getId();
        $user_id = $user->getId();

        // Preparing response
        $data = [
            'chat_id'      => $chat_id,
            // Remove any keyboard by default
            'reply_markup' => Keyboard::remove(['selective' => true]),
        ];

        if ($chat->isGroupChat() || $chat->isSuperGroup()) {
            // Force reply is applied by default so it can work with privacy on
            $data['reply_markup'] = Keyboard::forceReply(['selective' => true]);
        }

        // Conversation start
        $this->conversation = new Conversation($user_id, $chat_id, $this->getName());

        // Load any existing notes from this conversation
        $notes = &$this->conversation->notes;
        !is_array($notes) && $notes = [];

        $result = Request::emptyResponse();
        switch (0) {
            case 0:
                if ($text === '' || !in_array($text, ['Phone', 'Notebook', 'PC'], true)) {
                    $this->conversation->update();

                    $data['reply_markup'] = (new Keyboard(['Phone', 'Notebook', 'PC']))
                        ->setResizeKeyboard(true)
                        ->setOneTimeKeyboard(true)
                        ->setSelective(true);

                    $data['text'] = 'Select which item you want to buy:';
                    if ($text !== '') {
                        $data['text'] = 'Choose a keyboard option to select an item';
                    }

                    $result = Request::sendMessage($data);
                    break;
                }

                $notes['shopItem'] = $text;
            case 1:
                $this->conversation->update();

                switch ($notes['shopItem']) {
                    case 'Phone':
                        $price = Phone['price'];
                        $currency = Phone['currency'];
                        $itemName = Phone['itemName'];
                        $itemDescription = Phone['itemDescription'];
                        $itemIcon = __DIR__ . '/../files/' . Phone['itemIcon'];
                        break;
                    case 'Notebook':
                        $price = Notebook['price'];
                        $currency = Notebook['currency'];
                        $itemName = Notebook['itemName'];
                        $itemDescription = Notebook['itemDescription'];
                        $itemIcon = __DIR__ . '/../files/' . Notebook['itemIcon'];
                        break;
                    case 'PC':
                        $price = PC['price'];
                        $currency = PC['currency'];
                        $itemName = PC['itemName'];
                        $itemDescription = PC['itemDescription'];
                        $itemIcon = __DIR__ . '/../files/' . PC['itemIcon'];
                        break;
                }

                $inline_keyboard = new InlineKeyboard([
                    ['text' => 'Proceed to payment', 'callback_data' => $notes['shopItem']],
                ]);

                $data['caption'] = "You want to buy $itemName.\nItem description: $itemDescription.\nPrice: $price $currency ";
                $data['photo'] = $itemIcon;
                $data['reply_markup'] = $inline_keyboard;
                $result = Request::sendPhoto($data);
                $this->conversation->stop();
                break;
        }

        return $result;
    }
}
