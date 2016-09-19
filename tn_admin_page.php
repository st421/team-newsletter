<?php wp_enqueue_style('bootstrap'); ?>
<?php wp_enqueue_style('tn-style'); ?>
<h1>Team Newsletter Plugin</h1>
<h2>Settings</h2>
<?php tn_display_settings(); ?>
<h2>Manage Subscribers</h2>
<h3>Current subscriber count: <?php tn_subscriber_count(); ?></h3>
<h3>Current subscriber list:</h3>
<?php tn_display_contacts(); ?>
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
<?php } ?>