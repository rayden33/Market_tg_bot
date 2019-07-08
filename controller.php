<?php

function imageUrlFilter($text)
{
    $text2 = "";
    $textln = strlen($text);
    for($i = 0;$i < $textln; $i++)
    {
        if($text[$i] == ' ')
        {
            $text2.="%20";
        }
        else
        {
            $text2.=$text[$i];
        }
    }
    return $text2;
}

function textFilter($text)
{
    $textln = strlen($text);
    for($i = 0;$i < $textln; $i++)
    {
        if($text[$i] == '\'')
        {
            $text[$i] = '`';
        }
    }
    return $text;
}

function dataFilter($data)
{
    $dat = "";
    for($i=1;$i<strlen($data);$i++)
    {
        $dat.=$data[$i];
    }
    return $dat;
}

function nameFilter($text,$type)
{
    $name1 = "";
    $name2 = "";
    $prob = false;
    for($i=0;$i<strlen($text);$i++)
    {
        if($text[$i]==" ")
        {
            $prob = true;
            continue;
        }
        if(!$prob)
        {
            $name1.=$text[$i];
        }
        else
        {
            $name2.=$text[$i];
        }
    }
    switch ($type)
    {
        case 0:return $name1;
        case 1:return $name2;
    }
}

function htmlFilter($text)
{
    $tmp = "";
    $qo = false;
    for($i=0;$i<strlen($text)-2;$i++)
    {
        if(!$qo)
        {
            if($text[$i] == '&' && $text[$i+1] == 'l' && $text[$i+2] == 't')
            {
                $qo = true;
            }
        }
        else
        {
            if($text[$i] == '&' && $text[$i+1] == 'g' && $text[$i+2] == 't')
            {
                $qo = false;
                $i = $i+3;
                continue;
            }
        }
        if(!$qo)
        {
            $tmp .= $text[$i];
        }
    }
    return $tmp;
}


function rightProduct($chat_id,$mid,$db)
{
    $t = 0;
    $numrows = 0;
    $nav_get = mysqli_query($db,"SELECT * FROM bot_nav WHERE chat_id=$chat_id");
    $row = mysqli_fetch_array($nav_get,MYSQLI_ASSOC);
    $prod_id = $row['product_id'];
    $categ_id = $row['category_id'];
    if($prod_id != 0)
    {

        $site_url = "https://your_market_site/";
        $fnd = false;
        $db_get = mysqli_query($db,"SELECT * FROM oc_product p LEFT JOIN oc_product_description pd ON (p.product_id = pd.product_id) LEFT JOIN oc_product_to_category ptc ON (p.product_id = ptc.product_id) WHERE pd.language_id=1 and ptc.category_id=$categ_id and p.type=(SELECT type FROM bot_nav WHERE chat_id=$chat_id)");
        $numrows = mysqli_num_rows($db_get);
        if($numrows > 0)
        {
            while ($row = mysqli_fetch_array($db_get, MYSQLI_ASSOC))
            {
                $t++;
                if($fnd)
                {
                    $prod_id = $row['product_id'];
					$spec = mysqli_query($db,"SELECT * FROM oc_product_special WHERE product_id=$prod_id");
					if (mysqli_num_rows($spec)>0){
					foreach ($spec as $sp )
					$row['price'] = '** '.$sp['price'];
					}
                    if($t == $numrows)
                    {
                        $desc = htmlFilter($row['description']);
                        $name = strtoupper($row['name']);
						
                        bot('editMessageText',[
                            'message_id'=>$mid,
                            'chat_id'=>$chat_id,
                            'parse_mode'=>'HTML',
                            'text'=>"<b>".$name."</b>"
                                ."\r\n<i>ЦЕНА:</i> <b>".$row['price']."</b>\r\n"
                                .$desc
                                ."\r\n".$site_url."image/".imageUrlFilter($row['image']),
                            'reply_markup'=>json_encode([
                                'inline_keyboard'=>[
                                    [['text'=>'В корзину', 'callback_data'=>'+'.$row['product_id']]],
                                    [['text'=>'◀️Пред.','callback_data'=>'left']]
                                ]
                            ])
                        ]);
                    }
                    else
                    {
                        $desc = htmlFilter($row['description']);
                        $name = strtoupper($row['name']);
                        bot('editMessageText',[
                            'message_id'=>$mid,
                            'chat_id'=>$chat_id,
                            'parse_mode'=>'HTML',
                            'text'=>"<b>".$name."</b>"
                                ."\r\n<i>ЦЕНА:</i> <b>".$row['price']."</b>\r\n"
                                .$desc
                                ."\r\n".$site_url."image/".imageUrlFilter($row['image']),
                            'reply_markup'=>json_encode([
                                'inline_keyboard'=>[
                                    [['text'=>'В корзину', 'callback_data'=>'+'.$row['product_id']]],
                                    [['text'=>'◀️Пред.','callback_data'=>'left'],['text'=>'След.▶️','callback_data'=>'right']]
                                ]
                            ])
                        ]);
                    }

                    $upd = mysqli_query($db,"UPDATE bot_nav SET product_id=$prod_id, category_id=$categ_id WHERE chat_id=$chat_id");
                    if(!$upd)
                    {
                        sendSimpleMessage($chat_id,"Ошибка создание точки перехода");
                    }

                    break;
                }
                else
                {
                    if($prod_id == $row['product_id'])
                    {
                        $fnd = true;
                    }
                }
            }
        }
        else
        {
            sendSimpleMessage($chat_id,'Товаров нет');
        }

    }
    else
    {
        sendSimpleMessage($chat_id,"Ошибка перехода");
    }

}

