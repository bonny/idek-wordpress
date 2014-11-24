<?php

/**
 * Misc useful functions go here
 */


/**
 * EarthPeople debug
 * Output the type of a variable and it's contents in a <pre> tag
 *
 * @param mixed $var
 */
function ep_d($var) {

	printf('%1$sVariable is of type <strong>"%2$s"</strong> with value:%1$s', "\n", gettype($var));
	echo "<pre>";
	print_r($var);
	echo "</pre>";

}


// lägg på siddjup i body + om aktuellt artikel har barn
function ep_body_class($classes, $class) {
	global $post, $wp_query;
	$queried_object = $wp_query->get_queried_object();
	$child_count = 0;
	if (isset($queried_object)) {
		$parents = get_post_ancestors($post->ID);
		$children = get_children(array(
			"post_parent" => $post->ID,
			"post_type" => $queried_object->post_type,
			"post_status" => "publish"
		));
		$child_count = sizeof($children);
	}
	$classes[] = "post-childcount-$child_count";
	if ($child_count) {
		$classes[] = "post-has-children";
	} else {
		$classes[] = "post-no-children";
	}
	
	// skriv ut depth också
	$depth = 0;
	$parents = get_post_ancestors($post);
	$top_parent_id = $post->ID;
	if ($parents) {
		foreach ($parents as $one_parent_id) {
			$parent_post = get_post($one_parent_id);
			if ($parent_post->post_parent) {
				$top_parent_id = $one_parent_id;
				$depth++;
			}
		}
	}

	$classes[] = "post-depth-$depth";
	$classes[] = "post-top-id-$top_parent_id";
	
	return $classes;
}

/**
 * Simple wrapper for native get_template_part()
 * Allows you to pass in an array of parts and output them in your theme
 * e.g. <?php get_template_parts(array('part-1', 'part-2')); ?>
 *
 * @param 	array 
 * @return 	void
 * @author 	Keir Whitaker
 **/
function get_template_parts( $parts = array() ) {
	foreach( $parts as $part ) {
		get_template_part( $part );
	};
}

/**
 * Pass in a path and get back the page ID
 * e.g. get_page_id_from_path('about/terms-and-conditions');
 *
 * @param 	string 
 * @return 	integer
 * @author 	Keir Whitaker
 **/
function get_page_id_from_path( $path ) {
	$page = get_page_by_path( $path );
	if( $page ) {
		return $page->ID;
	} else {
		return null;
	};
}


/**
 * Custom callback for outputting comments 
 *
 * @return void
 * @author Keir Whitaker
 */
function starkers_comment( $comment, $args, $depth ) {
	$GLOBALS['comment'] = $comment; 
	?>
	<?php if ( $comment->comment_approved == '1' ): ?>	
	<li>
		<article id="comment-<?php comment_ID() ?>">
			<?php echo get_avatar( $comment ); ?>
			<h4><?php comment_author_link() ?></h4>
			<time><a href="#comment-<?php comment_ID() ?>" pubdate><?php comment_date() ?> at <?php comment_time() ?></a></time>
			<?php comment_text() ?>
		</article>
	<?php endif; ?>
	</li>
	<?php 
}

// Add css classes "current_page_item", "current_page_item" and "current_page_parent" to custom post types
// They are only added to regular pages by default
// As found here:
// http://kucrut.org/wp_list_pages-for-custom-post-types/
function ep_add_page_css_classes_to_custom_post_types( $css_class, $page, $depth, $args ) {

	if ( empty($args['post_type']) || !is_singular($args['post_type']) )
		return $css_class;

	$_current_page = get_queried_object();

	if ( in_array( $page->ID, $_current_page->ancestors ) )
		$css_class[] = 'current_page_ancestor';
	if ( $page->ID == $_current_page->ID )
		$css_class[] = 'current_page_item';
	elseif ( $_current_page && $page->ID == $_current_page->post_parent )
		$css_class[] = 'current_page_parent';

	return $css_class;

}


/**
 * Returns the content with more tag activated, for global post of for $post if supplied
 * @param string $read_more_string
 * @param post | id $post_arg
 */
function ep_get_the_content_force_more($read_more_string = "", $post_arg = null) {
	
	global $post, $more;

	$org_post = $post;
	if ($post_arg) {
		$post = get_post($post_arg);
		setup_postdata( $post );
	}

	$org_more = $more;
	$more = 0;
	ob_start();
	the_content($read_more_string);
	$more = $org_more;

	$post = $org_post;
	setup_postdata($post);

	return ob_get_clean();

}
