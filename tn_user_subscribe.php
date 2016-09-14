<?php wp_enqueue_style('tn-style'); ?>
<?php $nonce = wp_create_nonce("tn_nonce_subscribe"); ?>
<script>
jQuery(document).ready(function(){
  jQuery('#user_subscribe').click(function(){
    var data = {
      'action':'user_subscribe', 
      'name':jQuery('#name').val(), 
      'email':jQuery('#email').val(), 
      'security':'<?php echo $nonce; ?>' 
    };
		jQuery.post(ajaxurl, data, function(response) {
  		jQuery("#successful_subscription").html(response);
  		jQuery("#successful_subscription").fadeIn("fast");
  		jQuery("#user_subscribe_form").fadeOut("slow");
		});
		return false;
	});
});
</script>
<?php echo tn_subscribe_form(); ?>