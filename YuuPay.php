<?php
$nzshpcrt_gateways[$num]['name'] = 'YuuPay';
//$nzshpcrt_gateways[$num]['admin_name'] = 'YuuPay Payment Gateway';
$nzshpcrt_gateways[$num]['internalname'] = 'yuupay';
$nzshpcrt_gateways[$num]['function'] = 'yuupay_function';
$nzshpcrt_gateways[$num]['form'] = "form_yuupay";
$nzshpcrt_gateways[$num]['submit_function'] = "submit_yuupay";
$nzshpcrt_gateways[$num]['payment_type'] = "YuuPay";



function wpsc_yuupay_checkout_page()
{
	global $wpsc_cart,$wpdb,$wpsc_gateway;
	
	//getData
	$options = $_POST['custom_gateway'];
	$confirm = $_SESSION['confirmOrder'];	
	$yuupay_action = $_POST['yuupay_action'];
	$checkOtp = $_POST['enable4gs'];	
	if($yuupay_action == 'submit_payment')
	{
		$valid = "true";
		$currency_data = $wpdb->get_row("SELECT `symbol`,`symbol_html`,`code` FROM `".WPSC_TABLE_CURRENCY_LIST."` WHERE `id`='".get_option('currency_type')."' LIMIT 1",ARRAY_A) ;
		
		$action = $_POST['action'];
		$acctid = get_option('yuupay_MID');
		$amount = number_format($wpsc_cart->calculate_total_price(),2);
		$currencycode= $currency_data['code'];
		$ccname = $_POST['ccname'];
		$ccnum = $_POST['ccnum']; 
		$cvv2 = $_POST['cvv2'];
		$expmon = $_POST['expmon'];
		$expyear = $_POST['expyear'];
		$valid = validCreditCard($ccname,$ccnum,$cvv2,$expmon,$expyear);
		$postvar = "action=$action&acctid=$acctid&amount=$amount&amp;currencycode=$currencycode&ccname=$ccname&ccnum=$ccnum&cvv2=$cvv2&expmon=$expmon&expyear=$expyear";
		$url = get_option('yuupay_host').'/cgi-bin/process.cgi';
	}
	
	
	$form4gs = '<tr><td colspan="2">
				Enable 4G Secure &nbsp; <input id="enable4gs" type="checkbox" name="enable4gs" value=1';
	if($checkOtp == 1) $form4gs .= ' checked ';
	$form4gs .= '> &nbsp; <a href="http://www.4gsecure.fr/en/index.php" target="_new" style="color:blue;"><u>What is 4G Secure?</u></a>
				</td></tr>
				<tr><td colspan="2">
					<table id="4gs" ';
	if($checkOtp != 1) $form4gs .= 'style="display:none"';
	$form4gs .= '>
					<tr>
						<td>Login</td><td><input type="text" name="otp_login" value="demo2@localhost"  /></td>
					</tr>
					<tr>
						<td>Password</td><td><input type="password" name="otp_password" value="demodemo" /></td>
					</tr>
					<tr>
						<td>OTP</td><td><input type="text" name="devPass" value="" maxlength="6"/></td>
					</tr>
					</table>
				</td></tr>';
	
	$year = date('Y');
	$paymentform = ' 
	Yuupay Credit Card Payment
	<form action="" method="post" enctype="multipart/form-data">	
	<table border="0" id="ccform">		
		<tr>
			<td>Name of credit card holder:</td>
			<td><input id="ccname" name="ccname" type="text" value="'.$ccname.'"/></td>

		</tr>
		<tr>
			<td>Credit Card Number:</td>
			<td><input id="ccnum" name="ccnum" type="text" length="30" value="'.$ccnum.'"/></td>
		</tr>
		<tr>
			<td>CVV:</td>
			<td><input id="cvv2" name="cvv2" size="3" type="text" value="'.$cvv2.'"/></td>
		</tr>
		<tr>
            <td>Expiration month:</td>
            <td><select class="inputbox" id="expmon" name="expmon" size="1">';
	$monthList = array('January','February','March','April','May','June','July','August','September','October','November','December' );
	for($i=0;$i<12;$i++)
	{
		if(($i+1) < 10)
			$value = '0'.strval($i+1);
		else
			$value = strval($i+1);

		if($value == $expmon)
			$paymentform .= "<option value='$value' SELECTED>$monthList[$i]</option>";
		else
			$paymentform .= "<option value='$value'>$monthList[$i]</option>";
	}	
         $paymentform .=   '
            </select></td>
        </tr>
        <tr>
            <td>Expiration year:</td>
            <td><select class="inputbox" id="expyear" name="expyear" size="1">';	
 	for($i=0;$i<10;$i++)
 	{
    	$value = $year+$i;
    	if($value == $expyear)
    		$paymentform .= "<option value='$value' SELECTED>$value</option>";
    	else
			$paymentform .= "<option value='$value'>$value</option>";
 	}
 	$paymentform .= '
            	</select>
            </td>
        </tr> '; 
 	if(get_option('enable4gs') == 'TRUE') $paymentform .= $form4gs;
    $paymentform .= '<tr>
        	<td></td>
        	<td>
        		<input type="hidden" value="yes" name="agree" />   	
        		<input type="hidden" value="submit_payment" name="yuupay_action" />
        		<input id="action" name="action" value="ns_quicksale_cc"  type="hidden" /> 
				<input type="submit" value="Make Purchase" name="submit" id="submit" class="make_purchase" />       	
        	</td>
        </tr>
        <tr><td colspan="2">&nbsp;</td></tr>
	</table>
	</form>
	';

 	$script = "<script type='text/javascript'>
	 				jQuery(document).ready(
  						function()
 						 {
	 						jQuery('div#wpsc_shopping_cart_container h2').hide();
	 						jQuery('div#wpsc_shopping_cart_container .wpsc_cart_shipping').hide();
 							jQuery('.wpsc_checkout_forms').hide();
 							jQuery('#ccname').focus();
 							jQuery('#enable4gs').change(function() {
 								if (jQuery('#enable4gs:checked').val() !== undefined)
  								{	jQuery('#4gs').show('slow'); }
  								else
  								{   jQuery('#4gs').hide('slow'); }
 							}
 							
							);
							
							//jQuery('#4gs').hide();
	 					});
	 			</script>";

	
	if($yuupay_action == 'submit_payment')
	{
		if($valid != "true")
		{
			$checkPayment = 0;
			echo $script;
			echo $valid."<br />";
			echo $paymentform;
		}
		else
			$checkPayment = 1;
			
		if($checkOtp == 1)
		{
			$otp_login = $_POST['otp_login'];
			$otp_password = $_POST['otp_password'];
			$devPass = $_POST['devPass'];

			$checkPayment = 0;
			require_once('yuupay_lib/JSON.php');
	
			$otp_url = "http://4gsecure.dyndns.org:8080/meepass-web-services-1.0-SNAPSHOT/identify.htm";
			$ip = urlencode($_SERVER['REMOTE_ADDR']);
			$formdata_otp = "login=$otp_login&password=$otp_password&devPass=$devPass&loginType=deprecated&resType=json&ip=$ip";
			$response_otp = fgc($otp_url, $formdata_otp);
	
			$json = new Services_JSON();
			$result_otp = $json->decode($response_otp);
			if(intval($result_otp->code )!= 0)
			{
				//failed on OTP.
				echo $result_otp->message ."<br />";
				echo $script;
				echo $paymentform;
			}
			else
				$checkPayment = 1;	
		}
		
		if($checkPayment == 1)
		{
			$response = fgc($url,$postvar);
			$result = parseYuuPayResponse($response);
			//print_r($result);
			if($result['result'] == 1) //success
			{
				$_SESSION['confirmOrder'] = "";
				$id = $_SESSION['purchaseId'];
				$_SESSION['purchaseId'] = "";
				$wpdb->update( 'wp_wpsc_purchase_logs', array( 'processed' => '2'), array( 'id' => $id), null, null );
				echo "Payment success";
				echo '<script type="text/javascript"> window.location="';
				echo $confirm;
				echo '"</script>';
			}//redirect 
			else //not success
			{
				$reason =$result['Reason'];
				echo "PAYMENT UNSUCCESFUL<br /> $reason <br />";
				echo $script;
				echo $paymentform;
				
			}
		}	
	}
	else
	{	
		$errorMessage = $_SESSION['wpsc_checkout_error_messages'];
	 	if($options == 'yuupay' && count($errorMessage) == 0){
		 	unset($_SESSION['gateway']);
			echo $script;
			echo $paymentform;
	 	}
	}
	
}
add_action('wpsc_before_form_of_shopping_cart', 'wpsc_yuupay_checkout_page');


