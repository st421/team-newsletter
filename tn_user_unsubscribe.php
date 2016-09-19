<?php wp_enqueue_style('bootstrap'); ?>
<?php wp_enqueue_script('jquery'); ?>
<?php $nonce = wp_create_nonce("tn_nonce_unsubscribe"); ?>
<script>
var ajaxurl = "<?php echo admin_url('admin-ajax.php'); ?>";
jQuery(document).ready(function(){
  jQuery('#submit_form').click(function(){
    var data = {
      'action':'user_unsubscribe', 
      'email':jQuery('#email').val(), 
      'security':'<?php echo $nonce; ?>' 
    };
		jQuery.post(ajaxurl, data, function(response) {
  		jQuery("#unsubscribe_form").fadeOut("slow");
  		jQuery("#form_submitted").html(response);
  		jQuery("#form_submitted").fadeIn("fast");
		});
		return false;
	});
});
</script>
<form id='unsubscribe_form'>
  <div class="form-group">
		<label for="email">Email</label>
		<input type="text" class="form-control" id="email">
	</div>
	<button type="submit" id="submit_form" class="btn">Submit</button>
</form>
<div id="form_submitted"></div>