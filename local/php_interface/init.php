<?php

/**
 * Logs a message to Telegram.
 *
 * @param mixed $messageText The message to be logged.
 * @param string|null $groupChatId Optional group chat ID.
 * @return void
 */
function LogTG($messageText, $groupChatId = null)
{
    $botToken = "5865641167:AAF55jrqMP0zFAGrU7Bv-1KUDj_7chXsVWc";
    $defaultChatId = "141079661";
    $chatId = $groupChatId ? $groupChatId : $defaultChatId;

    $telegramUrl = "https://api.telegram.org/bot".$botToken."/sendMessage";
    $text = $_SERVER["HTTP_HOST"]. "\n" . print_r($messageText, true);

    $postFields = [
        'chat_id' => $chatId,
        'text' => $text,
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $telegramUrl);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        return ['status' => 'error', 'error' => curl_error($ch)];
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        return ['status' => 'error', 'http_code' => $httpCode, 'response' => $response];
    }

    return ['status' => 'success', 'response' => $response];
}

/**
 * Удобная принтилка
 * @param $ar
 * @param $dark
 * @param $die
 * @return string|void
 */
function pr($ar, $dark = false, $die = false)
{
    global $USER;
    if (!$USER->IsAdmin()) return "";

    if(!$dark)
    {
        echo "<pre style='font-size:11px;line-height:1.2; padding:5px;'>".print_r($ar, 1)."</pre>";
    }
    else
    {
        echo '<pre style="line-height:1.2; padding:2em;font-size:11px;background: #282c34; color: #61dafb">' .print_r($ar, true).'</pre>';
    }
    if($die) die();
}

/**
 * Подключение функций для работы с заказами B2B
 */

include $_SERVER["DOCUMENT_ROOT"]."/local/php_interface/crmB2B.php";;




