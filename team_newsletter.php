<?php  
/*
    Plugin Name: Team Newsletter
    Plugin URI: https://github.com/st421/team-newsletter
    Description: A plugin that emails a subscriber list when you post to your wordpress blog.
    Author: S. Tyler 
    Version: 1.0 
    Author URI: http://susanltyler.com 
*/

require_once(ABSPATH . '/wp-content/plugins/wp-plugin-helper/wp_display_helper.php');

global $contacts_table, $settings_table, $contacts_params, $settings_params, $wpdb;
$contacts_table = $wpdb->prefix . "tn_contacts";
$settings_table = $wpdb->prefix . "tn_settings";
$contacts_params = array(
  new TableField("name","VARCHAR(100)"),
  new TableField("email","VARCHAR(100)")
);
$settings_params = array(
  new TableField("name","VARCHAR(50)"),
  new TableField("value","VARCHAR(255)"),
  new TableField("description","VARCHAR(255)")
);

register_activation_hook(__FILE__,'tn_install'); 
register_deactivation_hook(__FILE__, 'tn_uninstall');

add_shortcode('user_subscribe_form', 'tn_user_subscribe');
add_shortcode('user_unsubscribe_form', 'tn_user_unsubscribe');
add_action('wp_ajax_user_subscribe', 'tn_add_contact');
add_action('wp_ajax_nopriv_user_subscribe', 'tn_add_contact'); 
add_action('wp_ajax_user_unsubscribe', 'tn_remove_contact');
add_action('wp_ajax_nopriv_user_unsubscribe', 'tn_remove_contact');

add_action('publish_post','tn_process_post');  
add_action('post_submitbox_misc_actions', 'tn_display_email_post_form');

add_action('admin_menu','tn_admin_setup'); 
add_action('wp_ajax_tn_save_setting', 'tn_save_setting');
add_action('wp_ajax_tn_delete_subscriber', 'tn_delete_subscriber'); 
add_action('wp_ajax_admin_add_contacts', 'tn_add_contacts'); 

/*
 * Upon installation of the plugin, create tables
 * to store contacts/settings for the email list.
 */
function tn_install() {
	global $wpdb, $contacts_table, $settings_table, $contacts_params, $settings_params;
	create_table($contacts_table,$contacts_params);
	create_table($settings_table,$settings_params);
	init_settings();
} 

function init_settings() {
	global $settings_table, $settings_params;
	save_table_item($settings_table, $settings_params, ["name"=>"tagline", "value"=>"", "description"=>'"tagline" for email subject. Will be displayed as "<tagline>: Title of Blog Post".']);
	save_table_item($settings_table, $settings_params, ["name"=>"email", "value"=>"", "description"=>'By default, the "email from" (i.e. the email your newsletter comes from) is the server your blog is running on; if you want a different email to appear instead, update this setting.']);
	save_table_item($settings_table, $settings_params, ["name"=>"name", "value"=>"", "description"=>'The name displayed with the "email from" setting.']);
	save_table_item($settings_table, $settings_params, ["name"=>"response", "value"=>"", "description"=>'If you want a confirmation email to be sent when a user signs up for email updates, please enter a message body here. If nothing is entered, there will be no confirmation email. Otherwise, the message will say "Thank you for registering for our email updates." and then your message.']);
}

/* 
 * Upon uninstalling the plugin, remove the tables created. 
 */
function tn_uninstall() {
	global $contacts_table, $settings_table;
	drop_table($contacts_table);
	drop_table($settings_table);
}

/*
 * Registers style sheets and menu pages.
 */
function tn_admin_setup() {  
	wp_register_style('bootstrap', '//maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css');
	wp_register_style('tn-style', plugins_url('css/team_newsletter_style.css', __FILE__));
	add_menu_page('Team Newsletter', 'Team Newsletter', 'administrator', 'team_newsletter', 'tn_admin'); 
	add_submenu_page(NULL, 'Edit setting', 'Edit setting', 'administrator', 'tn-edit-setting', 'tn_edit_setting');
} 

function tn_admin() {  
	include('tn_admin_page.php');  
}

function tn_user_subscribe() {
	include('tn_user_subscribe.php');
}

function tn_edit_setting() {
	include('tn_edit_setting.php');
}

function tn_subscribe_form() {
	global $contacts_params, $contacts_table;
	echo get_basic_form($contacts_params, "user_subscribe_form");
}

function tn_add_contact() {
	check_ajax_referer('tn_nonce_subscribe','security');
	global $contacts_table, $contacts_params;
	$email = $_POST['email'];
	if(tn_validate_email($email)) {
		if(save_table_item($contacts_table,$contacts_params,$_POST)) {
			//tn_send_confirmation_email(); // TODO
			echo "Success!";
		} else {
			echo "Email was not saved";
		}
	} else {
		echo "Please try again with a valid email.";
	}
	die();		
}

function tn_remove_contact() {
	check_ajax_referer('tn_nonce_unsubscribe');
	global $contacts_table, $contacts_params;
	$email = $_POST['email'];
	$subscriber = get_item_by_param($contacts_table, 'email', $email);
	$id = get_object_vars($subscriber)['id'];
	if(delete_table_item($contacts_table, ['id'=>$id])) {
		echo "The email " . $email . " was successfully removed from the mailing list.";
	} else {
		echo "The email " . $email . " was not found in our system.";
	}
	die();	
}

