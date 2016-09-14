<div class="wrap">
<?php wp_enqueue_style('teamNewsletterStyle'); ?>
<?php team_newsletter_admin_remove_contact(); ?>
<h1>Team Newsletter Plugin</h1>
<h2>Miscellaneous Settings</h2>
<h4>Set Subject Tagline</h4>
<p>If you want your emails to have a "tagline" displayed in the subject of the email, enter it here. The subject of the email will be displayed as "_Tagline_: Title of Blog Post". For example, if your blog is about cooking, you might want your tagline to be "Amazing Recipes" in which case the subject line of the email might be "Amazing Recipes: World's Best Mac 'n Cheese". If you don't want a tagline, don't enter one!</p>
<p>Your current tag is: 
<?php $tag = team_newsletter_get_tagline(); if($tag) { echo $tag; } else { echo "Tag not yet set."; } ?>
</p>
<?php team_newsletter_set_subject_tag(); ?>
<h4>Set FROM email:</h4>
<p>By default, the "email from" (i.e. the email it will appear your newsletter comes from) comes from the server your blog is running on...if you want your personal email/name or some other name/email to be displayed, enter it here.</p>
<?php team_newsletter_set_email_from(); ?>
<h4>Set sign up confirmation message:</h4>
<p>If you want a message to be sent when a user signs up for email updates, please enter a message body here. If nothing is entered, there will be no confirmation email. Otherwise, the message will say "Thank you, [name], for registering for our email updates." and then your message.</p>
<p>Your current message is: 
<?php $rm = team_newsletter_get_rm(); if($rm) { echo $rm; } else { echo "Message not yet set."; } ?>
</p>
<?php team_newsletter_set_rm(); ?>
<h2>Manage Subscribers</h2>
<h4>Current subscriber count:</h4>
<?php team_newsletter_get_subscriber_count(); ?>
<h4>Current subscriber list:</h4>
<?php team_newsletter_echo_contact_list(); ?>
<h4>Manually add email subscribers:</h4>
<p>You can manually add subscribers here (you will probably only use this if you already have a mailing list but wish to transfer it to wordpress). Enter as Name &ltemail&gt or email alone separated by commas (like Joe Smith &ltjoesmith@example.com&gt, &ltjanesmith@example.com&gt, etc.).</p>
<?php 
team_newsletter_admin_add_contacts();

function team_newsletter_set_subject_tag() {
	$nonce = wp_create_nonce('tn_nonce_3');
?>
<script type='text/javascript'>
<!--
jQuery(document).ready(function(){
	jQuery('#submit_tag').click(function() {
		jQuery.ajax({
			type: "post",
			url: "admin-ajax.php",
			data: {action: 'set_subject_tag', tag: jQuery('#tag').val(), _ajax_nonce: '<?php echo $nonce; ?>' },
			success: function(data){ 
				jQuery("#tag_submitted").html(data);
				jQuery("#tag_submitted").fadeIn("fast");
				jQuery("#set_tag").fadeOut("slow");
			}
		});
		return false;
	})
})
-->
</script>
<form method='POST' id='set_tag'><table class="form-table"><tr valign="top">
<th scope="row">Enter tag here:</th><td><input type='text' name='tag' id='tag'/></td><td><input type='submit' name='action' id='submit_tag' value='submit' class="button-secondary"/></td></tr></table></form>
<div id='tag_submitted'></div>
<?php
}

function team_newsletter_set_email_from() {
	$nonce = wp_create_nonce('tn_nonce_4');
?>
<script type='text/javascript'>
<!--
jQuery(document).ready(function(){
	jQuery('#submit_email_from').click(function() {
		jQuery.ajax({
			type: "post",
			url: "admin-ajax.php",
			data: {action: 'set_email_from', name_from: jQuery('#name_from').val(), email_from: jQuery('#email_from').val(), _ajax_nonce: '<?php echo $nonce; ?>' },
			success: function(data){ 
				jQuery("#email_from_submitted").html(data);
				jQuery("#email_from_submitted").fadeIn("fast");
				jQuery("#set_email_from").fadeOut("slow");
			}
		});
		return false;
	})
})
-->
</script>
<form method='POST' id='set_email_from'><table class="form-table">
<tr valign="top"><th scope="row">Name</th><td><input type='text' name='name_from' id='name_from'/></td><td>Email</td><td><input type='text' name='email_from' id='email_from'/></td><td><input type='submit' name='action' id='submit_email_from' value='submit' class="button-secondary"/></td></tr>
</table></form>
<div id='email_from_submitted'></div>
<?php
}

