<?
	$pluginData[shetab_direct_Parsian][type] = 'payment';
	$pluginData[shetab_direct_Parsian][name] = 'پارسیان';
	$pluginData[shetab_direct_Parsian][uniq] = 'shetab_direct_Parsian';
	$pluginData[shetab_direct_Parsian][description] = 'درگاه پرداخت اینترنتی <a href="http://www.shetab.biz" target=_blank">شتاب‌</a>';
	$pluginData[shetab_direct_Parsian][author][name] = 'Danesh Pajohan';
	$pluginData[shetab_direct_Parsian][author][url] = 'http://www.shetab.biz';
	$pluginData[shetab_direct_Parsian][author][email] = 'info@shetab.biz';
	
	$pluginData[shetab_direct_Parsian][field][config][1][title] = 'کد پذیرنده';
	$pluginData[shetab_direct_Parsian][field][config][1][name] = 'merchantID';

	function senddata($url,$id,$order_id,$amount,$redirect){
		$ch = curl_init();
		curl_setopt($ch,CURLOPT_URL,$url);
		curl_setopt($ch,CURLOPT_POSTFIELDS,"id=$id&resnum=$order_id&amount=$amount&callback=$redirect");
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
		$res = curl_exec($ch);
		curl_close($ch);
		return $res;
	}	
	function gateway__shetab_direct_Parsian($data)
	{
		global $config,$smarty,$db;
		$id = $data[merchantID];
		$amount = $data[amount];
		$redirect = $data[callback];
		$order_id= $data[invoice_id];
		$url = 'http://shetab.biz/webservice/index.php';
		$result = senddata($url,$id,$order_id,$amount,$redirect);
		
		if ($result > 0 && is_numeric($result))
		{
		$go = "http://shetab.biz/webservice/go.php?id=$result";
		header("Location: $go");
		exit;
		}
		else
		{
		//-- نمایش خطا
		$data[title] = 'خطای سیستم';
		$data[message] = '<font color="red">در ارتباط با درگاه Shetab.biz مشکلی به وجود آمده است. لطفا مطمئن شوید کد MerchantID خود را به درستی در قسمت مدیریت وارد کرده اید.</font> شماره خطا: '.$result.'<br /><a href="index.php" class="button">بازگشت</a>';
		switch(intval($result)){
			case -1:$data[message] .='<br> شناسه MerchantID صحيح نمي باشد.';break;
			case -2:$data[message] .='<br> مقدار Amount مبلغ قابل پرداخت صحيح نمي باشد';break;
			case -3:$data[message] .='<br> مقدار callback آدرس بازگشت  صحيح نمي باشد';break;
			case -4:$data[message] .='<br> درگاه شتاب فروشنده غيرفعال شده است';break;			
			case -5:$data[message] .='<br> مقدار resnum شناسه سفارش صحيح نمي باشد';break;
			case -6:$data[message] .='<br> شناسه سفارش تکراري مي باشد';break;
			case -7:$data[message] .='<br> خطا در اتصال به بانک لطفا مجدد تلاش کنيد.';break;
		}
		$smarty->assign('data', $data);
		$smarty->display('message.tpl');
		exit;
		
		}
	}
	
	//-- تابع بررسی وضعیت پرداخت
	function callback__shetab_direct_Parsian($data)
	{
		global $db,$post;
		
		if($_POST['status']!='1'){
			$output[status]	= 0;
			$output[message]= 'پرداخت با موفقيت انجام نشده است.';
			return $output;
		}
		$refID = $_POST['refnum'];
		$resCode = $_POST['resnum'];
			
		$sql 		= 'SELECT * FROM `payment` WHERE `payment_rand` = "'.$resCode.'" LIMIT 1;';
		$payment 	= $db->fetch($sql);
		if ($payment[payment_status] == 1)
		{
			$amount = $payment[payment_amount];
			///////////////////
			$url = 'http://www.shetab.biz/webservice/verify.php';
			$fields = array(
           		 'resnum'=>urlencode($resCode),
          		  'refnum'=>urlencode($refID),
			'amount'=>urlencode($amount),
        	);

			//url-ify the data for the POST
			$fields_string = "";
			foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
			rtrim($fields_string,'&');

			//open connection
			$ch = curl_init($url);

			//set the url, number of POST vars, POST data
			curl_setopt($ch,CURLOPT_URL,$url);
			curl_setopt($ch,CURLOPT_POST,count($fields));
			curl_setopt($ch,CURLOPT_POSTFIELDS,$fields_string);

			//execute post
			$result = curl_exec($ch);
			curl_close($ch);
			if( $result <= 0 ) {
			$pay = false;
			} else {
			$pay = true;
			}
			///////////////////
					
			if($pay)
			{
				//-- آماده کردن خروجی
				$output[status]		= 1;
				$output[res_num]	= $resCode;
				$output[ref_num]	= $refID;
				$output[payment_id]	= $payment[payment_id];
			}
			else
			{
				$output[status]	= 0;
				$output[message]= 'خطا در پرداخت';
			}
		}
		else
		{
			//-- سفارش قبلا پرداخت شده است.
			$output[status]	= 0;
			$output[message]= 'این سفارش قبلا پرداخت شده است.';
		}
		
		return $output;
	}