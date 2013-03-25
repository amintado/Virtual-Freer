<?
/*
  Virtual Freer
  http://freer.ir/virtual

  Copyright (c) 2011 Mohammad Hossein Beyram, freer.ir

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License v3 (http://www.gnu.org/licenses/gpl-3.0.html)
  as published by the Free Software Foundation.
*/
//---------------------- Clean Input vars Function------------------
function cleaner($input)
{
	if($input)
	{
		if(is_array($input))
		{
			foreach ($input as $key => $value)
			{
				$output[$key] = cleaner($value);
			}
		}
		else
		{
			$output = htmlspecialchars(trim($input),ENT_QUOTES);
		}
		return $output;
	}
	else
	{
		return '';
	}
}

//---------------------- Check Login Function------------------
function check_login()
{
	global $session;
	if ($session[admin] == 1)
		return true;
	else
		return false;
}

//------------------------------- Check if category exist or not
function check_category_exist($id){
	global $db;
	$result = $db->retrieve('category_id','category','category_id',$id);
	if($result)
	{
		return true;
	}
	else
	{
		return false;
	}
}

//------------------------------- Check if product exist or not
function check_product_exist($id){
	global $db;
	$result = $db->retrieve('product_id','product','product_id',$id);
	if($result)
	{
		return true;
	}
	else
	{
		return false;
	}
}

//------------------------------- Check if card exist or not
function check_card_exist($id){
	global $db;
	$result = $db->retrieve('card_id','card','card_id',$id);
	if($result)
	{
		return true;
	}
	else
	{
		return false;
	}
}

//------------------------------- Check if product exist or not
function check_payment_exist($id){
	global $db;
	$result = $db->retrieve('payment_id','payment','payment_id',$id);
	if($result)
	{
		return true;
	}
	else
	{
		return false;
	}
}

//---------------------- Create random chars Function------------------
function get_rand_id($length)
{
  if($length>0) 
  { 
	$rand_id="";
	for($i=1; $i<=$length; $i++)
	{
		mt_srand((double)microtime() * 1000000);
		$num = mt_rand(1,9);
		$rand_id .= $num;
	}
  }
	return $rand_id;
} 

