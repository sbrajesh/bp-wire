<?php do_action( 'bp_before_wire_post_content' ) ?>
<div id="wire-post-new">
	<form action="<?php bp_wire_get_action() ?>" id="wire-post-new-form" method="post">
		<div id="wire-poster-avatar">
		<a href="<?php echo bp_loggedin_user_domain() ?>">
			<?php bp_loggedin_user_avatar( 'width=60&height=60' ) ?>
		</a>
		</div>
		<div id="wire-post-new-metadata">
                    <h5><?php printf(__("Write something to %s %s","bp-wire"),bp_wire_get_whose_wire_name(),  strtolower(BP_WIRE_LABEL));?></h5>
			
		</div>
	
		<div id="wire-post-new-input">
			<?php do_action( 'bp_before_wire_post_form' );  ?>
			<textarea rows="5" cols="60" name="wire-post-textarea" id="wire-post-textarea" onfocus="if (this.value == '<?php _e( 'Start writing a short message...', 'bp-wire' ) ?>') {this.value = '';}" onblur="if (this.value == '') {this.value = '<?php _e( 'Start writing a short message...', 'bp-wire' ) ?>';}"><?php _e( 'Start writing a short message...', 'bp-wire' ) ?></textarea>
	
			<?php do_action( 'bp_after_wire_post_form' );  ?>
			
			<?php if ( bp_wire_show_email_notify() ) : ?>
			<div><span id="wire-email-notify"><input type="checkbox" name="wire-post-email-notify" id="wire-post-email-notify" value="1" /> <?php _e( 'Notify members via email (will slow down posting)', 'bp-wire' ) ?></span></div>
			<?php endif; ?>
			
			<br class="clear" />
			<input type="submit" name="wire-post-submit" id="wire-post-submit" value="<?php _e( 'Post &raquo;', 'bp-wire' ) ?>" />
			<input type="hidden" name="bp_wire_item_id" id="bp_wire_item_id" value="<?php echo bp_get_wire_item_id() ?>" />

			<?php wp_nonce_field( 'bp_wire_post' ) ?>
			
		</div>
	</form>
	
</div>

<?php do_action( 'bp_after_wire_post_content' ) ?>