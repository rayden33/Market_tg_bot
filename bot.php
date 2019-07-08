<?php

include 'config.php';
include 'bot_api.php';
include 'controller.php';

ob_start();
//if($db) echo 'ok';
//else echo 'kut';
$API_KEY = 'API_KEY';
define('API_KEY',$API_KEY);

$update = json_decode(file_get_contents('php://input'));
$message = $update->message;
$text = $message->text;
$chat_id = $message->chat->id;
$user = $message->from->username;
$user_id = $message->from->id;
$data = $update->callback_query->data;
$chat_id2 = $update->callback_query->message->chat->id;
$mid = $update->callback_query->message->message_id;
$cont = $message->contact->phone_number;

$text = textFilter($text);
//// Boshlash
if($text == '/start' || $text == 'Главная')
{
    //sendSimpleMessage($chat_id,"Hello");
    if(getStep($chat_id,$db)<10)
    {
        getTypeKeyboard($chat_id);
        setStep(1,$chat_id,$db);
    }
    else
    {
        sendSimpleMessage($chat_id,"Закончите оформление заказа");
    }
}
else
if($text == "Онлайн магазин")
{
    setNavType($chat_id,$db,1);
    getKeyboard($chat_id);
}
else
if($text == "Доставка товаров")
{
    setNavType($chat_id,$db,2);
    getKeyboard($chat_id);
}
else
if($text == "Обратная связь")
{
    sendSimpleMessage($chat_id,"about market");
}
else
if($text == "Категории")
{
    if(getStep($chat_id,$db)<10)
    {
        getCategories($chat_id,$db);
    }
    else
    {
        sendSimpleMessage($chat_id,"Закончите оформление заказа");
    }
}
else
if($text == 'Корзина')
{
    getCart($chat_id,$db);
}
else
{
    $step = getStep($chat_id,$db);
    if($text && $step>=10)
    {
        beginOrder($text,$step,$chat_id,$db);
        $step = getStep($chat_id,$db);
        if($step==14)
        {
            $data = getSamZone($chat_id,$db);
            $chat_id2 = $chat_id;
        }
    }
}
if($data && $data[0] == 's')
{
    $cat_id = dataFilter($data);
    getSubCategories($cat_id,$chat_id2,$mid,$db);
    getProduct($cat_id,$chat_id2,$db);
}

if($data && $data[0] == '+')
{
    $data = dataFilter($data);
    toCart($data,$chat_id2,$db);
}

if($data && $data[0] == '-')
{
    $data = dataFilter($data);
    clearCart($data,$db);
}
if($data && $data[0] == '=')
{
    $data = dataFilter($data);
    $step = 10;
    setStep($step,$chat_id2,$db);
    sendSimpleMessage($chat_id2,"Имя и фамилия:");
    getCache($chat_id2,$db,$step-1);
}
if($data && $data[0] == 'z')
{
    //sendSimpleMessage($chat_id2,"Hello");
    $data = dataFilter($data);
    beginOrder($data,getStep($chat_id2,$db),$chat_id2,$db);
}
if($data && $data == "right")
{
    rightProduct($chat_id2,$mid,$db);
}

if($data && $data == "left")
{
    leftProduct($chat_id2,$mid,$db);
}
?>