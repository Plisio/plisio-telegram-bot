<?php

require __DIR__ . '/config.php';

function wh_log($log_msg)
{
    $log_filename = "log";

    if (!file_exists($log_filename))
    {
        mkdir($log_filename, 0777, true);
    }

    $log_file_data = $log_filename.'/log_' . date('d-M-Y') . '.log';

    file_put_contents($log_file_data, $log_msg . "\n", FILE_APPEND);
}

function verifyCallbackData($post)
{
    $secretKey = API_KEY;
    if (!isset($post['verify_hash'])) {
        return false;
    }

    $verifyHash = $post['verify_hash'];
    unset($post['verify_hash']);
    ksort($post);
    if (isset($post['expire_utc'])){
        $post['expire_utc'] = (string)$post['expire_utc'];
    }
    if (isset($post['tx_urls'])){
        $post['tx_urls'] = html_entity_decode($post['tx_urls']);
    }
    $postString = serialize($post);
    $checkKey = hash_hmac('sha1', $postString, $secretKey);
    if ($checkKey != $verifyHash) {
        return false;
    }

    return true;
}

function callback()
{
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
}

callback();