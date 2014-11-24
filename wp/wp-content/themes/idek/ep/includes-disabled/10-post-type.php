<?php
/**
 * Setup things for Custom post type
 */

namespace EP\project\posttype;

const POSTTYPE = "ep-posttype";

add_action("init", __NAMESPACE__ . '\add_post_types');


function add_post_types() {

	// Post type for Locations, i.e. movie theaters, cafes, etc.
	register_post_type( POSTTYPE, array(
		"label" => __("Posttype", "ep"),
		"labels" => array(
			"name" => __("Posttype", "ep"),
			"singular_name" => __("Posttype", "ep"),
			"menu_name" => __("Posttypes", "ep"),
			"all_items" => __("Posttypes", "ep"),
			"add_new" => __("Add posttype", "ep")
		),
		"public" => true,
		"show_in_admin_bar" => false,
		"show_in_menu" => "edit.php?post_type=" . \EP\project\posttype\POSTTYPE,
		"supports" => array(
			"title",
			"editor",
			"revisions",
			"page-attributes"
		),
		"menu_position" => 1,
		"rewrite" => array(
			"slug" => "posttype",
			"with_front" => false
		),
		"menu_icon" => "dashicons-smiley"
	) );

}
