<?php
/*
Plugin Name: Buddypress Wire 
Plugin URI: http://buddydev.com/downloads
Description: The Buddypress wire component ported as a plugin
Author: The BuddyPress Community
Modified by: Brajesh singh
Version: 1.1.3
Author URI: http://buddypress.org/developers/
Credit: Original work of Andy Peatling(@apeatling) slightly modified to work as an addon plugin for bp 1.2 by @sbrajesh
Site Wide Only: true

*/

/********** Note***************/
/*
	* Most of the code are directly taken from bp 1.1.x series(and are original work of @apeatling ) and modified to work
	* Wire posts are no more part of the activity stream as bp 1.2 has a unified activity stream
	* I have removed support for group wire, if you people want, let me know, I will add that back
	*/
//define constarnts
//text domain: bp-wire

define("BP_WIRE_DB_VERSION",1902);
define( 'BP_WIRE_PLUGIN_DIR', WP_PLUGIN_DIR . '/bp-wire' );

define( 'BP_WIRE_URL', plugins_url( $path = '/bp-wire' ) );
/* Define the slug for the component */

function bp_wire_loader(){
if ( !defined( 'BP_WIRE_SLUG' ) )
	define ( 'BP_WIRE_SLUG', 'wire' );
define("BP_WIRE_PLUGIN_NAME","bp-wire");

if(!defined("BP_WIRE_POSITION"))
    define("BP_WIRE_POSITION",70);//define it in bp-custom.php to change the slug

if(!defined("BP_GROUP_WIRE_POSITION"))
    define("BP_GROUP_WIRE_POSITION",50);//define it in bp-custom.php to change the slug

if(!defined("BP_WIRE_LABEL"))
    define("BP_WIRE_LABEL","Wire");//it may not allow translation
require ( BP_WIRE_PLUGIN_DIR . '/inc/bp-wire-classes.php' );
require ( BP_WIRE_PLUGIN_DIR . '/inc/bp-wire-templatetags.php' );
require ( BP_WIRE_PLUGIN_DIR . '/inc/bp-wire-filters.php' );
require ( BP_WIRE_PLUGIN_DIR . '/inc/bp-wire-cssjs.php' );

 if(bp_is_active("groups")&&apply_filters("is_group_wire_enabled",false))//by default group wire is disabled
    require ( BP_WIRE_PLUGIN_DIR . '/inc/group-wire.php' );
}
add_action("bp_loaded","bp_wire_loader",1); //load bp-wire files requires bp 1.2.5.2+

//localization
/*
 * Localization support
 * Put your files into
 * bp-flickr/languages/bp-flickr-your_local.mo
 */
function bp_wire_load_textdomain() {
        $locale = apply_filters( 'bp_wire_load_textdomain_get_locale', get_locale() );
	// if load .mo file
	if ( !empty( $locale ) ) {
		$mofile_default = sprintf( '%s/languages/%s-%s.mo', BP_WIRE_PLUGIN_DIR, BP_WIRE_PLUGIN_NAME, $locale );
		$mofile = apply_filters( 'bp_wire_load_textdomain_mofile', $mofile_default );
		// make sure file exists, and load it
		if ( file_exists( $mofile ) ) {
			load_textdomain( BP_WIRE_PLUGIN_NAME, $mofile );
		}
	}
}
add_action ( 'bp_init', 'bp_wire_load_textdomain', 2 );

function bp_wire_setup_globals() {
	global $bp, $wpdb;
	/* For internal identification */
	$bp->wire->id = 'wire';
        $bp->wire->slug = BP_WIRE_SLUG;
        $bp->wire->format_notification_function = 'wire_format_notifications';
		/* Register this in the active components array */
	$bp->active_components[$bp->wire->slug] = $bp->wire->id;
	do_action( 'bp_wire_setup_globals' );
	$bp->profile->table_name_wire = $wpdb->base_prefix . 'bp_xprofile_wire';

	
}

add_action( 'bp_setup_globals', 'bp_wire_setup_globals' );
add_action( 'admin_menu', 'bp_wire_setup_globals', 2 );

