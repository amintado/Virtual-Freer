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
	$pluginData[freersms][type] = 'notify';
	$pluginData[freersms][name] = 'پیامک محصول توسط Freer SMS';
	$pluginData[freersms][uniq] = 'freersms';
	$pluginData[freersms][description] = 'ارسال اطلاعات خرید به موبایل کاربر با پنل رایگان';
	$pluginData[freersms][note] = 'برای تهیه رایگان پنل و ارسال پیامک به وب سایت <a href="http://sms.freer.ir" style="color:#FF7200">Freer SMS</a> مراجعه کنید.';
	$pluginData[freersms][author][name] = 'Freer';
	$pluginData[freersms][author][url] = 'http://sms.freer.ir';
	$pluginData[freersms][author][email] = 'sms@freer.ir';
	
	//-- فیلدهای تنظیمات پلاگین
	$pluginData[freersms][field][config][1][title] = 'شماره ارسال';
	$pluginData[freersms][field][config][1][name] = 'sender_number';
	$pluginData[freersms][field][config][2][title] = 'نام کاربری وب سرویس';
	$pluginData[freersms][field][config][2][name] = 'username';
	$pluginData[freersms][field][config][3][title] = 'کلمه عبور وب سرویس';
	$pluginData[freersms][field][config][3][name] = 'password';
	
	//-- تابع پردازش و ارسال اطلاعات
	function notify__freersms($data,$output,$payment,$product,$cards)
	{
		global $db,$smarty;
		if ($output[status] == 1 AND $payment[payment_mobile] AND $cards)
		{
			include_once('include/libs/nusoap.php');
			$sms_text='';
			foreach($cards as $card)
			{
				$sms_text = 'نوع:' . $product[product_title] . "\r\n";
				if($product[product_first_field_title]!="")
					$sms_text .= $product[product_first_field_title] . ': ' . $card[card_first_field];
				if($card[card_second_field]!="")
					$sms_text .= "\r\n" . $product[product_second_field_title] . ': ' . $card[card_second_field];
				if($card[card_third_field]!="")
					$sms_text .=  "\r\n" . $product[product_third_field_title] . ': ' . $card[card_third_field];
                
				$client	= new nusoap_client('http://sms.freer.ir/gateway/index.php?wsdl', 'wsdl');
				$res	= $client->call('SendSMS',array($data[username], $data[password], $data[sender_number], $payment[payment_mobile], $sms_text));
                $sms_text='';
			}
		}
	}
	