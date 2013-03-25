<?
/*
	Virtual Freer
	http://mehrdad.farsitext.com
	By Mehrdad Amini
	Copyright (c) 2011 Neo!
*/
$api_url_saman = 'http://ferestandeapi.com/';
$bank_name		= 'saman';
	//-- اطلاعات کلي پلاگين
	$pluginData[ferestande_saman][type] = 'payment';
	$pluginData[ferestande_saman][name] = 'بانک سامان';
	$pluginData[ferestande_saman][uniq] = 'ferestande_saman';
	$pluginData[ferestande_saman][description] = 'مخصوص پرداخت با دروازه پرداخت <a href="http://ferestande.com">فرستنده</a> و پرداخت با سیستم <a href="http://ferestandeapi.com">FerestandeAPI</a>';
	$pluginData[ferestande_saman][author][name] = 'Mehrdad';
	$pluginData[ferestande_saman][author][url] = 'http://mehrdad.farsitext.com';
	$pluginData[ferestande_saman][author][email] = 'Mehrdad@FarsiText.com';
	
	//-- فيلدهاي تنظيمات پلاگين
	$pluginData[ferestande_saman][field][config][1][title] = 'کد API که از سایت <a href="" target="_blank" >FerestandeAPI.com</a> گرفته اید را اینجا وارد نمایید';
	$pluginData[ferestande_saman][field][config][1][name] = 'api_key_saman';
	ini_set("default_charset", 'utf-8');
	require_once($config[MainInfo][path].'include/request_ferestande.php');
	//-- تابع انتقال به دروازه پرداخت
	function gateway__ferestande_saman($data)
	{
		global $config,$db,$smarty,$api_url_saman,$bank_name,$post;
		
		$api_key = trim($data[api_key_saman]);
		$amount_rial	= round($data[amount]);
		$invoice_id		 = $data[invoice_id];
		$callBackUrl 	 = $data[callback];
		$random_id		=	mysql_real_escape_string($_REQUEST[random]);
		
		$query	= 'SELECT * FROM `payment` WHERE `payment_rand` = "'.$random_id.'" LIMIT 1';
		$payment_data	= $db->fetch($query);
		
		if (!$post)
        {
            //echo form
			echo '
<title>درگاه پرداخت فرستنده. ورود اطلاعات</title>
<style type="text/css">
	.main {
	    background-color: #F1F1F1;
	    border: 1px solid #CACACA;
	    left: 50%;
	    margin-left: -265px;
	    position: absolute;
	    top: 200px;
	    width: 530px;
		padding: 10px;
		direction: rtl;
		text-align: right;
		font-family: tahoma;
		font-size: 12px;
	}
	label {
		float:right;
		display: block;
		width: 75px;
		text-align: left;
		margin:5px;
	}
	input {
		border: 1px solid;
		font-family: tahoma;
		font-size: 12px;
		margin:5px;
	}
</style>
</head>
<body>
    <div class="main">
	<p>در صورتي که مي‌خواهيد ناشناس خريد کنيد، مي‌توانيد اطلاعات نامعتبر وارد کنيد.<br/>
توجه کنيد در اين صورت امکان پيگيري پرداخت خود از درگاه فرستنده را نخواهيد داشت.</p>
	<p><font color="red"></font></p>
	<form method="post">
		<label for="label">نام:</label><input type="text" id="name" name="name" value="name" size="40"><br />
		<label>نام خانوادگي:</label><input type="text" name="family" value="family" size="40"><br />
		<label>&nbsp;</label><input type="submit" class="button" name="submit" value="پرداخت">
    </form>
    </div>
</body>
';
            exit;
        }
        else
        {
            if (!$post[name])
                $error .= 'نام‌تان را وارد کنید.<br />';
            if (!$post[family])
                $error .= 'نام خانوادگی‌تان را وارد کنید.<br />';
            if($error)
            {
                $smarty->assign('error', $error);
                $smarty->display('message.tpl');
                exit;
            }
        }
		$my_name = $post[name];
		$my_family = $post[family];
		$my_email = $payment_data[payment_email];
		
		//=================================
		$requested_link = $api_url_saman.'api.php?'.http_build_query(array('key'=>$api_key,'bank'=>$bank_name,'price'=>$amount_rial,'name'=>$my_name,'family'=>$my_family,'email'=>$my_email));
		//die($requested_link);
		$get_data = file_get_contents2($requested_link);
		$data_arr = xml_to_array_saman($get_data);
		//=================================
		$res_id = $data_arr['ResNum']; // resnum
		$wage = $data_arr['wage'];
		if ($res_id)
		{
			$update[payment_rand]	= $res_id;
			$sql = $db->queryUpdate('payment', $update, 'WHERE `payment_rand` = "'.$invoice_id.'" LIMIT 1;');
			$db->execute($sql);
			echo (redirect_form_saman($data_arr['Amount'],$res_id,$wage,$callBackUrl));
			exit;
		}
		else
		{
			$data[title] = 'خطاي سيستم';
			$data[message] = '<font color="red">در اتصال به درگاه فرستنده - بانک سامان مشکلي بوجود آمده است! لطفا از ساير درگاه ها استفاده نماييد</font>'.$res.'<br /><a href="index.php" class="button">بازگشت</a>';
			$smarty->assign('data', $data);
			$smarty->display('message.tpl');
		}
	}
	
	//-- تابع بررسي وضعيت پرداخت
	function callback__ferestande_saman($data)
	{
		global $db,$get,$api_url_saman,$bank_name;
		$api_key = trim($data[api_key_saman]);
		
		$RefNum = trim($_POST['RefNum']);
		$ResNum = trim($_POST['ResNum']);
		$MID	= trim($_POST['MID']);
		$State  = $_POST['State'];
		
		$verify_url = $api_url_saman.'verify.php?'.http_build_query(array_merge(array('key'=>$api_key),$_POST));
		
		$get_data = file_get_contents2($verify_url);
		$data_arr = xml_to_array_saman($get_data);
		if ($State=="OK" and strlen($RefNum) >5 )
		{
			
			$sql 		= 'SELECT * FROM `payment` WHERE `payment_rand` = "'.$ResNum.'" LIMIT 1;';
			$payment 	= $db->fetch($sql);
			$amount = $data_arr['price'];
			//$amount must be here
			if(!isset($data_arr['price']) or $data_arr['price']==NULL) $amount = -1;
			//-- پرداخت کاملا موفق بوده
			if (($amount>0) AND ($State=='OK'))
			{
				//-- مبلغ پرداختي با مبلغ ارسالي بايد چک شود
				$sql 		= 'SELECT * FROM `payment` WHERE `payment_rand` = "'.$ResNum.'" LIMIT 1;';
				$payment 	= $db->fetch($sql);
				
				if ($payment[payment_status] == 1)
				{
					if($amount == $payment[payment_amount])
					{
						//-- آماده کردن خروجي
						$output[status]		= 1;
						$output[res_num]	= $ResNum;
						$output[ref_num]	= $RefNum;
						$output[payment_id]	= $payment[payment_id];
					}
					else
					{
						//-- مقدار پرداختي با مقدار ارسالي برابر نيست? برگشت زده شود و پيغام خطا دهد.
						$output[status]	= 0;
						$output[message]= 'پرداخت توسط فرستنده تاييد نشد.';
					}
				}
				else
				{
					//-- سفارش قبلا پرداخت شده است.
					$output[status]	= 0;
					$output[message]= 'اين سفارش قبلا پرداخت شده است.';
				}
			}
			else
			{
				$output[status]	= 0;
				$output[message]= 'پرداخت تکميل نشده است.';
			}
		}
		else
		{
			//-- RefNum درست ن?ست.
			$output[status]	= 0;
			$output[message]= 'اطلاعات پرداخت کامل نيست.';
		}
		return $output;
	}