function bp_wire_setup_nav() {
	global $bp;
	/* Add 'Wire' to the main navigation */
	bp_core_new_nav_item( array( 'name' => sprintf(BP_WIRE_LABEL.__(' <span>(%d)</span>', 'bp-wire'),bp_wire_get_total_wire_post_count_for_user()), 'slug' => $bp->wire->slug, 'position' => BP_WIRE_POSITION, 'screen_function' => 'bp_wire_screen_latest', 'default_subnav_slug' => 'all-posts', 'item_css_id' => $bp->wire->id ) );

	$wire_link = $bp->loggedin_user->domain . $bp->wire->slug . '/';
	
	/* Add the subnav items to the wire nav */
	bp_core_new_subnav_item( array( 'name' => __( 'All Posts', 'bp-wire' ), 'slug' => 'all-posts', 'parent_url' => $wire_link, 'parent_slug' => $bp->wire->slug, 'screen_function' => 'bp_wire_screen_latest', 'position' => 10 ) );

	if ( $bp->current_component == $bp->wire->slug ) {
		if ( bp_is_my_profile() ) {
			$bp->bp_options_title = sprintf(__('My %s', 'bp-wire'),BP_WIRE_LABEL);
		} else {
			$bp->bp_options_avatar = bp_core_fetch_avatar( array( 'item_id' => $bp->displayed_user->id, 'type' => 'thumb' ) );
			$bp->bp_options_title = $bp->displayed_user->fullname; 
		}
	}
	
	do_action( 'bp_wire_setup_nav' );
}
add_action( 'bp_setup_nav', 'bp_wire_setup_nav' );
add_action( 'admin_menu', 'bp_wire_setup_nav' );


/********************************************************************************
 * Screen Functions
 *
 * Screen functions are the controllers of BuddyPress. They will execute when their
 * specific URL is caught. They will first save or manipulate data using business
 * functions, then pass on the user to a template file.
 */

function bp_wire_screen_latest() {
    global $bp;
    if($bp->current_component==$bp->wire_slug&&$bp->current_action=='feed')
            return false;
	do_action( 'bp_wire_screen_latest' );
        if(bp_is_home ())
            bp_core_delete_notifications_for_user_by_type( $bp->loggedin_user->id, $bp->wire->id, 'new_wire_post' );
	bp_core_load_template( apply_filters( 'bp_wire_template_latest', 'wire/latest' ) );	
}


/********************************************************************************
 * Business Functions
 *
 * Business functions are where all the magic happens in BuddyPress. They will
 * handle the actual saving or manipulation of information. Usually they will
 * hand off to a database class for data access, then return
 * true or false on success or failure.
 */

function bp_wire_new_post( $item_id, $message, $component_name, $deprecated = false, $table_name = null ) {
	global $bp;
	
	if ( empty($message) || !is_user_logged_in() )
		return false;
	
	if ( !$table_name )
		$table_name = $bp->{$component_name}->table_name_wire;

	$wire_post = new BP_Wire_Post( $table_name );
	$wire_post->item_id = $item_id;
	$wire_post->user_id = $bp->loggedin_user->id;
	$wire_post->date_posted = time();
	$wire_post->content = $message;
	
	if ( !$wire_post->save() )
		return false;
	
	do_action( 'bp_wire_post_posted', $wire_post->id, $wire_post->item_id, $wire_post->user_id );
	
	return $wire_post;
}

function bp_wire_delete_post( $wire_post_id, $component_name, $table_name = null ) {
	global $bp;

	if ( !is_user_logged_in() )
		return false;

	if ( !$table_name )
		$table_name = $bp->{$component_name}->table_name_wire;
	
	$wire_post = new BP_Wire_Post( $table_name, $wire_post_id );
	
	if ( !is_site_admin() ) {
		if ( !$bp->is_item_admin ) {
			if ( $wire_post->user_id != $bp->loggedin_user->id )
				return false;
		}
	}
	
	if ( !$wire_post->delete() )
		return false;

	do_action( 'bp_wire_post_deleted', $wire_post->id, $wire_post->item_id, $wire_post->user_id, $component_name );
	
	return true;
}

// List actions to clear super cached pages on, if super cache is installed
add_action( 'bp_wire_post_deleted', 'bp_core_clear_cache' );
add_action( 'bp_wire_post_posted', 'bp_core_clear_cache' );

	
	
	
/****** profile wire posts******/
/**
 * profile_action_new_wire_post()
 *
 * Posts a new wire post to the users profile wire.
 *
 * @package BuddyPress XProfile
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 * @uses bp_wire_new_post() Adds a new wire post to a specific wire using the ID of the item passed and the table name.
 * @uses bp_core_add_message() Adds an error/success message to the session to be displayed on the next page load.
 * @uses bp_core_redirect() Safe redirects to a new page using the wp_redirect() function
 */
