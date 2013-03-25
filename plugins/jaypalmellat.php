<?php
	$pluginData[jaypalmellat][type] = 'payment';
	$pluginData[jaypalmellat][name] = ' بانک ملت - جی پال ';
	$pluginData[jaypalmellat][uniq] = 'jaypalmellat';
	$pluginData[jaypalmellat][description] = 'مخصوص پرداخت با درگاه پرداخت اینترنتی <a href="http://jaypal.ir">جی پال</a>';
	$pluginData[jaypalmellat][author][name] = 'SepAdl71';
	$pluginData[jaypalmellat][author][url] = 'http://s-adel.info/';
	$pluginData[jaypalmellat][author][email] = '547.mcts@gmail.com';
	
	$pluginData[jaypalmellat][field][config][1][title] = 'شماره کارت شما در جی پال ';
	$pluginData[jaypalmellat][field][config][1][name] = 'jcard';
	$pluginData[jaypalmellat][field][config][2][title] = 'ایمیل شما در جی پال ';
	$pluginData[jaypalmellat][field][config][2][name] = 'jemail';
	$pluginData[jaypalmellat][field][config][3][title] = 'پسوورد شما در جی پال ';
	$pluginData[jaypalmellat][field][config][3][name] = 'jpassword';
	$pluginData[jaypalmellat][field][config][4][title] = 'نوع درگاه اختصاصی <BR> درگاه پرداخت اینترنتی اختصاصی - درصدی - سمت فروشنده = pga <BR> درگاه پرداخت اینترنتی اختصاصی - درصدی - سمت خریدار = pgb <BR> درگاه پرداخت اینترنتی اختصاصی - اجاره ای = pgc ';
	$pluginData[jaypalmellat][field][config][4][name] = 'jmode';
	
	function mellatGetJayPal($jmode, $jcard, $jemail, $jpassword, $jamount, $jdata, $jcallback)
	{
		$result = "";
		$data = "METHOD=GET"."&"."card=".$jcard."&"."email=".$jemail."&"."password=".$jpassword."&"."amount=".$jamount."&"."data=".$jdata."&"."callback=".$jcallback."&"."ip=".$_SERVER["REMOTE_ADDR"];
		$fp = fsockopen("jaypal.ir", 80);
		fputs($fp, "POST /mellat-".$jmode."/ HTTP/1.1\r\n");
		fputs($fp, "Host: jaypal.ir\r\n");
		fputs($fp, "Content-type: application/x-www-form-urlencoded\r\n");
		fputs($fp, "Content-length: ".strlen($data)."\r\n");
		fputs($fp, "Connection: close\r\n\r\n");
		fputs($fp, $data);
		while(!feof($fp)) $result .= fgets($fp, 128);
		fclose($fp);
		$resultdata = explode('|', $result);
		$resultstr = $resultdata[1];
		switch($resultstr) {
			case "E01E":
				return "method is not set or empty";
				break;

			case "E02E":
				return "card is not set or incorrect";
				break;
			case "E03E":
				return "email is not set or incorrect";
				break;
			case "E04E":
				return "password is not set or incorrect";
				break;
			case "E05E":
				return "bank gateway disable";
				break;
			case "E06E":
				return "amount is not set or empty";
				break;
			case "E07E":
				return "data is not set or empty";
				break;
			case "E08E":
				return "callback is not set or empty";
				break;
			case "E09E":
				return "ip is not set or empty";
				break;

			case "E10E":
				return "jaypalid is not set or empty";
				break;
			case "E11E":
				return "response1 is not set or empty";
				break;
			case "E12E":
				return "response2 is not set or empty";
				break;

			case "E13E":
				return "bank error";
				break;
			case "E14E":
				return "account disable";
				break;
			case "E15E":
				return "database error";
				break;

			case "E16E":
				return "transaction not found";
				break;
			case "E17E":
				return "transaction was verified";
				break;
			case "E18E":
				return "transaction error";
				break;

			default:
				return $resultstr;
		}
	}
	
	function mellatPostJaypal($jmode, $jcard, $jemail, $jpassword, $jaypalid, $response1, $response2)
	{
		$result = "";
		$data = "METHOD=POST"."&"."card=".$jcard."&"."email=".$jemail."&"."password=".$jpassword."&"."jaypalid=".$jaypalid."&"."response1=".$response1."&"."response2=".$response2;
		$fp = fsockopen("jaypal.ir", 80);
		fputs($fp, "POST /mellat-".$jmode."/ HTTP/1.1\r\n");
		fputs($fp, "Host: jaypal.ir\r\n");
		fputs($fp, "Content-type: application/x-www-form-urlencoded\r\n");
		fputs($fp, "Content-length: ".strlen($data)."\r\n");
		fputs($fp, "Connection: close\r\n\r\n");
		fputs($fp, $data);
		while(!feof($fp)) $result .= fgets($fp, 128);
		fclose($fp);
		$resultdata = explode('|', $result);
		$resultstr = $resultdata[1];
		switch($resultstr) {
			case "E01E":
				return "method is not set or empty";
				break;

			case "E02E":
				return "card is not set or incorrect";
				break;
			case "E03E":
				return "email is not set or incorrect";
				break;
			case "E04E":
				return "password is not set or incorrect";
				break;
			case "E05E":
				return "bank gateway disable";
				break;
			case "E06E":
				return "amount is not set or empty";
				break;
			case "E07E":
				return "data is not set or empty";
				break;
			case "E08E":
				return "callback is not set or empty";
				break;
			case "E09E":
				return "ip is not set or empty";
				break;

			case "E10E":
				return "jaypalid is not set or empty";
				break;
			case "E11E":
				return "response1 is not set or empty";
				break;
			case "E12E":
				return "response2 is not set or empty";
				break;

			case "E13E":
				return "bank error";
				break;
			case "E14E":
				return "account disable";
				break;
			case "E15E":
				return "database error";
				break;

			case "E16E":
				return "transaction not found";
				break;
			case "E17E":
				return "transaction was verified";
				break;
			case "E18E":
				return "transaction error";
				break;

			default:
				return $resultstr;
		}
	}
	
	function gateway__jaypalmellat($data)
	{
		global $config,$db,$smarty;
		$resultstr = mellatGetJayPal($data[jmode], $data[jcard], $data[jemail], $data[jpassword], $data["amount"], $data[invoice_id], $data[callback]);
		if(strpos($resultstr, '^') !== false) {
			$resultarray = explode ('^',$resultstr);
			$query	= 'SELECT * FROM `config` WHERE `config_id` = "1" LIMIT 1';
			$conf	= $db->fetch($query);
			$smarty->assign('config', $conf);
			$smarty->assign('title', ' . . . لطفا" صبر نمایید . . . ');
			$smarty->assign('message', ' . . . در حال انتقال به بانک ملت . . . ');
			$smarty->assign('response', $resultarray[0]);
			$smarty->assign('RefId', $resultarray[1]);
			$smarty->display('jaypalmellat.tpl');
			exit;
		} else {
			$smarty->assign('title', ' . . . خطا . . . ');
			$smarty->assign('message', $resultstr);
			$smarty->display('jaypalmellat.tpl');
			exit;
		}
	}
	
	function callback__jaypalmellat($data)
	{
		global $db,$get,$smarty;;
		if(isset($_POST['RefId']) && $_POST['ResCode'] == 0 && isset($_POST['SaleOrderId']) && isset($_POST['SaleReferenceId']))
		{
			$resultstr = mellatPostJaypal($data[jmode], $data[jcard], $data[jemail], $data[jpassword], $_POST['SaleOrderId'], $_POST['SaleReferenceId'], $_POST['RefId']);
			if(strpos($resultstr, '^') !== false) {
				$resultarray = explode ('^',$resultstr);
				$sql 	= "SELECT * FROM `payment` WHERE `payment_rand` = '".$resultarray[0]."' AND `payment_amount` = '".$resultarray[1]."' LIMIT 1;";
				$payment = $db->fetch($sql);
				$output[status]		= 1;
				$output[res_num]	= $_POST['SaleOrderId'];
				$output[ref_num]	= $_POST['SaleReferenceId'];
				$output[payment_id]	= $payment['payment_id'];
				return $output;
			} else {
				$fdata[title] = 'تایید تراکنش';
				$fdata[message] = $resultstr;
				$query	= 'SELECT * FROM `config` WHERE `config_id` = "1" LIMIT 1';
				$conf	= $db->fetch($query);
				$smarty->assign('config', $conf);
				$smarty->assign('data', $fdata);
				$smarty->display('message.tpl');
				exit();
			}
		}
	}