function team_newsletter_admin_remove_contact() {
?>
<script type="text/javascript">
<!--
jQuery(document).ready(function() {
	jQuery("td.delete").click(function() {
		var $this = jQuery(this);
		jQuery.ajax({
			type: "post",
			url: "admin-ajax.php",
			data: {action: 'admin_remove_contact', email_to_delete: $this.parent().children('td.email_span').html()},
			success: function(data){ 
				$this.parent().fadeOut('slow');
			}
		});
		return false;
	});

});
-->
</script>
<?php }

function team_newsletter_admin_add_contacts() {
	$nonce = wp_create_nonce('tn_nonce_5');
?>
<script type='text/javascript'>
<!--
jQuery(document).ready(function(){
	jQuery('#submit_email_list').click(function() {
		jQuery.ajax({
			type: "post",
			url: "admin-ajax.php",
			data: {action: 'admin_add_contacts', emails: jQuery('#emails').val(), _ajax_nonce: '<?php echo $nonce; ?>' },
			success: function(data){ 
				jQuery("#successful_bulk_add").html(data);
				jQuery("#successful_bulk_add").fadeIn("fast");
				jQuery("#add_contacts").fadeOut("slow");
			}
		});
		return false;
	})
})
-->
</script>
<form method='POST' id='add_contacts'>
<textarea name='emails' id='emails' COLS=150 ROWS=10>Enter your name/email list here.</textarea></br>
<input type='submit' name='action' id='submit_email_list' value='submit' class="button-secondary"/>
</form>
<div id='successful_bulk_add'></div><?php
}

function team_newsletter_set_rm() {
	$nonce = wp_create_nonce('tn_nonce_6');
?>
<script type='text/javascript'>
<!--
jQuery(document).ready(function(){
	jQuery('#submit_rm').click(function() {
		jQuery.ajax({
			type: "post",
			url: "admin-ajax.php",
			data: {action: 'set_rm', rm: jQuery('#rm').val(), _ajax_nonce: '<?php echo $nonce; ?>' },
			success: function(data){ 
				jQuery("#rm_submitted").html(data);
				jQuery("#rm_submitted").fadeIn("fast");
				jQuery("#set_rm").fadeOut("slow");
			}
		});
		return false;
	})
})
-->
</script>
<form method='POST' id='set_rm'><table class="form-table">
Response message<p><textarea name="rm" id="rm" rows="4" cols="2" ></textarea></p>
<input type='submit' name='action' id='submit_rm' value='submit' class="button-secondary"/></form>
<div id='rm_submitted'></div>
<?php
}

function team_newsletter_get_tagline() {
	global $wpdb, $tn_settings_table;
	$result = $wpdb->get_results("SELECT value FROM " . $tn_settings_table . " WHERE name='subject_tag';");
	$result = $result[0];
	$result = $result->value;
	return $result;
}
	
function team_newsletter_get_subscriber_count() {
	global $wpdb, $tn_contacts_table;
	$result = $wpdb->get_results("SELECT COUNT(*) as the_count FROM " . $tn_contacts_table . ";");
	$result = $result[0];
	echo $result->the_count;
}

function team_newsletter_get_rm() {
	global $wpdb, $tn_settings_table;
	$result = $wpdb->get_results("SELECT value FROM " . $tn_settings_table . " WHERE name='response_message';");
	$result = $result[0];
	$result = $result->value;
	return $result;
}

function team_newsletter_echo_contact_list() {
	global $wpdb, $tn_contacts_table;
	$contacts = $wpdb->get_results("SELECT * FROM " . $tn_contacts_table . ";");
	echo '<table class="widefat"><thead><tr><th>Name</th><th>Email</th><th>Delete?</th></tr><tbody>';
	foreach($contacts as $contact) {
		echo '<tr class="contact"><td>' . $contact->name . '</td><td class="email_span">' . $contact->email . '</td><td class="delete"></td></tr>';
	}
	echo '</tbody></table>';
}
?></div>