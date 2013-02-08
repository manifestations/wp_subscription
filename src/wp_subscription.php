<?php
	/*
	Plugin Name: Wordpress Subscription
	Plugin URI: http://meita.in
	Description: Plugin for Wordpress Subscription with PayPal 
	Author: wibby
	Version: 1.0
	Author URI: http://meita.in
	*/

$wps_errors = new WP_Error();

function wps_install() {
	global $wpdb;

	$the_page_title = 'Wordpress Subscription';
	$the_page_name = 'wp-subscription';

	delete_option("wps_page_title");
	add_option("wps_page_title", $the_page_title, '', 'yes');

	delete_option("wps_page_name");
	add_option("wps_page_name", $the_page_name, '', 'yes');

	delete_option("wps_page_id");
	add_option("wps_page_id", '0', '', 'yes');

	$the_page = get_page_by_title( $the_page_title );

	if ( ! $the_page ) {
		$_p = array();
		$_p['post_title'] = $the_page_title;
		$_p['post_content'] = "[wps-hook]";
		$_p['post_status'] = 'publish';
		$_p['post_type'] = 'page';
		$_p['comment_status'] = 'closed';
		$_p['ping_status'] = 'closed';
		$_p['post_category'] = array(1);
		$the_page_id = wp_insert_post( $_p );
	}
	else {
		$the_page_id = $the_page->ID;
		$the_page->post_status = 'publish';
		$the_page_id = wp_update_post( $the_page );
	}
	delete_option( 'wps_page_id' );
	add_option( 'wps_page_id', $the_page_id );
}

function wps_remove() {
    global $wpdb;

    $the_page_title = get_option( "wps_page_title" );
    $the_page_name = get_option( "wps_page_name" );

    $the_page_id = get_option( 'wps_page_id' );
    if( $the_page_id ) {
        wp_delete_post( $the_page_id );
    }
	
    delete_option("wps_page_title");
    delete_option("wps_page_name");
    delete_option("wps_page_id");
}

function wps_settings() {
	register_setting('wps-group', 'wps_page_title');
    register_setting('wps-group', 'wps_paypal_id');
    register_setting('wps-group', 'wps_fee', 'intval');
    register_setting('wps-group', 'wps_recaptcha_public');
    register_setting('wps-group', 'wps_recaptcha_private');
    register_setting('wps-group', 'wps_paypal_testing');
    register_setting('wps-group', 'wps_discount_rate', 'intval');
}

function wps_query_parser( $q ) {

	$the_page_name = get_option( "wps_page_name" );
	$the_page_id = get_option( 'wps_page_id' );

	$qv = $q->query_vars;

	if(!empty($q->query_vars['page_id']) AND (intval($q->query_vars['page_id']) == $this->page_id )) {
		$q->set('wps_page_is_called', TRUE );
		return $q;
	}
	elseif(isset($q->query_vars['pagename']) AND (($q->query_vars['pagename'] == $the_page_name) OR ($_pos_found = strpos($q->query_vars['pagename'],$the_page_name.'/') === 0))) {
		$q->set('wps_page_is_called', TRUE );
		return $q;
	}
	else {
		$q->set('wps_page_is_called', FALSE);
		return $q;
	}
}

function wps_admin() {
	include_once('wps_admin.php');  
}
require_once('wps_utils.php');

function wps_admin_actions() {
	add_menu_page('T-Shirt of the Month', 'T-Shirt of the Month Settings', 'administrator', __FILE__, 'wps_admin');
}

function wps_register($atts) {
	include_once('wps_register.php');
}

if(is_admin()) {
	register_activation_hook(__FILE__,'wps_install'); 
	register_deactivation_hook( __FILE__, 'wps_remove' );
	add_filter('parse_query', 'wps_query_parser');
	add_action('admin_menu', 'wps_admin_actions');
	add_action('admin_init', 'wps_settings');
}

function wps_login() {
	global $wps_errors;
	global $current_user;
	if($_POST['wps-login']) {
		$creds = array();
		$creds['user_login'] = isset($_POST['wps-username'])?esc_attr(trim($_POST['wps-username'])):'';
		$creds['user_password'] = isset($_POST['wps-password'])?esc_attr(trim($_POST['wps-password'])):'';
		$creds['remember'] = true;
		$user = wp_signon( $creds, false );
		if($user) {
			if (isset($_POST['redirect']) && $_POST['redirect']) {
				wp_safe_redirect(esc_attr($_POST['redirect']));
				exit;
			}
			if (wp_get_referer()) {
				wp_safe_redirect( wp_get_referer() );
				exit;
			}			
			wp_redirect(get_permalink(get_option('wps_page_id')));
			exit;
		}
		else $wps_errors->add('wps-error', "Invalid User ID or Password");
	}
}

add_action('init', 'wps_login', 1);

