<?php

	$pluginData[payline][type] = 'payment';
	$pluginData[payline][name] = 'پرداخت آنلاین با Payline';
	$pluginData[payline][uniq] = 'payline';
	$pluginData[payline][description] = 'پرداخت آنلاین با Payline';
	$pluginData[payline][author][name] = 'Payline developement team';
	$pluginData[payline][author][url] = 'http://payline.ir';
	$pluginData[payline][author][email] = 'info@payline.ir';

	$pluginData[payline][field][config][1][title] = 'لطفا API خود را در فیلد زیر وارد نمایید ';
	$pluginData[payline][field][config][1][name] = 'pin';


    function send($url,$api,$amount,$redirect){
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_POSTFIELDS,"api=$api&amount=$amount&redirect=$redirect");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
        $res = curl_exec($ch);
        curl_close($ch);
        return $res;
    }
    function get($url,$api,$trans_id,$id_get){
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_POSTFIELDS,"api=$api&id_get=$id_get&trans_id=$trans_id");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
        $res = curl_exec($ch);
        curl_close($ch);
        return $res;
    }
	function gateway__payline($data)
	{
		global $config,$smarty,$db;
		$api = $data[pin];
        $amount = $data[amount];
        $redirect = $data[callback];
		$order_id		= $data[invoice_id];
	    $url = 'http://payline.ir/payment/gateway-send';
        $result = send($url,$api,$amount,$redirect);

		if ($result > 0 && is_numeric($result))
		{
			$update[payment_rand]	= $result;
			$sql = $db->queryUpdate('payment', $update, 'WHERE `payment_rand` = "'.$order_id.'" LIMIT 1;');
			$db->execute($sql);
			$go = "http://payline.ir/payment/gateway-$result";
            header("Location: $go");
			exit;
		}
		else
		{
			//-- نمایش خطا
			$data[title] = 'خطای سیستم';
			$data[message] = '<font color="red">در ارتباط با درگاه Payline مشکلی به وجود آمده است. لطفا مطمئن شوید کد API خود را به درستی در قسمت مدیریت وارد کرده اید.</font> شماره خطا: '.$result.'<br /><a href="index.php" class="button">بازگشت</a>';
			$smarty->assign('data', $data);
			$smarty->display('message.tpl');
			exit;

		}
	}

	function callback__payline($data)
	{
		global $db,$get,$smarty;
        $api = $data[pin];
        $url = 'http://payline.ir/payment/gateway-result-second';
        $trans_id = $_POST['trans_id'];
        $id_get = $_POST['id_get'];
        $result = get($url,$api,$trans_id,$id_get);

		if($result == 1)
		{
			$sql 		= 'SELECT * FROM `payment` WHERE `payment_rand` = "'.$id_get.'" LIMIT 1;';
			$payment 	= $db->fetch($sql);
			if ($payment)
			{
				if ($payment[payment_status] == 1)
				{
				    $output[status] = 1;
					$output[res_num] = $authority;
					$output[payment_id] = $payment[payment_id];

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
			$output[message]= 'پرداخت موفقيت آميز نبود';
		}
		return $output;
	}