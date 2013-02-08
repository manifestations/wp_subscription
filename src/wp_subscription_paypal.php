<?php
$req = 'cmd=_notify-validate';
foreach($_POST as $key => $value) {
	$value = urlencode(stripslashes($value));
	$req .= "&$key=$value";
}

include_once($_SERVER['DOCUMENT_ROOT'].'/wp-load.php');

$paypal_url = "www.paypal.com";
if(get_option('wps_paypal_testing')=='yes') {
	$paypal_url = "www.sandbox.paypal.com";
}

$header .= "POST /cgi-bin/webscr HTTP/1.0\r\n";
$header .= "Host: $paypal_url\r\n";
$header .= "Content-Type: application/x-www-form-urlencoded\r\n";
$header .= "Content-Length: " . strlen($req) . "\r\n\r\n";
$fp = fsockopen ('ssl://' . $paypal_url, 443, $errno, $errstr, 30);

if(!$fp) {
	// HTTP ERROR
} else {
	fputs ($fp, $header . $req);
	while(!feof($fp)) {
		$res = fgets ($fp, 1024);
		
		$fh = fopen('wps_paypal_txn.txt', 'w');
		fwrite($fh, $res);
		fclose($fh);
		
		if (strcmp ($res, "VERIFIED") == 0) {
						
			$billing_info = array(
				"billing_first_name" => $_POST['first_name'],
				"billing_last_name" => $_POST['last_name'],
				"billing_email" => $_POST['payer_email'],
				"billing_address_1" => $_POST['address_street'],
				"billing_postcode" => $_POST['address_zip'],
				"billing_city" => $_POST['address_city'],
				"billing_state" => $_POST['address_state'],
				"billing_country" => $_POST['address_country']
			);
			
			$txn_id = $_POST['txn_id'];
			$_user_id = intval($_POST['custom']);
			
			update_user_meta($_user_id, 'paying_customer', true);
			update_user_meta($_user_id, 'wps-enabled', '1');
			update_user_meta($_user_id, 'wps-registered-time', time());
			update_user_meta($_user_id, 'wps-last-used', '0');
			update_user_meta($_user_id, 'wps-txn-id', $txn_id);
			
			foreach($billing_info as $key => $value) {
				update_user_meta($_user_id, $key, $value);
			}
			
			$_user = get_userdata($_user_id);
			$_expiry = date("m/d/Y", strtotime("+12 months", time()));
			$_discount = get_option('wps_discount_rate');
			$_fee = number_format(get_option('wps_fee'), 2);
			$_blogname = get_bloginfo('name');
			$_title = get_option('wps_page_title');
			$_terms = get_option('wps_terms');
			$_subject = "$_title at $_blogname";
			$_message =  "You have successfully registered to $_title at $_blogname \n\n";
			$_message =  "Your subscription will expire on $_expiry \n\n";
			$_message .= "Thank you for chosing $_blogname \n\n";
			$_message .= "Terms and Conditions \n\n";
			$_message .= "$_terms\n";
			wp_mail($_user->user_email, $_subject, $_message);			
		} else if(strcmp ($res, "INVALID") == 0) { 
			update_user_meta($_user_id, 'wps-enabled', '2');
		}
	}
	fclose($fp);
}
?>