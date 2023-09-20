<?php

namespace Longman\TelegramBot\Commands\SystemCommands;

use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;

use Plisio\PaymentGateway\PlisioClient;

require __DIR__ . '/../shopItems/Notebook.php';
require __DIR__ . '/../shopItems/PC.php';
require __DIR__ . '/../shopItems/Phone.php';
require __DIR__ . '/../config.php';

class CallbackqueryCommand extends SystemCommand
{
    /**
     * @var string
     */
    protected $name = 'callbackquery';

    /**
     * @var string
     */
    protected $description = 'Handle the callback query';

    /**
     * @var string
     */
    protected $version = '1.0.0';

    protected $plisio;

    /**
     * Main command execution
     *
     * @return ServerResponse
     * @throws \Exception
     */
    public function execute(): ServerResponse
    {
        $callback_query = $this->getCallbackQuery();
        $callback_data  = $callback_query->getData();
        $callbackDataParameters = explode('_', $callback_data);

        $this->plisio = new PlisioClient(API_KEY);

        switch ($callbackDataParameters[0]) {
            case 'PC':
                $request = array(
                    'source_amount' => PC['price'],
                    'source_currency' => PC['currency'],
                    'order_name' => PC['itemName'],
                    'order_number' => time(),
                    'description' => PC['itemDescription'],
                    'callback_url' => $_SERVER['SERVER_NAME'] . '/callback.php',
                    'plugin' => 'TGBot',
                    'version' => $this->version,
                );
                break;
            case 'Notebook':
                $request = array(
                    'source_amount' => Notebook['price'],
                    'source_currency' => Notebook['currency'],
                    'order_name' => Notebook['itemName'],
                    'order_number' => time(),
                    'description' => Notebook['itemDescription'],
                    'callback_url' => $_SERVER['SERVER_NAME'] . '/callback.php',
                    'plugin' => 'TGBot',
                    'version' => $this->version,
                );
                break;
            case 'Phone':
                $request = array(
                    'source_amount' => Phone['price'],
                    'source_currency' => Phone['currency'],
                    'order_name' => Phone['itemName'],
                    'order_number' => time(),
                    'description' => Phone['itemDescription'],
                    'callback_url' => $_SERVER['SERVER_NAME'] . '/callback.php',
                    'plugin' => 'TGBot',
                    'version' => $this->version,
                );
                break;
            case 'Donate':
                $request = array(
                    'source_amount' => $callbackDataParameters[1],
                    'source_currency' => $callbackDataParameters[2],
                    'order_name' => 'Donation',
                    'order_number' => time(),
                    'description' => 'Donation from a user for custom amount of money',
                    'callback_url' => $_SERVER['SERVER_NAME'] . '/callback.php',
                    'plugin' => 'TGBot',
                    'version' => $this->version,
                );
                break;
            default:
                return $callback_query->answer([
                    'text' => 'Invalid callback parameter',
                    'cache_time' => 5
                ]);
        }

        $response = $this->plisio->createTransaction($request);

        if ($response && $response['status'] !== 'error' && !empty($response['data'])) {
            $paymentLink = $response['data']['invoice_url'];
            $data = [];
            $data['parse_mode'] = 'MarkdownV2';
            $data['chat_id'] = $callback_query->getMessage()->getChat()->getId();
            $data['text'] = 'Your order payment link is ready, click to proceed: ' . "[Pay Here]($paymentLink)";
            Request::sendMessage($data);
            return $callback_query->answer([
                'text' => "Order created successfully: $paymentLink",
                'cache_time' => 30,
            ]);
        } else {
            return $callback_query->answer([
                'text'       => 'Failed to create order: ' . implode(',', json_decode($response['data']['message'], true)),
                'show_alert' => true,
                'cache_time' => 30,
            ]);
        }
    }
}
