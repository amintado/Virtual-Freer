<?
/*
  Ideh Pardazan SMS Module
  http://www.idehsms.ir
  http://www.ipj.ir

  Copyright (c) 2012 Ideh Pardazan Group .
*/
	//-- اطلاعات کلی پلاگین
	$pluginData[idehsms3000][type] = 'notify';
	$pluginData[idehsms3000][name] = 'پيامك اطلاعات محصول';
	$pluginData[idehsms3000][uniq] = 'idehsms3000';
	$pluginData[idehsms3000][description] = 'ارسال اطلاعات خرید به موبایل خريدار (با خطوط 3000)';
	$pluginData[idehsms3000][author][name] = 'Ideh Pardazan';
	$pluginData[idehsms3000][author][url] = 'http://www.ipj.ir';
	$pluginData[idehsms3000][author][email] = 'info@ipj.ir';
	
	//-- فیلدهای تنظیمات پلاگین
	$pluginData[idehsms3000][field][config][1][title] = 'شماره پيام كوتاه';
	$pluginData[idehsms3000][field][config][1][name] = 'sender_number';
	$pluginData[idehsms3000][field][config][2][title] = 'نام كاربري وب سرويس';
	$pluginData[idehsms3000][field][config][2][name] = 'username';
	$pluginData[idehsms3000][field][config][3][title] = 'كلمه عبور وب سرويس';
	$pluginData[idehsms3000][field][config][3][name] = 'password';
	
	//-- تابع پردازش و ارسال اطلاعات
	function notify__idehsms3000($data,$output,$payment,$product,$cards)
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
		
				$username = $data[username];
				$password = $data[password];
				$sender = $data[sender_number];
				$reciever = $payment[payment_mobile];
				$text = $sms_text;
				
				$soapclient = new nusoap_client('http://ws.idehsms.ir/index.php?wsdl','wsdl');
				$soapclient->soap_defencoding = 'UTF-8';
				$soapProxy	= $soapclient->getProxy() ;
				$res		= $soapProxy->SendSMS($username,$password,$reciever,$text,$sender );
			
				$smsid = $res;
				$sms_text='';
               
			}
		}
	}
	