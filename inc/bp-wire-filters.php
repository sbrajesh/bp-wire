<?php

/* Apply WordPress defined filters */
add_filter( 'bp_wire_post_content_before_save', 'bp_wire_filter_kses', 1 );
add_filter( 'bp_get_wire_post_content', 'bp_wire_filter_kses', 1 );

add_filter( 'bp_get_wire_post_content', 'wptexturize' );
add_filter( 'bp_get_wire_post_content', 'convert_smilies', 2 );
add_filter( 'bp_get_wire_post_content', 'convert_chars' );
add_filter( 'bp_get_wire_post_content', 'wpautop' );
add_filter( 'bp_get_wire_post_content', 'stripslashes_deep' );
add_filter( 'bp_get_wire_post_content', 'make_clickable' );

add_filter( 'bp_wire_post_content_before_save', 'force_balance_tags' );
add_filter( 'bp_get_wire_post_content', 'force_balance_tags' );

function bp_wire_filter_kses( $content ) {
	global $allowedtags;

	$wire_allowedtags = $allowedtags;
	$wire_allowedtags['span'] = array();
	$wire_allowedtags['span']['class'] = array();
	$wire_allowedtags['div'] = array();
	$wire_allowedtags['div']['class'] = array();
	$wire_allowedtags['div']['id'] = array();
	$wire_allowedtags['a']['class'] = array();
	$wire_allowedtags['img'] = array();
	$wire_allowedtags['img']['src'] = array();
	$wire_allowedtags['img']['alt'] = array();
	$wire_allowedtags['img']['class'] = array();
	$wire_allowedtags['img']['width'] = array();
	$wire_allowedtags['img']['height'] = array();
	$wire_allowedtags['img']['class'] = array();
	$wire_allowedtags['img']['id'] = array();
	$wire_allowedtags['img']['title'] = array();
	$wire_allowedtags['code'] = array();

	$wire_allowedtags = apply_filters( 'bp_wire_allowed_tags', $wire_allowedtags );
	return wp_kses( $content, $wire_allowedtags );
        
}

?>