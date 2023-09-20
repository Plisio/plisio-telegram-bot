<?php

namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\InlineKeyboard;

class DonateCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'donate';

    /**
     * @var string
     */
    protected $description = 'Donate dialog';

    /**
     * @var string
     */
    protected $usage = '/donate';

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

        $state = $notes['state'] ?? 0;

        $result = Request::emptyResponse();
        switch ($state) {
            case 0:
                if ($text === '') {
                    $notes['state'] = 0;
                    $this->conversation->update();

                    $data['text'] = 'Enter the amount you wish to donate without currency acronym (for example: 100):';

                    $result = Request::sendMessage($data);
                    break;
                }

                $notes['amount'] = $text;
                $text = '';
            case 1:
                if ($text === '') {
                    $notes['state'] = 1;
                    $this->conversation->update();

                    $data['text'] = 'Enter the currency acronym (for example: USD):';

                    $result = Request::sendMessage($data);
                    break;
                }

                $notes['currency'] = $text;
                $text = '';
            case 2:
                $notes['state'] = 2;
                $this->conversation->update();

                $orderTotal = $notes['amount'] . ' ' . $notes['currency'];

                $inline_keyboard = new InlineKeyboard([
                    ['text' => 'Proceed to payment', 'callback_data' => 'Donate_' . $notes['amount'] . '_' . $notes['currency']],
                ]);

                $data['text'] = "You want to donate $orderTotal.";
                $data['reply_markup'] = $inline_keyboard;
                $result = Request::sendMessage($data);
                $this->conversation->stop();
                break;
        }

        return $result;
    }
}
