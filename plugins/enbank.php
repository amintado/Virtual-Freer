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
	$pluginData[enbank][type] = 'payment';
	$pluginData[enbank][name] = 'بانک اقتصاد نوین';
	$pluginData[enbank][uniq] = 'enbank';
	$pluginData[enbank][description] = 'مخصوص پرداخت با دروازه پرداخت <a href="www.enbank.ir">بانک اقتصاد نوین‌</a>';
	$pluginData[enbank][author][name] = 'Merlin.McKeen';
	$pluginData[enbank][author][url] = 'http://novincharge.in';
	$pluginData[enbank][author][email] = 'merlin.mckeen@gmail.com';
	
	//-- فیلدهای تنظیمات پلاگین
	$pluginData[enbank][field][config][1][title] = 'مرچنت';
	$pluginData[enbank][field][config][1][name] = 'merchant';
	$pluginData[enbank][field][config][2][title] = 'کلمه عبور';
	$pluginData[enbank][field][config][2][name] = 'password';
	
	//-- تابع انتقال به دروازه پرداخت
	function gateway__enbank($data)
	{
		global $smarty;
		$smarty->assign('data', $data);
		$smarty->display('enbank.tpl');
	}
	
	//-- تابع بررسی وضعیت پرداخت
	function callback__enbank($data)
	{
		global $db,$post;
		$ResNum	= $post['ResNum'];
		$RefNum	= $post['RefNum'];
		$State	= $post['State'];
		if (isset($RefNum))
		{
			include_once('include/libs/nusoap.php');
			$merchantID = $data[merchant];
			$password	= $data[password];
			$soapclient = new nusoap_client('https://modern.enbank.net/ref-payment/ws/ReferencePayment?WSDL','wsdl');
			$soapProxy	= $soapclient->getProxy() ;
			$amount		= $soapProxy->VerifyTransaction($RefNum,$merchantID);
			//-- پرداخت کاملا موفق بوده
			if (($amount>0) AND ($State=='OK'))
			{
				//-- مبلغ پرداختی با مبلغ ارسالی باید چک شود
				$sql 		= "SELECT * FROM `payment` WHERE `payment_rand` = '$ResNum' LIMIT 1;";
				$payment 	= $db->fetch($sql);
				if ($payment[payment_status] == 1)
				{
					if($amount == $payment[payment_amount])
					{
						//-- آماده کردن خروجی
						$output[status]		= 1;
						$output[res_num]	= $ResNum;
						$output[ref_num]	= $RefNum;
						$output[payment_id]	= $payment[payment_id];
					}
					else
					{
						//-- مقدار پرداختی با مقدار ارسالی برابر نیست٬ برگشت زده شود و پیغام خطا دهد.
						$res			= $soapProxy->ReverseTransaction($RefNum,$merchantID,$password,$amount);
						$output[status]	= 0;
					}
				}
				else
				{
					//-- سفارش قبلا پرداخت شده است.
					$output[status]	= 0;
				}
			}
			else
			{
				$output[status]	= 0;
			}
		}
		else
		{
			//-- RefNum درست نیست.
			$output[status]	= 0;
		}
		return $output;
	}
