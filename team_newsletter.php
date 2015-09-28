<?php  
/*
    Plugin Name: Team Newsletter
    Plugin URI: http://susanltyler.com 
    Description: A plugin that simply emails a subscriber list when you post to your wordpress blog.
    Author: S. Tyler 
    Version: 1.0 
    Author URI: http://susanltyler.com 
*/

// <--- Hooks, shortcodes, and global variables --->
register_activation_hook(__FILE__,'team_newsletter_install'); // On install, call function team_newsletter_install.
register_deactivation_hook(__FILE__, 'team_newsletter_uninstall'); // On uninstall, call function team_newsletter_uninstall.
add_action('admin_menu','team_newsletter_admin_actions');  // In admin menu, call function team_newsletter_admin_actions.
add_shortcode('user_unsubscribe_form', 'team_newsletter_user_unsubscribe'); // Creates shortcode for placement of unsubscribe form.
add_shortcode('user_subscribe_form', 'team_newsletter_user_subscribe'); // Creates shortcode for placement of subscribe form.
add_action('publish_post','team_newsletter_email_post'); // When a post is published, email it with the function team_newsletter_email_post. 
add_action('post_submitbox_misc_actions', 'set_email_post_status');
add_action('wp_ajax_user_subscribe', 'team_newsletter_add_contact'); // Use ajax to sign up user.
add_action('wp_ajax_nopriv_user_subscribe', 'team_newsletter_add_contact'); // Again, use ajax to sign up (not logged in) users.
add_action('wp_ajax_admin_add_contacts', 'team_newsletter_add_contacts'); // If admin wants to bulk import an email list, that happens here. 
add_action('wp_ajax_user_unsubscribe', 'team_newsletter_remove_contact'); // Again, use ajax to remove a contact.
add_action('wp_ajax_nopriv_user_unsubscribe', 'team_newsletter_remove_contact');
add_action('wp_ajax_admin_remove_contact', 'team_newsletter_admin_delete_user'); 
add_action('wp_ajax_set_subject_tag', 'team_newsletter_save_tag'); // Save tag to be displayed in email subject.
add_action('wp_ajax_set_email_from', 'team_newsletter_save_email_from');
add_action('wp_ajax_set_rm', 'team_newsletter_save_rm');
$tn_contacts_table = $wpdb->prefix . "team_newsletter_contacts";
$tn_settings_table = $wpdb->prefix . "team_newsletter_settings";
// <----------------------------------------------->

function team_newsletter_admin_actions() {  
	add_menu_page('Team Newsletter', 'Team Newsletter', 'administrator', 'team_newsletter', 'team_newsletter_admin');  
} 

function team_newsletter_admin() {  
	wp_register_style('teamNewsletterStyle', plugins_url('team_newsletter_style.css', __FILE__) );
        include('team_newsletter_admin_page.php');
}

/*
 * Upon installation of the plugin, create tables
 * to store contacts/settings for the email list.
 */
function team_newsletter_install() {
	global $wpdb;
	$tn_contacts_table = $wpdb->prefix . "team_newsletter_contacts";
	if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $tn_contacts_table) {
		$sql = "CREATE TABLE " . $tn_contacts_table . " (
			name VARCHAR(255),
			email VARCHAR(300),
			UNIQUE (email)		
		);";
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
	}
	$tn_settings_table = $wpdb->prefix . "team_newsletter_settings";
	if($wpdb->get_var("SHOW TABLES LIKE '$table2_name'") != $tn_settings_table) {
		$sql = "CREATE TABLE " . $tn_settings_table . " (
			name VARCHAR(255),
			value VARCHAR(255)		
		);";
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
	}
	$wpdb->query("INSERT INTO " . $tn_settings_table . " (name,value) VALUES ('subject_tag','0');");
	$wpdb->query("INSERT INTO " . $tn_settings_table . " (name,value) VALUES ('email_from','0');");
	$wpdb->query("INSERT INTO " . $tn_settings_table . " (name,value) VALUES ('name_from','0');");
	$wpdb->query("INSERT INTO " . $tn_settings_table . " (name,value) VALUES ('response_message','0');");
} 

/*
 * Code called in user_subscribe_form shortcode; uses jQuery/AJAX.
 */
function team_newsletter_user_subscribe() {
	$nonce = wp_create_nonce('tn_nonce_1');
	$ajax_url = admin_url('admin-ajax.php');
?>
<script type='text/javascript'>
<!--
jQuery(document).ready(function(){
	jQuery('#user_subscribe').click(function() {
		jQuery.ajax({
			type: "post",
			url: '<?php echo $ajax_url; ?>',
			data: {action: 'user_subscribe', name: jQuery('#name').val(), email: jQuery('#email').val(), _ajax_nonce: '<?php echo $nonce; ?>' },
			success: function(data){ 
				jQuery("#successful_subscription").html(data);
				jQuery("#successful_subscription").fadeIn("fast");
				jQuery("#user_subscribe_form").fadeOut("slow");
			}
		});
		return false;
	})
})
-->
</script>
<form method='POST' id='user_subscribe_form'>
<input type='text' name='name' id='name' value="Name" class="round"/></br>
<input type='text' name='email' id='email' value="Email address" class="round" /></br>
<input type='submit' name='action' id='user_subscribe' value='SUBMIT' class="submit" />
</form>
<div id='successful_subscription'></div><?php
}

function team_newsletter_add_contact() {
	check_ajax_referer('tn_nonce_1');
	$name = $_POST['name'];
	$email = $_POST['email'];
	if(team_newsletter_validate_email($email)) {
		team_newsletter_save_user_data(mysql_real_escape_string($name),mysql_real_escape_string($email));
		echo "Thank you, " . $name . "! You are now registered to receive email updates from " . get_bloginfo() . ".";
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
		mail($contact_list,$subject,$message,$headers);
	} else {
		echo "Please try again with a valid email.";
	}
	die();		
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
<input type='text' name='remove_email' id='remove_email' value="Email" class="round" /></br>
<input type='submit' name='action' id='user_unsubscribe' value='SUBMIT' class="submit" />
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

function team_newsletter_validate_email($email) {
	if(filter_var($email,FILTER_VALIDATE_EMAIL)) {
		return true;
	} else return false;
}

/* 
 * Upon uninstalling the plugin, remove the tables created. 
 */
function team_newsletter_uninstall() {
	global $wpdb, $tn_contacts_table, $tn_settings_table;
	$wpdb->query("DROP TABLE IF EXISTS " . $tn_contacts_table . ";");
	$wpdb->query("DROP TABLE IF EXISTS " . $tn_settings_table . ";");
}
?>