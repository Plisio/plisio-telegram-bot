## Plisio Payment Gateway integration with Telegram shop bot
This repository is an example of a Telegram shop bot that supports cryptocurrency payment using the Plisio payment gateway
>__Here is only a very simple implementation of a store based on a telegram bot, it is completely unsuitable for use in production and is full of flaws. Use this code only as a reference for how you can implement the Plisio payment gateway in your code.__

### Installing the Plisio library
Only one file is needed for the library to work - the library itself, no additional dependencies are needed.
1. Download the library at https://github.com/Plisio/php-lib
2. Include the library in places where you plan to use it. 
```php
require_once __DIR__ . '/PlisioClient.php'
```
### Using the Plisio library
As an example of creating an invoice - the bot uses a user-selected item. When the user clicks on the callback button "Proceed to payment"
```php
$inline_keyboard = new InlineKeyboard([
                    ['text' => 'Proceed to payment', 'callback_data' => $notes['shopItem']],
                ]);
```
the bot gets information about the name of the product (usually it can be the product id or any other designation that allows to find the product card in the database) and gets information about it from a local file (in this example it will be - PC.php).
#### Creating an invoice
Once we have information about the items the user wants to pay for, we can start generating an invoice using the Plisio library.
```php
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
                
 $response = $this->plisio->createTransaction($request);
```
#### Api response processing
In this example, if an invoice is successfully created, the user receives a message with a link to the invoice, and if an error occurs, the user receives an error message that can be passed to the bot's technical support.
```php
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
```
#### Processing the callback sent by Plisio.
When an invoice is created, as well as any change in its status, Plisio sends a callback to the address specified in the `callback_url` field when creating the invoice. A callback is a POST request containing basic information about the order, its current status and a field to authenticate the callback. An example of callback processing is contained in the `callback.php` file.
```php
if (verifyCallbackData($_POST)) {

        $order_id = $_POST['order_number'];

        switch ($_POST['status']) {
            case 'new':
                $order_status = 'pending';
            case 'completed':
            case 'mismatch':
                $order_status = 'completed';
                break;
            case 'expired':
            case 'cancelled':
                $order_status = 'cancelled';
                break;
        }

        wh_log("Successfully received an callback for order# $order_id . Order status changed to $order_status");

    } else {
        wh_log('Plisio response looks suspicious. Skip updating order.');
    }
```
To find the order to which the callback relates, use the `order_number` field - this is a unique numeric order identifier that you specify when creating an invoice, in our example the `time()` function is used to create the unique identifier, so when the invoice is successfully created you should place it in the database along with the rest of the order information. 
>__The verifyCallbackData() function is used to authenticate the callback, it uses your Plisio api_key, so don't let your key leak to the public!__

### More information about the bot
As said before - the bot source code only serves as an example that shows how you can integrate Plisio Payment Gateway into your bot, but if for some reason you want to run this bot and test it, you may need to know the details below.
The bot uses the [PHP Telegram Bot library](https://github.com/php-telegram-bot/core) to interact with the Telegram API. For testing and debugging, a [local Telegram bot server](https://github.com/tdlib/telegram-bot-api) was compiled and run.
When you are done cloning the repository and setting up the database for the bot - don't forget to enter your settings values in the `config.php`, `getWebhookMethod.php`, `setUpdateMethod.php` files. 
