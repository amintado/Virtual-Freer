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
	$pluginData[saman][type] = 'payment';
	$pluginData[saman][name] = 'بانک سامان';
	$pluginData[saman][uniq] = 'saman';
	$pluginData[saman][description] = 'مخصوص پرداخت با دروازه پرداخت <a href="http://sb24.com" target=_blank">بانک سامان‌</a>';
	$pluginData[saman][author][name] = 'Freer';
	$pluginData[saman][author][url] = 'http://freer.ir';
	$pluginData[saman][author][email] = 'hossin@gmail.com';
	
	//-- فیلدهای تنظیمات پلاگین
	$pluginData[saman][field][config][1][title] = 'کد پذیرنده';
	$pluginData[saman][field][config][1][name] = 'merchant';
	$pluginData[saman][field][config][2][title] = 'رمز پذیرنده';
	$pluginData[saman][field][config][2][name] = 'password';
	
	//-- تابع انتقال به دروازه پرداخت
	function gateway__saman($data)
	{
		global $db,$smarty;
		$query	= 'SELECT * FROM `config` WHERE `config_id` = "1" LIMIT 1';
		$conf	= $db->fetch($query);
		$smarty->assign('config', $conf);
		$smarty->assign('data', $data);
		$smarty->display('saman.tpl');
	}
	
	//-- تابع بررسی وضعیت پرداخت
	function callback__saman($data)
	{
		global $db,$post;
		$ResNum	= $post['ResNum'];
		$RefNum	= $post['RefNum'];
		$State	= $post['State'];
		if (isset($RefNum))
		{
			include_once('include/libs/nusoap.php');
			$merchantID = trim($data[merchant]);
			$password	= $data[password];
			$soapclient = new nusoap_client('https://acquirer.samanepay.com/payments/referencepayment.asmx?WSDL','wsdl');
			$soapProxy	= $soapclient->getProxy() ;
			$amount		= $soapProxy->VerifyTransaction($RefNum,$merchantID);
			//-- پرداخت کاملا موفق بوده
			if (($amount>0) AND ($State=='OK'))
			{
				//-- مبلغ پرداختی با مبلغ ارسالی باید چک شود
				$sql 		= 'SELECT * FROM `payment` WHERE `payment_rand` = "'.$ResNum.'" LIMIT 1;';
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
						$output[message]= 'مقدار پرداختی با مقدار ارسالی برابر نیست٬ مبلغ پرداختی به حساب شما بازگردانده خواهد شد.';
					}
				}
				else
				{
					//-- سفارش قبلا پرداخت شده است.
					$output[status]	= 0;
					$output[message]= 'این سفارش قبلا پرداخت شده است.';
				}
			}
			else
			{
				$output[status]	= 0;
				$output[message]= 'پرداخت تکمیل نشده است.'.$amount;
			}
		}
		else
		{
			//-- RefNum درست نیست.
			$output[status]	= 0;
			$output[message]= 'اطلاعات پرداخت کامل نیست.';
		}
		return $output;
	}
