<?
/*
  Virtual Freer
  http://freer.ir/virtual

  Copyright (c) 2011 Mohammad Hossein Beyram, freer.ir

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License v3 (http://www.gnu.org/licenses/gpl-3.0.html)
  as published by the Free Software Foundation.
*/
	//-- اطلاعات کلي پلاگين	
	$pluginData[saman_ferestande][type] = 'payment';
	$pluginData[saman_ferestande][name] = 'بانک سامان';
	$pluginData[saman_ferestande][uniq] = 'saman_ferestande';
	$pluginData[saman_ferestande][description] = 'پرداخت انلاین فرستنده٬ سامان';
	$pluginData[ferestande][author][name] = 'Mehrdad(Freer Edition)';
	$pluginData[ferestande][author][url] = 'http://mehrdad.farsitext.com';
	$pluginData[ferestande][author][email] = 'Mehrdad@FarsiText.com';
	
	//-- فیلدهای تنظیمات پلاگین
	$pluginData[saman_ferestande][field][config][1][title] = 'نام کاربری - آدرس ایمیل';
	$pluginData[saman_ferestande][field][config][1][name] = 'ferestande_user';
	$pluginData[saman_ferestande][field][config][2][title] = 'رمز عبور فرستنده';
	$pluginData[saman_ferestande][field][config][2][name] = 'ferestande_pass';
	$pluginData[saman_ferestande][field][config][3][title] = 'کارمزد فرستنده';
	$pluginData[saman_ferestande][field][config][3][name] = 'ferestande_tax';
	require_once($config[MainInfo][path].'/include/request_ferestande.php');
	//-- تابع انتقال به دروازه پرداخت
	function gateway__saman_ferestande($data)
	{
		global $config,$db,$smarty,$post;
        $query	= 'SELECT * FROM `config` WHERE `config_id` = "1" LIMIT 1';
        $conf	= $db->fetch($query);
        if (!$post)
        {
            $smarty->assign('config', $conf);
            $smarty->display('ferestande-saman.tpl');
            exit;
        }
        else
        {
            if (!$post[name])
                $error .= 'نام‌تان را وارد کنید.<br />';
            if (!$post[family])
                $error .= 'نام خانوادگی‌تان را وارد کنید.<br />';
            if (!$post[email])
                $error .= 'ایمیلتان را وارد کنید.<br />';
            elseif(filter_var($post[email], FILTER_VALIDATE_EMAIL) == false)
                $error .= 'ایمیلتان نامعتبر است.<br />';
            
            if($error)
            {
                $smarty->assign('error', $error);
                $smarty->assign('config', $conf);
                $smarty->display('ferestande-saman.tpl');
                exit;
            }
        }
		$ferestande_user	= trim($data[ferestande_user]);
		$ferestande_pass	= trim($data[ferestande_pass]);
		$amount				= $data[amount];
		$invoice_id			= $data[invoice_id];
		$callBackUrl		= $data[callback];
		$ferestande_domain	= 'http://ferestande.com/customer';
		
		$price_ferestande_id= saman_pid_create($ferestande_user,$ferestande_pass,$amount);
		$sefaresh_url		= $ferestande_domain.'/Validate.php?PId='.$price_ferestande_id;
        $res = saman_send_request_to_ferestande_and_get_result($post[name], $post[family], $post[email], $sefaresh_url, $callBackUrl);
		$res_id = saman_get_resNum($res); // resnum
		if ($res_id)
		{
			$update[payment_rand]	= $res_id;
			$sql = $db->queryUpdate('payment', $update, 'WHERE `payment_rand` = "'.$invoice_id.'" LIMIT 1;');
			$db->execute($sql);
            preg_match_all('/<form action="(.*)" method="post">\s*<input type="hidden" name="MID" value="(.*)" \/>\s*<input type="hidden" name="Amount" value="(.*)" \/>\s*<input type="hidden" name="ResNum" value="(.*)" \/>\s*<input type="hidden" name="RedirectURL" value="(.*)" \/>.*<\/form>/msU',$res,$result);
            $data   = array (
                'action' => $result[1][0],
                'amount' => $result[3][0],
                'merchant' => $result[2][0],
                'invoice_id' => $result[4][0],
                'callback' => $result[5][0]
            );
            $smarty->assign('config', $conf);
            $smarty->assign('data', $data);
            $smarty->display('ferestande-saman.tpl');
            //echo($res);
			exit;
		}
		else
		{
			$data[title] = 'خطای سیستم';
			$data[message] = '<font color="red">در اتصال به درگاه فرستنده مشکلی به وجود آمده لطفا از درگاه سایر بانک‌ها استفاده نمایید.</font>'.$res.'<br /><a href="index.php" class="button">بازگشت</a>';
			$smarty->assign('data', $data);
			$smarty->display('message.tpl');
		}
	}
	
	//-- تابع بررسی وضعیت پرداخت
	function callback__saman_ferestande($data)
	{
		global $db,$get;
		$ferestande_user = trim($data[ferestande_user]);
		$ferestande_pass = trim($data[ferestande_pass]);
		
		$RefNum = trim($_POST['RefNum']);
		$ResNum = trim($_POST['ResNum']);
		$MID	= trim($_POST['MID']);
		$State  = $_POST['State'];
		
		if (strlen($RefNum) == 20)
		{
			//include_once('include/libs/Request/Request.php');
			$sql 		= 'SELECT * FROM `payment` WHERE `payment_rand` = "'.$ResNum.'" LIMIT 1;';
			$payment 	= $db->fetch($sql);
			saman_send_bank_result_to_ferestande_and_get_result($RefNum,$ResNum,$MID="00240448-36882");
			$check = saman_check_invoice($ferestande_user,$ferestande_pass,$ResNum);	
			$amount		= $check[1];
			//-- پرداخت کاملا موفق بوده
			if (($amount>0) AND ($State=='OK'))
			{
				//-- مبلغ پرداختی با مبلغ ارسالی باید چک شود
				$sql 		= 'SELECT * FROM `payment` WHERE `payment_rand` = "'.$ResNum.'" LIMIT 1;';
				$payment 	= $db->fetch($sql);
				
				if ($payment[payment_status] == 1)
				{
					if ($payment[payment_amount] <= $data[ferestande_tax])
						$payment[payment_amount] = $data[ferestande_tax];
					
					if($amount == $payment[payment_amount])
					{
						//-- آماده کردن خروجی
						$output[status]		= 1;
						$output[res_num]	= $ResNum;
						$output[ref_num]	= $RefNum;
						$output[payment_id]	= $payment[payment_id];
					}
					else
					{
						//-- مقدار پرداختی با مقدار ارسالی برابر نیستی برگشت زده شود و پیغام خطا دهد.
						$output[status]	= 0;
						$output[message]= 'پرداخت توسط فرستنده تایید نشد.مقدار پرداختی با مقدار فاکتور برابر نیست.';
					}
				}
				else
				{
					//-- سفارش قبلا پرداخت شده است.
					$output[status]	= 1;
					$output[message]= 'این سفارش قبلا پرداخت شده است.';
				}
			}
			else
			{
				$output[status]	= 0;
				$output[message]= 'پرداخت تکمیل نشده است.';
			}
		}
		else
		{
			//-- RefNum درست نیست.
			$output[status]	= 0;
			$output[message]= 'اطلاعات پرداخت کامل نیست.';
		}
		return $output;
	}