//---------------------- Send mail Function------------------
function send_mail($from_email,$from_name,$to_email,$to_name,$subject,$body,$signature,$attachment=null) {
	require_once('libs/class.phpmailer.php');
	if ($signature)
		$signature = '
			<tr>
				<td style="background-color:#3a3a3a; padding:5px; direction:rtl; text-align:right; font-size: 10px; font-family:tahoma; color:#E0E0E0">'.$signature.'</td>
			</tr>';
	
	$mail_body = '
		<table style="margin-left:auto; margin-right:auto; width:80%; border:0px;">
			<tr>
				<td style="background-color:#3a3a3a; padding:5px; direction:rtl; text-align:right; font-size: 12px; font-family:tahoma; font-weight:bold; color:#E0E0E0">'.$from_name.'</td>
			</tr>
			<tr>
				<td style="background-color:#f5f5f5; padding:25px; border: 1px solid #c6c6c6; direction:rtl; text-align:right; font-size: 12px; font-family:tahoma; color:#3a3a3a">'.$body.'</td>
			</tr>'.$signature.'
		</table>';
	$mail = new PHPMailer(true); //defaults to using php "mail()"; the true param means it will throw exceptions on errors, which we need to catch
	try {
	  $mail->AddReplyTo($from_email, $from_name);
	  $mail->SetFrom($from_email, $from_name);
	  $mail->AddAddress($to_email, $to_name);
	  $mail->CharSet = 'UTF-8';
	  $mail->Subject = $subject;
	  $mail->AltBody = 'To view the message, please use an HTML compatible email viewer!'; // optional - MsgHTML will create an alternate automatically
	  $mail->MsgHTML($mail_body);
	  if ($attachment)
	  	$mail->AddAttachment($attachment);
	  $mail->Send();
	  return 1;
	} catch (phpmailerException $e) {
	  echo $e->errorMessage(); //Pretty error messages from PHPMailer
	} catch (Exception $e) {
	  echo $e->getMessage(); //Boring error messages from anything else!
	}
}
//---------------------- Get mellat error string Function------------------
function check_mellat_state_error($ResCode)
{
	switch($ResCode){
		case '0' :
			$prompt="&#1578;&#1585;&#1575;&#1705;&#1606;&#1588; &#1576;&#1575; &#1605;&#1608;&#1601;&#1602;&#1610;&#1578; &#1575;&#1606;&#1580;&#1575;&#1605; &#1588;&#1583;.";
			break;
		case '11' :
			$prompt="&#1588;&#1605;&#1575;&#1585;&#1607; &#1705;&#1575;&#1585;&#1578; &#1606;&#1575;&#1605;&#1593;&#1578;&#1576;&#1585; &#1575;&#1587;&#1578;.";
			break;
		case '12' :
			$prompt="&#1605;&#1608;&#1580;&#1608;&#1583;&#1610; &#1705;&#1575;&#1601;&#1610; &#1606;&#1610;&#1587;&#1578;.";
			break;
		case '13' :
			$prompt="&#1585;&#1605;&#1586; &#1606;&#1575;&#1583;&#1585;&#1587;&#1578; &#1575;&#1587;&#1578;.";
			break;
		case '14' :
			$prompt="&#1578;&#1593;&#1583;&#1575;&#1583; &#1583;&#1601;&#1593;&#1575;&#1578; &#1608;&#1575;&#1585;&#1583; &#1705;&#1585;&#1583;&#1606; &#1585;&#1605;&#1586; &#1662;&#1610;&#1588; &#1575;&#1586; &#1581;&#1583; &#1605;&#1580;&#1575;&#1586; &#1575;&#1587;&#1578;.";
			break;
		case '15' :
			$prompt="&#1705;&#1575;&#1585;&#1578; &#1606;&#1575;&#1605;&#1593;&#1578;&#1576;&#1585;&#1575;&#1587;&#1578;.";
			break;
		case '17' :
			$prompt="&#1705;&#1575;&#1585;&#1576;&#1585; &#1575;&#1586; &#1575;&#1606;&#1580;&#1575;&#1605; &#1578;&#1585;&#1575;&#1705;&#1606;&#1588; &#1605;&#1606;&#1589;&#1585;&#1601; &#1588;&#1583;&#1607; &#1575;&#1587;&#1578;.";
			break;
		case '18' :
			$prompt="&#1578;&#1575;&#1585;&#1610;&#1582; &#1575;&#1606;&#1602;&#1590;&#1575;&#1610; &#1705;&#1575;&#1585;&#1578; &#1711;&#1584;&#1588;&#1578;&#1607; &#1575;&#1587;&#1578;.";
			break;
		case '111' :
			$prompt="&#1589;&#1575;&#1583;&#1585;&#1705;&#1606;&#1606;&#1583;&#1607; &#1705;&#1575;&#1585;&#1578; &#1606;&#1575;&#1605;&#1593;&#1578;&#1576;&#1585; &#1575;&#1587;&#1578;.";
			break;
		case '112' :
			$prompt="&#1582;&#1591;&#1575;&#1610; &#1587;&#1608;&#1610;&#1610;&#1670; &#1589;&#1575;&#1583;&#1585;&#1705;&#1606;&#1606;&#1583;&#1607; &#1705;&#1575;&#1585;&#1578;.";
			break;
		case '113' :
			$prompt="&#1662;&#1575;&#1587;&#1582; &#1575;&#1586; &#1589;&#1575;&#1583;&#1585;&#1705;&#1606;&#1606;&#1583;&#1607; &#1705;&#1575;&#1585;&#1578; &#1583;&#1585;&#1610;&#1575;&#1601;&#1578; &#1606;&#1588;&#1583;.";
			break;
		case '114' :
			$prompt="&#1583;&#1575;&#1585;&#1606;&#1583;&#1607; &#1705;&#1575;&#1585;&#1578; &#1605;&#1580;&#1575;&#1586; &#1576;&#1607; &#1575;&#1606;&#1580;&#1575;&#1605; &#1575;&#1610;&#1606; &#1578;&#1585;&#1575;&#1705;&#1606;&#1588; &#1606;&#1610;&#1587;&#1578;.";
			break;
		case '21' :
			$prompt="&#1662;&#1584;&#1610;&#1585;&#1606;&#1583;&#1607; &#1606;&#1575;&#1605;&#1593;&#1578;&#1576;&#1585; &#1575;&#1587;&#1578;.";
			break;
		case '22' :
			$prompt="&#1578;&#1585;&#1605;&#1610;&#1606;&#1575;&#1604; &#1605;&#1580;&#1608;&#1586; &#1575;&#1585;&#1575;&#1574;&#1607; &#1587;&#1585;&#1608;&#1610;&#1587; &#1583;&#1585;&#1582;&#1608;&#1575;&#1587;&#1578;&#1610; &#1585;&#1575; &#1606;&#1583;&#1575;&#1585;&#1583;.";
			break;
		case '23' :
			$prompt="&#1582;&#1591;&#1575;&#1610; &#1575;&#1605;&#1606;&#1610;&#1578;&#1610; &#1585;&#1582; &#1583;&#1575;&#1583;&#1607; &#1575;&#1587;&#1578;.";
			break;
		case '24' :
			$prompt="&#1575;&#1591;&#1604;&#1575;&#1593;&#1575;&#1578; &#1705;&#1575;&#1585;&#1576;&#1585;&#1610; &#1662;&#1584;&#1610;&#1585;&#1606;&#1583;&#1607; &#1606;&#1575;&#1605;&#1593;&#1578;&#1576;&#1585; &#1575;&#1587;&#1578;.";
			break;
		case '25' :
			$prompt="&#1605;&#1576;&#1604;&#1594; &#1606;&#1575;&#1605;&#1593;&#1578;&#1576;&#1585; &#1575;&#1587;&#1578;.";
			break;
		case '31' :
			$prompt="&#1662;&#1575;&#1587;&#1582; &#1606;&#1575;&#1605;&#1593;&#1578;&#1576;&#1585; &#1575;&#1587;&#1578;.";
			break;
		case '32' :
			$prompt="&#1601;&#1585;&#1605;&#1578; &#1575;&#1591;&#1604;&#1575;&#1593;&#1575;&#1578; &#1608;&#1575;&#1585;&#1583; &#1588;&#1583;&#1607; &#1589;&#1581;&#1610;&#1581; &#1606;&#1610;&#1587;&#1578;.";
			break;
		case '33' :
			$prompt="&#1581;&#1587;&#1575;&#1576; &#1606;&#1575;&#1605;&#1593;&#1578;&#1576;&#1585; &#1575;&#1587;&#1578;.";
			break;
		case '34' :
			$prompt="&#1582;&#1591;&#1575;&#1610; &#1587;&#1610;&#1587;&#1578;&#1605;&#1610;.";
			break;
		case '35' :
			$prompt="&#1578;&#1575;&#1585;&#1610;&#1582; &#1606;&#1575;&#1605;&#1593;&#1578;&#1576;&#1585; &#1575;&#1587;&#1578;.";
			break;
		case '41' :
			$prompt="&#1588;&#1605;&#1575;&#1585;&#1607; &#1583;&#1585;&#1582;&#1608;&#1575;&#1587;&#1578; &#1578;&#1705;&#1585;&#1575;&#1585;&#1610; &#1575;&#1587;&#1578;.";
			break;
		case '42' :
			$prompt="&#1578;&#1585;&#1575;&#1705;&#1606;&#1588; sale &#1610;&#1575;&#1601;&#1578; &#1606;&#1588;&#1583;.";
			break;
		case '43' :
			$prompt="&#1602;&#1576;&#1604;&#1575; &#1583;&#1585;&#1582;&#1608;&#1575;&#1587;&#1578; verify &#1583;&#1575;&#1583;&#1607; &#1588;&#1583;&#1607; &#1575;&#1587;&#1578;.";
			break;
		case '44' :
			$prompt="&#1583;&#1585;&#1582;&#1608;&#1575;&#1587;&#1578; verify  &#1610;&#1575;&#1601;&#1578; &#1606;&#1588;&#1583;.";
			break;
		case '45' :
			$prompt="&#1578;&#1585;&#1575;&#1705;&#1606;&#1588; settle &#1588;&#1583;&#1607; &#1575;&#1587;&#1578;.";
			break;
		case '46' :
			$prompt="&#1578;&#1585;&#1575;&#1705;&#1606;&#1588; settle &#1606;&#1588;&#1583;&#1607; &#1575;&#1587;&#1578;.";
			break;
		case '47' :
			$prompt="&#1578;&#1585;&#1575;&#1705;&#1606;&#1588; settle &#1610;&#1575;&#1601;&#1578; &#1606;&#1588;&#1583;.";
			break;
		case '48' :
			$prompt="&#1578;&#1585;&#1575;&#1705;&#1606;&#1588; reverse &#1588;&#1583;&#1607; &#1575;&#1587;&#1578;.";
			break;
		case '49' :
			$prompt="&#1578;&#1585;&#1575;&#1705;&#1606;&#1588; refund &#1610;&#1575;&#1601;&#1578; &#1606;&#1588;&#1583;.";
			break;
		case '412' :
			$prompt="&#1588;&#1606;&#1575;&#1587;&#1607; &#1602;&#1576;&#1590; &#1606;&#1575;&#1583;&#1585;&#1587;&#1578; &#1575;&#1587;&#1578;.";
			break;
		case '413' :
			$prompt="&#1588;&#1606;&#1575;&#1587;&#1607; &#1662;&#1585;&#1583;&#1575;&#1582;&#1578; &#1606;&#1575;&#1583;&#1585;&#1587;&#1578; &#1575;&#1587;&#1578;.";
			break;
		case '414' :
			$prompt="&#1587;&#1575;&#1586;&#1605;&#1575;&#1606; &#1589;&#1575;&#1583;&#1585;&#1705;&#1606;&#1606;&#1583;&#1607; &#1602;&#1576;&#1590; &#1606;&#1575;&#1605;&#1593;&#1578;&#1576;&#1585; &#1575;&#1587;&#1578;.";
			break;
		case '415' :
			$prompt="&#1586;&#1605;&#1575;&#1606; &#1580;&#1604;&#1587;&#1607; &#1705;&#1575;&#1585;&#1610; &#1576;&#1607; &#1662;&#1575;&#1610;&#1575;&#1606; &#1585;&#1587;&#1610;&#1583;&#1607; &#1575;&#1587;&#1578;.";
			break;
		case '416' :
			$prompt="&#1582;&#1591;&#1575; &#1583;&#1585; &#1579;&#1576;&#1578; &#1575;&#1591;&#1604;&#1575;&#1593;&#1575;&#1578;.";
			break;
		case '417' :
			$prompt="&#1588;&#1606;&#1575;&#1587;&#1607; &#1662;&#1585;&#1583;&#1575;&#1582;&#1578; &#1705;&#1606;&#1606;&#1583;&#1607; &#1606;&#1575;&#1605;&#1593;&#1578;&#1576;&#1585;&#1575;&#1587;&#1578;.";
			break;
		case '418' :
			$prompt="&#1575;&#1588;&#1705;&#1575;&#1604; &#1583;&#1585; &#1578;&#1593;&#1585;&#1610;&#1601; &#1575;&#1591;&#1604;&#1575;&#1593;&#1575;&#1578; &#1605;&#1588;&#1578;&#1585;&#1610;.";
			break;
		case '419' :
			$prompt="&#1578;&#1593;&#1583;&#1575;&#1583; &#1583;&#1601;&#1593;&#1575;&#1578; &#1608;&#1585;&#1608;&#1583; &#1575;&#1591;&#1604;&#1575;&#1593;&#1575;&#1578; &#1575;&#1586; &#1581;&#1583; &#1605;&#1580;&#1575;&#1586; &#1711;&#1584;&#1588;&#1578;&#1607; &#1575;&#1587;&#1578;.";
			break;
		case '421' :
			$prompt="IP &#1606;&#1575;&#1605;&#1593;&#1578;&#1576;&#1585; &#1575;&#1587;&#1578;.";
			break;
		case '51' :
			$prompt="&#1578;&#1585;&#1575;&#1705;&#1606;&#1588; &#1578;&#1705;&#1585;&#1575;&#1585;&#1610; &#1575;&#1587;&#1578;.";
			break;
		case '52' :
			$prompt="&#1587;&#1585;&#1608;&#1610;&#1587; &#1583;&#1585;&#1582;&#1608;&#1575;&#1587;&#1578;&#1610; &#1605;&#1608;&#1580;&#1608;&#1583; &#1606;&#1605;&#1610; &#1576;&#1575;&#1588;&#1583;.";
			break;
		case '54' :
			$prompt="&#1578;&#1585;&#1575;&#1705;&#1606;&#1588; &#1605;&#1585;&#1580;&#1593; &#1605;&#1608;&#1580;&#1608;&#1583; &#1606;&#1610;&#1587;&#1578;.";
			break;
		case '55' :
			$prompt="&#1578;&#1585;&#1575;&#1705;&#1606;&#1588; &#1606;&#1575;&#1605;&#1593;&#1578;&#1576;&#1585; &#1575;&#1587;&#1578;.";
			break;
		case '61' :
			$prompt="&#1582;&#1591;&#1575; &#1583;&#1585; &#1608;&#1575;&#1585;&#1610;&#1586;.";
			break;
		DEFAULT :
			$prompt="&#1582;&#1591;&#1575;&#1610; &#1606;&#1575;&#1605;&#1588;&#1582;&#1589;.";
			break;
	}
	return  '&#1705;&#1583; ' . $ResCode .' : '. $prompt;
}
//---------------------- xml2array Function------------------
function xml2array($contents, $get_attributes=1, $priority = 'tag') {
    if(!$contents) return array();

    if(!function_exists('xml_parser_create')) {
        //print "'xml_parser_create()' function not found!";
        return array();
    }

    //Get the XML parser of PHP - PHP must have this module for the parser to work
    $parser = xml_parser_create('');
    xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, "UTF-8"); # http://minutillo.com/steve/weblog/2004/6/17/php-xml-and-character-encodings-a-tale-of-sadness-rage-and-data-loss
    xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
    xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
    xml_parse_into_struct($parser, trim($contents), $xml_values);
    xml_parser_free($parser);

    if(!$xml_values) return;//Hmm...

    //Initializations
    $xml_array = array();
    $parents = array();
    $opened_tags = array();
    $arr = array();

    $current = &$xml_array; //Refference

    //Go through the tags.
    $repeated_tag_index = array();//Multiple tags with same name will be turned into an array
    foreach($xml_values as $data) {
        unset($attributes,$value);//Remove existing values, or there will be trouble

        //This command will extract these variables into the foreach scope
        // tag(string), type(string), level(int), attributes(array).
        extract($data);//We could use the array by itself, but this cooler.

        $result = array();
        $attributes_data = array();
        
        if(isset($value)) {
            if($priority == 'tag') $result = $value;
            else $result['value'] = $value; //Put the value in a assoc array if we are in the 'Attribute' mode
        }

        //Set the attributes too.
        if(isset($attributes) and $get_attributes) {
            foreach($attributes as $attr => $val) {
                if($priority == 'tag') $attributes_data[$attr] = $val;
                else $result['attr'][$attr] = $val; //Set all the attributes in a array called 'attr'
            }
        }

        //See tag status and do the needed.
        if($type == "open") {//The starting of the tag '<tag>'
            $parent[$level-1] = &$current;
            if(!is_array($current) or (!in_array($tag, array_keys($current)))) { //Insert New tag
                $current[$tag] = $result;
                if($attributes_data) $current[$tag. '_attr'] = $attributes_data;
                $repeated_tag_index[$tag.'_'.$level] = 1;

                $current = &$current[$tag];

            } else { //There was another element with the same tag name

                if(isset($current[$tag][0])) {//If there is a 0th element it is already an array
                    $current[$tag][$repeated_tag_index[$tag.'_'.$level]] = $result;
                    $repeated_tag_index[$tag.'_'.$level]++;
                } else {//This section will make the value an array if multiple tags with the same name appear together
                    $current[$tag] = array($current[$tag],$result);//This will combine the existing item and the new item together to make an array
                    $repeated_tag_index[$tag.'_'.$level] = 2;
                    
                    if(isset($current[$tag.'_attr'])) { //The attribute of the last(0th) tag must be moved as well
                        $current[$tag]['0_attr'] = $current[$tag.'_attr'];
                        unset($current[$tag.'_attr']);
                    }

                }
                $last_item_index = $repeated_tag_index[$tag.'_'.$level]-1;
                $current = &$current[$tag][$last_item_index];
            }

        } elseif($type == "complete") { //Tags that ends in 1 line '<tag />'
            //See if the key is already taken.
            if(!isset($current[$tag])) { //New Key
                $current[$tag] = $result;
                $repeated_tag_index[$tag.'_'.$level] = 1;
                if($priority == 'tag' and $attributes_data) $current[$tag. '_attr'] = $attributes_data;

            } else { //If taken, put all things inside a list(array)
                if(isset($current[$tag][0]) and is_array($current[$tag])) {//If it is already an array...

                    // ...push the new element into that array.
                    $current[$tag][$repeated_tag_index[$tag.'_'.$level]] = $result;
                    
                    if($priority == 'tag' and $get_attributes and $attributes_data) {
                        $current[$tag][$repeated_tag_index[$tag.'_'.$level] . '_attr'] = $attributes_data;
                    }
                    $repeated_tag_index[$tag.'_'.$level]++;

                } else { //If it is not an array...
                    $current[$tag] = array($current[$tag],$result); //...Make it an array using using the existing value and the new value
                    $repeated_tag_index[$tag.'_'.$level] = 1;
                    if($priority == 'tag' and $get_attributes) {
                        if(isset($current[$tag.'_attr'])) { //The attribute of the last(0th) tag must be moved as well
                            
                            $current[$tag]['0_attr'] = $current[$tag.'_attr'];
                            unset($current[$tag.'_attr']);
                        }
                        
                        if($attributes_data) {
                            $current[$tag][$repeated_tag_index[$tag.'_'.$level] . '_attr'] = $attributes_data;
                        }
                    }
                    $repeated_tag_index[$tag.'_'.$level]++; //0 and 1 index is already taken
                }
            }

        } elseif($type == 'close') { //End of tag '</tag>'
            $current = &$parent[$level-1];
        }
    }
    
    return($xml_array);
}