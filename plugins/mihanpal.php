<?php
/*
  Virtual Freer
  http://freer.ir/virtual

  Copyright (c) 2011 Mohammad Hossein Beyram, freer.ir

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License v3 (http://www.gnu.org/licenses/gpl-3.0.html)
  as published by the Free Software Foundation.
*/
	//-- اطلاعات کلی پلاگین
	$pluginData[mihanpal][type] = 'payment';
	$pluginData[mihanpal][name] = 'میهن پال';
	$pluginData[mihanpal][uniq] = 'mihanpal';
	$pluginData[mihanpal][description] = 'مخصوص پرداخت با دروازه پرداخت <a href="http://mihanpal.com">میهن پال</a>';
	$pluginData[mihanpal][author][name] = 'MihanPal';
	$pluginData[mihanpal][author][url] = 'http://mihanpal.com';
	$pluginData[mihanpal][author][email] = 'info@mihanpal.com';
	
	//-- فیلدهای تنظیمات پلاگین
	$pluginData[mihanpal][field][config][1][title] = 'پین';
	$pluginData[mihanpal][field][config][1][name] = 'merchant';
	$pluginData[mihanpal][field][config][2][title] = 'عنوان خرید';
	$pluginData[mihanpal][field][config][2][name] = 'title';
	
	//-- تابع انتقال به دروازه پرداخت
	function gateway__mihanpal($data)
	{
		global $config,$db,$smarty;
		include_once('include/libs/nusoap.php');
		$merchantID 	= trim($data[merchant]);
		$amount 		= round($data[amount]/10);
		$invoice_id		= $data[invoice_id];
		$callBackUrl 	= $data[callback];
		
		$client = new nusoap_client('http://mihanpal.com/index.php/payment/wsdl', 'wsdl');
		$res = $client->call('request', array($merchantID, $amount, $callBackUrl,$invoice_id, urlencode($data[title])));
		if ($res > 0)
		{
			$update[payment_rand]		= $res;
			$sql = $db->queryUpdate('payment', $update, 'WHERE `payment_rand` = "'.$invoice_id.'" LIMIT 1;');
			$db->execute($sql);
			header('location: http://mihanpal.com/index.php/paymentgateway/?au='.$res);
			exit;
		}
		else
		{
			$data[title] = 'خطای سیستمي';
			$data[message] = '<font color="red">خطا در اتصال به میهن پال</font>'.$res.'<br /><a href="index.php" class="button">بازگشت</a>';
			$query	= 'SELECT * FROM `config` WHERE `config_id` = "1" LIMIT 1';
			$conf	= $db->fetch($query);
			$smarty->assign('config', $conf);
			$smarty->assign('data', $data);
			$smarty->display('message.tpl');
		}
	}
	
	//-- تابع بررسی وضعیت پرداخت
	function callback__mihanpal($data)
	{
		global $db,$get;
		$au 	= $get['au'];
		$ref_id = $get['order_id'];
		if (strlen($au)>8)
		{
			include_once('include/libs/nusoap.php');
			$merchantID = $data[merchant];
			$sql 		= 'SELECT * FROM `payment` WHERE `payment_rand` = "'.$au.'" LIMIT 1;';
			$payment 	= $db->fetch($sql);
			
			$amount		= round($payment[payment_amount]/10);
			$client = new nusoap_client('http://mihanpal.com/index.php/payment/wsdl', 'wsdl');
			$res = $client->call("verify", array($merchantID, $au, $amount));
			if ($payment[payment_status] == 1)
			{
				if ($res == 1)//-- موفقیت آمیز
				{
					//-- آماده کردن خروجی
					$output[status]		= 1;
					$output[res_num]	= $au;
					$output[ref_num]	= $ref_id;
					$output[payment_id]	= $payment[payment_id];
				}
				else
				{
					//-- در تایید پرداخت مشکلی به‌وجود آمده است‌
					$output[status]	= 0;
					$output[message]= 'پرداخت توسط میهن پال انجام نشده است .';
				}
			}
			else
			{
				//-- قبلا پرداخت شده است‌
				$output[status]	= 0;
				$output[message]= 'سفارش قبلا پرداخت شده است.';
			}
		}
		else
		{
				//-- شماره یکتا اشتباه است
				$output[status]	= 0;
				$output[message]= 'شماره یکتا اشتباه است.';
		}
		return $output;
	}