function saman_get_resNum($text)
{
	preg_match('@ResNum" value="(.*?)"@',$text,$match);
	$tmp=$match[1];
	if(!$tmp)return false;
	return $tmp;
}

function saman_get_redirect_url($text)
{
	preg_match('@value="(.*?)verify.php@',$text,$match);
	$tmp=$match[1];
	if(!$tmp)return false;
	return $tmp;
}

function saman_send_request_to_ferestande_and_get_result($name, $family, $email, $sefaresh_url, $redirect_url)
{
	$req = &new HTTP_Request($sefaresh_url);
	$req->setMethod(HTTP_REQUEST_METHOD_POST);
	$req->sendRequest();
	$html = $req->getResponseBody();
	$link2  = saman_get_link($html,'Validate_Shetab2.php?value=',"'</script>");
	//=====================================================================
	$link3 = 'http://www.ferestande.com/customer/'.$link2;

	$req = &new HTTP_Request($link3);
	$req->setMethod(HTTP_REQUEST_METHOD_POST);
	$req->sendRequest();
	$cookie = $req->getResponseCookies();
	$html = $req->getResponseBody();
	$idfind  = substr(saman_get_link($html,'name="IDFind" value="','" />'),'21');

	
	$req = &new HTTP_Request('http://www.ferestande.com/customer/shetabVerify.php');
	$req->setMethod(HTTP_REQUEST_METHOD_POST);
	$req->addHeader('Referer', $link3);
	$req->addPostData('name',$name);
	$req->addPostData('family',$family);
	$req->addPostData('jens', 'مرد');
	$req->addPostData('email', $email);
	$req->addPostData('ostanName', 'البرز');
	$req->addPostData('id_ostan', '90');
	$req->addPostData('shahrName', 'کرج');
	$req->addPostData('id_shahr', '7%2F37800%2F35380%2F1420%2F1000%2F12440%2F11000%2F440%2F1000');
	$req->addPostData('address', 'nadaram');
	$req->addPostData('telephone', '021123456789');
	$req->addPostData('ZipCode', '1234567890');
	$req->addPostData('mobile', '0935'.rand(1111111,9999999));
	$req->addPostData('massage', 'message');
	$req->addPostData('payment', 'saman');
	$req->addPostData('IDFind', $idfind);
	$req->addPostData('EndOfShop', '');
	$req->addPostData('ReadirectURL', '');
	$req->addPostData('commentVendorShoppingEnd', '1');
	$req->addPostData('Admin_sendSms_Active', '1');
	$req->addPostData('Admin_readirectUrl_Active', '0');
	$req->addPostData('vendor_sendSms_Active', '0');
	$req->addPostData('button3','تاييد');
	$req->addCookie($cookie[0]['name'], $cookie[0]['value']);
	$req->sendRequest();
	$res = $req->getResponseBody();
	
	$base_url = saman_get_redirect_url($res);
	
	$replaced = str_replace($base_url."verify.php",$redirect_url,$res);
	return $replaced;
}

