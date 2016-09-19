<?php wp_enqueue_style('jquery'); ?>
<?php wp_enqueue_style('bootstrap'); ?>
<?php $nonce = wp_create_nonce("tn_nonce_save"); ?>
<script>  
jQuery(document).ready(function(){
	jQuery('#submit_form').click(function(){ 
		var data = {
			'action':'tn_save_setting', 
			'id':jQuery(this).attr('name'),
			'name':jQuery('#name').val(), 
			'value':jQuery('#value').val(), 
			'description':jQuery("#description").val(),
			'security':'<?php echo $nonce; ?>'
		};
		jQuery.post(ajaxurl, data, function(response) {
			jQuery("#setting_form").fadeOut("fast");
			jQuery("#form_submitted").html(response);
			jQuery("#form_submitted").fadeIn("fast");
		});
		return false;
	});
});
</script>
<h1>Edit setting</h1>
<?php $id = $_GET['id']; tn_edit_setting_form($id); ?>
<div id='form_submitted'></div>