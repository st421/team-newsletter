<?php 

function tn_send_confirmation_email($name, $email) {
	global $settings_table;
	$message = get_item_by_param($settings_table,'name','response');
	$body = "Thank you for registering for our email updates!\n";
	if($message) {
		$body .= $message['value'];
	}		
	$subject = get_item_by_param($settings_table,'name','tagline');
	$contact = $name . ' <' . $email . '>';
	send_email($subject['value'],$body,$contact);
}

function send_email($subject, $body, $contacts) {
	global $wpdb, $settings_table;
	$name_from = get_item_by_param($settings_table,'name','name')['value'];
	$email_from = get_item_by_param($settings_table,'name','email')['value'];
	$headers = '';
	if(!empty($email_from) && !empty($name_from)) {
		$headers .= "Reply-To: " . $name_from . " <" . $email_from . ">\r\n"; 
	  	$headers .= "Return-Path: " . $name_from . " <" . $email_from . ">\r\n";
	  	$headers .= "From: " . $name_from . " <" . $email_from . ">\r\n";
	}
	$headers .= 'MIME-Version: 1.0' . "\r\n";
	$headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";
	$headers .= "X-Priority: 3\r\n";
	$headers .= "X-Mailer: PHP". phpversion() ."\r\n";
	$body = '<body>' . $body . get_unsubscribe_info() . '</body>';
	mail($contacts,$subject,$body,$headers);
}

function get_unsubscribe_info() {
	$page = get_page_by_title('Unsubscribe');
	return "<h6>Click <a href='" . get_page_link($page->ID) . "'>here</a> to unsubscribe from our email updates.</h6>";
}
?>