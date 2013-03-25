<?
/*
  DanaServer
  http://Danaserver.com
  Copyright (c) 2012 A. JamshidiDana, DanaServer.com
*/
	//-- اطلاعات کلی پلاگین
	$pluginData[paypaad][type] = 'payment';
	$pluginData[paypaad][name] = 'درگاه پرداخت امن پاسارگاد';
	$pluginData[paypaad][uniq] = 'paypaad';
	$pluginData[paypaad][description] = 'مخصوص پرداخت با دروازه <a href="http://paypaad.ir" target="_blank">پرداخت امن پاسارگاد‌</a>';
	$pluginData[paypaad][author][name] = 'Danaserver';
	$pluginData[paypaad][author][url] = 'http://Danaserver.com';
	$pluginData[paypaad][author][email] = 'info@Danaserver.com';

	//-- فیلدهای تنظیمات پلاگین
	$pluginData[paypaad][field][config][1][title] = 'مرچنت کد';
	$pluginData[paypaad][field][config][1][name] = 'merchant_code';
	$pluginData[paypaad][field][config][2][title] = 'ترمینال کد';
	$pluginData[paypaad][field][config][2][name] = 'terminal_code';
	$pluginData[paypaad][field][config][3][title] = 'آدرس فایل کلید pem';
	$pluginData[paypaad][field][config][3][name] = 'key_file';
	$pluginData[paypaad][field][config][4][title] = 'روز تحویل';
	$pluginData[paypaad][field][config][4][name] = 'delivery_days';


	//-- تابع انتقال به دروازه پرداخت
	function gateway__paypaad($data)
	{
		global $config,$db,$smarty,$post;	
		$callBackUrl 	= $data[callback];
		$amount			= $data[amount];
		$invoice_id		= $data[invoice_id];
		$merchant_code	= $data[merchant_code];
		$terminal_code	= $data[terminal_code];
		$delivery_days	= $data[delivery_days];
		$key_file		= $data[key_file];

        if (!$post)
        {
            $smarty->assign('config', $config);
            $smarty->display('paypaad.tpl');
            exit;
        }
        else
        {
            if (!$post[name])
                $error .= '- نام‌تان را وارد کنید.<br />';
            if (!$post[family])
                $error .= '- نام خانوادگی‌تان را وارد کنید.<br />';          
            if($error)
            {
                $smarty->assign('error', $error);
                $smarty->assign('config', $config);
                $smarty->display('paypaad.tpl');
                exit;
            }
        }
		
		$pasargad	= new Pasargad();
		$gdate = $pasargad->gregorian_to_jalali(date('Y'),date('m'),date('d'),'/');		
		
		//-- اطلاعات فاکتور پرداخت
		$sql		= 'SELECT * FROM `payment` WHERE `payment_rand` = "'.$invoice_id.'" LIMIT 1;';
		$payment	= $db->fetch($sql);
				
		$cart_data = array(
			'buyer_name' => $post[name].".".$post[family],
			'buyer_tel' => $payment[payment_mobile],
			'total_amount' => $amount,
			'merchant_code' => $merchant_code,
			'terminal_code' => $terminal_code,
			'delivery_days' => $delivery_days,
			'delivery_address' => $payment[payment_email],
			'invoice_date' => $gdate,
			'redirect_address' => $callBackUrl,
			'referrer_address' => $callBackUrl,
			'invoice_number' => $invoice_id,
			'cart' => array(
			   array(
				   'content' => 'فاکتور شماره: '.$invoice_id,
				   'fee' => $amount,
				   'count' => 1
				   )
		   )
		);		

		$cart = new PasargadCart($cart_data);
		$xml = $pasargad->createXML($cart);
		$sign = $pasargad->sign($xml,$key_file);

		$data[xml]  = $xml;
		$data[sign] = $sign;

		$smarty->assign('data', $data);
		$smarty->assign('config', $config);
		$smarty->display('paypaad.tpl');
		exit;
	}


	//-- تابع بررسی وضعیت پرداخت
	function callback__paypaad($data)
	{
		global $db,$get;

		$i_date = $_GET['iD'];
		$i_c = $_GET['iC'];
		$i_number = $_GET['iN'];
		$tref = $_GET['tref'];

		$pasargad= new Pasargad();
		$response = $pasargad->getResponse($tref);

		if($response->result == 'true')
		{
			//-- موفقیت آمیز
			$payment='';
			$sql 		= 'SELECT * FROM `payment` WHERE `payment_rand` = "'.$i_number.'" LIMIT 1;';
			$payment 	= $db->fetch($sql);
			if ($payment)
			{
				if ($payment[payment_status] == 1)
				{
					//-- آماده کردن خروجی
					$output[status]		= 1;
					$output[res_num]	= $i_c;
					$output[ref_num]	= $i_number;
					$output[payment_id]	= $payment[payment_id];
				}
				else
				{
					$output[status]	= 0;
					$output[message]= 'سفارش قبلا ارسال شده است.';
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
			$output[message]='پرداخت موفقيت آميز نبود و يا توسط خريدار کنسل شده است';
		}
		return $output;
	}

	

// کلاس اصلی مورد استفاده paypaad.ir
class PasargadCart {
	var $time_stamp = '';			
	var $invoice_date = '';			
	var $invoice_number = '';		
	var $merchant_code = '';		
	var $terminal_code = '';		
	var $redirect_address = '';		
	var $referrer_address = '';		
	var $delivery_days = 0;			
	var $total_amount = 0;			
	var $buyer_name = '';			
	var $buyer_tel = '';			
	var $delivery_address = '';		
	var $cart = array();			

	function __construct($data=array()){
		$this->time_stamp = date('Y/m/d H:i:s');
		$this->merchant_code = $merchant_code;
		$this->terminal_code = $terminal_code;
		$this->redirect_address = $redirect_address;
		$this->referrer_address = $referrer_address;
		$this->delivery_days = $delivery_days;
		if(count($data)>0){
			foreach($data as $var => $value){
				if(property_exists('PasargadCart', $var) && $var!='cart'){
					$value = strip_tags($value);
					$value = str_replace(array("\n","\r"), ' ', $value);
					$this->$var = $value;
				}
				if($var == 'cart') $this->cart = $this->makeProductItems($value);
			}
		}
    }

	function PasargadCart($data=array()){
		$this->__construct($data);
	}

	function makeProductItems($products = array()){
		$content = '';		
		$count = 0;			
		$fee = 0;			
		$description = '';	
		$result = array();

		if(count($products)>0){
			foreach($products as $product){
				$item = NULL;
				$item->content = $content;
				$item->count = $count;
				$item->fee = $fee;
				$item->description = $description;
				if(is_array($product) && count($product)>0) {
					foreach($product as $key => $value){
						$value = strip_tags($value);
						$value = str_replace(array("\n","\r"), ' ', $value);
						$item->$key = $value;
					}
				}
				$item->amount = $item->fee * $item->count;
				$result[] = $item;
			}
		}
		return $result;
	}

}


class Pasargad {

	var $response = NULL;

	function __construct($data=array()){
		//
	}

	function Pasargad($data=array()){
		$this->__construct($data);
	}

	function createXML($cart){
		$output = '<?xml version="1.0" encoding="utf-8" ?>'
		.'<invoice'
		.' time_stamp="'.$cart->time_stamp.'"'
		.' invoice_date="'.$cart->invoice_date.'"'
		.' invoice_number="'.$cart->invoice_number.'"'
		.' terminal_code="'.$cart->terminal_code.'"'
		.' merchant_code="'.$cart->merchant_code.'"'
		.' redirect_address="'.$cart->redirect_address.'"'
		.' referer_address="'.$cart->referrer_address.'"'
		.' delivery_days="'.(int)$cart->delivery_days.'"'
		.' total_amount="'.(int)$cart->total_amount.'"'
		.' buyer_name="'.$cart->buyer_name.'"'
		.' buyer_tel="'.$cart->buyer_tel.'"'
		.' delivery_address="'.$cart->delivery_address.'"'
		.'>'
		;

		$i=1; 
		if(count($cart->cart)>0){
			foreach($cart->cart as $item){
				$output .= '<item number="'.$i.'">'
				.'<content>'.$item->content.'</content>'
				.'<fee>'.(int)$item->fee.'</fee>'
				.'<count>'.(int)$item->count.'</count>'
				.'<amount>'.(int)$item->amount.'</amount>'
				.'<description>'.$item->description.'</description>'
				.'</item>'
				;
				$i++; 
			}
		}
		$output .= '</invoice>';

		return $output;
	}

	function sign($xml,$key_file){
		$key = file_get_contents($key_file);
		$priv_key = openssl_pkey_get_private($key); // notice: there must be pkcs#8 representation of your privateKey in .PEM file format.
		$signature = '';
		if(!openssl_sign($xml, $signature, $priv_key, OPENSSL_ALGO_SHA1)) {
			return false;
		}else{
			$result = base64_encode($signature);
		}
		return $result;
	}

	function getResponse($tref='',$cart=NULL){
		$url = "https://paypaad.bankpasargad.com/PaymentTrace";
		$curl_session = curl_init($url); 
		curl_setopt($curl_session, CURLOPT_POST, 1);	
		curl_setopt($curl_session, CURLOPT_FOLLOWLOCATION, 1); 
		curl_setopt($curl_session, CURLOPT_HEADER, 0); 
		curl_setopt($curl_session, CURLOPT_RETURNTRANSFER, 1); 
		if($tref!='' && $tref!=NULL && !empty ($tref)){ 
			$post_data = "tref=".$tref;
		}elseif(is_object($cart) && $cart->invoice_number!=''){
			$post_data = 'invoice_number='.$cart->invoice_number
					.'&invoice_date='.$cart->invoice_date
					.'&merchant_code'.$cart->merchant_code
					.'&terminal_code'.$cart->terminal_code
					;
		}else{
			return false;
		}
		curl_setopt($curl_session, CURLOPT_POSTFIELDS, $post_data);
		$output = curl_exec($curl_session);
		curl_close($curl_session);
		$parser = xml_parser_create();
		xml_parse_into_struct($parser, $output, $values);
		xml_parser_free($parser);
		$this->response = NULL;
		foreach($values as $res_item){
			$tag = strtolower($res_item['tag']);
			$this->response->$tag = $res_item['value'];
		}
		return $this->response;
	}

	function contents($parser,$data){
		$this->response = $data;
	}

	function startTag($parser,$data) {
		//
	}
	function endTag($parser,$data){
		//
	}
		function gregorian_to_jalali($g_y,$g_m,$g_d,$mod=''){
				 $d_4=$g_y%4;
				 $g_a=array(0,0,31,59,90,120,151,181,212,243,273,304,334);
				 $doy_g=$g_a[(int)$g_m]+$g_d;
				 if($d_4==0 and $g_m>2)$doy_g++;
				 $d_33=(int)((($g_y-16)%132)*.0305);
				 $a=($d_33==3 or $d_33<($d_4-1) or $d_4==0)?286:287;
				 $b=(($d_33==1 or $d_33==2) and ($d_33==$d_4 or $d_4==1))?78:(($d_33==3 and $d_4==0)?80:79);
				 if((int)(($g_y-10)/63)==30){$a--;$b++;}
				 if($doy_g>$b){
				  $jy=$g_y-621; $doy_j=$doy_g-$b;
				 }else{
				  $jy=$g_y-622; $doy_j=$doy_g+$a;
				 }
				 if($doy_j<187){
				  $jm=(int)(($doy_j-1)/31); $jd=$doy_j-(31*$jm++);
				 }else{
				  $jm=(int)(($doy_j-187)/30); $jd=$doy_j-186-($jm*30); $jm+=7;
				 }
				 return($mod=='')?array($jy,$jm,$jd):$jy.$mod.$jm.$mod.$jd;
		}

}