function wps_registration() {
	global $wps_errors;
	global $current_user;
	if($_POST['wps-register']) {
		if(wps_recaptcha_valid()) {
			$user_email = isset($_POST['wps-email'])?esc_attr(trim($_POST['wps-email'])):'';
			$password = isset($_POST['wps-password'])?esc_attr(trim($_POST['wps-password'])):'';
			$password2 = isset($_POST['wps-password2'])?esc_attr(trim($_POST['wps-password2'])):'';
			if(filter_var($user_email, FILTER_VALIDATE_EMAIL) && $password != '' && $password == $password2) {
				if (!username_exists($user_email) && !email_exists($user_email)) {
					$user_id = wp_create_user($user_email, $password, $user_email);
					if($user_id) {
						wp_update_user(array ('ID' => $user_id, 'role' => 'customer')) ;
						$creds = array();
						$creds['user_login'] = $user_email;
						$creds['user_password'] = $password;
						$creds['remember'] = true;
						$user = wp_signon( $creds, false );
						$_blogname = get_bloginfo('name');
						$_title = get_option('wps_page_title');
						$_subject = "Registration to $_title at $_blogname";					
						$_message =  "$_blogname Registration Successful!\n\n";
						$_message .= "You are now successfully registered to $_blogname \n\n";
						$_message .= "Here are you login details:\n\n";
						$_message .= "User Name: $user_email \n";
						$_message .= "Password: $password \n\n";
						$_message .= "Please keep this information safe.\n\n";
						$_message .= "Thank you for chosing $_blogname \n\n";
						wp_mail($user_email, $_subject, $_message);;
						wp_redirect(get_permalink(get_option('wps_page_id')));
						exit;
					} else {
						$wps_errors->add('wps-error', "Unable to register at this time.  Please try again later");
					}
				} else {
					$wps_errors->add('wps-error', "User with the email id " . $user_email . " already exists in our system.  Please use this link if you have forgotten your password :<a class='lost_password' href='" . esc_url(wp_lostpassword_url(home_url())) . "'>Lost Password</a>");
				}
			} else {
				$wps_errors->add('wps-error', "Invalid Email Address or Passwords do not match. Please provide a correct email address and and matching passwords to proceed with the registration.");
			}
		} else {
			$wps_errors->add('wps-error', "Incorrect reCaptcha code entered. Please try again.");
		}
	}
}

add_action('init', 'wps_registration', 1);

function wps_recaptcha_valid() {
	$_wps_recaptcha_public = get_option('wps_recaptcha_public');
	$_wps_recaptcha_private = get_option('wps_recaptcha_private');
	if($_wps_recaptcha_public && $_wps_recaptcha_private) {
		require_once('recaptchalib.php');
		$resp = recaptcha_check_answer($_wps_recaptcha_private,
			$_SERVER["REMOTE_ADDR"],
			$_POST["recaptcha_challenge_field"],
			$_POST["recaptcha_response_field"]);
		if($resp->is_valid) return true;
		else return false;
	} else {
		return true;
	}
}

function wps_paypal() {
	global $wps_errors;
	$_user_id = intval($_POST['custom']);
	if($_POST['item_number'] == 'wps' && $_POST['payment_status'] == 'Completed' && !get_user_meta($_user_id, "wps-enabled", true)) {
		update_user_meta($_user_id, 'paying_customer', true);
		update_user_meta($_user_id, 'wps-enabled', '1');
		update_user_meta($_user_id, 'wps-registered-time', time());
		update_user_meta($_user_id, 'wps-last-used', '0');
		update_user_meta($_user_id, 'wps-txn-id', $_POST['txn_id']);		
	}
}

add_action('init', 'wps_paypal', 1);

function wps_unsubscribe() {
	global $wps_errors;
	global $current_user;
	
	$_user_id = $current_user->ID;
	
	get_currentuserinfo();
	if($_POST['wps-unsubscribe']) {
		update_user_meta($_user_id, 'wps-enabled', '');
		$_expiry = date("m/d/Y", strtotime("+12 months", time()));
		$_blogname = get_bloginfo('name');
		$_title = get_option('wps_page_title');
		$_subject = "Shirt of the Month Club at $_blogname";
		$_message =  "You have been successfully unregistered from $_title at $_blogname \n\n";
		$_message =  "Please contact the site admin if this happened by error.\n\n";
		$_message .= "Thank you for chosing $_blogname \n\n";
		$_message .= "Terms and Conditions \n\n";
		$_message .= "1. Shirt of the month club is for 12 months.  Participants will recieve a 25% discount and free shipping on one shirt per month.\n";
		$_message .= "2. Participant can stop participating at any time but the $20 sign up fee is non-refundable.\n";
		$_message .= "3. Cannot be combined with other coupons or discounts.";
		wp_mail($current_user->user_email, $_subject, $_message);		
		$wps_errors->add('wps-error', "You have been unsubscribed from the Shirt of the Month Club.  Contact the site admin if this happened by mistake.");
	}		
}

add_action('init', 'wps_unsubscribe', 1);

function wps_add_enabled_column($column) {
	$column['wps-enabled'] = 'SOTM Enabled';
	$column['wps-registered-time'] = 'SOTM Reg. Date';
	$column['wps-last-used'] = 'SOTM Last Used';
	return $column;
}

add_filter( 'manage_users_columns', 'wps_add_enabled_column' );

function wps_add_enabled_column_value($val, $column_name, $user_id) {

	switch ($column_name) {
		case 'wps-enabled' :
			if(!intval(get_user_meta($user_id, "wps-enabled", true)))
				return 'Unregistered';
			else if(intval(get_user_meta($user_id, "wps-enabled", true)) == 1)
				return 'Registered';
			else 
				return "Registration Error (" . intval(get_user_meta($user_id, "wps-enabled", true)) . ")";
			break;
		case 'wps-registered-time' :
			if(!intval(get_user_meta($user_id, "wps-registered-time", true)))
				return 'Unregistered';
			else
				return date('m/d/Y', get_user_meta($user_id, "wps-registered-time", true));			
			break;
		case 'wps-last-used' :
			if(!intval(get_user_meta($user_id, "wps-last-used", true)))
				return 'Never';
			else 
				return date('m/d/Y', get_user_meta($user_id, "wps-last-used", true));
			break;			
		default:
	}

	return $return;
}

add_filter( 'manage_users_custom_column', 'wps_add_enabled_column_value', 10, 3 );

add_shortcode( 'wps-hook', 'wps_register' );

?>