<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
//enable wire support for group wire
define("BP_GROUP_WIRE_DB_VERSION",1902);
function bp_group_wire_setup_globals() {
	global $bp, $wpdb;
	$bp->groups->table_name_wire = $wpdb->base_prefix . 'bp_groups_wire';//$bp->$active_components[$current_component]->ttable_name_wire

}
add_action( 'bp_wire_setup_globals', 'bp_group_wire_setup_globals', 10 );


function bp_group_wire_setup_nav() {
	global $bp;
        if(empty($bp->groups->current_group))
                return;

     $group_link=bp_get_group_permalink($bp->groups->current_group);
	bp_core_new_subnav_item( array( 'name' => __( BP_WIRE_LABEL, 'bp-wire' ), 'slug' => BP_WIRE_SLUG, 'parent_url' => $group_link, 'parent_slug' => $bp->groups->slug, 'screen_function' => 'groups_screen_group_wire', 'position' => BP_GROUP_WIRE_POSITION, 'user_has_access' => $bp->groups->current_group->user_has_access, 'item_css_id' => 'group-wire'  ) );

}
add_action( 'bp_init', 'bp_group_wire_setup_nav',5 );


function groups_wire_install() {
	global $wpdb, $bp;

	if ( !empty($wpdb->charset) )
		$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";

	$sql[] = "CREATE TABLE {$bp->groups->table_name_wire} (
	  		id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
			item_id bigint(20) NOT NULL,
			user_id bigint(20) NOT NULL,
			content longtext NOT NULL,
			date_posted datetime NOT NULL,
			KEY item_id (item_id),
			KEY user_id (user_id)
	 	   ) {$charset_collate};";

	require_once(ABSPATH . 'wp-admin/upgrade-functions.php');
	dbDelta($sql);
        //update meta
	update_site_option( 'bp-group-wire-db-version',BP_WIRE_DB_VERSION );
}

function bp_group_wire_check_installed() {
	global $wpdb, $bp;

	if ( !is_site_admin() )
		return false;


	/* Need to check db tables exist, activate hook no-worky in mu-plugins folder. */
	if ( get_site_option('bp-group-wire-db-version') < BP_GROUP_WIRE_DB_VERSION )
		groups_wire_install();
}
add_action( 'admin_menu', 'bp_group_wire_check_installed' );

function groups_screen_group_wire() {
	global $bp;
       // print_r($bp->action_variables);
if(!($bp->current_component==$bp->groups->slug&&$bp->current_action==$bp->wire->slug))
        return;
	$wire_action = $bp->action_variables[0];
        
       if($wire_action=='feed')
            return;
       // echo $bp->current_component;

	if ( $bp->is_single_item ) {
		if ( 'post' == $wire_action && ( is_site_admin() || groups_is_user_member( $bp->loggedin_user->id, $bp->groups->current_group->id ) ) ) {
			/* Check the nonce first. */
			if ( !check_admin_referer( 'bp_wire_post' ) )
				return false;

			if ( !groups_new_wire_post( $bp->groups->current_group->id, $_POST['wire-post-textarea'],$_POST["wire-post-email-notify"] ) )
				bp_core_add_message( sprintf(__('%s message could not be posted.', 'bp-wire'),BP_WIRE_LABEL), 'error' );
			else
				bp_core_add_message( sprintf(__('%s message successfully posted.', 'bp-wire'),BP_WIRE_LABEL ));

			if ( !strpos( wp_get_referer(), $bp->wire->slug ) )
				bp_core_redirect( bp_get_group_permalink( $bp->groups->current_group ) );
			else
				bp_core_redirect( bp_get_group_permalink( $bp->groups->current_group ) . $bp->wire->slug );

		} else if ( 'delete' == $wire_action && ( is_site_admin() || groups_is_user_member( $bp->loggedin_user->id, $bp->groups->current_group->id ) ) ) {
			$wire_message_id = $bp->action_variables[1];

			/* Check the nonce first. */
			if ( !check_admin_referer( 'bp_wire_delete_link' ) )
				return false;

			if ( !groups_delete_wire_post($bp->groups->current_group->id, $wire_message_id, $bp->groups->table_name_wire ) )
				bp_core_add_message( sprintf(__('There was an error deleting the %s message.', 'bp-wire'),BP_WIRE_LABEL), 'error' );
			else
				bp_core_add_message( sprintf(__('%s message successfully deleted.', 'bp-wire'),BP_WIRE_LABEL ));

			if ( !strpos( wp_get_referer(), $bp->wire->slug ) )
				bp_core_redirect( bp_get_group_permalink( $bp->groups->current_group ) );
			else
				bp_core_redirect( bp_get_group_permalink( $bp->groups->current_group ) . $bp->wire->slug );

		} 
			
				bp_core_load_template( apply_filters( 'groups_template_group_wire', 'wire/group-wire' ) );
		
	}
}

/*** Group Wire ****************************************************************/

