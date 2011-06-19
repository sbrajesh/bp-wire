<?php
//load css and js

//load the css file for the bp-wire

function bp_wire_load_css(){
    if(apply_filters ("bp_wire_load_css",true)){
        wp_enqueue_style("bp-wire-css", bp_wire_get_template_dir_url()."/style.css");
    }
}

add_action("wp_print_styles","bp_wire_load_css");//load css

//find the template directory url(parent/child theme, guive child theme priority
function bp_wire_get_template_dir_url(){
   if ( file_exists(STYLESHEETPATH . '/wire/style.css'))
           $theme_uri=get_stylesheet_directory_uri();//child theme
    else if ( file_exists(TEMPLATEPATH .'/wire/style.css' ) )
	    $theme_uri=get_template_directory_uri();//parent theme
    return $theme_uri."/wire";

}
?>