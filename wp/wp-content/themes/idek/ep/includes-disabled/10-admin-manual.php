<?php

/**
 * 
 * 
 */

namespace ParkenZoo\UserManual;

const SLUG = "ep-user-manual";

// Only run this in admin
if ( ! is_admin() ) {
	return;
}

// Add post types
add_action("init", function() {

	$args_common = array(
		"labels" => array(
			"name" => __("Manual/FAQ", "parkenzoo"),
			"singular_name" => __("Manual", "parkenzoo")
		),
		"public" => false,
		// dashicons-welcome-learn-more
		// dashicons-format-aside
		// dashicons-book
		// dashicons-book-alt
		"menu_icon" => "dashicons-book",
		"menu_position" => 100,
		// "rewrite" => array( "slug" => REWRITE_SLUG ),
		"show_in_admin_bar" => false,
		"supports" => array(
			"title",
			"editor",
			// "thumbnail",
			"revisions",
			"page-attributes",
			"post-formats"
		),
		"hierarchical" => true
	);

	// Developers can add new manual entries
	// Non-developers can only view the manual
	if ( current_user_can("develop") ) {

		$args_develop = array(
			"show_ui" => true
		);

		$args = wp_parse_args( $args_develop, $args_common );

	} else {

		$args_non_develop = array(
			"show_ui" => true,
			'capabilities' => array(
				'create_posts' => false, // Removes support for the "Add New" function
			)
		);

		$args = wp_parse_args( $args_non_develop, $args_common );

	}

	register_post_type( SLUG, $args);

});

// change the "View page"-link that is used within wordpress to point at the manual page instead to a 404-page
add_action("post_type_link", function( $url, $post, $leavename ) {

	if ( is_a($post, "WP_Post") && SLUG === $post->post_type ) {
		
		$url = admin_url( "edit.php?post_type=ep-user-manual&page=ep-user-manual-menu-slug" . "#" . $post->post_name );

	}

	return $url;

}, 10, 3);

// Add menu item to view manual
add_action("admin_menu", function() {

	$parent_slug = "edit.php?post_type=" . SLUG;
	$page_title = "Manual";
	$menu_title = "Visa manual";
	$capability = "edit_pages";
	$menu_slug = SLUG . "-menu-slug";

	add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, __NAMESPACE__ . '\menu_page_output');


});

// If a non-developer user goes to manual posts overview screen then redirect to page with the actual manual
add_action("current_screen", function($screen) {
	
	if ( ! is_a($screen, "WP_Screen") || ! isset( $screen->base ) || ! isset( $screen->post_type ) ) {
		return;
	}

	if ( current_user_can("develop") ) {
		return;
	}

	if ($screen->base === "edit" && ( SLUG === $screen->post_type ) ) {
		$url = admin_url( "edit.php?post_type=ep-user-manual&page=ep-user-manual-menu-slug" );
		wp_safe_redirect( $url );
		exit;
	}

});

/**
 * Output styles for the menu page
 */
