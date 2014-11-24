<?php

/**
 * @author Pär Thernström
 */
class EP {

	// Cache group to use for the wp_cache_* functions
	var $cache_group = "ep";
	var $cache_namespace_key = null;

	// Bool to detect if debug should be outputed
	var $is_debug = false;

	/**
	 * Init class
	 */
	function init() {

		$this->detect_debug();

		$this->load_functions();

		$this->setup_cache();

		$this->add_actions_and_filters();

		// $this->add_debug_info_to_footer();

		$this->load_external_helpers();

		add_action( 'wp_enqueue_scripts', function() {

			wp_enqueue_style( 'parent-style', get_template_directory_uri().'/style.css' );

		} );

	}

	/**
	 * Setup cache namespace key, as explained here:
	 * http://core.trac.wordpress.org/ticket/4476
	 *
	 * When using wp_cache_set and wp_cache_get, do like this:
	 *
	 * $my_key = "foo_" . $this->cache_namespace_key . "_12345";
	 * $my_value = wp_cache_get( $my_key, $this->cache_group );
	 * wp_cache_set( $my_key, $my_vals, $this->cache_group );
	 *
	 */
	function setup_cache() {

		// Get previos saved namespace key
		$this->cache_namespace_key = wp_cache_get( 'cache_namespace_key', $this->cache_group );

		// If not set, initialize it
		if ( $this->cache_namespace_key === false ) {
			wp_cache_set( 'cache_namespace_key', 1, $this->cache_group );
		}

	}

	/**
	 * Increment cache group key, so next time caches are used they are freshed ("emptied")
	 * Use when caches need to be cleared, and you have keys that are dynamically created,
	 * or if you for some reason need to clear the whole cache for the group
	 */
	function cache_incr() {

		$this->cache_namespace_key = wp_cache_incr( 'cache_namespace_key', 1, $this->cache_group );
		echo "<br>new cache namespace key: " . $this->cache_namespace_key;

	}


	/**
	 * Load helper functions
	 */
	function load_functions() {

		require_once(dirname(__FILE__) . "/ep/ep-functions.php");
		//require_once(dirname(__FILE__) . "/ep/ep-simple-front-end-edit-buttons.php");

	}

	/**
	 * Load external helpers
	 * Simple place a PHP file in includes-enabled/ and it will be loaded
	 * Files are loaded in alphabetical order
	 */
	function load_external_helpers() {

		// find and include files in bugs directory
		$files = glob( get_stylesheet_directory() . "/ep/includes-enabled/*");

		// Can't trust that glob always returns files in same order
		// https://glotpress.trac.wordpress.org/ticket/211
		natsort($files);

		foreach ($files as $filepath) {
			// Use load_template so $post and other globals are automatically set
			load_template($filepath, true, true );
		}

	}

	/**
	 * Detect debug mode
	 * Default if on a *.ep-domain, otherwise can be activated with querystring like:
	 * example.com/?ep-enable-debug=1
	 */
	function detect_debug() {

		$is_debug = false;

		if ( preg_match('/.ep$/', $_SERVER["HTTP_HOST"] ) ) {
			// if domain has top level domain ep, like domain.ep | example.ep | site.ep | *.ep
			$is_debug = true;
		} else if ( isset( $_GET["ep-enable-debug"] ) && $_GET["ep-enable-debug"] ) {
			// if debug flag is set
			$is_debug = true;
		}

		$this->is_debug = $is_debug;

	}

	/**
	 * Actions and filters that are to be run during all request
	 */
	function add_actions_and_filters() {

		// Load our scripts and styles
		add_action('wp_enqueue_scripts', array($this, 'enqueue_styles_and_scripts') );

		// Add things to head-stuff, like titles, tagss
		add_filter('wp_title', array($this, "add_tagline_to_title_if_front_or_home"), 10, 3);

		// Makes the function var_template_include() work
		add_filter( 'template_include', array($this, 'var_template_include'), 1000 );

	}

	/**
	 * Add tagline to title if we are of front page or home
	 */
	function add_tagline_to_title_if_front_or_home($title, $sep, $seplocation) {

		$title .= get_bloginfo( 'name' );

		$site_description = get_bloginfo( 'description', 'display' );
		if ( $site_description && ( is_home() || is_front_page() ) ) {
			$title = "$title $sep $site_description";
		}

		return $title;
	}


	/**
	 * Add scripts for this site
	 * Automatically cache busts them, based on last changed date on any file
	 */
	function enqueue_styles_and_scripts() {

		// find modification time of the latest js or css file, max one folder down
		#$files = array_merge( glob( plugin_dir_path( __FILE__ ) . "*.{css,js}", GLOB_BRACE ), glob( plugin_dir_path( __FILE__ ) . "*/*.{css,js}", GLOB_BRACE ) );
		#$files = array_combine($files, array_map("filemtime", $files));
		#arsort($files);		
		#$latest_file_time = $files[key($files)];

		// assetsminify takes care of this
		$latest_file_time = 1;

		// queue styles
		//wp_enqueue_style("style_screen", get_template_directory_uri() . '/scss/style.scss', null, $latest_file_time);
		//wp_enqueue_style("style_screen", get_template_directory_uri() . '/css/main.css', null, $latest_file_time);

		// queue scripts
		wp_enqueue_script("ep_scripts", get_stylesheet_directory_uri() . '/js/common.js', array("jquery"), $latest_file_time, true);

		// jquery cookie
		//wp_enqueue_script("jquery.cookie", get_template_directory_uri() . '/bower_components/jquery.cookie/jquery.cookie.js', array("jquery", "ep_scripts"), $latest_file_time, true);


	}


	/**
	 * Sets a global variable to track the current template being used
	 * Needed for get_current_template()
	 *
	 * Code to get current template. Found here:
	 * http://wordpress.stackexchange.com/questions/10537/get-name-of-the-current-template-file
	 */
	function var_template_include( $t ){
		$GLOBALS['current_theme_template'] = basename($t);
		return $t;
	}

	/**
	 * Return the name of the current template
	 * @return string Template file name
	 */
	function get_current_template( $echo = false ) {

		if( !isset( $GLOBALS['current_theme_template'] ) )
			return false;

		if ( $echo )
			echo $GLOBALS['current_theme_template'];
		else
			return $GLOBALS['current_theme_template'];

	}

	/**
	 * Show debug info in the footer, if ep_debug is detected/activated
	 */
	function add_debug_info_to_footer() {

		add_action("wp_footer", function() {
			global $wpdb;
			?>
			<div class="ep-debug ep-debug-footer">
				<?php echo $GLOBALS["ep"]->get_current_template() ?>:
				<?php echo $wpdb->num_queries; ?> <?php _e('queries'); ?>,
				generated in <?php timer_stop(1); ?> seconds,
				<?php echo round( memory_get_peak_usage() / 1024 / 1024, 2 ) ?> MB peak memory usage.
			</div>
		<?php
		});

	}


} // end class

$GLOBALS["ep"] = new EP();
$GLOBALS["ep"]->init();

