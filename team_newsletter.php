<?php  
/*
    Plugin Name: Team Newsletter
    Plugin URI: https://github.com/st421/team-newsletter
    Description: A plugin that emails a subscriber list when you post to your wordpress blog.
    Author: S. Tyler 
    Version: 1.0 
    Author URI: http://susanltyler.com 
*/

require_once(ABSPATH . '/wp-content/plugins/wp-sql-helper/wp_sql_helper.php');

global $contacts_table, $settings_table, $wpdb;
$contacts_table = $wpdb->prefix . "team_newsletter_contacts";
$settings_table = $wpdb->prefix . "team_newsletter_settings";
$contacts_params = array(
  new TableField("name","VARCHAR(255)"),
  new TableField("email","VARCHAR(300)")
);
$settings_params = array(
  new TableField("name","VARCHAR(255)"),
  new TableField("value","VARCHAR(255)")
);

register_activation_hook(__FILE__,'tn_install'); 
register_deactivation_hook(__FILE__, 'tn_uninstall');

add_shortcode('user_unsubscribe_form', 'tn_user_unsubscribe');
add_shortcode('user_subscribe_form', 'tn_user_subscribe');

add_action('admin_menu','team_newsletter_admin_actions'); 
add_action('publish_post','team_newsletter_email_post');  
add_action('post_submitbox_misc_actions', 'set_email_post_status');
add_action('wp_ajax_user_subscribe', 'team_newsletter_add_contact');
add_action('wp_ajax_nopriv_user_subscribe', 'team_newsletter_add_contact'); 
add_action('wp_ajax_admin_add_contacts', 'team_newsletter_add_contacts'); 
add_action('wp_ajax_user_unsubscribe', 'team_newsletter_remove_contact');
add_action('wp_ajax_nopriv_user_unsubscribe', 'team_newsletter_remove_contact');
add_action('wp_ajax_admin_remove_contact', 'team_newsletter_admin_delete_user'); 
add_action('wp_ajax_set_subject_tag', 'team_newsletter_save_tag');
add_action('wp_ajax_set_email_from', 'team_newsletter_save_email_from');
add_action('wp_ajax_set_rm', 'team_newsletter_save_rm');

/*
 * Upon installation of the plugin, create tables
 * to store contacts/settings for the email list.
 */
function tn_install() {
	global $wpdb, $contacts_table, $settings_table;
	create_table($contacts_table,$contacts_params);
	create_table($settings_table,$settings_params);
/*	$wpdb->query("INSERT INTO " . $tn_settings_table . " (name,value) VALUES ('subject_tag','0');");
	$wpdb->query("INSERT INTO " . $tn_settings_table . " (name,value) VALUES ('email_from','0');");
	$wpdb->query("INSERT INTO " . $tn_settings_table . " (name,value) VALUES ('name_from','0');");
	$wpdb->query("INSERT INTO " . $tn_settings_table . " (name,value) VALUES ('response_message','0');");*/
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
	//wp_register_style('bootstrap', '//maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css');
	wp_register_style('tn-style', plugins_url('css/team_newsletter_style.css', __FILE__));
	add_menu_page('Team Newsletter', 'Team Newsletter', 'administrator', 'team_newsletter', 'tn_admin'); 
} 

function tn_admin() {  
	include('tn_admin_page.php');  
}

function tn_subscribe_form() {
	global $contacts_params;
	$form = '<form id="user_subscribe_form">';
	foreach($contacts_params as $param) {
		$form .= '<div class="form-group">';
		$form .= '<label for="' . $param->name . '">' . $param->name . '</label>';
		$form .= '<input type="text" class="form-control" id="' . $param->name . '">';
		$form .= '</div>';
	}
	$form .= '<button type="submit" id="user_subscribe" class="btn">Submit</button></form>';
	return $form;
}

function tn_user_subscribe() {
	include('tn_user_subscribe.php');
}

function tn_add_contact() {
	check_ajax_referer('tn_nonce_subscribe','security');
	global $contacts_table, $contacts_params;
	$email = $_POST['email'];
	if(tn_validate_email($email)) {
		if(save_table_item($contacts_table,$contacts_params,$_POST)) {

		}
	} else {
		echo "Please try again with a valid email.";
	}
	die();		
}

