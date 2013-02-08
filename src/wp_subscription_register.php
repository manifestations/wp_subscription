<?php

function wps_register_form() {
	global $current_user;
	if(is_user_logged_in()) {
		$_wps_enabled = intval(get_user_meta($current_user->ID, "wps-enabled", true));
		$_wps_datetime = intval(get_user_meta($current_user->ID, "wps-registered-time", true));
		$_wps_now = time();
		$_wps_datetime_expiry = strtotime("+12 months", $_wps_datetime);
		
		get_currentuserinfo();
		
		$_wps_form = "";
		$_wps_fee = intval(get_option('wps_fee'));
		$_wps_register_button = "<div style='text-align:center'><input type='submit' class='button alt' value='Click Here to Pay Subscription Charges $ $_wps_fee with PayPal' /></div>";
		$_wps_reregister_button = "<div style='text-align:center'><input type='submit' class='button alt' value='Click Here Renew Subscription by paying $ $_wps_fee with PayPal' /></div>";
		$_wps_unsubscribe_button = "<form method='post' class='unsubscribe-wps' onsubmit=\"return confirm('Are you sure you want to Unsubscribe?  This cannot be undone.');\">" . wp_nonce_field('wps', 'wps') . "<div style='text-align:center'><input type='submit' class='button' name='wps-unsubscribe' value='Click Here to Unsubscribe' /></div></form>";
		$_wps_paypal_id = get_option('wps_paypal_id');
		$_wps_home = get_bloginfo('home');
		$_wps_user_id = $current_user->ID;
		$_wps_page = get_permalink(get_option('wps_page_id'));
		if (isset($_POST['redirect']) && $_POST['redirect']) {
			$_wps_page = esc_attr($_POST['redirect']);
		}
		$_wps_notify_page = plugin_dir_url('') . 'wps/wps_paypal.php';
		
		$paypal_url = "www.paypal.com";
		if(get_option('wps_paypal_testing')=='yes') {
			$paypal_url = "www.sandbox.paypal.com";
		}

		$_wps_title = get_option('wps_page_title');
		
		$_paypal_form = "
			<form action='https://$paypal_url/cgi-bin/webscr' method='post' class='standard-form'>
				<input type='hidden' name='item_name' value='$_wps_title' />
				<input type='hidden' name='item_number' value='wps' />
				<input type='hidden' name='custom' value='$_wps_user_id' />
				<input type='hidden' name='amount' value='$_wps_fee' />
				<input type='hidden' name='cmd' value='_xclick' />
				<input type='hidden' name='upload' value='1' />
				<input type='hidden' name='business' value='$_wps_paypal_id' />
				<input type='hidden' name='currency_code' value='USD' />
				<input type='hidden' name='lc' value='US' />
				<input type='hidden' name='rm' value='2' />
				<input type='hidden' name='return' value='$_wps_page' />
				<input type='hidden' name='cancel_return' value='$_wps_page' />
				<input type='hidden' name='notify_url' value='$_wps_notify_page' />";
		
		$_wps_label = "Subscribe/Renew Subscription to $_wps_title";
		if(!$_wps_enabled) {
			$_wps_form =  $_paypal_form . $_wps_register_button . "</form>";
		} else if($_wps_enabled == 2) {
			$_wps_label = "Your previous attempt to subscribe was unsuccessful.";
			$_wps_form =  $_paypal_form . $_wps_register_button . "</form>";
		} else if($_wps_enabled == 1 && $_wps_now >= $_wps_datetime_expiry) {
			$_wps_label = "Your subscription has expired on " .  date('m/d/Y', $_wps_datetime_expiry);
			$_wps_form =  $_paypal_form . $_wps_register_button . "</form>";
		} else {
			$_wps_label = "Unsubscribe to the $_wps_title";
			$_wps_form =  $_wps_unsubscribe_button;
		}		
	?>
		<fieldset>
			<legend><?php echo $_wps_label ?> *</legend>
			<div class="clear"></div>
			<?php echo $_wps_form; ?>
			<div class="clear"></div>
		</fieldset>	
	<?php
	}
}

