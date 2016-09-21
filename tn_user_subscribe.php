<?php wp_enqueue_style('bootstrap'); ?>
<?php wp_enqueue_script('jquery'); ?>
<?php $nonce = wp_create_nonce("tn_nonce_subscribe"); ?>
<script>
var ajaxurl = "<?php echo admin_url('admin-ajax.php'); ?>";
jQuery(document).ready(function(){
  jQuery('#submit_form').click(function(){
    var data = {
      'action':'user_subscribe', 
      'name':jQuery('#name').val(), 
      'email':jQuery('#email').val(), 
      'security':'<?php echo $nonce; ?>' 
    };
		jQuery.post(ajaxurl, data, function(response) {
  		jQuery("#successful_subscription").html(response);
  		jQuery("#user_subscribe_form").fadeOut("slow");
  		jQuery("#successful_subscription").fadeIn("fast");
		});
		return false;
	});
});
</script>
<?php tn_subscribe_form(); ?>
<div id="successful_subscription"></div>