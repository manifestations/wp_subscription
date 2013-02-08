
<div class="wrap">
	<?php screen_icon(); ?>
	<h2>T-Shirt of the Month Settings</h2>
	<form method="post" action="options.php"> 
	<?php settings_fields('wps-group'); ?>
	<?php do_settings_fields('wps-group'); ?>
		<table class="form-table">
			<tr valign="top">
				<th scope="row">Page Title</th>
				<td><input type="text" name="wps_paypal_id" value="<?php echo get_option('wps_page_title'); ?>" /></td>
			</tr>		
			<tr valign="top">
				<th scope="row">PayPal ID</th>
				<td><input type="text" name="wps_paypal_id" value="<?php echo get_option('wps_paypal_id'); ?>" /></td>
			</tr>
			<tr valign="top">
				<th scope="row">Membership Fee in Dollars</th>
				<td><input type="text" name="wps_fee" value="<?php echo get_option('wps_fee'); ?>" /></td>
			</tr>	
			<tr valign="top">
				<th scope="row">reCaptcha Public Key</th>
				<td><input type="text" name="wps_recaptcha_public" value="<?php echo get_option('wps_recaptcha_public'); ?>" /></td>
			</tr>	
			<tr valign="top">
				<th scope="row">reCaptcha Private Key</th>
				<td><input type="text" name="wps_recaptcha_private" value="<?php echo get_option('wps_recaptcha_private'); ?>" /></td>
			</tr>	
			<tr valign="top">
				<th scope="row">Enable Paypal Testing Mode</th>
				<td><input type="checkbox" name="wps_paypal_testing" value="yes" <?php if(get_option('wps_paypal_testing')=='yes') echo "checked='checked'"; ?> /></td>
			</tr>
			<tr valign="top">
				<th scope="row">Discount Rate</th>
				<td><input type="text" name="wps_discount_rate" value="<?php echo get_option('wps_discount_rate'); ?>" /> %</td>
			</tr>	
		</table>
		<?php submit_button(); ?>
	</form>
</div>
	