function leftProduct($chat_id,$mid,$db)
{
    $t = 0;
    $numrows = 0;
    $nav_get = mysqli_query($db,"SELECT * FROM bot_nav WHERE chat_id=$chat_id");
    $row = mysqli_fetch_array($nav_get,MYSQLI_ASSOC);
    $lprod_id = $row['product_id'];
    $prod_id = $row['product_id'];
    $categ_id = $row['category_id'];
    $lprice = 0.00;
    $limage = "";
    if($prod_id != 0)
    {
        $ldesc = "";
        $lname = "";
        $site_url = "https://your_market_site/";
        $fnd = false;
        $db_get = mysqli_query($db,"SELECT * FROM oc_product p LEFT JOIN oc_product_description pd ON (p.product_id = pd.product_id) LEFT JOIN oc_product_to_category ptc ON (p.product_id = ptc.product_id) WHERE pd.language_id=1 and ptc.category_id=$categ_id and p.type=(SELECT type FROM bot_nav WHERE chat_id=$chat_id)");
        $numrows = mysqli_num_rows($db_get);
        if($numrows > 0)
        {
            while ($row = mysqli_fetch_array($db_get, MYSQLI_ASSOC))
            {
                $t++;

                if($t == 2)
                {
                    if($prod_id == $row['product_id'])
                    {
                        $desc = $ldesc;
                        $name = $lname;
                        bot('editMessageText',[
                            'message_id'=>$mid,
                            'chat_id'=>$chat_id,
                            'parse_mode'=>'HTML',
                            'text'=>"<b>".$name."</b>"
                                ."\r\n<i>ЦЕНА:</i> <b>".$lprice."</b>\r\n"
                                .$desc
                                ."\r\n".$site_url."image/".$limage,
                            'reply_markup'=>json_encode([
                                'inline_keyboard'=>[
                                    [['text'=>'В корзину', 'callback_data'=>'+'.$lprod_id]],
                                    [['text'=>'След.▶️','callback_data'=>'right']]
                                ]
                            ])
                        ]);

                        $upd = mysqli_query($db,"UPDATE bot_nav SET product_id=$lprod_id, category_id=$categ_id WHERE chat_id=$chat_id");
                        if(!$upd)
                        {
                            sendSimpleMessage($chat_id,"Ошибка создание точки перехода");
                        }
                        break;
                    }
                }


                if($prod_id == $row['product_id'])
                {
                    $desc = $ldesc;
                    $name = $lname;
                    bot('editMessageText',[
                        'message_id'=>$mid,
                        'chat_id'=>$chat_id,
                        'parse_mode'=>'HTML',
                        'text'=>"<b>".$name."</b>"
                            ."\r\n<i>ЦЕНА:</i> <b>".$lprice."</b>\r\n"
                            .$desc
                            ."\r\n".$site_url."image/".$limage,
                        'reply_markup'=>json_encode([
                            'inline_keyboard'=>[
                                [['text'=>'В корзину', 'callback_data'=>'+'.$lprod_id]],
                                [['text'=>'◀️Пред.','callback_data'=>'left'],['text'=>'След.▶️','callback_data'=>'right']]
                            ]
                        ])
                    ]);

                    $upd = mysqli_query($db,"UPDATE bot_nav SET product_id=$lprod_id, category_id=$categ_id WHERE chat_id=$chat_id");
                    if(!$upd)
                    {
                        sendSimpleMessage($chat_id,"Ошибка создание точки перехода");
                    }
                    break;
                }
                $lprod_id = $row['product_id'];
                $ldesc = htmlFilter($row['description']);
                $lname = strtoupper($row['name']);
                $limage = imageUrlFilter($row['image']);
                $lprice = $row['price'];
            }
        }
        else
        {
            sendSimpleMessage($chat_id,'Товаров нет');
        }

    }
    else
    {
        sendSimpleMessage($chat_id,"Ошибка перехода");
    }
}

