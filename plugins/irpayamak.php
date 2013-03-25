<?
/*
  IR-Payamak
  http://www.ir-payamak.com

  Copyright (c) 2012 IR PAYAMAK, www.ir-payamak.com
  
  SMS Plugin for http://freer.ir/virtual, Copyright (c) 2011 Mohammad Hossein Beyram, freer.ir

  The virtual_freer is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License v3 (http://www.gnu.org/licenses/gpl-3.0.html)
  as published by the Free Software Foundation.
*/
	//-- اطلاعات کلی پلاگین
	$pluginData[irpayamak][type] = 'notify';
	$pluginData[irpayamak][name] = 'پیامک محصول';
	$pluginData[irpayamak][uniq] = 'irpayamak';
	$pluginData[irpayamak][description] = 'ارسال اطلاعات خرید به موبایل کاربر';
	$pluginData[irpayamak][author][name] = 'IR-Payamak';
	$pluginData[irpayamak][author][url] = 'http://www.ir-payamak.com';
	$pluginData[irpayamak][author][email] = 'sattaribm@gmail.com';
	
	//-- فیلدهای تنظیمات پلاگین
	$pluginData[irpayamak][field][config][1][title] = 'شماره ارسال';
	$pluginData[irpayamak][field][config][1][name] = 'sender_number';
	$pluginData[irpayamak][field][config][2][title] = 'نام کاربری ارسال';
	$pluginData[irpayamak][field][config][2][name] = 'username';
	$pluginData[irpayamak][field][config][3][title] = 'کلمه عبور ارسال';
	$pluginData[irpayamak][field][config][3][name] = 'password';
	
	//-- تابع پردازش و ارسال اطلاعات
	function notify__irpayamak($data,$output,$payment,$product,$cards)
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
      $username= "$data[username]"; 
      $password= "$data[password]";
      $from="$data[sender_number]";
      $to="$payment[payment_mobile]";
      $text="$sms_text"; 
      $isflash;
      $url = 'http://ir-payamak.com/sendsms.php';
      $fields = array( 'programmer'=>"4",
                       'username'=>"$username",
                       'password'=>"$password",
                       'from'=>$from,
                       'to'=>$to,
                       'text'=>("$text"),
                       'isflash'=>"$isflash",
                       'udh'=>""
      );
            foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
            rtrim($fields_string,'&');
             $ch = curl_init();
             curl_setopt($ch,CURLOPT_URL,$url);
             curl_setopt($ch,CURLOPT_POST,count($fields));
             curl_setopt($ch,CURLOPT_POSTFIELDS,$fields_string);
             $result = curl_exec($ch);    
             curl_close($ch);  
             $sms_text='';
			}
		}
	}
	