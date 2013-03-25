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
	$pluginData[parsian][type] = 'payment';
	$pluginData[parsian][name] = 'درگاه بانک پارسیان';
	$pluginData[parsian][uniq] = 'parsian';
	$pluginData[parsian][description] = 'مخصوص پرداخت با دروازه پرداخت <a href="http://parsian-bank.com" target="_blank">بانک پارسیان‌</a>';
	$pluginData[parsian][author][name] = 'Freer';
	$pluginData[parsian][author][url] = 'http://freer.ir';
	$pluginData[parsian][author][email] = 'virtual@freer.ir';
	
	//-- فیلدهای تنظیمات پلاگین
	$pluginData[parsian][field][config][1][title] = 'پین کد';
	$pluginData[parsian][field][config][1][name] = 'pin';
	
	//-- تابع انتقال به دروازه پرداخت
	function gateway__parsian($data)
	{
		global $config,$smarty,$db;
		include_once('include/libs/nusoap.php');
		$authority 		= 0;	// مقدار پیشفرض
		$status 		= 1;	// وضعیت پیشفرض
		$callbackUrl	= $data[callback];
		$pin			= $data[pin];
		$amount 		= $data[amount];
		$order_id		= $data[invoice_id];
		//-- تبدیل اطلاعات به آرایه برای ارسال به بانک
	    $params = array(
			'pin' => $pin,
			'amount' => $amount,
			'orderId' => $order_id,
			'callbackUrl' => $callbackUrl,
			'authority' => $authority,
			'status' => $status
	    );
		$sendParams = array($params);
		$soapclient = new nusoap_client('https://www.pec24.com/pecpaymentgateway/eshopservice.asmx?wsdl', 'wsdl');
		$res 		= $soapclient->call('PinPaymentRequest', $sendParams);
		$authority 	= $res['authority'];
		$status 	= $res['status'];
		if ($authority AND $status==0)
		{
			$update[payment_rand]	= $authority;
			$sql = $db->queryUpdate('payment', $update, 'WHERE `payment_rand` = "'.$order_id.'" LIMIT 1;');
			$db->execute($sql);
			header('location:https://www.pec24.com/pecpaymentgateway/?au='.$authority);
			exit;
		}
		else
		{
			//-- نمایش خطا
			$data[title] = 'خطای سیستم';
			$data[message] = '<font color="red">در اتصال به درگاه بانک پارسیان مشکلی به وجود آمد٬ لطفا از درگاه سایر بانک‌ها استفاده نمایید.</font> شماره خطا: '.$status.'<br /><a href="index.php" class="button">بازگشت</a>';
			$query	= 'SELECT * FROM `config` WHERE `config_id` = "1" LIMIT 1';
			$conf	= $db->fetch($query);
			$smarty->assign('config', $conf);
			$smarty->assign('data', $data);
			$smarty->display('message.tpl');
			exit;
			
		}
	}
	
	//-- تابع بررسی وضعیت پرداخت
	function callback__parsian($data)
	{
		global $db,$get,$smarty;
		$authority = $get['au'];
		$status = $get['rs'];
		if(($status == 0) AND $authority)
		{
			$sql 		= 'SELECT * FROM `payment` WHERE `payment_rand` = "'.$authority.'" LIMIT 1;';
			$payment 	= $db->fetch($sql);
			if ($payment)
			{
				//-- یعنی کد درست وارد شده است و وجود دارد٬ حالا وضعیت سفارش چک شود در چه مرحله ای هست
				if ($payment[payment_status] == 1)//-- آماده پرداخت است
				{
					//-- چک شود که پرداخت مربوط به کد در چه حالیه
					include_once('include/libs/nusoap.php');
					$pin 		= $data[pin];
					$client 	= new nusoap_client('https://www.pec24.com/pecpaymentgateway/eshopservice.asmx?wsdl', 'wsdl');
					$status 	= 1;   // default status
					$params 	= array(
					        'pin' 		=> $pin ,
							'authority' => $authority,
					        'status' 	=> $status);
					$sendParams = array($params);
					$res = $client->call('PinPaymentEnquiry', $sendParams);
					$status = $res['status'];
					if ($status == '0')//-- پرداخت تایید شده
					{
						//-- آماده کردن خروجی
						$output[status]		= 1;
						$output[res_num]	= $authority;
						$output[payment_id]	= $payment[payment_id];
					}
					else
					{
						$output[status]	= 0;
						$output[message]= 'پرداخت تکمیل نشده است.';
					}
				}
				else
				{
					$output[status]	= 0;
					$output[message]= 'چنین سفارشی تعریف نشده است.';
				}
			}
			else
			{
				$output[status]	= 0;
				$output[message]= 'اطلاعات پرداخت کامل نیست.';
			}
		}
		else
		{
			$output[status]	= 0;
			$output[message]= 'اطلاعات پرداخت کامل نیست.';
		}
		return $output;
	}