function getNavType($chat_id,$db)
{
    $get_db = mysqli_query($db,"SELECT * FROM bot_nav WHERE chat_id=$chat_id");
    $row = mysqli_fetch_array($get_db,MYSQLI_ASSOC);
    return $row['type'];
}

function setNavType($chat_id,$db,$type)
{
    $upd = mysqli_query($db,"UPDATE bot_nav SET type=$type WHERE chat_id=$chat_id");
    if(!$upd)
    {
        sendSimpleMessage($chat_id,"Ошибка изменение типа");
    }
}

//// Asosiy controller
function sendSimpleMessage($chat_id,$text)
{
    bot('sendMessage',[
        'chat_id'=>$chat_id,
        'text'=>$text
    ]);
}

function getKeyboard($chat_id)
{
    bot('sendMessage',[
        'chat_id'=>$chat_id,
        'text'=>'Добро пожаловать',
        'reply_markup'=>json_encode([
            'resize_keyboard'=>true,
            'keyboard'=>[
                [['text'=>'Категории'],['text'=>'Корзина']],
                [['text'=>'Главная'],['text'=>'Обратная связь']]
            ]
        ])
    ]);
}

function getTypeKeyboard($chat_id)
{
    bot('sendMessage',[
        'chat_id'=>$chat_id,
        'text'=>'Добро пожаловать',
        'reply_markup'=>json_encode([
            'resize_keyboard'=>true,
            'keyboard'=>[
                [['text'=>'Онлайн магазин'],['text'=>'Доставка товаров']],
                [['text'=>'Обратная связь']]
            ]
        ])
    ]);
}


function getStep($chat_id,$db)
{
    $numrows = 0;
    $get_db = mysqli_query($db,"SELECT * FROM bot_nav WHERE chat_id='$chat_id'");
    $numrows = mysqli_num_rows($get_db);
    if($numrows > 0)
    {
        $row = mysqli_fetch_array($get_db,MYSQLI_ASSOC);
        return $row['step_level'];
    }
    else
    {
        $ins = mysqli_query($db,"INSERT bot_nav SET chat_id='$chat_id', step_level=1");
        if(!$ins)
        {
            sendSimpleMessage($chat_id,"Ошибка навигации инсерт");
            return 0;
        }
        return 1;
    }
}

