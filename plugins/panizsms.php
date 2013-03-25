<?
/*
  Panizsms
  http://www.panizsms.com

  Copyright (c) 2011 PanizSMS, www.Panizsms.com, www.Spts.co.ir
  
  SMS Plugin for http://freer.ir/virtual, Copyright (c) 2011 Mohammad Hossein Beyram, freer.ir

  The virtual_freer is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License v3 (http://www.gnu.org/licenses/gpl-3.0.html)
  as published by the Free Software Foundation.
*/
	//-- اطلاعات کلی پلاگین
	$pluginData[panizsms][type] = 'notify';
	$pluginData[panizsms][name] = 'پلاگین پیامک محصول';
	$pluginData[panizsms][uniq] = 'panizsms';
	$pluginData[panizsms][description] = 'ارسال اطلاعات خرید به موبایل کاربر با سرویس panizsms';
	$pluginData[panizsms][author][name] = 'PanizSMS';
	$pluginData[panizsms][author][url] = 'http://www.panizsms.com';
	$pluginData[panizsms][author][email] = 'info@panizsms.com';
	
	//-- فیلدهای تنظیمات پلاگین
	$pluginData[panizsms][field][config][1][title] = 'شماره ارسال';
	$pluginData[panizsms][field][config][1][name] = 'sender_number';
	$pluginData[panizsms][field][config][2][title] = 'نام کاربری ارسال';
	$pluginData[panizsms][field][config][2][name] = 'username';
	$pluginData[panizsms][field][config][3][title] = 'کلمه عبور ارسال';
	$pluginData[panizsms][field][config][3][name] = 'password';
	
	//-- تابع پردازش و ارسال اطلاعات
	function notify__panizsms($data,$output,$payment,$product,$cards)
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
				$f = @file_get_contents("http://panel.panizsms.com/post/sendSMS.ashx?from=" . $data[sender_number] . "&to=" . substr($payment[payment_mobile], 1) . "&text=" . urlencode($sms_text) . "&password=" . $data[password] . "&username=" . $data[username]);
			}
		}
	}