function wps_user_login_register_form() {
	if(!is_user_logged_in()) {		
	?>
		<div class="col2-set" id="customer_login">
			<div class="col-1">
				<h2>Login</h2>
				<form method="post" class="login">
					<p class="form-row form-row-first">
						<label for="username">Username or email <span class="required">*</span></label>
						<input type="text" class="input-text" name="wps-username" id="username" />
					</p>
					<p class="form-row form-row-last">
						<label for="password">Password <span class="required">*</span></label>
						<input class="input-text" type="password" name="wps-password" id="password" />
					</p>
					<div class="clear"></div>
					<?php 
					if (isset($_GET['redirect']) && $_GET['redirect']) {
						echo "<input type='hidden' name='redirect' value ='" . esc_attr($_GET['redirect']) . "' />";
					}
					?>					
					<p class="form-row">
						<?php wp_nonce_field('login', 'login') ?>
						<input type="submit" class="button" name="wps-login" value="Login" />
						<a class="lost_password" href="<?php echo esc_url(wp_lostpassword_url(home_url())); ?>">Lost Password</a>
					</p>
				</form>
			</div>
			<div class="col-2">
				<h2>Register</h2>
				<form method="post" class="register">			
					<p class="form-row form-row-wide">			
						<label for="reg_email">Email <span class="required">*</span></label>
						<input type="email" class="input-text" name="wps-email" id="reg_email" value="" />
					</p>
					<div class="clear"></div>
					<p class="form-row form-row-first">
						<label for="reg_password">Password <span class="required">*</span></label>
						<input type="password" class="input-text" name="wps-password" id="reg_password" value="" />
					</p>
					<p class="form-row form-row-last">
						<label for="reg_password2">Re-enter password <span class="required">*</span></label>
						<input type="password" class="input-text" name="wps-password2" id="reg_password2" value="" />
					</p>
					<div class="clear"></div>
					<?php wps_get_recaptcha() ?>
					<!-- Spam Trap -->
					<div style="left:-999em; position:absolute;"><label for="trap">Anti-spam</label><input type="text" name="email_2" id="trap" /></div>
					<?php 
					if (isset($_GET['redirect']) && $_GET['redirect']) {
						echo "<input type='hidden' name='redirect' value ='" . esc_attr($_GET['redirect']) . "' />";
					}
					?>
					<p class="form-row">
						<?php wp_nonce_field('register', 'register') ?>
						<input type="submit" class="button" name="wps-register" value="Register" />
					</p>
				</form>
			</div>
		</div>	
	<?php
	}
}

function wps_get_recaptcha() {
	$_wps_recaptcha_public = get_option('wps_recaptcha_public');
	$_wps_recaptcha_private = get_option('wps_recaptcha_private');
	if($_wps_recaptcha_public && $_wps_recaptcha_private) {
		require_once('recaptchalib.php');
		echo "<p>";
		echo recaptcha_get_html($_wps_recaptcha_public);
		echo "</p>";
	}
}

function wps_print_errors() {
	global $wps_errors;
	if(is_wp_error($wps_errors)) {
		$_wps_errors = $wps_errors->get_error_messages('wps-error');
		if(sizeof($_wps_errors)) {
			?> <div class="wps-errors"> <?php 
			foreach($_wps_errors as $_wps_error) {
				echo "<p>" . $_wps_error . "</p>";
			}
			?> </div> <div class="clear"></div> <?php
		}
	}
}

/* foreach($_POST as $key => $value) {   
  echo '<p><strong>Key: </strong>'.$key.'</p>';  
  echo '<p><strong>Value: </strong>'.$value.'</p>';  
} */  
wps_print_errors();
if(is_user_logged_in()) {
	wps_register_form();
} else {
	wps_user_login_register_form();
}
?>