<?
/*
  Ideh Pardazan SMS Module
  http://www.idehsms.ir
  http://www.ipj.ir

  Copyright (c) 2012 Ideh Pardazan Group .
*/
	//-- اطلاعات کلی پلاگین
	$pluginData[idehsms][type] = 'notify';
	$pluginData[idehsms][name] = 'پيامك اطلاعات محصول';
	$pluginData[idehsms][uniq] = 'idehsms';
	$pluginData[idehsms][description] = 'ارسال اطلاعات خرید به موبایل خريدار';
	$pluginData[idehsms][author][name] = 'Ideh Pardazan';
	$pluginData[idehsms][author][url] = 'http://www.ipj.ir';
	$pluginData[idehsms][author][email] = 'info@ipj.ir';
	
	//-- فیلدهای تنظیمات پلاگین
	$pluginData[idehsms][field][config][1][title] = 'شماره پيام كوتاه';
	$pluginData[idehsms][field][config][1][name] = 'sender_number';
	$pluginData[idehsms][field][config][2][title] = 'كد ريموت';
	$pluginData[idehsms][field][config][2][name] = 'RemoteCode';
	
	//-- تابع پردازش و ارسال اطلاعات
	function notify__idehsms($data,$output,$payment,$product,$cards)
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
                
				$url = "http://sms.idehsms.ir/remote.php";
				$parameters["Number"] = "$data[sender_number]"; 
				$parameters["RemoteCode"] = "$data[RemoteCode]"; 
				$parameters["Message"] = "$sms_text"; 
				$parameters["Farsi"] = "1"; 
				$parameters["To"] = "$payment[payment_mobile]"; 
				
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_TIMEOUT, 310000);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
				$data1 = curl_exec($ch);
				curl_close($ch);
				
				$smsid = $data1['Return'];
				$sms_text='';
               
			}
		}
	}
	