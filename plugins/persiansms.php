<?
/*
  AftabWeb
  http://www.AftabWeb.Net

  Copyright (c) 2011 S.M.B Productions, www.f2u.ir
  
  SMS Plugin for http://freer.ir/virtual, Copyright (c) 2011 Mohammad Hossein Beyram, freer.ir

  The virtual_freer is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License v3 (http://www.gnu.org/licenses/gpl-3.0.html)
  as published by the Free Software Foundation.
*/
	//-- اطلاعات کلی پلاگین
	$pluginData[persiansms][type] = 'notify';
	$pluginData[persiansms][name] = 'پیامک محصول';
	$pluginData[persiansms][uniq] = 'persiansms';
	$pluginData[persiansms][description] = 'ارسال اطلاعات خرید به موبایل کاربر';
	$pluginData[persiansms][author][name] = 'AftabWeb';
	$pluginData[persiansms][author][url] = 'http://www.AftabWeb.Net';
	$pluginData[persiansms][author][email] = 'info@aftabweb.net';
	
	//-- فیلدهای تنظیمات پلاگین
	$pluginData[persiansms][field][config][1][title] = 'شماره ارسال';
	$pluginData[persiansms][field][config][1][name] = 'sender_number';
	$pluginData[persiansms][field][config][2][title] = 'نام کاربری ارسال';
	$pluginData[persiansms][field][config][2][name] = 'username';
	$pluginData[persiansms][field][config][3][title] = 'کلمه عبور ارسال';
	$pluginData[persiansms][field][config][3][name] = 'password';
	
	//-- تابع پردازش و ارسال اطلاعات
	function notify__persiansms($data,$output,$payment,$product,$cards)
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
                $my_class = new SoapClient('http://www.persiansms.info/webservice/smsService.php?wsdl' , array('trace' => 1) );
                    $smsid = $my_class->send_sms ( "$data[username]" , "$data[password]" , "$data[sender_number]", "$payment[payment_mobile]"  , "$sms_text" );
                    $sms_text='';
			}
		}
	}
	