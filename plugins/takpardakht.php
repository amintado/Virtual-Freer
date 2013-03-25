<?
/**
 * Mehdi Naderi
 * almasproject@gmail.com
 */
	//-- اطلاعات کلی پلاگین
	$pluginData[takpardakht][type] = 'payment';
	$pluginData[takpardakht][name] = 'تک پرداخت';
	$pluginData[takpardakht][uniq] = 'takpardakht';
	$pluginData[takpardakht][description] = 'مخصوص پرداخت با دروازه پرداخت <a href="http://takpardakht.com" target=_blank">تک پرداخت‌</a>';
	$pluginData[takpardakht][author][name] = 'Mehdi Naderi';
	$pluginData[takpardakht][author][url] = 'http://takpardakht.com';
	$pluginData[takpardakht][author][email] = 'almasproject@gmail.com';
	
	//-- فیلدهای تنظیمات پلاگین
	$pluginData[takpardakht][field][config][1][title] = 'کد پذیرنده';
	$pluginData[takpardakht][field][config][1][name] = 'merchantID';
	$pluginData[takpardakht][field][config][2][title] = 'نام پذیرنده';
	$pluginData[takpardakht][field][config][2][name] = 'merchantName';
	$pluginData[takpardakht][field][config][3][title] = 'لوگو پذیرنده';
	$pluginData[takpardakht][field][config][3][name] = 'merchantLogoURL';
	
	//-- تابع انتقال به دروازه پرداخت
	function gateway__takpardakht($data)
	{
		global $config,$smarty,$db;
/*		
		$payment_id = $data[payment_id];
		$data[payment_rand] = $payment_id;
		$data[invoice_id] = $payment_id;
		
		$update[payment_rand] = $payment_id;
		$sql = $db->queryUpdate('payment', $update, 'WHERE `payment_id` = "'.$payment_id.'" LIMIT 1;');
		$db->execute($sql);
*/		
		$smarty->assign('data', $data);
		$smarty->display('takpardakht.tpl');
	}
	
	//-- تابع بررسی وضعیت پرداخت
	function callback__takpardakht($data)
	{
		global $db,$post;
		$result	= $post['result'];
		if ($result == 0)
		{
			$paymentID = $post['paymentID'];
			$invoiceNumber = $post['invoiceNumber'];

			$merchantID = trim($data[merchantID]);
			
			$sql 		= 'SELECT * FROM `payment` WHERE `payment_rand` = "'.$invoiceNumber.'" LIMIT 1;';
			$payment 	= $db->fetch($sql);
			if ($payment[payment_status] == 1)
			{
				$amount = $payment[payment_amount];
				
				include_once('include/libs/nusoap.php');
				
				$soapclient = new nusoap_client('https://takpardakht.com/ws/ws.php?wsdl','wsdl');
				$soapProxy	= $soapclient->getProxy() ;
				$verifyResult = $soapProxy->verify($paymentID, $merchantID, $invoiceNumber, $amount);
			
				if($verifyResult['done'])
				{
					//-- آماده کردن خروجی
					$output[status]		= 1;
					$output[res_num]	= $invoiceNumber;
					$output[ref_num]	= $paymentID;
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
		}
		else
		{
			$output[status]	= 0;
			$output[message]= 'خطا در پرداخت';
		}
		return $output;
	}