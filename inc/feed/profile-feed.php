<?php
/**
 * RSS2 Feed Template for displaying a users profile wire feed
 *
 * @package BuddyPress
 */
header('Content-Type: text/xml; charset=' . get_option('blog_charset'), true);
header('Status: 200 OK');
?>
<?php echo '<?xml version="1.0" encoding="'.get_option('blog_charset').'"?'.'>'; ?>

<rss version="2.0"
	xmlns:content="http://purl.org/rss/1.0/modules/content/"
	xmlns:wfw="http://wellformedweb.org/CommentAPI/"
	xmlns:dc="http://purl.org/dc/elements/1.1/"
	xmlns:atom="http://www.w3.org/2005/Atom"
	<?php do_action('bp_activity_group_feed'); ?>
>

<channel>
    <title><?php echo bp_site_name() ?> | <?php echo bp_get_displayed_user_fullname() ?> | <?php _e( BP_WIRE_LABEL, 'bp-wire' ); ?></title>
	<atom:link href="<?php self_link(); ?>" rel="self" type="application/rss+xml" />
        <link><?php echo bp_get_displayed_user_link() . $bp->wire->slug . '/feed' ?></link>
        <description><?php printf( __( '%s - Profile %s Feed', 'bp-wire' ), bp_get_displayed_user_fullname(),BP_WIRE_LABEL  ) ?></description>
	<pubDate><?php echo mysql2date('D, d M Y H:i:s O', bp_wire_get_last_updated("profile",$bp->diaplyed_user->id), false); ?></pubDate>
	<generator>http://buddypress.org/?v=<?php echo BP_VERSION ?></generator>
	<language><?php echo get_option('rss_language'); ?></language>
	<?php do_action('bp_wire_group_feed_head'); ?>
        <?php global $bp;?>
        <?php if ( bp_has_wire_posts( 'component_slug='.$bp->profile->slug.'&item_id=' . $bp->displayed_user->id ."&max=20" ) ) : ?>
			<?php while ( bp_wire_posts() ) : bp_the_wire_post(); ?>
			<item>
				<guid><?php echo bp_wire_get_post_permalink() ?></guid>
				<title><![CDATA[<?php echo bp_wire_feed_item_title() ?>]]></title>
				<link><?php echo bp_wire_get_post_permalink() ?></link>
				<pubDate><?php echo mysql2date('D, d M Y H:i:s O', bp_get_wire_feed_item_date(), false); ?></pubDate>

				<description>
					<![CDATA[
						<?php echo bp_wire_feed_item_description() ?>


					]]>
				</description>
				<?php do_action('bp_wire_group_feed_item'); ?>
			</item>
		<?php endwhile; ?>

	<?php endif; ?>
</channel>
</rss>