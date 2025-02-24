<?php
/**
 * SSO helpers
 *
 * @since   2.6.30
 * @package BuddyBossPro/SSO
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Return the sso path.
 *
 * @since 2.6.30
 *
 * @param string $path path of sso.
 *
 * @return string path.
 */
function bb_sso_path( $path = '' ) {
	$bb_platform_pro = bb_platform_pro();

	return trailingslashit( $bb_platform_pro->sso_dir ) . trim( $path, '/\\' );
}

/**
 * Return the sso url.
 *
 * @since 2.6.30
 *
 * @param string $path url of sso.
 *
 * @return string url.
 */
function bb_sso_url( $path = '' ) {
	return trailingslashit( bb_platform_pro()->sso_url ) . trim( $path, '/\\' );
}

/**
 * Is the SSO enabled.
 *
 * @since 2.6.30
 *
 * @param bool $retval  Optional. Fallback value if not found in the database.
 *                      Default: true.
 *
 * @return bool True if the SSO is enabled, otherwise false.
 */
function bb_enable_sso( $retval = false ) {

	/**
	 * Filters whether or not the SSO enable.
	 *
	 * @since 2.6.30
	 *
	 * @param bool $value Whether or not the SSO enable.
	 */
	return (bool) apply_filters( 'bb_enable_sso', (bool) bp_get_option( 'bb-enable-sso', $retval ) );
}

/**
 * Is the SSO additional data name enabled.
 *
 * @since 2.6.30
 *
 * @param bool $retval  Optional. Fallback value if not found in the database.
 *                      Default: true.
 *
 * @return bool True if the SSO additional data name is enabled, otherwise false.
 */
function bb_enable_additional_sso_name( $retval = false ) {

	/**
	 * Filters whether or not the SSO additional data name enable.
	 *
	 * @since 2.6.30
	 *
	 * @param bool $value Whether or not the SSO additional data name enable.
	 */
	return (bool) apply_filters( 'bb_enable_additional_sso_name', (bool) bp_get_option( 'bb-additional-sso-name', $retval ) );
}

/**
 * Is the SSO additional data profile picture enabled.
 *
 * @since 2.6.30
 *
 * @param bool $retval  Optional. Fallback value if not found in the database.
 *                      Default: true.
 *
 * @return bool True if the SSO additional data profile picture is enabled, otherwise false.
 */
function bb_enable_additional_sso_profile_picture( $retval = false ) {

	/**
	 * Filters whether or not the SSO additional data profile picture enable.
	 *
	 * @since 2.6.30
	 *
	 * @param bool $value Whether or not the SSO additional data profile picture enable.
	 */
	return (bool) apply_filters( 'bb_enable_additional_sso_profile_picture', (bool) bp_get_option( 'bb-additional-sso-profile-picture', $retval ) );
}

/**
 * SSO allowed tags.
 *
 * @since 2.6.30
 *
 * @return array Allowed tags.
 */
function bb_sso_allowed_tags() {

	$allowed_tags = array(
		'svg'      => array(
			'width'   => true,
			'height'  => true,
			'viewBox' => true,
			'fill'    => true,
			'xmlns'   => true,
		),
		'g'        => array(
			'clip-path' => true,
		),
		'path'     => array(
			'd'    => true,
			'fill' => true,
		),
		'defs'     => array(),
		'clipPath' => array(
			'id' => true,
		),
		'rect'     => array(
			'x'      => true,
			'width'  => true,
			'height' => true,
			'rx'     => true,
			'fill'   => true,
		),
	);

	return apply_filters( 'bb_sso_allowed_tags', $allowed_tags );
}

/**
 * Function to check if the sso params exists.
 *
 * @since 2.6.60
 *
 * @return bool True if the sso params exists, otherwise false.
 */
function bb_sso_get_params_exists() {
	$bb_sso_notices = isset( $_GET['bb-sso-notice'] ) ? (int) $_GET['bb-sso-notice'] : 0;
	$bb_sso_type    = isset( $_GET['sso_type'] ) ? sanitize_text_field( $_GET['sso_type'] ) : '';

	if ( 1 === $bb_sso_notices && $bb_sso_type ) {
		return true;
	}

	return false;
}

/**
 * Is the SSO registration options enabled.
 *
 * @since 2.6.60
 *
 * @param string $retval Optional. Fallback value if not found in the database.
 *
 * @return bool True if the SSO registration options are enabled, otherwise false.
 */
function bb_enable_sso_reg_options( $retval = false ) {

	/**
	 * Filters whether or not the SSO registration options enable.
	 *
	 * @since 2.6.60
	 *
	 * @param bool $value Whether or not the SSO registration options enable.
	 */
	return (bool) apply_filters( 'bb_sso_reg_options', bp_get_option( 'bb-sso-reg-options', $retval ) );
}