function result_comment()
{
	echo 'The bill descriptor on your credit card statement will be <b>"Yuupay Services, Las Vegas, NV"</b><br/><br/>';//exit();
}
add_action('wpsc_confirm_checkout', 'result_comment');




function yuupay_function($seperator, $sessionid) {
	global $wpdb;
  $transact_url = get_option('transact_url');
  //$_SESSION['nzshpcrt_cart'] = null;
  //$_SESSION['nzshpcrt_serialized_cart'] = null;
  //exit($transact_url.$seperator."sessionid=".$sessionid);
  //header("Location: ".$transact_url.$seperator."sessionid=".$sessionid);
  
  $id =$wpdb->get_var('SELECT max(id) FROM wp_wpsc_purchase_logs WHERE sessionid="'.$sessionid.'"');
  $_SESSION['purchaseId'] = $id;
  //echo $temp . "<br />" . $sessionid; exit();
  $_SESSION['confirmOrder'] = $transact_url.$seperator."sessionid=".$sessionid;
  $_SESSION['wpsc_sessionid'] = "";
}

function submit_yuupay() {
	//submit in admin page
	if($_POST['yuupay_MID'] != null)
    {
    	update_option('yuupay_MID', $_POST['yuupay_MID']);
    }
	if($_POST['yuupay_host'] != null)
    {
    	update_option('yuupay_host', $_POST['yuupay_host']);
    }
	if($_POST['enable4gs'] != null)
    {
    	update_option('enable4gs', $_POST['enable4gs']);
    }
    else
    {
    	update_option('enable4gs', 'FALSE');
    }
  return true;
}

