<?
/*
  S.M.B Productions
  http://www.f2u.ir

  Copyright (c) 2011 S.M.B Productions, www.f2u.ir
  
  SMS Plugin for http://freer.ir/virtual, Copyright (c) 2011 Mohammad Hossein Beyram, freer.ir

  The virtual_freer is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License v3 (http://www.gnu.org/licenses/gpl-3.0.html)
  as published by the Free Software Foundation.
*/
	//-- اطلاعات کلی پلاگین
	$pluginData[f2usms2][type] = 'notify';
	$pluginData[f2usms2][name] = 'پیامک محصول';
	$pluginData[f2usms2][uniq] = 'f2usms2';
	$pluginData[f2usms2][description] = 'ارسال اطلاعات خرید به موبایل کاربر';
	$pluginData[f2usms2][author][name] = 'S.M.B Productionss';
	$pluginData[f2usms2][author][url] = 'http://www.f2u.ir';
	$pluginData[f2usms2][author][email] = 'info@f2u.ir';
	
	//-- فیلدهای تنظیمات پلاگین
	$pluginData[f2usms2][field][config][1][title] = 'شماره ارسال';
	$pluginData[f2usms2][field][config][1][name] = 'sender_number';
	$pluginData[f2usms2][field][config][2][title] = 'نام کاربری ارسال';
	$pluginData[f2usms2][field][config][2][name] = 'username';
	$pluginData[f2usms2][field][config][3][title] = 'کلمه عبور ارسال';
	$pluginData[f2usms2][field][config][3][name] = 'password';
	
	//-- تابع پردازش و ارسال اطلاعات
	function notify__sms($data,$output,$payment,$product,$cards)
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
				$f = @file_get_contents("http://sms.panel2u.ir/post/sendSMS.ashx?from=" . $data[sender_number] . "&to=" . substr($payment[payment_mobile], 1) . "&text=" . urlencode($sms_text) . "&password=" . $data[password] . "&username=" . $data[username]);
				$sms_text='';
			}
		}
	}