function saman_send_bank_result_to_ferestande_and_get_result($RefNum,$ResNum,$MID="00240448-36882"){
	$req = &new HTTP_Request('http://www.ferestande.com/customer/verify.php');
	$req->setMethod(HTTP_REQUEST_METHOD_POST);
	$req->addHeader('Referer',"https://acquirer.sb24.com/CardServices/controller");
	$req->addPostData('RefNum',$RefNum);
	$req->addPostData('MID',$MID);
	$req->addPostData('ResNum',$ResNum);
	$req->addPostData('State', 'OK');
	$req->sendRequest();
	$res = $req->getResponseBody();
	return $res;
}

function saman_get_pid($text)
{
	preg_match('@PId=(.*?)"@',$text,$match);
	$tmp=$match[1];
	if(!$tmp)return false;
	return $tmp;
}
function saman_pid_create($ferestande_user,$ferestande_pass,$mahsool_price){
	global $data;
	$mahsool_price = $mahsool_price - $data[ferestande_tax];
	
	
	$mahsool_code = 'cder'.$mahsool_price;
	$mahsool_name = 'cdta'.$mahsool_price;
	
	$req = &new HTTP_Request('http://www.ferestande.com/vendor/index.php');
	$req->setMethod(HTTP_REQUEST_METHOD_POST);
	$req->addHeader('Referer', 'http://www.ferestande.com/vendor/success_pay.php');
	$req->addPostData('email', $ferestande_user);
	$req->addPostData('password', $ferestande_pass);
	$req->sendRequest();
	$res = $req->getResponseBody();
	if(!substr_count($res, "success_pay.php")){return false;}
	$cookie = $req->getResponseCookies();
	//login shod!
	
	$req = &new HTTP_Request("http://www.ferestande.com/vendor/product.php?start=1&sortField=&caseSort=1");	
	$req->setMethod(HTTP_REQUEST_METHOD_POST);
	$req->addHeader('Referer', 'http://www.ferestande.com/vendor/product.php');
	$req->addPostData('codeOfProduct',$mahsool_code);
	$req->addCookie($cookie[0]['name'], $cookie[0]['value']);
	$req->sendRequest();
	$res = $req->getResponseBody();
	
	
	$string=explode('<table width="95%" border="0"',$res);
	//$string2=explode('<tr>',$string['1']);
	//return $string2[3];
	
	$pid = saman_get_pid($string[1]);
	if($pid){
		return $pid;
	} else {
		
		$req = &new HTTP_Request("http://www.ferestande.com/vendor/code_Addproduct.php");	
		$req->setMethod(HTTP_REQUEST_METHOD_POST);
		$req->addHeader('Referer', 'http://www.ferestande.com/vendor/add_product.php');
		$req->addPostData('code',$mahsool_code);
		$req->addPostData('name',$mahsool_name);
		$req->addPostData('price',$mahsool_price);
		$req->addPostData('weight', '0');
		$req->addPostData('DoPost', 'no');
		$req->addPostData('list2', '#');
		$req->addPostData('productSituation', '0');
		
		$req->addCookie($cookie[0]['name'], $cookie[0]['value']);
		$req->sendRequest();
		$res = $req->getResponseBody();
		//return $res; 
		// end create and start searching for pid
		$req = &new HTTP_Request("http://www.ferestande.com/vendor/product.php?start=1&sortField=&caseSort=1");	
		$req->setMethod(HTTP_REQUEST_METHOD_POST);
		$req->addHeader('Referer', 'http://www.ferestande.com/vendor/product.php');
		$req->addPostData('codeOfProduct',$mahsool_code);
		$req->addCookie($cookie[0]['name'], $cookie[0]['value']);
		$req->sendRequest();
		$res = $req->getResponseBody();

		$string=explode('<table width="95%" border="0"',$res);
		//$string2=explode('<tr>',$string['1']);
		//return $string2[3];
	
		$pid = saman_get_pid($string[1]);
		return $pid;
	}
}

