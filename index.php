<?
/*
  Virtual Freer
  http://freer.ir/virtual

  Copyright (c) 2011 Mohammad Hossein Beyram, freer.ir

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License v3 (http://www.gnu.org/licenses/gpl-3.0.html)
  as published by the Free Software Foundation.
*/
//------------------ Load Configuration
include 'include/configuration.php';
//------------------ Start Smarty
include 'include/startSmarty.php';

	if($post['action'] != '' || $get['action'] != '') 
	{
		if($post['action'] == '')
		{
			$data[action] 	= $get['action'];
			$data[card]		= $get['card']; 
			$data[qty]		= $get['qty']; 
			$data[gateway]	= $get['gateway'];
			$data[email]	= $get['email'];
			$data[mobile]	= $get['mobile'];
			$noJavaScript 	= 1;
		} else {
			$data[action] 	= $post['action'];
			$data[card]		= $post['card'];
			$data[qty]		= $post['qty'];
			$data[gateway]	= $post['gateway'];
			$data[email]	= $post['email'];
			$data[mobile]	= $post['mobile'];
			$noJavaScript 	= 0;
		}
	}
	if ($data[action] == "payit")
	{
		if (!$data[card])
			$error	.= 'کارتی انتخاب نکرده‌اید.‌<br />';
		if (!$data[qty])
			$error	.= 'تعداد کارت درخواستی مشخص نشده است.‌<br />';
		if ($data[card] AND $data[qty])
		{
			$count_query	= 'SELECT COUNT(*) FROM `card` WHERE `card_product` = "'.$data[card].'" AND (`card_res_time` < "'.($now-(60*$config[card][reserveExpire])).'" OR `card_res_time` = "") AND `card_status` = "1" AND `card_show` = "1"';
			$count_card		= $db->fetch($count_query);
			$total_card		= $count_card['COUNT(*)'];
			if ($total_card < $data[qty])
				if ($total_card != 0)
					$error .= 'متاسفانه تعداد کارت درخواستی شما در حال حاضر موجود نمی‌باشد٬ شما الان می‌توانید حداکثر '.Convertnumber2farsi($total_card).' کارت از این نوع سفارش دهید.<br />';
				else
					$error .= 'متاسفانه کارت درخواستی شما در حال حاضر موجود نمی‌باشد.‌<br />';
		}
		if (!$data[gateway])
			$error	.= 'دروازه پرداخت را مشخص نکرده اید.‌<br />';
		
		$input_validate	= $db->retrieve('config_input_validate','config','config_id',1);
		if ($input_validate)
		{
			if (!$data[email] AND !$data[mobile])
				$error	.= 'برای استفاده از پشتیبانی سایت ایمیل یا شماره همراه خود را وارد کنید.‌<br />';
			if ($data[email] AND filter_var($data[email], FILTER_VALIDATE_EMAIL)== false)
				$error .= 'ایمیل وارد شده نامعتبر است.<br />';
			if ($data[mobile] AND !eregi("^09([0-9]{9})$", $data[mobile]))
				$error .= "شماره همراه نامعتبر است.<br />";
		}
		if($error)
			echo $error.'__2';
		else
		{
			$insert[payment_user]		= $request[PHPSESSID];
			$insert[payment_email]		= $data[email];
			$insert[payment_mobile]		= $data[mobile];
			$insert[payment_amount]		= $db->retrieve('product_price','product','product_id',$data[card])*$data[qty];
			$insert[payment_gateway]	= $data[gateway];
			$insert[payment_time]		= $now;
			$insert[payment_ip]			= $server[REMOTE_ADDR];
			
			$sql 						= $db->queryInsert('payment', $insert);
			$db->execute($sql);
			$payment_id 				= mysql_insert_id();
			
			$randlen					= 9-strlen($payment_id);
			$update[payment_rand]		= $payment_id.get_rand_id($randlen);
			$sql = $db->queryUpdate('payment', $update, 'WHERE `payment_id` = "'.$payment_id.'" LIMIT 1;');
			$db->execute($sql);
			$random						= $update[payment_rand];
			unset($update);
			
			$update[card_customer_email]	= $data[email];
			$update[card_customer_mobile]	= $data[mobile];
			$update[card_res_user]			= $request[PHPSESSID];
			$update[card_res_time]			= $now;
			$update[card_payment_id]		= $payment_id;
			$sql = $db->queryUpdate('card', $update, 'WHERE `card_product` = "'.$data[card].'" AND (`card_res_time` < "'.($now-(60*$config[card][reserveExpire])).'" OR `card_res_time` = "") AND `card_status` = "1" AND `card_show` = "1" LIMIT '.$data[qty].';');
			$db->execute($sql);
			
			echo 'gateway.php?random='.$random.'__1';
		}
		exit;
	}

	$query		= 'SELECT * FROM `category` WHERE `category_parent_id` = "0" ORDER BY `category_order`';
	$categories	= $db->fetchAll($query);
	if ($categories)
		foreach ($categories as $key => $category)
		{
			if ($categories[$key][category_image])
				$categories[$key][category_image] = $config[MainInfo][url].$config[MainInfo][upload][image].'resized/category_'.$category[category_image];
			$query		= 'SELECT * FROM `product` WHERE `product_category` = "'.$category[category_id].'" ORDER BY `product_id` ASC';
			$categories[$key][products]	= $db->fetchAll($query);
			if ($categories[$key][products])
				foreach ($categories[$key][products] as $product_key => $product)
				{
					$count_query	= 'SELECT COUNT(*) FROM `card` WHERE `card_product` = "'.$product[product_id].'" AND (`card_res_time` < "'.($now-(60*$config[card][reserveExpire])).'" OR `card_res_time` = "") AND `card_status` = "1" AND `card_show` = "1"';
					$count_card		= $db->fetch($count_query);
					$total_card		= $count_card['COUNT(*)'];
					$categories[$key][products][$product_key][counter] = $total_card;
				}
		}

	$query				= 'SELECT * FROM `plugin` WHERE `plugin_type` = "payment" AND `plugin_status` = "1"';
	$payment_methods	= $db->fetchAll($query);

	for ($i=0;$i<768;$i=$i+32)	{
		$banks_logo 	.= '<li style="background-position: -'.$i.'px 0px;"></li>';
	}

	//-- نمایش صفحه
	$query	= 'SELECT * FROM `config` WHERE `config_id` = "1" LIMIT 1';
	$config	= $db->fetch($query);

	$smarty->assign('config', $config);
	$smarty->assign('categories', $categories);
	$smarty->assign('products', $products);
	$smarty->assign('payment_methods', $payment_methods);
	$smarty->assign('banks_logo', $banks_logo);
	$smarty->display('index.tpl');
	exit;