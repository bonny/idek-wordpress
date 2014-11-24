<?php

/**
 * Add developer capability to some users
 * Can be used to for example show more/other info in admin area for only developers.
 * 
 * Example
 * <code>
 * if ( current_user_can("develop") ) {
 *   // omg user haz dev skillz
 *   // do awesome stuff here
 * }
 * </code>
 *
 * Todo/Bugs
 * - If a user has been granded the developer capability, it will stick. So even if their email is changed
 *   from @earthpeople.se to something else, they will still have the capability.
 *
 */

namespace EP\admin\devcap;

// Name of the capability to add
const CAPABILITY = "develop";

// Name of option to store info
const OPTION_INFO_KEY = "ep_devcap_options";

/*
 * Check if capabilties needs update on init
 * Usually caps are added when user is added/changed
 * but if this dropin is added to an existing site we don't want to have to edit a user to get dev cap
 */
add_action("init", function() {

	// Check if this has been done already
	$update_needed = false;	
	$options = get_option( OPTION_INFO_KEY );

	if ( empty( $options ) ) {

		// If no options exists then update is needed
		$update_needed = true;

	} else {

		// Options exists, but it may be set to update
		if ( ! isset( $options["update_needed"] ) ) {
			$update_needed = true;
		} else {
			// key with update info exists, so use value from there to determine update
			$update_needed = $options["update_needed"];
		}

	}

	if ( ! $update_needed ) {
		return;
	}

	add_caps_to_users();

});

/**
 * Add capability to users
 */
function add_caps_to_users() {
	
	// Get all users that have email that ends with @earthpeople.se
	// and that are admins
	$args = array(
		"search" => "*@earthpeople.se",
		"search_columns" => array("user_email"),
		"role" => "administrator"
	);

	$user_query = new \WP_User_Query( $args );
	
	$arr_user_emails_with_cap = array();

	// Loop through all found users and add capability
	foreach ( $user_query->results as $one_user ) {

		if ( ! is_a($one_user, "WP_User") ) {
			continue;
		}
	
		$one_user->add_cap( CAPABILITY );

		$arr_user_emails_with_cap[] = $one_user->user_email;

	}

	$arr_info = array(
		"update_needed" => false,
		"added_user_emails" => $arr_user_emails_with_cap
	);

	update_option( OPTION_INFO_KEY, $arr_info );

}

/**
 * Re-add caps again after the folling actions are being called
 * delete_user
 * profile_update - Runs when a user's profile is updated. Action function argument: user ID.
 * user_register - Runs when a user's profile is first created. Action function argument: user ID.
 * wpmu_new_user - Runs when a user's profile is first created in a Multisite environment. Action function argument: user ID. If not in Multisite then use user_register.
 */
add_action("admin_init", function() {

	$arr_actions_to_update = array(
		"delete_user",
		"profile_update",
		"user_register",
		"wpmu_new_user"
	);

	foreach ( $arr_actions_to_update as $one_action ) {
		add_action($one_action, __NAMESPACE__ . '\add_caps_to_users');
	}

});


/**
 * Show developers on a dashboard meta box
 * Should be added using add_meta_boxes action, but it seems to be to late (action never being called)
 */
// wp_add_dashboard_widget( $widget_id, $widget_name, $callback, $control_callback = null, $callback_args = null ) {
add_action("admin_init", function($post_type, $post = "") {

	if ( ! current_user_can(CAPABILITY) ) {
		return;
	}

	add_meta_box('ep_devcaps_widget', 'EP DevCaps', __NAMESPACE__ . '\dashboard_meta_box_output', 'dashboard', 'side', 'high');

}, 10, 2);

function dashboard_meta_box_output() {

	$options = get_option(OPTION_INFO_KEY, array());
	
	if (empty($options) || empty($options["added_user_emails"])) {

		echo "<p>No developer users found.</p>";
	
	} else {

		echo "<h4>Users with developer capability</h4>";

		echo "<ul>";

		foreach ($options["added_user_emails"] as $user_email) {
			printf('<li>%1$s</li>', $user_email);
		}

		echo "</ul>";

	}

	?>
	<p><small>This meta box is only visible for developers.</small></p>
	<?php


}