function saman_get_link($contents,$first,$end)
{
	$begPos = strpos($contents, $first); 
	$endPos = strpos($contents,$end,$begPos);
	$re= $endPos - $begPos;
	$tmp = substr($contents,$begPos,$re);
	return $tmp;
}

function saman_check_invoice($ferestande_user,$ferestande_pass,$peygiri){

	$req = &new HTTP_Request('http://www.ferestande.com/vendor/index.php');
	$req->setMethod(HTTP_REQUEST_METHOD_POST);
	$req->addHeader('Referer', 'http://www.ferestande.com/vendor/success_pay.php');
	$req->addPostData('email', $ferestande_user);
	$req->addPostData('password', $ferestande_pass);
	$req->sendRequest();
	$res = $req->getResponseBody();
	if(!substr_count($res, "success_pay.php")){return false;}
	$cookie = $req->getResponseCookies();
	
	$req = &new HTTP_Request("http://www.ferestande.com/vendor/success_pay.php");	
	$req->setMethod(HTTP_REQUEST_METHOD_POST);
	$req->addHeader('Referer', 'http://www.ferestande.com/vendor/success_pay.php');
	$req->addPostData('code', $peygiri);
	$req->addCookie($cookie[0]['name'], $cookie[0]['value']);
	$req->sendRequest();
	$res = $req->getResponseBody();
	
	if(!substr_count($res, "shomarePeigiriCustomer=".$peygiri)){
		return false;
	}else
	{
	
		$string=explode('<table width="700px"',$res);
		$string=explode('<tr>',$string['2']);
		
		$code=explode('<td bgcolor="#FFFFFF"class="style3"><div align="center" class="style1">',$string['2']);
		$code=explode('</div>',$code['5']);
		$code=$code['0'];
		
		$price=explode('<td bgcolor="#FFFFFF"class="style3"><div align="center" class="style1">',$string['2']);
		$price=explode('</div>',$price['3']);
		$price=$price['0'];
		
		$resid=explode('<div align="center">',$string['2']);
		$resid=explode('<input type="hidden"',$resid['1']);
		$resid=trim($resid['0']);
		
		return array($code,$price,$resid);
	}
}