<?
/*
  sigmasms
  http://www.sigmasms.ir

  Copyright (c) 2012 sigmasms, www.sigmasms.ir
  
  SMS Plugin for http://freer.ir/virtual, Copyright (c) 2011 Mohammad Hossein Beyram, freer.ir

  The virtual_freer is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License v3 (http://www.gnu.org/licenses/gpl-3.0.html)
  as published by the Free Software Foundation.
*/
	//-- اطلاعات کلی پلاگین
	$pluginData[sgmsms][type] = 'notify';
	$pluginData[sgmsms][name] = 'پیامک محصول';
	$pluginData[sgmsms][uniq] = 'sgmsms';
	$pluginData[sgmsms][description] = 'ارسال اطلاعات خرید به موبایل کاربر';
	$pluginData[sgmsms][author][name] = 'sigmasms';
	$pluginData[sgmsms][author][url] = 'http://www.sigmasms.ir';
	$pluginData[sgmsms][author][email] = 'info@sigmasms.ir';
	
	//-- فیلدهای تنظیمات پلاگین
	$pluginData[sgmsms][field][config][1][title] = 'شماره ارسال';
	$pluginData[sgmsms][field][config][1][name] = 'sender_number';
	$pluginData[sgmsms][field][config][2][title] = 'نام کاربری ارسال';
	$pluginData[sgmsms][field][config][2][name] = 'username';
	$pluginData[sgmsms][field][config][3][title] = 'کلمه عبور ارسال';
	$pluginData[sgmsms][field][config][3][name] = 'password';
	
	//-- تابع پردازش و ارسال اطلاعات
	function notify__sgmsms($data,$output,$payment,$product,$cards)
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
				$f = @file_get_contents("http://panel.sigmasms.ir/post/sendSMS.ashx?from=" . $data[sender_number] . "&to=" . substr($payment[payment_mobile], 1) . "&text=" . urlencode($sms_text) . "&password=" . $data[password] . "&username=" . $data[username]);
				$sms_text='';
			}
		}
	}