<?php
/* Home for Backwards Compatibility Functions */

/* Form Display Functions */
if (!function_exists('mc_display_widget')){
    function mc_display_widget($args=array()){
        mailchimpSF_signup_form($args);
    }
}
if (!function_exists('mailchimpSF_display_widget')){
    function mailchimpSF_display_widget($args=array()){
        mailchimpSF_signup_form($args);
    }
}


/* Shortcodes */
add_shortcode('mailchimpsf_widget', 'mailchimpSF_shortcode');


/* Functions for < WP 3.0 Compat */

if (!function_exists('home_url')) {
	/**
	 * Retrieve the home url for the current site.
	 *
	 * Returns the 'home' option with the appropriate protocol,  'https' if
	 * is_ssl() and 'http' otherwise. If $scheme is 'http' or 'https', is_ssl() is
	 * overridden.
	 *
	 * @package WordPress
	 * @since 3.0.0
	 *
	 * @uses get_home_url()
	 *
	 * @param  string $path   (optional) Path relative to the home url.
	 * @param  string $scheme (optional) Scheme to give the home url context. Currently 'http','https'
	 * @return string Home url link with optional path appended.
	*/
	function home_url( $path = '', $scheme = null ) {
		return get_home_url(null, $path, $scheme);
	}
	
}

if (!function_exists('get_home_url')) {
	/**
	 * Retrieve the home url for a given site.
	 *
	 * Returns the 'home' option with the appropriate protocol,  'https' if
	 * is_ssl() and 'http' otherwise. If $scheme is 'http' or 'https', is_ssl() is
	 * overridden.
	 *
	 * @package WordPress
	 * @since 3.0.0
	 *
	 * @param  int $blog_id   (optional) Blog ID. Defaults to current blog.
	 * @param  string $path   (optional) Path relative to the home url.
	 * @param  string $scheme (optional) Scheme to give the home url context. Currently 'http','https'
	 * @return string Home url link with optional path appended.
	*/
	function get_home_url( $blog_id = null, $path = '', $scheme = null ) {
		$orig_scheme = $scheme;

		if ( !in_array( $scheme, array( 'http', 'https' ) ) )
			$scheme = is_ssl() && !is_admin() ? 'https' : 'http';

		if ( empty( $blog_id ) || !is_multisite() )
			$home = get_option( 'home' );
		else
			$home = get_blog_option( $blog_id, 'home' );

		$url = str_replace( 'http://', "$scheme://", $home );

		if ( !empty( $path ) && is_string( $path ) && strpos( $path, '..' ) === false )
			$url .= '/' . ltrim( $path, '/' );

		return apply_filters( 'home_url', $url, $path, $orig_scheme, $blog_id );
	}
}

if (!function_exists('is_multisite')) {
	/**
	 * Whether Multisite support is enabled
	 *
	 * @since 3.0.0
	 *
	 * @return bool True if multisite is enabled, false otherwise.
	 */
	function is_multisite() {
		if ( defined( 'MULTISITE' ) )
			return MULTISITE;

		if ( defined( 'VHOST' ) || defined( 'SUNRISE' ) )
			return true;

		return false;
	}
}
?>