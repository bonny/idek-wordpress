<?php

/**
 * Add support for columns using shortcodes.
 * 
 * [columns] is used to wrap all columns
 * [column] is used to wrap each column.
 * The wrapper is used to clear floats so content after last column is not floated next to column.
 * 
 * 
 * Example usage:
 *
 * [columns]
 *
 *   [column]
 *
 *	    Lorem ipsum dolor sit amet.
 *      Lorem ipsum dolor sit amet.
 *      ...column content...
 *
 *   [/column]
 *
 *   [column]
 *     another column and its content
 *   [/column]
 *
 * [/columns]
 *
 */

namespace EP\columns;

add_action("init", __NAMESPACE__ . '\add_shortcodes');
function add_shortcodes() {

	add_shortcode( "columns", __NAMESPACE__ . '\do_shortcode_columns' );
	add_shortcode( "column", __NAMESPACE__ . '\do_shortcode_column' );
	add_filter('the_content', __NAMESPACE__ . '\clean_shortcodes');
	// add_action("wp_head", __NAMESPACE__ . '\add_example_styles');

}

/**
 * Remove br and p that gets added to shortcodes and adding lots of unwanted vertical space
 * Based on solution from http://www.dino-digital.com/news/wordpress/unwrap-shortcode-from-paragraph-tags/
 */
function clean_shortcodes($content){   

	$array = array (

		'<p>[columns]' => '[columns]', 
		'[columns]</p>' => '[columns]', 
		'[/columns]</p>' => '[/columns]', 
		'[/columns]<br />' => '[/columns]',		

		'<p>[column]' => '[column]', 
		'[column]</p>' => '[column]', 
		'[/column]</p>' => '[/column]', 
		'[/column]<br />' => '[/column]'

	);

	$content = strtr($content, $array);

	return $content;

}

/**
 * Add example styles
 */
function add_example_styles() {
	?>
	<style>

		.ep-columns, 
		.ep-columns * {
			-webkit-box-sizing: border-box; -moz-box-sizing: border-box; box-sizing: border-box;
		}
	
		/* Smaller screens */
		.ep-columns {
			overflow: hidden;
		}

		.ep-column {
			float: left;
			width: 50%;
			padding: .5em;
		}

		.ep-column:nth-child(2n+3) {
			clear: left;
		}


		/* Larger screens */
		@media only screen and (min-width : 1224px) {
	
			.ep-column {
				width: 33.333%;
			}
		
			/* Unclear small version */
			.ep-column:nth-child(2n+3) {
				clear: none;
			}

			/* Clear first cols */
			.ep-column:nth-child(3n+4) {
				clear: left;
			}

		}

	</style>
	<?php
}

function do_shortcode_columns($args, $content) {
	
	$defaults = array();
	$args = wp_parse_args( $args, $defaults );

	$out = sprintf('<div class="ep-columns">%1$s</div>', $content);

	$out = do_shortcode( $out );

	return $out;

}

function do_shortcode_column($args, $content) {
	
	$defaults = array();
	$args = wp_parse_args( $args, $defaults );

	$out = sprintf('<div class="ep-column"><div class="ep-column-inner">%1$s</div></div>', $content);

	$out = do_shortcode( $out );
	
	return $out;

}