function send_confirmation_email() {
	$body = $wpdb->get_results("SELECT value FROM " . $tn_settings_table . " WHERE name='response_message';");
		$body = $body[0];
		$body = $body->value;
		$message = "<body>Thank you, " . $name . ", for registering for our email updates.\n";
		if($body) {
			$message .= $body;
		}		
		$page = get_page_by_title('Unsubscribe');
		$unsubscribe_info = "<h6>Click <a href='" . get_page_link($page->ID) . "'>here</a> to unsubscribe from our email updates.</h6>";
		$message .= $unsubscribe_info;
		
				$email_subject_tag = $wpdb->get_results("SELECT value FROM " . $tn_settings_table . " WHERE name='subject_tag';");
		$email_subject_tag = $email_subject_tag[0];
		$email_subject_tag = $email_subject_tag->value;	
		if($email_subject_tag != '0') {
			$subject = $email_subject_tag . ": Thank you for signing up!";
		} 
		
		$contact_list = $name . ' <';
		$contact_list .= $email . '>,';
		$message =$message . '</body>';
}

function send_email($subject, $body, $contacts) {
	global $wpdb, $settings_table;
	$name_from = get_item_by_param($settings_table, 'name', 'name_from');
	$email_from = get_item_by_param($settings_table, 'name', 'email_from');
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

	mail($contacts,$subject,$body,$headers);
}

function team_newsletter_user_unsubscribe() {
	$nonce = wp_create_nonce('tn_nonce_2');
	$ajax_url = admin_url('admin-ajax.php');
?>
<script type='text/javascript'>
<!--
jQuery(document).ready(function(){
	jQuery('#user_unsubscribe').click(function() {
		jQuery.ajax({
			type: "post",
			url: '<?php echo $ajax_url; ?>',
			data: {action: 'user_unsubscribe', remove_email: jQuery('#remove_email').val(), _ajax_nonce: '<?php echo $nonce; ?>' },
			success: function(data){ 
				jQuery("#successful_unsubscribe").html(data);
				jQuery("#successful_unsubscribe").fadeIn("fast");
				jQuery("#user_unsubscribe_form").fadeOut("slow");
			}
		});
		return false;
	})
})
-->
</script>
<form method='POST' id='user_unsubscribe_form'>
<input type='text' name='remove_email' id='remove_email' value="Email"/></br>
<input type='submit' name='action' id='user_unsubscribe' value='SUBMIT'/>
</form>
<div id='successful_unsubscribe'></div>
<?php
}

function team_newsletter_remove_contact() {
	check_ajax_referer('tn_nonce_2');
	$email = $_POST['remove_email'];
	if(team_newsletter_delete_user_data($email)) {
		echo "The email " . $email . " was successfully removed from the mailing list.";
	} else {
		echo "The email " . $email . " was not found in our system.";
	}
	die();	
}

function team_newsletter_add_contacts() {
	check_ajax_referer('tn_nonce_5');
	$invalid_emails = '';
	$emails = $_POST['emails'];
	$emails = explode(',',$emails);
	foreach($emails as $person) {
		$person = explode(' ',$person);
		$length = sizeof($person);
		$name = '';
		switch ($length) {
			case 1:
  			$email = substr($person[0],1,-1);
  			break;
			case 2:
			$name = $person[0];
  			$email = substr($person[1],1,-1);
  			break;
			default:
  			$name = $person[0];
  			for($i=1;$i<$length-1;$i++) {
  				$name .= " " . $person[$i];
  			}
  			$email = substr($person[$length-1],1,-1);
		}
		if(!team_newsletter_validate_email($email)) {
			$invalid_emails .= $name . ' &lt;' . $email . '&rt;</br>';
		} else {
			team_newsletter_save_user_data(mysql_real_escape_string($name),mysql_real_escape_string($email));
		}
	}
	if($invalid_emails == '') {
		echo "Your contacts were successfully added to the mailing list!</br>";
	} else {
		echo "The following emails were invalid and were not added to the mailing list:</br>";
		echo $invalid_emails;
	}
	die();		
}

function team_newsletter_admin_delete_user() {
	$email = $_POST['email_to_delete'];
	team_newsletter_delete_user_data($email);
	die();
}

function team_newsletter_save_user_data($name, $email) {
	global $wpdb, $tn_contacts_table;
	$query = "INSERT INTO " . $tn_contacts_table . " (name, email) VALUES ('" . $name . "','" . $email . "');";
	if($wpdb->query($query)) return 1; else return 0;
}

function team_newsletter_delete_user_data($email) {
	global $wpdb, $tn_contacts_table;
	$query = "DELETE FROM " . $tn_contacts_table . " WHERE email='" . $email . "';";
	if($wpdb->query($query)) return 1; else return 0;
}