function form_yuupay() { 
	if (get_option('yuupay_MID') == null | get_option('yuupay_MID') == "") 
		 update_option('yuupay_MID', 'TEST0');
	if (get_option('yuupay_host') == null | get_option('yuupay_host') == "") 
		 update_option('yuupay_host', 'https://trans.yuupay.com');
	if (get_option('enable4gs') == null | get_option('enable4gs') == "") 
		 update_option('enable4gs', 'FALSE');
	if(get_option('enable4gs') == 'TRUE') $checked = 'checked';
		 
	$output = "<tr>\n\r";
	$output .= "	<td>\n\rYuupay MID : ";
	$output .= "	</td>\n\r";
	$output .= "	<td>\n\r<input type='text' name='yuupay_MID' value=".get_option('yuupay_MID')." />";
	$output .= "	</td>\n\r";
	$output .= "</tr>\n\r";
	$output .= "<tr>\n\r";
	$output .= "	<td>\n\rYuupay Server Host : ";
	$output .= "	</td>\n\r";
	$output .= "	<td>\n\r<input type='text' name='yuupay_host' value=".get_option('yuupay_host')." />";
	$output .= "	</td>\n\r";
	$output .= "</tr>\n\r";
	$output .= "<tr>\n\r";
	$output .= "	<td>\n\rEnable 4G Secure  ";
	$output .= "	</td>\n\r";
	$output .= "	<td>\n\r<input type='checkbox' name='enable4gs' value='TRUE'". $checked ."  /> &nbsp; <a href='http://www.4gsecure.fr/en/index.php' target='_new' style='color:blue;'><u>What is 4G Secure?</u></a>";
	$output .= "	</td>\n\r";
	$output .= "</tr>\n\r";
  return $output;
}

function fgc($url, $postvar = null) 
{
	//example $postvar="option=com_content&task=blogcategory&id=24&Itemid=55";
	$ch = curl_init();
	           
	curl_setopt ($ch, CURLOPT_URL, $url);
	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
				
	curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 60); // 60 seconds
	           
	//attach post
	if($postvar){
	   	curl_setopt ($ch, CURLOPT_POST, 1);
	  	curl_setopt ($ch, CURLOPT_POSTFIELDS, $postvar);
	}
	           
	//add ssl
	if(stripos($url,'https')===0){
	   curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 2);
	   curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	}
	           
	$output = curl_exec ($ch);
	curl_close ($ch);
	           
	return $output;
}
     
 //parse the http payment response from yuupay.com
function parseYuuPayResponse($str)
{
	$result = array();
	if(strlen($str)<=0) return $result;
	$temp = explode("\n",$str);
	           
	for($i=0, $n=count($temp); $i<$n; $i++){
		$line = $temp[$i];
	    $tokens = explode("=", $line);
	  	if(count($tokens)!=2) continue;
	  	$result[$tokens[0]] = trim($tokens[1]);
	}
	           
	return $result;
}

function validCreditCard($ccname,$ccnum,$cvv2,$expmon,$expyear)
{
	$year = date('Y');
	$month = date('m');
	if($ccname == "")
		return "Credit Card Name is Empty";
	else if($ccnum == "")
		return "Credit Card Number is Empty";
	else if($cvv2 == "")
		return "CVV is Empty";
	else if($expyear == $year)
	{
		if($expmon < $month)
			return "Invalid Expired Date";
	}
	return "true";
}

?>