function profile_action_new_wire_post() {
	global $bp;

	if ( $bp->current_component != $bp->wire->slug )
		return false;

	if ( 'post' != $bp->current_action )
		return false;

	/* Check the nonce */
	if ( !check_admin_referer( 'bp_wire_post' ) )
		return false;

	if ( !$wire_post = bp_wire_new_post( $bp->displayed_user->id, $_POST['wire-post-textarea'], $bp->profile->slug, false, $bp->profile->table_name_wire ) ) {
		bp_core_add_message( sprintf(__( '%s message could not be posted. Please try again.', 'bp-wire' ),BP_WIRE_LABEL), 'error' );
	} else {
		bp_core_add_message( sprintf(__( '%s message successfully posted.', 'bp-wire' ),BP_WIRE_LABEL) );

		/* Record the notification for the reciever if it's not on their own wire */
		if ( !bp_is_home() ){
			bp_core_add_notification( $bp->loggedin_user->id, $bp->displayed_user->id, $bp->wire->id, 'new_wire_post' );
                       wire_notification_new_wire_post(array("sender_id"=>$bp->loggedin_user->id,"user_id"=>$bp->displayed_user->id,"content"=>$wire_post->content));

                }
		//we do not need the wire on the activity stream right

		do_action( 'xprofile_new_wire_post', &$wire_post );
	}

	//if ( !strpos( wp_get_referer(), $bp->wire->slug ) ) {
		//bp_core_redirect( $bp->displayed_user->domain );
	//} else {
		bp_core_redirect( $bp->displayed_user->domain . $bp->wire->slug );//always redirect to wire even if it is posted somewhere else
	//}
}
add_action( 'wp', 'profile_action_new_wire_post', 3 );

/**
 * xprofile_action_delete_wire_post()
 *
 * Deletes a wire post from the users profile wire.
 *
 * @package BuddyPress Wire
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 * @uses bp_wire_delete_post() Deletes a wire post for a specific wire using the ID of the item passed and the table name.
 * @uses bp_core_add_message() Adds an error/success message to the session to be displayed on the next page load.
 * @uses bp_core_redirect() Safe redirects to a new page using the wp_redirect() function
 */
function profile_action_delete_wire_post() {
	global $bp;

	if ( $bp->current_component != $bp->wire->slug )
		return false;

	if ( $bp->current_action != 'delete' )
		return false;

	if ( !check_admin_referer( 'bp_wire_delete_link' ) )
		return false;

	$wire_post_id = $bp->action_variables[0];

	if ( bp_wire_delete_post( $wire_post_id, $bp->profile->slug, $bp->profile->table_name_wire ) ) {
		bp_core_add_message( sprintf(__('%s message successfully deleted.', 'bp-wire'),BP_WIRE_LABEL ));

		
		do_action( 'profile_delete_wire_post', $wire_post_id );
	} else {
		bp_core_add_message( sprintf(__('%s post could not be deleted, please try again.', 'bp-wire'),BP_WIRE_LABEL), 'error' );
	}

	if ( !strpos( wp_get_referer(), $bp->wire->slug ) ) {
		bp_core_redirect( $bp->displayed_user->domain );
	} else {
		bp_core_redirect( $bp->displayed_user->domain. $bp->wire->slug );
	}
}
add_action( 'wp', 'profile_action_delete_wire_post', 3 );



/***** Installation********/
function profile_wire_install() {
	global $bp, $wpdb;

	if ( !empty($wpdb->charset) )
		$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";

	$sql[] = "CREATE TABLE {$bp->profile->table_name_wire} (
	  		   id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
			   item_id bigint(20) NOT NULL,
			   user_id bigint(20) NOT NULL,
			   content longtext NOT NULL,
			   date_posted datetime NOT NULL,
			   KEY item_id (item_id),
		       KEY user_id (user_id)
	 	       ) {$charset_collate};";

	require_once( ABSPATH . 'wp-admin/upgrade-functions.php' );
	dbDelta($sql);
	//update meta
	update_site_option( 'bp-wire-db-version',BP_WIRE_DB_VERSION );
	
}


function bp_wire_check_installed() {
	global $wpdb, $bp;

	if ( !is_site_admin() )
		return false;


	/* Need to check db tables exist, activate hook no-worky in mu-plugins folder. */
	if ( get_site_option('bp-wire-db-version') < BP_WIRE_DB_VERSION )
		profile_wire_install();
}
add_action( 'admin_menu', 'bp_wire_check_installed' );

function bp_wire_get_total_wire_post_count_for_user(){
global $bp;

    $user_id=$user_id = ( $bp->displayed_user->id ) ? $bp->displayed_user->id : $bp->loggedin_user->id;
    return BP_Wire_Post::total_count_for_user($user_id);
}