add_action("admin_head", function() {
	
	// Only output styles on our own page
	$screen = get_current_screen();
	if (is_null($screen) || $screen->post_type != SLUG ) {
		return;
	}

	?>
	<style>

		/* Hide first item in nav, the item that goes to post overview screen, since we don't wanna show that to users, they should go stright to the manual */
		#menu-posts-ep-user-manual .wp-first-item,
		.menu-icon-ep-user-manual  .wp-first-item
		 {
			display: none;
		}

		.wrap-ep-manual {

		}

		.wrap-ep-manual-inner {
			max-width: 900px;
			margin: 0 auto;
		}

		.wrap .ep-manual-headline {
			margin-top: 2em;
			margin-bottom: 2em;
		}

		.wrap-ep-manual-inner img,
		.wrap-ep-manual-inner .wp-caption {
			max-width: 100%;
		}

		.oembed,
		.oembed iframe {
			width: 100%;
			max-width: 100%;
		}

		.wrap-ep-manual-inner img {
			height: auto;
			border: 1px solid #ccc;
			padding: 10px;
			background: white;
			box-shadow: 1px 1px 5px rgba(0,0,0,0.2);
		}

		.wrap-ep-manual-inner .wp-caption-text {
			font-style: italic;
		}

		.ep-manual-toc {
			background-color: #fff;
			padding: 2em;
		}

		.ep-manual-toc li {
			margin: 6px 0;
		}
		
		
		.wrap-ep-manual-inner ul {
			list-style-type: disc;
			margin-left: 20px;
		}

		.ep-manual-toc-headline {
			margin-top: 0;
			font-size: 1.5em;
			/*display: none;*/
		}


		.ep-manual-articles {

		}

		.ep-manual-articles {
			font-size: 15px;
		}

		.ep-manual-articles p {
			font-size: 1em
		}

		.ep-manual-article {
			margin-top: 4em;
			margin-bottom: 4em;
			padding: 2em;
			-webkit-transition: all .5s ease-out;
			        transition: all .5s ease-out;			
		}

		.ep-manual-article:target,
		.ep-manual-article.is-scrolledTo {
			background: rgba(0,255,0,.2);
		}

		.ep-manual-article-headline {
			line-height: 1.2;
			font-size: 26px;
			font-weight: normal;
			margin-top: 0;
		}

		.ep-manual-article-headline a {
			text-decoration: none;
			color: inherit;
		}

		.ep-manual-article-headline a:hover {
			border-bottom: 1px dotted;
		}

	</style>

	<script>
		jQuery(function($) {

			// When click on toc link scroll smoothly and nice down to that article
			$(".ep-manual-toc").on("click", "a", function(e) {

				var target = $(this).attr("href");
				window.location = "#";

				$(".is-scrolledTo").removeClass("is-scrolledTo");

				$('html, body').animate({
					scrollTop: $(target).offset().top - 100
				}, 500, function() {

					$target = $(target);
					$target.addClass("is-scrolledTo");

					setTimeout(function() {

						$target.removeClass("is-scrolledTo");

					}, 2000);

				});

				e.preventDefault();

			});

			// when click on headline we go to the natural hash
			$(".ep-manual-article-headline a").on("click", function() {
				$(".is-scrolledTo").removeClass("is-scrolledTo");
			});

		});
	</script>
	<?
});

/**
 * Show manual as one single long page
 */
function menu_page_output() {

	$query_args = array(
		"post_type" => SLUG,
		"nopaging" => true,
		"orderby" => "menu_order",
		"order" => "ASC"
	);
	$query_manual_posts = new \WP_Query($query_args);

	?>

	<div class="wrap wrap-ep-manual">

		<div class="wrap-ep-manual-inner">

			<h2 class="ep-manual-headline">Manual/FAQ</h2>
		
			<div class="ep-manual-toc">
			
				<h3 class="ep-manual-toc-headline">Innehåll</h3>
				
				<ul>
					<?php
					wp_list_pages(array(
						"title_li" => "",
						"post_type" => SLUG
					));
					?>
				</ul>

			</div>

			<div class="ep-manual-articles">
				<?php

				$query_manual_posts->rewind_posts();
				while ( $query_manual_posts->have_posts() ) {

					$query_manual_posts->the_post();

					$elm_id = get_post_field( "post_name", get_post());

					$content = apply_filters("the_content", get_the_content());

					ob_start(); edit_post_link(); 
					$edit_post_link = ob_get_clean();

					printf( '
						<section>
							<div class="ep-manual-article" id="%2$s">
								<h1 class="ep-manual-article-headline">
									<a href="#%2$s">
										%1$s
									</a>
								</h1>
								%4$s
								%3$s
							</div>
						</section>
						',
						get_the_title(), // 1
						$elm_id, // 2
						$content, // 3
						$edit_post_link // 4
					);

				}
				?>
			</div>

		</div>

	</div>

	<?php

	wp_reset_postdata();

}

