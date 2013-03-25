<?
	$pluginData[mehr][type] = 'payment';
	$pluginData[mehr][name] = 'درگاه مستقیم مهرپی';
	$pluginData[mehr][uniq] = 'mehr';
	$pluginData[mehr][description] = 'درگاه پرداخت اینترنتی <a href="http://www.mehrpay.ir" target=_blank">مهرپی‌</a>';
	$pluginData[mehr][author][name] = 'توسعه مهر';
	$pluginData[mehr][author][url] = 'http://www.mehrpay.ir';
	$pluginData[mehr][author][email] = 'info@mehrpay.ir';
	
	$pluginData[mehr][field][config][1][title] = 'کد پذیرنده';
	$pluginData[mehr][field][config][1][name] = 'merchantID';
	
	function gateway__mehr($data)
	{
		global $config,$smarty,$db;
		
		$ok = false;

		$sql = 'SELECT * FROM `payment` WHERE `payment_rand` = "'.$data[invoice_id].'" LIMIT 1;';
		$payment = $db->fetch($sql);
		if ( $payment ) {
			$merchantID 	= $data[merchantID];
			$amount 		= $data[amount];
			$invoice_id	= $data[invoice_id];
			$callbackUrl   = $data[callback];
			$email		 = $payment[payment_email];
			$id = $payment[payment_id];
			///////////////\\\\\\\\\\\\\\\\
			$url = 'http://www.mehrpay.ir/direct.php';
			$fields = array(
            'resnum'=>urlencode("$invoice_id"),
            'email'=>urlencode("$email"),
			'amount'=>urlencode("$amount"),
			'id'=>urlencode("$merchantID"),
			'callback'=>urlencode("$callbackUrl"),
        	);
			$fields_string = "";
			foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
			rtrim($fields_string,'&');
			$ch = curl_init($url);
			curl_setopt($ch,CURLOPT_URL,$url);
			curl_setopt($ch,CURLOPT_POST,count($fields));
			curl_setopt($ch,CURLOPT_POSTFIELDS,$fields_string);
			curl_exec($ch);
			curl_close($ch);
			///////////////\\\\\\\\\\\\\\\\
			$update2[payment_res_num]	= $invoice_id;
			
			$sql = $db->queryUpdate('payment', $update2, 'WHERE `payment_id` = "'.$id.'" LIMIT 1;');
			$db->execute($sql);
			$ll = "http://www.mehrpay.ir/ask.php?id=" . $merchantID . "&resnum=" . $invoice_id;
			$update[payment_rand] = file_get_contents($ll);
			
			$sql = $db->queryUpdate('payment', $update, 'WHERE `payment_id` = "'.$id.'" LIMIT 1;');
			$db->execute($sql);
			
			$sql2 		= 'SELECT * FROM `payment` WHERE `payment_id` = "'.$id.'" LIMIT 1;';
			$id2 	= $db->fetch($sql2);
			header('location:https://www.pecco24.com:27635/pecpaymentgateway/?au='.$id2[payment_rand]);
			exit;
			}
	}
	
	function callback__mehr($data)
	{
		global $db,$get,$smarty;
		$authority = $get['au'];
		$status = $get['rs'];
		if(($status == 0) AND $authority)
		{
			$sql 		= 'SELECT * FROM `payment` WHERE `payment_rand` = "'.$authority.'" LIMIT 1;';
			$payment 	= $db->fetch($sql);
			if ($payment)
			{
				//-- یعنی کد درست وارد شده است و وجود دارد٬ حالا وضعیت سفارش چک شود در چه مرحله ای هست
				if ($payment[payment_status] == 1)//-- آماده پرداخت است
				{
					
					
					$url = 'http://www.mehrpay.ir/verify.php?authority='.$authority;
					$rn = file_get_contents($url);
					$rr = substr($rn, 3, 1);
						$output[status]		= $rr;
						$output[res_num]	= $payment[payment_res_num];
						$output[ref_num]	= $authority;
						$output[payment_id]	= $payment[payment_id];
				}
				else
				{
					$output[status]	= 0;
					$output[message]= 'چنین سفارشی تعریف نشده است.';
				}
			}
			else
			{
				$output[status]	= 0;
				$output[message]= 'اطلاعات پرداخت کامل نیست.';
			}
		}
		else
		{
			$output[status]	= 0;
			$output[message]= 'اطلاعات پرداخت کامل نیست.';
		}
		return $output;
	}