//==================================================
function xml_to_array_saman($xmlstr) 
{
  $doc = new DOMDocument();
  $doc->loadXML($xmlstr);
  return domnode_to_array_saman($doc->documentElement);
}
//**********************************
function domnode_to_array_saman($node) 
{
  $output = array();
  switch ($node->nodeType) {

    case XML_CDATA_SECTION_NODE:
    case XML_TEXT_NODE:
      $output = trim($node->textContent);
    break;

    case XML_ELEMENT_NODE:
      for ($i=0, $m=$node->childNodes->length; $i<$m; $i++) {
        $child = $node->childNodes->item($i);
        $v = domnode_to_array_saman($child);
        if(isset($child->tagName)) {
          $t = $child->tagName;
          if(!isset($output[$t])) {
            $output[$t] = array();
          }
          $output[$t][] = $v;
        }
        elseif($v || $v === '0') {
          $output = (string) $v;
        }
      }
      if(is_array($output)) {
        if($node->attributes->length) {
          $a = array();
          foreach($node->attributes as $attrName => $attrNode) {
            $a[$attrName] = (string) $attrNode->value;
          }
          $output['@attributes'] = $a;
        }
        foreach ($output as $t => $v) {
          if(is_array($v) && count($v)==1 && $t!='@attributes') {
            $output[$t] = $v[0];
          }
        }
      }
    break;
  }
  return $output;
}
//==================================================
function redirect_form_saman($amount,$resnum,$wage,$redirect_url)
{
$form_saman = '
<form action="https://acquirer.samanepay.com/payment.aspx" method="post" name="ferestandeform" >
<input type="hidden" name="MID" value="3017" />
<input type="hidden" name="Amount" value="'.$amount.'" />
<input type="hidden" name="wage" value="'.$wage.'" />
<input type="hidden" name="ResNum" value="'.$resnum.'" />
<input type="hidden" name="RedirectURL" value="'.$redirect_url.'" />
<input type="submit" value="Click Me If Can Not Redirect" name="ferestande_btn" />
</form>
<script type="text/javascript">window.onload=function(){window.setTimeout('."'document.ferestandeform.submit()'".', 0)}</script>
';
return $form_saman;
}
//================================================== 
function file_get_contents2($url){
	$req = &new HTTP_Request($url);
	$req->setMethod(HTTP_REQUEST_METHOD_GET);
	$req->sendRequest();
	$res = $req->getResponseBody();
	return $res;
}
//======================================================
?>