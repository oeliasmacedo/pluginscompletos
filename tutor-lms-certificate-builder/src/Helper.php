<?php
/**
 * Helper ulits functions
 *
 * @package  Tutor\Certificate\Builder
 * @author   Themeum <support@themeum.com>
 * @license  GPLv2 or later
 * @link     https://www.themeum.com/product/tutor-lms/
 * @since    1.0.0
 */

namespace Tutor\Certificate\Builder;

/**
 * Class Helper
 *
 * @package Tutor\Certificate\Builder
 *
 * @since   1.0.0
 */
class Helper {

	/**
	 * Is open editor
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 *
	 * @return bool True if editor is active, false otherwise.
	 */
	public static function is_open_editor() {
		return ( isset( $_GET['action'] ) && 'tutor_certificate_builder' === $_GET['action'] );
	}

	/**
	 * On delete certificate
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 *
	 * @return bool True if action is active, false otherwise.
	 */
	public static function on_delete_certificate() {
		return ( isset( $_GET['action'] ) && 'tutor_certificate_builder_delete' === $_GET['action'] );
	}

	/**
	 * On delete certificate
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 *
	 * @param string $nonce WP Nonce value.
	 *
	 * @return bool True if action is active, false otherwise.
	 */
	public static function is_nonce_valid( $nonce = '', $action = -1 ) {
		return wp_verify_nonce( $nonce, $action );
	}

	/**
	 * Has editor access
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 *
	 * @return bool True if user has editor access, false otherwise.
	 */
	public static function has_editor_access() {
		$disable_certificate_creation_for_instructors = (bool) tutor_utils()->get_option( 'disable_certificate_creation_for_instructors', false );
		return current_user_can( 'administrator' ) ||
				( current_user_can( tutor()->instructor_role ) && ! $disable_certificate_creation_for_instructors ) ||
				isset( $_GET['post'], $_GET['cert_hash'], $_GET['course_id'], $_GET['orientation'] );
	}

	/**
	 * Has User access to do action
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 *
	 * @param $post_id Current post id to check permission
	 * @param $permission_type permission type delete_certificate
	 *
	 * @return bool True if user has permission, false otherwise.
	 */
	public static function has_user_permission( $post_id = 0, $permission_type = '' ) {
		$user_id = get_current_user_id();
		if ( ! $user_id || ! $post_id ) {
			return false;
		}
		$author_id = get_post_field( 'post_author', $post_id );
		if ( ! $author_id ) {
			return false;
		}
		if ( $permission_type === 'delete_certificate' ) {
			return (int) $user_id === (int) $author_id;
		}
		return false;
	}

	/**
	 * Setup editor post data
	 *
	 * @param int $post_id
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 *
	 * @return void
	 */
	public static function setup_post_data( $post_id ) {
		if ( get_the_ID() === $post_id ) {
			return;
		}
		$GLOBALS['post'] = get_post( $post_id );
		setup_postdata( $GLOBALS['post'] );
	}

	/**
	 * Get editor URL
	 *
	 * @param int $post_id
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 *
	 * @return string
	 */
	public static function certificate_builder_url( $id = null, $additional = array() ) {
		$editor_data               = array( 'action' => Plugin::EDITOR_ACTION );
		$id ? $editor_data['post'] = $id : 0;
		$editor_data               = array_merge( $editor_data, $additional );

		return add_query_arg( $editor_data, get_home_url() );
	}

	/**
	 * Get duplicate certificate URL
	 *
	 * @param int $id certificate id.
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 *
	 * @return string
	 */
	public static function certificate_duplicate_url( $id = null, $additional = array() ) {
		$editor_data                    = array( 'action' => Plugin::EDITOR_ACTION );
		$id ? $editor_data['duplicate'] = $id : 0;
		$editor_data                    = array_merge( $editor_data, $additional );

		return add_query_arg( $editor_data, get_home_url() );
	}

	/**
	 * Duplicate a certificate
	 *
	 * @param int $post_id certificate id.
	 */
	public static function duplicate_certificate( $post_id ) {
		$post = get_post( $post_id );

		if ( ! $post ) {
			return false;
		}

		$args = array(
			'post_author'  => $post->post_author,
			'post_content' => $post->post_content,
			'post_excerpt' => $post->post_excerpt,
			'post_name'    => $post->post_name,
			'post_status'  => 'draft',
			'post_title'   => $post->post_title . ' (Copy)',
			'post_type'    => $post->post_type,
		);

		$new_post_id = wp_insert_post( $args );

		$post_meta          = get_post_meta( $post_id );
		$excluded_meta_keys = array( 'tutor_cb_template_preview_name' );
		foreach ( $post_meta as $meta_key => $meta_values ) {
			if ( in_array( $meta_key, $excluded_meta_keys, true ) ) {
				continue;
			}
			foreach ( $meta_values as $meta_value ) {
				add_post_meta( $new_post_id, $meta_key, maybe_unserialize( $meta_value ) );
			}
		}

		return $new_post_id;

	}
}
