<?
/*
  Hezarnevis
  http://www.Hezarnevis.com

  Copyright (c) 2011 S.M.B Productions, www.hezarnevis.com
  
  SMS Plugin for http://freer.ir/virtual, Copyright (c) 2012 , freer.ir

  The virtual_freer is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License v3 (http://www.gnu.org/licenses/gpl-3.0.html)
  as published by the Free Software Foundation.
*/
	//-- اطلاعات کلی پلاگین
	$pluginData[hezarnevis][type] = 'notify';
	$pluginData[hezarnevis][name] = 'پیامک محصول';
	$pluginData[hezarnevis][uniq] = 'hezarnevis';
	$pluginData[hezarnevis][description] = 'ارسال اطلاعات خرید به موبایل کاربر';
	$pluginData[hezarnevis][author][name] = 'hezarnevis';
	$pluginData[hezarnevis][author][url] = 'http://www.hezarnevis.com';
	$pluginData[hezarnevis][author][email] = 'info@hezarnevis.com';
	
	//-- فیلدهای تنظیمات پلاگین
	$pluginData[hezarnevis][field][config][1][title] = 'شماره ارسال';
	$pluginData[hezarnevis][field][config][1][name] = 'sender_number';
	$pluginData[hezarnevis][field][config][2][title] = 'نام کاربری ارسال';
	$pluginData[hezarnevis][field][config][2][name] = 'username';
	$pluginData[hezarnevis][field][config][3][title] = 'کلمه عبور ارسال';
	$pluginData[hezarnevis][field][config][3][name] = 'password';
	
	//-- تابع پردازش و ارسال اطلاعات
	function notify__hezarnevis($data,$output,$payment,$product,$cards)
	{
		global $db,$smarty;
		if ($output[status] == 1 AND $payment[payment_mobile] AND $cards)
		{
			$sms_text='';
			foreach($cards as $card)
			{
				$sms_text = 'نوع:' . $product[product_title] . "\r\n";
				if($product[product_first_field_title]!="")
					$sms_text .= $product[product_first_field_title] . ':' . $card[card_first_field];
				if($card[card_second_field]!="")
					$sms_text .= "\r\n" . $product[product_second_field_title] . ':' . $card[card_second_field];
				if($card[card_third_field]!="")
					$sms_text .=  "\r\n" . $product[product_third_field_title] . ':' . $card[card_third_field];
$url="http://panel.hezarnevis.com/API/SendSms.ashx?username=".$data[username]."&password=".$data[password]."&from=".$data[sender_number]."&to=".$payment[payment_mobile]."&text=".urlencode($sms_text);
$result = @file_get_contents($url);

                    $sms_text='';
			}
		}
	}
	