function team_newsletter_save_tag() {
	check_ajax_referer('tn_nonce_3');
	global $wpdb, $tn_settings_table;
	$email_subject_tag = $_POST['tag'];
	$wpdb->query("UPDATE " . $tn_settings_table . " SET value='" . $email_subject_tag . "' WHERE name='subject_tag';");
	echo "Your tag has been updated!";
	die();		
}

function team_newsletter_save_email_from() {
	check_ajax_referer('tn_nonce_4');
	global $wpdb, $tn_settings_table;
	$email_from = $_POST['email_from'];
	$name_from = $_POST['name_from'];
	$wpdb->query("UPDATE " . $tn_settings_table . " SET value='" . $email_from . "' WHERE name='email_from';");
	$wpdb->query("UPDATE " . $tn_settings_table . " SET value='" . $name_from . "' WHERE name='name_from';");
	echo "Your 'email from' has been updated!";
	die();
}

function team_newsletter_save_rm() {
	check_ajax_referer('tn_nonce_6');
	global $wpdb, $tn_settings_table;
	$rm = $_POST['rm'];
	$wpdb->query("UPDATE " . $tn_settings_table . " SET value='" . $rm . "' WHERE name='response_message';");
	echo "Your message has been updated!";
	die();		
}

function set_email_post_status() {
	global $post;
	$category = get_the_category($post->id); 
	$category = $category[0]->cat_name;
    	if(get_post_type($post) == 'post' && $category == 'News')  {
    		if($post->post_status == 'publish') {
    			echo '<div class="misc-pub-section">Email this post to subscribers?<p><input type="radio" name="email_post" value="Yes"> Yes&nbsp<input type="radio" name="email_post" value="No" checked> No</p></div>';
		} else {
    			echo '<div class="misc-pub-section">Email this post to subscribers?<p><input type="radio" name="email_post" value="Yes" checked> Yes&nbsp<input type="radio" name="email_post" value="No"> No</p></div>';
		}
	}
}

function team_newsletter_get_contact_list() {
	global $wpdb, $tn_contacts_table;
	return $wpdb->get_results("SELECT name,email FROM " . $tn_contacts_table . ";");
}

function team_newsletter_email_post() {
	$email_post = $_POST['email_post'];
	if($email_post == 'Yes') {
		global $wpdb, $tn_settings_table;
		$name_from = $wpdb->get_results("SELECT value FROM " . $tn_settings_table . " WHERE name='name_from';");
		$name_from = $name_from[0];
		$name_from = $name_from->value;
		$email_from = $wpdb->get_results("SELECT value FROM " . $tn_settings_table . " WHERE name='email_from';");
		$email_from = $email_from[0];
		$email_from = $email_from->value;
		$headers = '';
		if($email_from != '0' && $name_from != '0') {
			$headers = "Reply-To: " . $name_from . " <" . $email_from . ">\r\n"; 
  			$headers .= "Return-Path: " . $name_from . " <" . $email_from . ">\r\n";
  			$headers .= "From: " . $name_from . " <" . $email_from . ">\r\n";
		}
		$headers .= 'MIME-Version: 1.0' . "\r\n";
		$headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";
		$headers .= "X-Priority: 3\r\n";
  		$headers .= "X-Mailer: PHP". phpversion() ."\r\n";
		$subject = stripslashes($_POST["post_title"]);
		$message = '<body>';
		$message .= wpautop(stripslashes($_POST["post_content"]));
		$page = get_page_by_title('Unsubscribe');
		$unsubscribe_info = "<h6>Click <a href='" . get_page_link($page->ID) . "'>here</a> to unsubscribe from our email updates.</h6>";
		$message .= $unsubscribe_info;
		$message = $message . '</body>';
		$email_subject_tag = $wpdb->get_results("SELECT value FROM " . $tn_settings_table . " WHERE name='subject_tag';");
		$email_subject_tag = $email_subject_tag[0];
		$email_subject_tag = $email_subject_tag->value;	
		if($email_subject_tag != '0') {
			$subject = $email_subject_tag . ": " . $subject;
		} 
		$contacts = team_newsletter_get_contact_list();
		foreach($contacts as $contact) {
			$contact_list = $contact->name . ' <';
			$contact_list .= $contact->email . '>,';
			mail($contact_list,$subject,$message,$headers);
		}
	}
}

function tn_validate_email($email) {
	return filter_var($email,FILTER_VALIDATE_EMAIL);
}


?>
