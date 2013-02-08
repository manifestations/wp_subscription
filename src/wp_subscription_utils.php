<?php

function wps_get_eligibility() {
	global $current_user;
	$_retval['eligible'] = false;
	$_retval['registered'] = false;
	$_retval['next'] = '';
	$_retval = array();
	if(is_user_logged_in()) {
		get_currentuserinfo();
		$_user_id = $current_user->ID;
		$_en = intval(get_user_meta($_user_id, 'wps-enabled', true));
		$_regtime = intval(get_user_meta($_user_id, 'wps-registered-time', true));
		$_lastused = intval(get_user_meta($_user_id, 'wps-last-used', true));
		$_now = time();
		if($_en == 1) {
			$_expiry = strtotime("+12 months", $_regtime);
			$_retval['registered'] = true;
			if($_now < $_regtime || !$_regtime) {
				return $_retval;
			}

			$_rrange = $_expiry;
			$_lrange = strtotime("-1 month", $_expiry);
			if($_now >= $_rrange) {
				$_retval['eligible'] = false;			
			}
			while($_lrange >= $_regtime) {				
				if($_now >= $_lrange && $_now < $_rrange) {
					if(!_lastused) {
						$_retval['eligible'] = true;
						if($_rrange != $_expiry)
							$_retval['next'] = date("m/d/Y", $_rrange);
						break;						
					} else if($_lastused >= $_lrange && $_lastused < $_rrange) {
						$_retval['eligible'] = false;
						if($_rrange != $_expiry)
							$_retval['next'] = date("m/d/Y", $_rrange);
						break;
					} else {
						$_retval['eligible'] = true;
						if($_rrange != $_expiry)
							$_retval['next'] = date("m/d/Y", $_rrange);							
						break;
					}
				}
				$_rrange = strtotime("-1 month", $_rrange);
				$_lrange = strtotime("-1 month", $_rrange);
			}
		}
	}
	return $_retval;	
}

function wps_get_expiry() {
	global $current_user;
	if(is_user_logged_in()) {
		get_currentuserinfo();
		$_user_id = $current_user->ID;
		$_en = intval(get_user_meta($_user_id, 'wps-enabled'));
		$_regtime = intval(get_user_meta($_user_id, 'wps-registered-time'));
		$_expiry = strtotime("+1 year", $_regtime);
		if($_en == 1) {	
			return date("m/d/Y", $_expiry);
		}
	}
	return '';
}

?>