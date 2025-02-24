<?php
/**
 * Manage Assets.
 *
 * @package TutorPro\CourseBundle
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 2.2.0
 */

namespace TutorPro\CourseBundle;

use TUTOR\Input;
use TutorPro\CourseBundle\CustomPosts\CourseBundle;

/**
 * Assets Class.
 *
 * @since 2.2.0
 */
class Assets {
	/**
	 * Register hooks.
	 *
	 * @since 2.2.0
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'load_frontend_assets' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'load_backend_assets' ) );

		// Common assets.
		add_action( 'admin_enqueue_scripts', array( $this, 'load_common_assets' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'load_common_assets' ) );
		add_filter( 'tutor_localize_data', array( $this, 'localize_course_bundle_builder_data' ) );
	}


	/**
	 * Localize custom course bundle builder data for _tutorobject.
	 *
	 * @since 3.2.0
	 *
	 * @param array $data the localized data.
	 *
	 * @return array
	 */
	public function localize_course_bundle_builder_data( $data ) {
		if ( Utils::is_bundle_editor() ) {
			$full_settings                             = get_option( 'tutor_option', array() );
			$settings['chatgpt_enable']                = $full_settings['chatgpt_enable'] ?? '';
			$settings['chatgpt_key_exist']             = ! empty( $full_settings['chatgpt_api_key'] ?? '' );
			$settings['instructor_can_delete_course']  = $full_settings['instructor_can_delete_course'] ?? '';
			$settings['instructor_can_publish_course'] = $full_settings['instructor_can_publish_course'] ?? '';
			$settings['hide_admin_bar_for_users']      = $full_settings['hide_admin_bar_for_users'] ?? '';
			$data['backend_bundle_list_url']           = get_admin_url( null, 'admin.php?page=course-bundle' );
			$data['frontend_bundle_list_url']          = tutor_utils()->tutor_dashboard_url( 'my-bundles' );
			$data['settings']                          = array_merge( $settings, $data['settings'] );
			$data['max_upload_size']                   = size_format( wp_max_upload_size() );
		}

		return $data;
	}

	/**
	 * Load assets for frontend.
	 *
	 * @since 2.2.0
	 *
	 * @return void
	 */
	public function load_frontend_assets() {
		wp_enqueue_style( 'tutor-course-bundle-frontend', Utils::asset_url( 'css/frontend.css' ), array(), TUTOR_VERSION );
	}

	/**
	 * Load assets for backend.
	 *
	 * @since 2.2.0
	 *
	 * @return void
	 */
	public function load_backend_assets() {
		if ( is_admin() && ( CourseBundle::POST_TYPE === Input::get( 'page', '' ) || CourseBundle::POST_TYPE === Input::get( 'post_type', '' ) || CourseBundle::POST_TYPE === get_post_type( get_the_ID() ) ) ) {
			wp_enqueue_style( 'tutor-course-bundle-backend', Utils::asset_url( 'css/backend.css' ), array(), TUTOR_VERSION );
			wp_enqueue_script( 'tutor-course-bundle-backend', Utils::asset_url( 'js/backend.js' ), array( 'jquery' ), TUTOR_VERSION, true );
		}
	}

	/**
	 * Load common assets.
	 *
	 * @since 2.2.0
	 *
	 * @return void
	 */
	public function load_common_assets() {
		wp_enqueue_script( 'tutor-course-bundle-common', Utils::asset_url( 'js/common.js' ), array( 'jquery' ), TUTOR_VERSION, true );

		wp_add_inline_script(
			'tutor-course-bundle-common',
			'var tutorProCourseBundle = ' . wp_json_encode( self::inline_script_data() ),
			'high'
		);

		if ( Utils::is_bundle_editor() ) {
			// Fix: function print_emoji_styles and wp_admin_bar_header are deprecated since WP version 6.4.0!
			remove_action( 'wp_print_styles', 'print_emoji_styles' );
			remove_action( 'wp_head', 'wp_admin_bar_header' );

			wp_enqueue_script( 'wp-tinymce' );
			wp_enqueue_script( 'mce-view' );
			wp_enqueue_editor();

			wp_enqueue_media();

			$builder_js_url  = Utils::asset_url( 'js/bundle-builder/index.min.js' );
			$builder_js_path = Utils::asset_path( 'js/bundle-builder/index.min.js' );

			wp_enqueue_script(
				'tutor-course-bundle-builder',
				$builder_js_url,
				array( 'wp-i18n', 'wp-element' ),
				filemtime( $builder_js_path ),
				true
			);
		}
	}

	/**
	 * Inline script data to use in js
	 *
	 * @since 2.2.0
	 *
	 * @return array
	 */
	public static function inline_script_data(): array {
		$is_bundle_editor = false;

		// For frontend bundle editor.
		if ( tutils()->is_tutor_frontend_dashboard() && Input::get( 'bundle-id', 0, Input::TYPE_INT ) ) {
			$is_bundle_editor = true;
		}

		// For backend bundle editor.
		if ( is_admin() && ( CourseBundle::POST_TYPE === Input::get( 'post_type', '' ) || CourseBundle::POST_TYPE === get_post_type( get_the_ID() ) ) ) {
				$is_bundle_editor = true;
		}

		$data = array(
			'is_course_bundle_editor'     => $is_bundle_editor,
			'course_bundle_list_page_url' => admin_url( 'admin.php?page=' . CourseBundle::POST_TYPE ),
			'course_bundle_post_type'     => CourseBundle::POST_TYPE,
		);

		return apply_filters( 'tutor_pro_course_bundle_inline_data', $data );
	}


}