function groups_new_wire_post( $group_id, $content,$email_notify=false ) {
	global $bp;

	if ( !function_exists( 'bp_wire_new_post' ) )
		return false;

	if ( $wire_post = bp_wire_new_post( $group_id, $content, 'groups' ) ) {

		
		/* Record this in activity streams */
		$activity_action = sprintf( __( '%s wrote on the %s of the group %s:', 'bp-wire'), bp_core_get_userlink( $bp->loggedin_user->id ), strtolower(BP_WIRE_LABEL),'<a href="' . bp_get_group_permalink( $bp->groups->current_group ) . '">' . attribute_escape( $bp->groups->current_group->name ) . '</a>' );
		$activity_content .= '<blockquote>' . bp_create_excerpt( attribute_escape( $content ) ) . '</blockquote>';

		groups_record_activity( array(
			'action' => apply_filters( 'groups_activity_new_wire_post', $activity_action ),
			'content' => apply_filters( 'groups_activity_new_wire_post', $activity_content ),
			'primary_link' => apply_filters( 'groups_activity_new_wire_post_primary_link', bp_get_group_permalink( $bp->groups->current_group ) ),
			'type' => 'new_wire_post',
			'item_id' => $bp->groups->current_group->id,
			'secondary_item_id' => $wire_post->id
		) );
                if($email_notify)
                    wire_notify_group_members(array("sender_id"=>$bp->loggedin_user->id,"group_id"=>$group_id,"content"=>$wire_post->content));
		do_action( 'groups_new_wire_post', $group_id, $wire_post->id );

		return true;
	}

	return false;
}

function groups_delete_wire_post($group_id, $wire_post_id, $table_name ) {
	if ( bp_wire_delete_post( $wire_post_id, 'groups', $table_name ) ) {
		/* Delete the activity stream item */

           
		if ( function_exists( 'bp_activity_delete_by_item_id' ) ) {
			 bp_activity_delete_by_item_id( array( 'item_id'=>$group_id,'secondary_item_id' => $wire_post_id, 'component' => 'groups', 'type' => 'new_wire_post' ) );
		}

		do_action( 'groups_deleted_wire_post', $wire_post_id );
		return true;
	}

	return false;
}

add_filter("bp_get_wire_get_action","group_wire_link");
function group_wire_link($link){
   global $bp;
   if(empty ($bp->groups->current_group))
           return $link;
   $link=bp_get_group_permalink($bp->groups->current_group);
   return $link.BP_WIRE_SLUG."/post";

}


//group wire notification
//notify all group members
function wire_notify_group_members($args){

  global $bp;

  extract($args);
  if(empty($group_id))
      return;
  $group=new BP_Groups_Group($group_id);

    $sender_name = bp_core_get_user_displayname( $sender_id );


        $message_link =bp_get_group_permalink($group) . BP_WIRE_SLUG .'/';
	//$settings_link = bp_core_get_user_domain( $user_id ) .  BP_SETTINGS_SLUG . '/notifications/';

	$sender_name = stripslashes( $sender_name );

	$content = stripslashes( wp_filter_kses( $content ) );

		// Set up and send the message

        $members=  BP_Groups_Member::get_group_member_ids($group_id);
        foreach($members as $user_id){
            if($sender_id==$user_id)
                continue;//no self notification mail

            $ud = get_userdata( $user_id );
            $email_to = $ud->user_email;
            $email_subject = '[' . wp_specialchars_decode( get_blog_option( BP_ROOT_BLOG, 'blogname' ) ) . '] ' . sprintf( __( 'New wire post from %s on group %s \' wire', 'bp-wire' ), $sender_name,  bp_get_group_name($group) );
$email_text=__(
'%s wrote on the group %s \'s %s:

"%s"

To view and read  %s posts please log in and visit: %s

---------------------
', 'bp-wire' );
            $email_text=apply_filters("group_wire_notification_text",$email_text);//change the text to anything
            $email_content = sprintf( $email_text, $sender_name,  bp_get_group_name($group),  strtolower(BP_WIRE_LABEL),  $content, strtolower(BP_WIRE_LABEL),$message_link );


		//$email_content .= sprintf( __( 'To disable these notifications please log in and go to: %s', 'bp-wire' ), $settings_link );

		/* Send the message */
		$email_to = apply_filters( 'wire_notification_new_wire_post_to', $email_to );
		$email_subject = apply_filters( 'wire_notification_new_wire_subject', $email_subject, $sender_name );
		$email_content = apply_filters( 'wire_notification_new_wire_message', $email_content, $sender_name, $subject, $content, $message_link );

		wp_mail( $email_to, $email_subject, $email_content );
}

}

function bp_wire_action_group_feed() {
	global $bp, $wp_query;

	if ( $bp->current_component != $bp->groups->slug || !$bp->groups->current_group || $bp->current_action != 'wire'||!$bp->action_variables['0']=='feed' )
		return false;

	$wp_query->is_404 = false;
	status_header( 200 );

	include_once(BP_WIRE_PLUGIN_DIR. '/inc/feed/group-feed.php' );
	die;
}
add_action( 'wp', 'bp_wire_action_group_feed', 4 );

?>