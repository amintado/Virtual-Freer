<?
/*
  Virtual Freer
  http://freer.ir/virtual

  Copyright (c) 2011 Mohammad Hossein Beyram, freer.ir

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License v3 (http://www.gnu.org/licenses/gpl-3.0.html)
  as published by the Free Software Foundation.
*/
	//-- اطلاعات کلی پلاگین
	$pluginData[zarinpal][type] = 'payment';
	$pluginData[zarinpal][name] = 'زرین پال';
	$pluginData[zarinpal][uniq] = 'zarinpal';
	$pluginData[zarinpal][description] = 'مخصوص پرداخت با دروازه پرداخت <a href="http://zarinpal.com">زرین‌پال‌</a>';
	$pluginData[zarinpal][author][name] = 'Freer';
	$pluginData[zarinpal][author][url] = 'http://freer.ir';
	$pluginData[zarinpal][author][email] = 'hossin@gmail.com';
	
	//-- فیلدهای تنظیمات پلاگین
	$pluginData[zarinpal][field][config][1][title] = 'مرچنت';
	$pluginData[zarinpal][field][config][1][name] = 'merchant';
	$pluginData[zarinpal][field][config][2][title] = 'عنوان خرید';
	$pluginData[zarinpal][field][config][2][name] = 'title';
	
	//-- تابع انتقال به دروازه پرداخت
	function gateway__zarinpal($data)
	{
		global $config,$db,$smarty;
		include_once('include/libs/nusoap.php');
		$merchantID 	= trim($data[merchant]);
		$amount 		= round($data[amount]/10);
		$invoice_id		= $data[invoice_id];
		$callBackUrl 	= $data[callback];
		
		$client = new nusoap_client('http://www.zarinpal.com/WebserviceGateway/wsdl', 'wsdl');
		$res = $client->call('PaymentRequest', array($merchantID, $amount, $callBackUrl, urlencode($data[title])));
		if ($res > 0)
		{
			$update[payment_rand]		= $res;
			$sql = $db->queryUpdate('payment', $update, 'WHERE `payment_rand` = "'.$invoice_id.'" LIMIT 1;');
			$db->execute($sql);
			header('location:https://www.zarinpal.com/users/pay_invoice/'.$res);
			exit;
		}
		else
		{
			$data[title] = 'خطای سیستم';
			$data[message] = '<font color="red">در اتصال به درگاه زرین‌پال مشکلی به وجود آمد٬ لطفا از درگاه سایر بانک‌ها استفاده نمایید.</font>'.$res.'<br /><a href="index.php" class="button">بازگشت</a>';
			$query	= 'SELECT * FROM `config` WHERE `config_id` = "1" LIMIT 1';
			$conf	= $db->fetch($query);
			$smarty->assign('config', $conf);
			$smarty->assign('data', $data);
			$smarty->display('message.tpl');
		}
	}
	
	//-- تابع بررسی وضعیت پرداخت
	function callback__zarinpal($data)
	{
		global $db,$get;
		$au 	= $get['au'];
		$ref_id = $get['refID'];
		if (strlen($au) == 36)
		{
			include_once('include/libs/nusoap.php');
			$merchantID = $data[merchant];
			$sql 		= 'SELECT * FROM `payment` WHERE `payment_rand` = "'.$au.'" LIMIT 1;';
			$payment 	= $db->fetch($sql);
			
			$amount		= round($payment[payment_amount]/10);
			$client = new nusoap_client('http://www.zarinpal.com/WebserviceGateway/wsdl', 'wsdl');
			$res = $client->call("PaymentVerification", array($merchantID, $au, $amount));
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
					$output[message]= 'پرداخت توسط زرین‌پال تایید نشد‌.';
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