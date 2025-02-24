<?php
/**
 * Plugin Name: Tutor LMS Pro
 * Plugin URI: https://tutorlms.com
 * Description: Power up Tutor LMS plugins by Tutor Pro
 * Author: Themeum
 * Version: 3.2.3
 * Author URI: http://themeum.com
 * Requires PHP: 7.4
 * Requires at least: 5.3
 * Tested up to: 6.7
 * Text Domain: tutor-pro
 * Domain Path: /languages/
 * Requires Plugins: tutor
 *
 * @package TutorPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
update_option( 'tutor_license_info', [ 'activated' => true, 'license_to' => $_SERVER['SERVER_NAME'] ] );
/**
 * Tutor Pro dependency on Tutor core
 *
 * Define Tutor core version on that Tutor Pro is dependent to run,
 * without require version pro will just show admin notice to install require core version.
 *
 * @since 2.0.0
 */
define( 'TUTOR_CORE_REQ_VERSION', '3.2.3' );
define( 'TUTOR_PRO_VERSION', '3.2.3' );
define( 'TUTOR_PRO_FILE', __FILE__ );

/**
 * Load tutor-pro text domain for translation
 *
 * @since 1.0.0
 */
add_action( 'init', fn () => load_plugin_textdomain( 'tutor-pro', false, basename( __DIR__ ) . '/languages' ) );

if ( ! function_exists( 'tutor_pro' ) ) {
	/**
	 * Tutor Pro helper function
	 *
	 * @return object
	 */
	function tutor_pro() {
		if ( isset( $GLOBALS['tutor_pro_plugin_info'] ) ) {
			return $GLOBALS['tutor_pro_plugin_info'];
		}

		$path = plugin_dir_path( TUTOR_PRO_FILE );
		$info = array(
			'path'         => $path,
			'templates'    => trailingslashit( $path . 'templates' ),
			'languages'    => trailingslashit( $path . 'languages' ),
			'url'          => plugin_dir_url( TUTOR_PRO_FILE ),
			'icon_dir'     => plugin_dir_url( TUTOR_PRO_FILE ) . 'assets/images/',
			'basename'     => plugin_basename( TUTOR_PRO_FILE ),
			'version'      => TUTOR_PRO_VERSION,
			'nonce_action' => 'tutor_pro_nonce_action',
			'nonce'        => '_wpnonce',
		);

		$GLOBALS['tutor_pro_plugin_info'] = (object) $info;
		return $GLOBALS['tutor_pro_plugin_info'];
	}
}

require 'classes/Init.php';

( new \TUTOR_PRO\Init() )->run();