/*Notification*/
function wire_screen_notification_settings() {
	global $current_user; ?>
	<table class="notification-settings zebra" id="wire-notification-settings">
		<thead>
			<tr>
				<th class="icon"></th>
				<th class="title"><?php _e( BP_WIRE_LABEL, 'bp-wire' ) ?></th>
				<th class="yes"><?php _e( 'Yes', 'bp-wire' ) ?></th>
				<th class="no"><?php _e( 'No', 'bp-wire' )?></th>
			</tr>
		</thead>

		<tbody>
			<tr>
				<td></td>
				<td><?php printf(__( 'A member writes on your %s', 'bp-wire' ),strtolower(BP_WIRE_LABEL)); ?></td>
				<td class="yes"><input type="radio" name="notifications[notification_wire_new_post]" value="yes" <?php if ( !get_user_meta( $current_user->id, 'notification_wire_new_post', true ) || 'yes' == get_user_meta( $current_user->id, 'notification_wire_new_post', true ) ) { ?>checked="checked" <?php } ?>/></td>
				<td class="no"><input type="radio" name="notifications[notification_wire_new_post]" value="no" <?php if ( 'no' == get_user_meta( $current_user->id, 'notification_wire_new_post', true ) ) { ?>checked="checked" <?php } ?>/></td>
			</tr>
			

			<?php do_action( 'wire_screen_notification_settings' ) ?>
		</tbody>
	</table>
<?php
}
add_action( 'bp_notification_settings', 'wire_screen_notification_settings', 2 );

function wire_format_notifications( $action, $item_id, $secondary_item_id, $total_items ) {
	global $bp;

	if($action=='new_wire_post'){
	if ( (int)$total_items > 1 ) {
			return apply_filters( 'bp_wire_multiple_new_post_notification', '<a href="' . $bp->loggedin_user->domain . $bp->wire->slug . '/" title="' . sprintf(__( 'My %s', 'bp-wire' ),BP_WIRE_LABEL) . '">' . sprintf( __('You have %d new %s posts', 'bp-wire' ), (int)$total_items,strtolower(BP_WIRE_LABEL) ) . '</a>', (int)$total_items );
			} else {
				$user_fullname = bp_core_get_user_displayname( $item_id );
				$user_url = bp_core_get_user_domain( $bp->loggedin_user->id).BP_WIRE_SLUG."/";
				return apply_filters( 'bp_wire_multiple_new_post_notification', '<a href="' . $user_url . '?new" title="' . $user_fullname .'\'s profile">' . sprintf( __( '%s wrote you a new %s post', 'bp-wire' ), $user_fullname,strtolower(BP_WIRE_LABEL) ) . '</a>', $user_fullname );
			}
        }
		
	do_action( 'wire_format_notifications', $action, $item_id, $secondary_item_id, $total_items );

	return false;
}




/**** Email notification*/
function wire_notification_new_wire_post( $args ) {
	global $bp;
	extract($args);

	$sender_name = bp_core_get_user_displayname( $sender_id );

        if ( $sender_id == $user_id || 'no' == get_user_meta( $user_id, 'notification_wire_new_post', true ) ) return;

		$ud = get_userdata( $user_id );
		$message_link = bp_core_get_user_domain( $user_id ) . BP_WIRE_SLUG .'/';
		$settings_link = bp_core_get_user_domain( $user_id ) .  BP_SETTINGS_SLUG . '/notifications/';

		$sender_name = stripslashes( $sender_name );
               
		$content = stripslashes( wp_filter_kses( $content ) );

		// Set up and send the message
		$email_to = $ud->user_email;
		$email_subject = '[' . wp_specialchars_decode( get_blog_option( BP_ROOT_BLOG, 'blogname' ) ) . '] ' . sprintf( __( 'New %s post from %s', 'bp-wire' ), $sender_name,strtolower(BP_WIRE_LABEL) );
$email_text=__(
'%s wrote on your %s:

"%s"

To view and read your %s posts please log in and visit: %s

---------------------
', 'bp-wire' );
$email_text=apply_filters("wire_notification_text",$email_text);//change the text to anything
		$email_content = sprintf( $email_text, $sender_name, strtolower(BP_WIRE_LABEL), $content,strtolower(BP_WIRE_LABEL), $message_link );


		$email_content .= sprintf( __( 'To disable these notifications please log in and go to: %s', 'bp-wire' ), $settings_link );

		/* Send the message */
		$email_to = apply_filters( 'wire_notification_new_wire_post_to', $email_to );
		$email_subject = apply_filters( 'wire_notification_new_wire_subject', $email_subject, $sender_name );
		$email_content = apply_filters( 'wire_notification_new_wire_message', $email_content, $sender_name, $subject, $content, $message_link );

		wp_mail( $email_to, $email_subject, $email_content );
	
}

function bp_wire_action_profile_feed() {
	global $bp, $wp_query;

	if ( $bp->current_component != $bp->wire->slug || !$bp->displayed_user->id || $bp->current_action != 'feed')
		return false;

	$wp_query->is_404 = false;
	status_header( 200 );

	include_once(BP_WIRE_PLUGIN_DIR. '/inc/feed/profile-feed.php' );
	die;
}
add_action( 'wp', 'bp_wire_action_profile_feed', 4 );

?>