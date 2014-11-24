<?php
/**
 * As found here
 * https://github.com/ryanburnette/fragment-cache
 * Usage:
 *  <?php fragment_cache('my_block_tag', DAY_IN_SECONDS, function() { ?>
 * 
 * [...]
 * 
 * <?php } ?>
 *
 * "Clearing the cache is why I have a filter attached to the line where the $key variable is created. I attach a randomly generated number to the key, then have a button I put in the webmaster’s admin area that says “clear cache.” By clicking it they are regenerating the randomly generated number. The old transients are cleared upon timeout. Deleting the transient works, but that would require keeping up with what transients you are creating and deleting those or some other fanciness. I just use the random number because it’s quick and easy.
 *
 * I also have a couple lines in there that bypasses the whole function if the current user is logged in. I usually develop while logged into WordPress. So, that’s why that is there. It’s easy enough to remove those lines if they are not needed.""
 */
function fragment_cache($key, $ttl, $function) {
	$key = apply_filters('fragment_cache_prefix','fragment_cache_').$key;
	$output = get_transient($key);
	if ( false === $output ) {
		ob_start();
		call_user_func($function);
		$output = ob_get_clean();
		set_transient($key, $output, $ttl);
	}
	echo $output;
}
