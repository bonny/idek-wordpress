<?php

/**
 * Add X-UA-Compatible-header to IE browsers
 * This is better than adding this info in the html header
 * because the data in html header can be overridden by local intranet settings,
 * i.e. 
 */

namespace EP\frontend\ie_header;

add_filter('wp_headers', __NAMESPACE__ . '\modify_header');

function modify_header($headers) {

	if (isset($_SERVER['HTTP_USER_AGENT']) && (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false)) {

		$headers['X-UA-Compatible'] = 'IE=edge,chrome=1';

	}
	
	return $headers;
}
