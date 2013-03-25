<?

	$pluginData[spadsms][type] = 'notify';
	$pluginData[spadsms][name] = 'پیامک محصول';
	$pluginData[spadsms][uniq] = 'spadsms';
	$pluginData[spadsms][description] = 'ارسال اطلاعات خرید به تلفن همراه کاربر - <a href="http://spadweb.ir">اسـپــاد اس ام اس</a>';
	$pluginData[spadsms][author][name] = 'spadsms';
	$pluginData[spadsms][author][url] = 'http://www.spadweb.ir';
	$pluginData[spadsms][author][email] = 'support@spadweb.ir';
	$pluginData[spadsms][field][config][1][title] = 'شماره پیامک';
	$pluginData[spadsms][field][config][1][name] = 'sender_number';
	$pluginData[spadsms][field][config][2][title] = 'نام کاربری';
	$pluginData[spadsms][field][config][2][name] = 'username';
	$pluginData[spadsms][field][config][3][title] = 'رمز عبور';
	$pluginData[spadsms][field][config][3][name] = 'password';

	function notify__spadsms($data,$output,$payment,$product,$cards)
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
				$f = @file_get_contents("http://spadsms.net/post/sendSMS.ashx?from=" . $data[sender_number] . "&to=" . substr($payment[payment_mobile], 1) . "&text=" . urlencode($sms_text) . "&password=" . $data[password] . "&username=" . $data[username]);
				$sms_text='';
			}
		}
	}