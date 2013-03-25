<?
/*
  PulseNET
  http://pulsenet.org
  http://samanpayamak.ir

  Copyright (c) 2012 PulseNET.
*/
	//-- اطلاعات کلی پلاگین
	$pluginData[samanpayamak][type] = 'notify';
	$pluginData[samanpayamak][name] = 'پيامك اطلاعات محصول';
	$pluginData[samanpayamak][uniq] = 'samanpayamak';
	$pluginData[samanpayamak][description] = 'ارسال اطلاعات خرید به موبایل خريدار';
	$pluginData[samanpayamak][author][name] = 'PulseNET';
	$pluginData[samanpayamak][author][url] = 'http://pulsenet.org/';
	$pluginData[samanpayamak][author][email] = 'info@pulsenet.org';
	
	//-- فیلدهای تنظیمات پلاگین
	$pluginData[samanpayamak][field][config][1][title] = 'شماره پيام كوتاه';
	$pluginData[samanpayamak][field][config][1][name] = 'sender_number';
	$pluginData[samanpayamak][field][config][2][title] = 'نام كاربري وب سرويس';
	$pluginData[samanpayamak][field][config][2][name] = 'username';
	$pluginData[samanpayamak][field][config][3][title] = 'كلمه عبور وب سرويس';
	$pluginData[samanpayamak][field][config][3][name] = 'password';
	
	//-- تابع پردازش و ارسال اطلاعات
	function notify__samanpayamak($data,$output,$payment,$product,$cards)
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
		
				$get["username"] = $data[username];
				$get["password"] = $data[password];
				$get["from"] = $data[sender_number];
				$get["To"] = $payment[payment_mobile];
				$get["text"] = $sms_text;
				
				$baseURL = 'http://samanpayamak.ir/API/SendSms.ashx';
				$filename = $baseURL . '?' . http_build_query($get);
				$res = file_get_contents($filename);
			
				$smsid = $res;
				$sms_text='';
               
			}
		}
	}
	