function tn_display_settings() {
	global $settings_table, $settings_params;
	echo get_settings_table($settings_table, $settings_params, "tn_settings", "name", "tn-edit-setting");
}

function tn_edit_setting_form($id) {
	global $settings_table, $settings_params;
	echo get_basic_form($settings_params, "setting_form", true, get_item_by_id($settings_table, $id));
}

function tn_save_setting() {
	check_ajax_referer('tn_nonce_save','security');
	global $settings_table, $settings_params;
	if(!empty($_POST['id'])) {
		$success = update_table_item($settings_table,$settings_params,$_POST);
	} else {
		$success = save_table_item($settings_table,$settings_params,$_POST);
	}
	if($success) {
		echo "Setting successfully saved";
	} else {
		echo "ERROR; setting not saved";
	}
	die();
}

function tn_delete_subscriber() {
	check_ajax_referer('tn_nonce_del','security');
	global $contacts_table;
	delete_table_item($contacts_table, $_POST);
	die();
}

function tn_add_contacts() {
	$nonce = wp_create_nonce('tn_nonce_add');
	$invalid_emails = '';
	$subscribers = sanitize_text_field($_POST['emails']);
	$subscribers = explode(',',$emails);
	foreach($subscribers as $subscriber) {
		$subscriber = explode(' ',$subscriber);
		$length = sizeof($subscriber);
		$name = '';
		$email = '';
		switch ($length) {
			case 1:
  			$email = substr($subscriber[0],1,-1);
  			break;
			case 2:
				$name = $subscriber[0];
  			$email = substr($subscriber[1],1,-1);
  			break;
			default:
  			$name = $subscriber[0];
  			for($i=1;$i<$length-1;$i++) {
  				$name .= " " . $subscriber[$i];
  			}
  			$email = substr($subscriber[$length-1],1,-1);
		}
		if(!tn_validate_email($email)) {
			$invalid_emails .= $name . ' &lt;' . $email . '&rt;</br>';
		} else {
			global $contacts_table, $contacts_params;
			if(!save_table_item($contacts_table,$contacts_params,["name"=>$name, "email"=>$email])) {
				$invalid_emails .= $name . ' &lt;' . $email . '&rt;</br>';
			}
		}
	}
	if(empty($invalid_emails)) {
		echo "Your contacts were successfully added to the mailing list!";
	} else {
		echo "The following emails were invalid and were not added to the mailing list:</br>";
		echo $invalid_emails;
	}
	die();		
}

function tn_validate_email($email) {
	return filter_var($email,FILTER_VALIDATE_EMAIL);
}

function tn_subscriber_count() {
	global $contacts_table;
	echo get_table_count($contacts_table);
}

function tn_display_contacts() {
	global $contacts_table, $contacts_params;
	echo get_admin_table($contacts_table, $contacts_params, "tn_contacts");
}

function tn_get_contact_list() {
	global $contacts_table;
	return get_all($contacts_table);
}

function tn_display_email_post_form() {
	if(get_post_type($post) == 'post') {
		global $post;
		$category = get_the_category($post->id); 
		$category = $category[0]->cat_name;
		echo tn_get_checkbox_form($post->post_status == 'publish');
	}
}

function tn_get_checkbox_form($published) {
	$checkbox = '<div class="misc-pub-section">Email this post to subscribers?<p>';
	$checkbox .= tn_get_checkbox_input("email_post", "Yes", !$published);
	$checkbox .= tn_get_checkbox_input("email_post", "No", $published);
	$checkbox .= '</p></div>';
	return $checkbox;
}

function tn_get_checkbox_input($name, $value, $checked) {
	$checkbox = ' <input type="radio" name="' . $name . '" value="' . $value . '"';
	if($checked) {
		$checkbox .= ' checked';
	}
	$checkbox .= '>' . $value;
	return $checkbox;
}

function tn_process_post() {
	$email_post = $_POST['email_post'];
	if($email_post == 'Yes') {
		global $settings_table;
		$subject = stripslashes($_POST["post_title"]);
		$message .= wpautop(stripslashes($_POST["post_content"]));
		$subject_tag = get_item_by_param($settings_table,'name','tagline');
		if(!empty($subject_tag)) {
			$subject = $subject_tag . ": " . $subject;
		} 
		$contacts = tn_get_contact_list();
		foreach($contacts as $contact) {
			$address = $contact->name . ' <';
			$address .= $contact->email . '>,';
			send_email($subject,$message,$address);
		}
	}
}

function tn_send_confirmation_email($name, $email) {
	global $settings_table;
	$message = get_item_by_param($settings_table,'name','response');
	$body = "Thank you for registering for our email updates!\n";
	if($message) {
		$body .= $message;
	}		
	$subject = get_item_by_param($settings_table,'name','tagline');
	$contact = $name . ' <' . $email . '>';
	send_email($subject,$body,$contact);
}

function send_email($subject, $body, $contacts) {
	global $wpdb, $settings_table;
	$name_from = get_item_by_param($settings_table,'name','name');
	$email_from = get_item_by_param($settings_table,'name','email');
	$headers = '';
	if($email_from != '0' && $name_from != '0') {
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
