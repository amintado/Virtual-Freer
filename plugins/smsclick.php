<?
/*
  SMS Plugin for http://freer.ir/virtual, Copyright (c) 2011 Mohammad Hossein Beyram, freer.ir

  The virtual_freer is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License v3 (http://www.gnu.org/licenses/gpl-3.0.html)
  as published by the Free Software Foundation.
*/
	//-- اطلاعات کلی پلاگین
	$pluginData[smsclick][type] = 'notify';
	$pluginData[smsclick][name] = 'پیامک محصول با کمک سامانه اس ام اس کلیک';
	$pluginData[smsclick][uniq] = 'smsclick';
	$pluginData[smsclick][description] = 'ارسال اطلاعات خرید به موبایل کاربر';
	$pluginData[smsclick][author][name] = 'SMSCLiCK';
	$pluginData[smsclick][author][url] = 'http://smsclick.ir';
	$pluginData[smsclick][author][email] = '';
	
	//-- فیلدهای تنظیمات پلاگین
	$pluginData[smsclick][field][config][1][title] = 'شماره ارسال';
	$pluginData[smsclick][field][config][1][name] = 'sender_number';
	$pluginData[smsclick][field][config][2][title] = 'نام کاربری ارسال';
	$pluginData[smsclick][field][config][2][name] = 'username';
	$pluginData[smsclick][field][config][3][title] = 'کلمه عبور ارسال';
	$pluginData[smsclick][field][config][3][name] = 'password';
	
	//-- تابع پردازش و ارسال اطلاعات
	function notify__smsclick($data,$output,$payment,$product,$cards)
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
					try {
						$client = new SoapClient("http://sms.dorbid.ir/post/send.asmx?wsdl", array('encoding'=>'UTF-8'));
						$parameters['username'] = "$data[username]";
						$parameters['password'] = "$data[password]";
						$parameters['from'] = "$data[sender_number]";
						$parameters['to'] = array("$payment[payment_mobile]");
						$parameters['text'] = "$sms_text";
						$parameters['isflash'] = false;
						$parameters['udh'] = "";
						$parameters['recId'] = array(0);
						$parameters['status'] = 0x0;
						echo $client->GetCredit(array("username"=>"wsdemo","password"=>"wsdemo"))->GetCreditResult;
						echo $client->SendSms($parameters)->SendSmsResult;
						echo $status;
						} catch (SoapFault $ex) {
						echo $ex->faultstring;
					}
			}
		}
	}
	