function setStep($step,$chat_id,$db)
{
    $numrows = 0;
    $get_db = mysqli_query($db,"SELECT * FROM bot_nav WHERE chat_id='$chat_id'");
    $numrows = mysqli_num_rows($get_db);
    if($numrows > 0)
    {
        $upd = mysqli_query($db, "UPDATE bot_nav SET step_level='$step' WHERE chat_id='$chat_id'");
        if(!$upd)
        {
            sendSimpleMessage($chat_id,"Ошибка навигации апдейт");
        }
    }
    else
    {
        $ins = mysqli_query($db,"INSERT bot_nav SET chat_id='$chat_id', step_level='$step'");
        if(!$ins)
        {
            sendSimpleMessage($chat_id,"Ошибка навигации инсерт");
        }
    }
}

function getZone($chat_id,$db)
{
    $numrows = 0;
    $db_get = mysqli_query($db,"SELECT * FROM oc_zone WHERE country_id=226");
    $numrows = mysqli_num_rows($db_get);
    $array = [];
    if($numrows)
    {
        $t=0;
        while($row = mysqli_fetch_array($db_get,MYSQLI_ASSOC))
        {
            $array[$t/3][$t%3]=array('text'=>$row['name'],'callback_data'=>'z'.$row['zone_id']);
            $t++;
        }
        $rmikb = array('inline_keyboard' => $array);
        bot('sendMessage',[
            'chat_id'=>$chat_id,
            'text'=>'Выберите область:',
            'reply_markup'=>json_encode($rmikb)
        ]);
    }
    else
    {
        sendSimpleMessage($chat_id, "Нет городов");
    }
}

function setZone($data,$chat_id,$db)
{
    $get_db = mysqli_query($db,"SELECT name FROM oc_zone WHERE zone_id=$data");
    $row = mysqli_fetch_array($get_db,MYSQLI_ASSOC);
    $zone_name = textFilter($row['name']);
    $upd = mysqli_query($db,"UPDATE oc_order SET payment_zone='$zone_name', payment_zone_id=$data WHERE fax='$chat_id' and ip=0");
    if($upd)
    {
        return true;
    }
    else
    {
        return false;
    }
}

function getSamZone($chat_id,$db)
{
    sendSimpleMessage($chat_id,"Выбран город Самарканд");
    return "z3714";
}

//// Kategoriyalar
function getCategories($chat_id,$db)
{
    $numrows = 1;
    $db_get = mysqli_query($db,"SELECT * FROM oc_category c LEFT JOIN oc_category_description cd ON (c.category_id = cd.category_id) WHERE cd.language_id=1 and top=1 and c.type=(SELECT type FROM bot_nav WHERE chat_id=$chat_id)");
    $numrows = mysqli_num_rows($db_get);
    $array =[];
    if($numrows > 0)
    {
        $t=0;
        while ($row = mysqli_fetch_array($db_get, MYSQLI_ASSOC)){
            $array[$t/1][$t%1]=array('text'=>$row['name'],'callback_data'=>'s'.$row['category_id']);
            $t++;
        }
        //$ikeyb = $array;
        $rmikb = array("inline_keyboard" => $array);
        bot('sendMessage',[
            'chat_id'=>$chat_id,
            'text'=>'Выберите категорию:',
            'reply_markup'=>json_encode($rmikb)
        ]);
    }
    else
    {
        sendSimpleMessage($chat_id,'Ошибка (404)');
    }
}
function getSubCategories($cat_id,$chat_id,$message_id,$db)
{
    $numrows = 1;
    $db_get = mysqli_query($db,"SELECT * FROM oc_category c LEFT JOIN oc_category_description cd ON (c.category_id = cd.category_id) WHERE cd.language_id=1 and top=0 and parent_id=$cat_id and c.type=(SELECT type FROM bot_nav WHERE chat_id=$chat_id)");
    $numrows = mysqli_num_rows($db_get);
    $array =[];
    if($numrows > 0)
    {
        $t=0;
        while ($row = mysqli_fetch_array($db_get, MYSQLI_ASSOC)){
            $array[$t/1][$t%1]=array('text'=>$row['name'],'callback_data'=>'s'.$row['category_id']);
            $t++;
        }
        //$ikeyb = $array;
        $rmikb = array("inline_keyboard" => $array);
        bot('editMessageText',[
            'message_id'=>$message_id,
            'chat_id'=>$chat_id,
            'text'=>'Выберите категорию:',
            'reply_markup'=>json_encode($rmikb)
        ]);
    }
}

