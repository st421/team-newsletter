<?php wp_enqueue_style('bootstrap'); ?>
<?php wp_enqueue_style('tn-style'); ?>
<?php $nonce_del = wp_create_nonce("tn_nonce_del"); ?>
<?php $nonce_add = wp_create_nonce("tn_nonce_add"); ?>
<script>
jQuery(document).ready(function(){
	jQuery("td.delete").click(function() {
		var $this = jQuery(this);
		var data = {
			'action':'tn_delete_subscriber', 
			'id':$this.attr('id'),
			'security':'<?php echo $nonce_del; ?>'
		};
		jQuery.post(ajaxurl, data, function(response) {
			$this.parent().fadeOut('slow');
		});
		return false;
	});
	jQuery('#submit_form').click(function(){ 
		var data = {
			'action':'tn_add_contacts', 
			'emails':jQuery('#emails').val(),
			'security':'<?php echo $nonce_add; ?>'
		};
		jQuery.post(ajaxurl, data, function(response) {
			jQuery("#emails_form").fadeOut("fast");
			jQuery("#form_submitted").html(response);
			jQuery("#form_submitted").fadeIn("fast");
		});
		return false;
	});
});
</script>
<h1>Team Newsletter Plugin</h1>
<h2>Settings</h2>
<?php tn_display_settings(); ?>
<h2>Manage Subscribers</h2>
<h3>Current subscriber count: <?php tn_subscriber_count(); ?></h3>
<h3>Current subscriber list:</h3>
<?php tn_display_contacts(); ?>
<h4>Manually add email subscribers:</h4>
<p>You can manually add subscribers here (you will probably only use this if you already have a mailing list 
but wish to transfer it to wordpress). Enter as Name &ltemail&gt or email alone separated by commas 
(like Joe Smith &ltjoesmith@example.com&gt, &ltjanesmith@example.com&gt, etc.).</p>
<form id='emails_form'>
	<div class="form-group">
		<label for="emails">Emails</label>
		<textarea type="text" class="form-control" id="emails" COLS=100 ROWS=5>Enter your name/email list here.</textarea>
	</div>
	<button type="submit" id="submit_form" class="btn">Submit</button>
</form>
<div id='form_submitted'></div>
