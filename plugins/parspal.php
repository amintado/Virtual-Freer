<?
/*
  Virtual Freer PARSPAL module 
  Copyright (c) 2012 SOFTIRAN.ORG

*/
	//-- اطلاعات کلی پلاگین
	$pluginData[parspal][type] = 'payment';
	$pluginData[parspal][name] = 'پارس پال';
	$pluginData[parspal][uniq] = 'parspal';
	$pluginData[parspal][description] = 'مخصوص پرداخت با دروازه پرداخت <a href="http://parspal.com">پارس پال</a>';
	$pluginData[parspal][author][name] = 'parspal.com';
	$pluginData[parspal][author][url] = 'http://www.parspal.com';
	$pluginData[parspal][author][email] = 'info@parspal.com';
	
	//-- فیلدهای تنظیمات پلاگین
	$pluginData[parspal][field][config][1][title] = 'شناسه درگاه';
	$pluginData[parspal][field][config][1][name] = 'merchant';
	$pluginData[parspal][field][config][2][title] = 'رمز';
	$pluginData[parspal][field][config][2][name] = 'pass';
	$pluginData[parspal][field][config][3][title] = 'عنوان خرید';
	$pluginData[parspal][field][config][3][name] = 'title';
	
	//-- تابع انتقال به دروازه پرداخت
	function gateway__parspal($data)
	{
		global $config,$db,$smarty;
		include_once('include/libs/nusoap.php');
		$ParspalPin 	= trim($data[merchant]);
		$pass			= trim($data[pass]);
		$amount 		= round($data[amount]/10);
		$invoice_id		= $data[invoice_id];
		$callBackUrl 	= $data[callback];
		$soapclient = new nusoap_client('http://merchant.parspal.com/WebService.asmx?wsdl','wsdl');
		 $params = array(
						"MerchantID" =>$ParspalPin,
						"Password"=>$pass,
		                "Price" => $amount,
						"ReturnPath" => $callBackUrl,
						"ResNumber" => $invoice_id,
						"Description"=>urlencode($data[title]),
						"Paymenter"=>'aaa@aa.aa',
						"Email"=>'aaa@aa.aa',
						"Mobile"=>'aaa@aa.aa'
		              );
		$res = $soapclient->call('RequestPayment', $params);
		$PayPath = $res['RequestPaymentResult']['PaymentPath'];
		$Status = $res['RequestPaymentResult']['ResultStatus'];
	  if(strtolower($Status) == 'succeed')
		{
			$update[payment_rand]		= $invoice_id;
			$sql = $db->queryUpdate('payment', $update, 'WHERE `payment_rand` = "'.$invoice_id.'" LIMIT 1;');
			$db->execute($sql);
			header('location:'.$PayPath);
			exit;
		}
		else
		{
			$data[title] = 'خطای سیستم';
			$data[message] = '<font color="red">در اتصال به درگاه پارس پال مشکلی پیش آمد دوباره امتحان کنید و یا به پشتیبانی خبر دهید</font>'.$res.'<br /><a href="index.php" class="button">بازگشت</a>';
			$query	= 'SELECT * FROM `config` WHERE `config_id` = "1" LIMIT 1';
			$conf	= $db->fetch($query);
			$smarty->assign('config', $conf);
			$smarty->assign('data', $data);
			$smarty->display('message.tpl');
		}
	}
	
	//-- تابع بررسی وضعیت پرداخت
	function callback__parspal($data)
	{
		global $db,$get;
	$Status = $_POST['status'];
	$Refnumber = $_POST['refnumber'];
	$Resnumber = $_POST['resnumber'];
		if ($Status == 100)
		{
			include_once('include/libs/nusoap.php');
			$ParspalPin 	= trim($data[merchant]);
			$pass		= $data[pass];
				$sql 		= 'SELECT * FROM `payment` WHERE `payment_rand` = "'.$Resnumber.'" LIMIT 1;';
			$payment 	= $db->fetch($sql);
			$amount		= round($payment[payment_amount]/10);
			$soapclient = new nusoap_client('http://merchant.parspal.com/WebService.asmx?wsdl','wsdl');
			$params = array(
							'MerchantID' => $ParspalPin,
							'Password' =>$pass,
							'Price' => $amount,
							'RefNum' =>$Refnumber
							) ;
			$res = $soapclient->call('verifyPayment', $params);
			$Status =$res['verifyPaymentResult']['ResultStatus']; 
				if (strtolower($Status)=='success')//-- موفقیت آمیز
				{
					//-- آماده کردن خروجی
					$output[status]		= 1;
					$output[res_num]	= $au;
					$output[ref_num]	= $ref_id;
					$output[payment_id]	= $payment[payment_id];
				}
				else
				{
					//-- در تایید پرداخت مشکلی به‌وجود آمده است‌
					$output[status]	= 0;
					$output[message]= 'پرداخت ناموفق است. خطا';
				}

		}
		else
		{
				$output[status]	= 0;
				$output[message]= 'پرداخت ناموفق است. خطا';
		}
		return $output;
	}