function getProduct($cat_id,$chat_id,$db)
{
    $site_url = "https://your_market_site//";
    $numrows = 1;
    $db_get = mysqli_query($db,"SELECT * FROM oc_product p LEFT JOIN oc_product_description pd ON (p.product_id = pd.product_id) LEFT JOIN oc_product_to_category ptc ON (p.product_id = ptc.product_id) WHERE pd.language_id=1 and ptc.category_id=$cat_id and p.type=(SELECT type FROM bot_nav WHERE chat_id=$chat_id) LIMIT 1");
    $numrows = mysqli_num_rows($db_get);
    $array =[];
    if($numrows > 0)
    {
        $t=0;
        $prod_id = 0;
        while ($row = mysqli_fetch_array($db_get, MYSQLI_ASSOC)){

            $prod_id = $row['product_id'];
            $desc = htmlFilter($row['description']);
            $name = strtoupper($row['name']);
            bot('sendMessage',[
                'chat_id'=>$chat_id,
                'parse_mode'=>'HTML',
                'text'=>"<b>".$name."</b>"
                    ."\r\n<i>ЦЕНА:</i> <b>".$row['price']."</b>\r\n"
                    .$desc
                    ."\r\n".$site_url."image/".imageUrlFilter($row['image']),
                'reply_markup'=>json_encode([
                    'inline_keyboard'=>[
                        [['text'=>'В корзину', 'callback_data'=>'+'.$prod_id]],
                        [['text'=>'След.▶️','callback_data'=>'right']]
                    ]
                ])
            ]);
            $t++;
        }

        $upd = mysqli_query($db,"UPDATE bot_nav SET product_id=$prod_id, category_id=$cat_id WHERE chat_id=$chat_id");
        if(!$upd)
        {
            sendSimpleMessage($chat_id,"Ошибка создание точки перехода");
        }
    }
    else
    {
        sendSimpleMessage($chat_id,'Товаров нет');
    }
}


function createCache($chat_id,$db)
{
    $db_ins = mysqli_query($db,"INSERT bot_cache SET chat_id=$chat_id");
    if(!$db_ins)
        sendSimpleMessage($chat_id,"Кеш create error");
}

//function setCache($chat_id,$db,$data)

function getCache($chat_id,$db,$step)
{
    $data = "Null";
    $tr = "";
    switch ($step)
    {
        case 9:$data="name";$tr = "⬇️";break;
        //case 11:$data="lastname";break;
        case 10:$data="email";$tr = "Email:";break;
        case 12:$data="phone";$tr = "Телефон:";break;
        case 14:$data="address";$tr = "Адрес:";break;
    }
    if($data=="Null")
        sendSimpleMessage($chat_id,"Error getCache");

    $db_get = mysqli_query($db,"SELECT * FROM bot_cache WHERE chat_id=$chat_id");
    $numrows = mysqli_num_rows($db_get);
    if($numrows>0)
    {
        $row = mysqli_fetch_array($db_get);
        $text = $row[$data];
        bot('sendMessage',[
            'chat_id'=>$chat_id,
            'text'=>$tr,
            'reply_markup'=>json_encode([
                'resize_keyboard'=>true,
                'keyboard'=>[
                    [['text'=>$text]]
                ]
            ])
        ]);
    }
    else
    {
        //sendSimpleMessage($chat_id,"Cache null");
        createCache($chat_id,$db);
    }
}

