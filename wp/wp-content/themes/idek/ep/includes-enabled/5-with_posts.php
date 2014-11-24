<?php
/**
 * WordPress WP_QUERY-wrapper to simplify getting and working with posts
 *
 * Does something with posts, using a callback
 * Setups global post variable before running callback
 * And restores it afterwards
 *
 * Examples:
 * https://gist.github.com/bonny/5005579
 *
 * @author Pär Thernstrom <https://twitter.com/eskapism>
 * @param ID, array, string, WP POST, WP_QUERY
 * @param $do callable Function to run for each matching post
 * @param bool $buffer_and_return_output True if output should be buffered and returned
 * @return Mixed Returns the return value of the $do function
 */
function with_posts($post_thing, $do, $buffer_and_return_output = FALSE) {

	if ( ! is_callable( $do ) ) return FALSE;

	// Set defaults
	$wp_query_args = array(
		"post_status" => "publish",
		"posts_per_page" => -1,
		"orderby" => "date",
		"order" => "DESC",
	);

	// Get all public custom post types and add to query args
	$get_post_types_args = array(
		"public" => TRUE
	);
	$post_types = get_post_types( $get_post_types_args, $output = 'names');
	$wp_query_args["post_type"] = array_keys($post_types);

	$posts_query = NULL;
	$callback_return = NULL;
	$buffered_output = NULL;
	$found_valid_post_thing = FALSE;

	global $post;
	$original_post_global = $post;

	if ( is_numeric( $post_thing ) ) {

		// If post_thing is numeric then get the post with that id
		$wp_query_args["post__in"] = array( (int) $post_thing);

		$found_valid_post_thing = TRUE;

	} elseif ( is_string( $post_thing ) ) {

		// If post_thing is a string,
		// check if it's a wp_query compatible string with args,
		// or simply a comma separated list of ids.
		// or none of that.
		// compatible format is like: 'post_type=regions&posts_per_page=3&orderby=title&order=asc

		parse_str($post_thing, $arr_parsed_thing);

		if ( is_null( $arr_parsed_thing ) || ! is_array( $arr_parsed_thing ) ) {
			// Something went bananas
			return;
		}

		// If size is just one, and key contains commas, and value is empty,
		// then this looks like a comma separated list of id's
		// or it could be a non-integer string = get post by path/slug/post_name
		if ( sizeof( $arr_parsed_thing ) === 1 ) {

			reset($arr_parsed_thing);
			$first_key = key($arr_parsed_thing);
			if ( $arr_parsed_thing[ $first_key ] === "" && strpos( $first_key , ",") !== FALSE ) {

				// If post_thing is a comma separated string then get the posts, in the order they are in the string

				// First check for numeric
				$arr_post_vals = explode(",", $first_key);

				/*
				Example all strings
				Array
				(
				    [0] => nickelodeon
				    [1] => se
				    [2] => fi
				    [3] => punkd
				)

				Example all integers
				Array
				(
				    [0] => 1
				    [1] =>
				    [2] => 2
				    [3] => 3
				    [4] => 5
				    [5] => 993
				)

				*/

				// Remove empty vals from array
				$arr_post_vals = array_filter($arr_post_vals);

				// Check if array only is integers
				$found_only_integers = TRUE;
				foreach ($arr_post_vals as $one_val) {

					if ( ! is_numeric($one_val) ) {
						$found_only_integers = FALSE;
						break;
					}
				}

				$arr_post_ids = NULL;

				// If not only integers, then assume post_slugs
				// So quickly fetch the ids of matching pages
				if ( FALSE === $found_only_integers ) {

					// Match post things like:
					// with_posts(",nickelodeon,se,fi,,punkd,,hepp,hopp,,"

					global $wpdb;
					$arr_sql_in = array();
					foreach ( $arr_post_vals as $one_val ) {
						$arr_sql_in[] = $wpdb->prepare("%s", $one_val);
					}

					$sql_in = implode(",", $arr_sql_in);
					$sql_in = "( $sql_in )";
					$sql = "SELECT ID from $wpdb->posts WHERE post_name IN $sql_in";
					$results = $wpdb->get_results( $sql, "OBJECT_K" );
					$arr_post_ids = array_keys( $results );

				} else {

					// Matched post thing like
					// with_posts("1,,2,3,5,993,5634,"
					$arr_post_ids = $arr_post_vals;

				}

				$wp_query_args["post__in"] = $arr_post_ids;
				$wp_query_args["orderby"] = "post__in";

				$found_valid_post_thing = TRUE;

			} else if ( $arr_parsed_thing[ $first_key ] === "" ) {

				// get post by slug.
				// Could we use get_page_by_path here for some reason? Would that improve anything?
				$wp_query_args["name"] = $first_key;
			}

		}

		// If still not found valid thing, it wasn't a comma separated list
		// So let's go with wp_query_args instead
		if ( ! $found_valid_post_thing ) {

			$wp_query_args = wp_parse_args($arr_parsed_thing , $wp_query_args);

			$found_valid_post_thing = TRUE;

		}

	} elseif ( is_object( $post_thing) && get_class( $post_thing ) === "WP_Post" ) {

		// If post_thing is a WP_Post-object, like the one you get when using get_post()
		$wp_query_args["post__in"] = array( (int) $post_thing->ID );

		$found_valid_post_thing = TRUE;

	} elseif ( is_object( $post_thing ) && get_class( $post_thing ) === "WP_Query" ) {

		// If post_thing is wp_query object
		// Then just use it
		$posts_query = $post_thing;

		$found_valid_post_thing = TRUE;

	} elseif ( is_array( $post_thing ) && isset( $post_thing[0] ) && is_array( $post_thing[0] ) && isset($post_thing[0]["ID"]) && is_numeric($post_thing[0]["ID"]) ) {

		// Post thing is an array of post arrays, like we get from get_posts or wp_get_recent_posts
		$arr_post_ids = array();
		foreach ($post_thing as $one_post_thing) $arr_post_ids[] = $one_post_thing["ID"];
		$wp_query_args["post__in"] = $arr_post_ids;
		$found_valid_post_thing = TRUE;

	} elseif( is_array( $post_thing ) && isset( $post_thing[0] ) && get_class( $post_thing[0] ) === "WP_Post" ) {

		// Post thing is array of post objects
		$arr_post_ids = array();
		foreach ($post_thing as $one_post_thing) $arr_post_ids[] = $one_post_thing->ID;
		$wp_query_args["post__in"] = $arr_post_ids;
		$found_valid_post_thing = TRUE;

	}

	// We're getting called with something we don't support
	if (FALSE === $found_valid_post_thing) {

		_doing_it_wrong( __FUNCTION__, 'You passed something to me that I don\'t understand', '3.5' );
		return FALSE;

	}

	if ($buffer_and_return_output === TRUE) {
		ob_start();
	}

	if ( is_null( $posts_query ) ) $posts_query = new wp_query($wp_query_args);
	if ( $posts_query->have_posts() ) {

		$arr_return_to_callback = array(
			"post_count" => NULL,
			"current_post" => NULL,
			"post" => NULL,
			"wp_query" => $posts_query,
		);

		while( $posts_query->have_posts() ) :

			$posts_query->the_post();

			$arr_return_to_callback["post_count"] = $posts_query->post_count;
			$arr_return_to_callback["current_post"] = $posts_query->current_post;
			$arr_return_to_callback["post"] = $posts_query->post;

			// Run callback, for each post
			// Also include some nice and useful stuff
			// the current post is the first argument, then an array with all other info
			$callback_return = call_user_func( $do, $arr_return_to_callback["post"], $arr_return_to_callback);

		endwhile;

	}

	// Should this be called or not?
	// If we have set global post to something else in the loop, then this destroys that,
	// meaning we can have different post before calling with_posts and after...
	// wp_reset_postdata();
	// setup postdata for original global post, instead of the one in the global query (that may have been overwritten)
	$post = $original_post_global;
	setup_postdata($original_post_global);

	if ($buffer_and_return_output === TRUE) {
		$buffered_output = ob_get_clean();
		$posts_query->buffered_output = $buffered_output;
	}

	// Return the posts_query we used
	return $posts_query;

}