function toCart($prod_id,$chat_id,$db)
{
    $numrows = 1;
    $db_get = mysqli_query($db,"SELECT * FROM bot_cart WHERE chat_id='$chat_id' and product_id='$prod_id'");
    $numrows = mysqli_num_rows($db_get);
    if($numrows > 0)
    {
        $cart_id = "";
        $quantity = 0;
        while ($row = mysqli_fetch_array($db_get, MYSQLI_ASSOC)){
            $cart_id = $row['cart_id'];
            $quantity = $row['quantity_cart'];
        }
        $quantity ++;
        $upd = mysqli_query($db,"UPDATE bot_cart SET quantity_cart=$quantity WHERE cart_id=$cart_id");
        if($upd)
        {
            sendSimpleMessage($chat_id,"Товар в корзине ($quantity)");
        }
        else
        {
            sendSimpleMessage($chat_id,"Ошибка");
        }
    }
    else
    {
        $ins = mysqli_query($db,"INSERT bot_cart SET chat_id='$chat_id', product_id='$prod_id', quantity_cart=1");
        if($ins)
        {
            sendSimpleMessage($chat_id,"Товар в корзине");
        }
        else
        {
            sendSimpleMessage($chat_id,"Ошибка");
        }
    }
}

function getCart($chat_id,$db)
{
    $numrows = 0;
    $get_db = mysqli_query($db,"SELECT * FROM bot_cart bc LEFT JOIN oc_product_description pd ON (bc.product_id = pd.product_id) LEFT JOIN oc_product op ON (bc.product_id=op.product_id) WHERE bc.chat_id='$chat_id' AND pd.language_id=1");
    $numrows = mysqli_num_rows($get_db);
    if($numrows)
    {
        $t = 0;
        //$tmp = "В корзине:\r\n";
        while ($row = mysqli_fetch_array($get_db, MYSQLI_ASSOC)){
            $t++;
            //$tmp .= $row['quantity_cart']." ".$row['name']." - ".$row['price']."\r\n";
            $products[$t][0] = $row['name'];
            $products[$t][1] = $row['quantity_cart'];
            $products[$t][2] = $row['price'];

        }
        $tmp = "В корзине:\r\n";
        $tot = 0;
        for($i=1;$i<=$t;$i++)
        {
            $tot = $products[$i][1]*$products[$i][2];
            $tmp .= $products[$i][1]."x"." ".$products[$i][0]." - ".$tot." UZS\r\n";
        }

        bot('sendMessage',[
            'chat_id'=>$chat_id,
            'parse_mode'=>'HTML',
            'text'=>$tmp,
            'reply_markup'=>json_encode([
                'inline_keyboard'=>[
                    [['text'=>'Оформить заказ', 'callback_data'=>'='.$chat_id]],
                    [['text'=>'Отменить', 'callback_data'=>'-'.$chat_id]]
                ]
            ])
        ]);
    }
    else
    {
        sendSimpleMessage($chat_id,"Корзина пуста");
    }
}

function clearCart($chat_id,$db)
{
    $clear_db = mysqli_query($db,"DELETE FROM bot_cart WHERE chat_id=$chat_id");
    if($clear_db)
    {
        sendSimpleMessage($chat_id,"Корзина очищена");
    }
    else
    {
        sendSimpleMessage($chat_id,"Ошибка");
    }
}

function endOrder($chat_id,$db)
{
    $get_order_id = mysqli_query($db,"SELECT order_id FROM oc_order WHERE  fax='$chat_id' and ip='0'");
    $row = mysqli_fetch_array($get_order_id,MYSQLI_ASSOC);
    $order_id = $row['order_id'];


    /// oc_order_product
    $numrows2 = 0;
    $get_db = mysqli_query($db,"SELECT * FROM bot_cart bc LEFT JOIN oc_product op ON(bc.product_id=op.product_id) LEFT JOIN oc_product_description opd ON(bc.product_id=opd.product_id) WHERE bc.chat_id=$chat_id and opd.language_id=1");
    $numrows2 = mysqli_num_rows($get_db);
    if($numrows2 > 0)
    {
        $p_id = [];
        $name = [];
        $model = [];
        $quantity = [];
        $price = [];
        $total = [];
        $order_total_total = 0;
        $i=0;
        while($row = mysqli_fetch_array($get_db))
        {
            $p_id[$i] = $row['product_id'];
            $name[$i] = textFilter($row['name']);
            $model[$i] = textFilter($row['model']);
            $quantity[$i] = $row[3];
            $price[$i] = $row['price'];
            $total[$i] = $price[$i]*$quantity[$i];
            $order_total_total += $total[$i];
            $i++;
        }
        for($j=0;$j<$i;$j++)
        {
            $ins = mysqli_query($db,"INSERT oc_order_product SET order_id=$order_id, 
            product_id=$p_id[$j],
            name='$name[$j]',
            model='$model[$j]',
            quantity=$quantity[$j],
            price=$price[$j],
            total=$total[$j]");
            if(!$ins)
            {
                $error = mysqli_error($db);
                sendSimpleMessage($chat_id,"Ошибка с товаро $error $name[$j]");
            }
        }
    }
    else
    {
        sendSimpleMessage($chat_id,"Товаров нет");
    }

    //oc_order_history
    $ins = mysqli_query($db,"INSERT oc_order_history SET order_id=$order_id, order_status_id=1, date_added=NOW()");
    if(!$ins)
    {
        sendSimpleMessage($chat_id,"Ошибка статус ид");
    }

    /// oc_order_total

    $upd = mysqli_query($db,"INSERT oc_order_total SET order_id=$order_id, code='sub_total', title='Сумма', value=$order_total_total, sort_order=1");
    if(!$upd)
    {
        sendSimpleMessage($chat_id,"Ошибка тотал");
    }

    $upd = mysqli_query($db,"INSERT oc_order_total SET order_id=$order_id, code='shipping', title='Бесплатная доставка', value=0, sort_order=3");
    if(!$upd)
    {
        sendSimpleMessage($chat_id,"Ошибка тотал");
    }

    $upd = mysqli_query($db,"INSERT oc_order_total SET order_id=$order_id, code='total', title='Итого', value=$order_total_total, sort_order=9");
    if(!$upd)
    {
        sendSimpleMessage($chat_id,"Ошибка тотал");
    }

    /// bot_cache
    $selp = mysqli_query($db,"SELECT * FROM bot_cache WHERE chat_id=$chat_id");
    $numrows3 = mysqli_num_rows($selp);
    if($numrows3>0)
    {
        $upd = mysqli_query($db,"UPDATE bot_cache bc LEFT JOIN oc_order oo ON(bc.chat_id=oo.fax) SET 
bc.name=CONCAT(oo.firstname,' ',oo.lastname), 
bc.phone=oo.telephone,
 bc.email=oo.email, 
 bc.address=oo.payment_address_1 WHERE oo.fax='$chat_id' and oo.ip='0'");
        if(!$upd)
        {
            sendSimpleMessage($chat_id,"Error bot_cache set");
        }
    }
    else
    {
        sendSimpleMessage($chat_id,"bot_cache error");
    }


    /// oc_order
    $upd = mysqli_query($db,"UPDATE oc_order SET payment_country='Uzbekistan', payment_country_id=226, 
    payment_firstname=firstname,
    payment_lastname=lastname, 
    shipping_firstname=firstname,
    shipping_lastname=lastname,
    shipping_address_1=payment_address_1,
    shipping_country=payment_country,
    shipping_country_id=payment_country_id,
    shipping_zone=payment_zone,
    shipping_zone_id=payment_zone_id,
    invoice_prefix='INV-2013-00',
    store_name='Market_name',
    store_url='https://t.me/your_market_tg',
    customer_group_id=1,
    custom_field='[]',
    payment_address_format='{firstname} {lastname}
{company}
{address_1}
{address_2}
{city} {postcode}
{zone}
{country}',
    payment_custom_field='[]',
    payment_method='Оплата при доставке',
    payment_code='cod',
    payment_postcode='123456',
    shipping_address_format='{firstname} {lastname}
{company}
{address_1}
{address_2}
{city} {postcode}
{zone}
{country}',
    shipping_custom_field='[]',
    shipping_postcode='123456',
    payment_method='Бесплатная доставка',
    payment_code='free.free',
    currency_id=4,
    currency_code='UZS',
    total=$order_total_total,
    language_id=1,
    order_status_id=1,
    date_added=NOW(),
    date_modified=NOW(),
    ip='1' WHERE fax='$chat_id' and ip='0'");
    if(!$upd)
    {
        sendSimpleMessage($chat_id,"Ошибка Базы данных 1");
        //return;
    }

    setStep(1,$chat_id,$db);
    sendSimpleMessage($chat_id,"Заказ принят");
    clearCart($chat_id,$db);
    getKeyboard($chat_id,$db);
}

function beginOrder($text,$step,$chat_id,$db)
{
    if($step == 10)
    {
        $text1 = nameFilter($text,0);
        $text2 = nameFilter($text,1);
        $ins = mysqli_query($db,"INSERT oc_order SET firstname='$text1', lastname='$text2', fax='$chat_id',ip='0'");
        if($ins)
        {
//            sendSimpleMessage($chat_id,"Фамилия:");
//            setStep(11,$chat_id,$db);
            //sendSimpleMessage($chat_id,"Email:");
            getCache($chat_id,$db,$step);
            setStep(12,$chat_id,$db);
        }
        else
        {
            sendSimpleMessage($chat_id,"Ошибка имени");
        }
    }
    else
    {
        if($step == 11)
        {
            $upd = mysqli_query($db,"UPDATE oc_order SET lastname='$text' WHERE fax='$chat_id' and ip='0'");
            if($upd)
            {
                sendSimpleMessage($chat_id,"Email:");
                setStep(12,$chat_id,$db);
            }
            else
            {
                sendSimpleMessage($chat_id,"Ошибка фамилии");
            }
        }
        else
        {
            if($step == 12)
            {
                $upd = mysqli_query($db,"UPDATE oc_order SET email='$text' WHERE fax='$chat_id' and ip='0'");
                if($upd)
                {
                    //sendSimpleMessage($chat_id,"Телефон:");
                    getCache($chat_id,$db,$step);
                    setStep(13,$chat_id,$db);
                }
                else
                {
                    sendSimpleMessage($chat_id,"Ошибка маил");
                }
            }
            else
            {
                if($step == 13)
                {
                    $upd = mysqli_query($db,"UPDATE oc_order SET telephone='$text' WHERE fax='$chat_id' and ip='0'");
                    if($upd)
                    {
                        //getZone($chat_id,$db);
                        //getSamZone($chat_id,$db);
                        setStep(14,$chat_id,$db);

                    }
                    else
                    {
                        sendSimpleMessage($chat_id,"Ошибка телефона");
                    }
                }
                else
                {
                    if($step == 14)
                    {
                        if(setZone($text,$chat_id,$db))
                        {
                            //sendSimpleMessage($chat_id,"Адрес:");
                            getCache($chat_id,$db,$step);
                            setStep(15,$chat_id,$db);
                        }
                        else
                        {
                            sendSimpleMessage($chat_id,"Ошибка города");
                        }
                    }
                    else
                    {
                        if($step == 15)
                        {
                            $upd = mysqli_query($db,"UPDATE oc_order SET payment_address_1='$text' WHERE fax='$chat_id' and ip='0'");
                            if($upd)
                            {
                                sendSimpleMessage($chat_id,"Комментария:");
                                setStep(16,$chat_id,$db);
                            }
                            else
                            {
                                sendSimpleMessage($chat_id,"Ошибка адреса");
                            }
                        }
                        else
                        {
                            if($step == 16)
                            {
                                $upd = mysqli_query($db,"UPDATE oc_order SET comment='$text' WHERE fax='$chat_id' and ip='0'");
                                if($upd)
                                {
                                    sendSimpleMessage($chat_id,"В обработке...");
                                    setStep(17,$chat_id,$db);
                                    endOrder($chat_id,$db);
                                }
                                else
                                {
                                    sendSimpleMessage($chat_id,"Ошибка коммента");
                                }
                            }
                            else
                            {
                                sendSimpleMessage($chat_id,"Ошибка оформления");
                            }
                        }
                    }
                }
            }

        }
    }
}
?>