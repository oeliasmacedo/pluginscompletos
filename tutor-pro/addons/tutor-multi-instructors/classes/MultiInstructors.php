<?php
/**
 * Handle Multi Instructor Logic
 *
 * @package TutorPro/Addons
 * @subpackage MultiInstructor
 * @author Themeum <support@themeum.com>
 * @since 1.0.0
 */

namespace TUTOR_MT;

use Tutor\Helpers\HttpHelper;
use TUTOR\Input;
use Tutor\Helpers\QueryHelper;
use Tutor\Models\CourseModel;
use Tutor\Traits\JsonResponse;
use TUTOR\User;

/**
 * Handle multi instructors logics
 */
class MultiInstructors {
	use JsonResponse;

	/**
	 * Register Hooks
	 */
	public function __construct() {
		add_filter( 'tutor/options/extend/attr', array( $this, 'extend_instructor_options' ) );
		add_action( 'wp_ajax_tutor_course_instructor_search', array( $this, 'tutor_course_instructor_search' ) );

		/**
		 * Change the main instructor of the course
		 *
		 * @since v2.0.7
		 */
		$course_post_type = tutor()->course_post_type;
		add_action( "save_post_{$course_post_type}", array( $this, 'change_main_instructor' ) );

		add_action( "save_post_{$course_post_type}", array( $this, 'add_instructors_to_course' ) );
		add_filter( 'tutor_course_details_response', array( $this, 'extend_course_details_response' ) );
	}

	/**
	 * Extend instructor options.
	 *
	 * @since 3.2.0
	 *
	 * @param array $attr instructor options.
	 *
	 * @return array
	 */
	public function extend_instructor_options( $attr ) {
		$attr['general']['blocks'][3]['fields'][] = array(
			'key'         => 'instructor_can_manage_co_instructors',
			'type'        => 'toggle_switch',
			'label'       => __( 'Allow Instructors to Manage Co-Instructors', 'tutor-pro' ),
			'label_title' => '',
			'default'     => 'on',
			'desc'        => __( 'If enabled, instructors can add or remove co-instructors for their courses.', 'tutor-pro' ),
		);

		return $attr;
	}

	/**
	 * Handle ajax request, search instructor
	 *
	 * @return void wp_json response
	 */
	public function tutor_course_instructor_search() {
		if ( ! tutor_utils()->is_nonce_verified() ) {
			$this->json_response( tutor_utils()->error_message( 'nonce' ), null, HttpHelper::STATUS_BAD_REQUEST );
		}

		global $wpdb;

		$course_id = Input::post( 'course_id', 0, Input::TYPE_INT );

		if ( ! tutor_utils()->can_user_manage( 'course', $course_id ) ) {
			$this->json_response( tutor_utils()->error_message(), null, HttpHelper::STATUS_BAD_REQUEST );
		}

		$args = array(
			'fields'     => array( 'ID', 'display_name', 'user_email', 'user_login' ),
			'meta_key'   => '_tutor_instructor_status',
			'meta_value' => 'approved',
		);

		if ( $course_id ) {
			$instructor_ids = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT user_id FROM $wpdb->usermeta WHERE meta_key=%s AND meta_value=%s",
					'_tutor_instructor_course_id',
					$course_id
				)
			);

			if ( count( $instructor_ids ) ) {
				$args['exclude'] = $instructor_ids;
			}
		}

		$users = get_users( $args );

		foreach ( $users as $user ) {
			$user->avatar_url = get_avatar_url( $user->ID );
		}

		$this->json_response(
			__( 'Data fetched successfully!', 'tutor-pro' ),
			$users
		);
	}

	/**
	 * On course save add instructor to course
	 *
	 * @since 3.0.0
	 *
	 * @param int $course_id course id.
	 *
	 * @return void
	 */
	public function add_instructors_to_course( int $course_id ) {
		/**
		 * Restriction added for instructors to modify instructors
		 *
		 * @since 3.2.0
		 */
		$can_instructors_modify_instructors = (bool) tutor_utils()->get_option( 'instructor_can_manage_co_instructors', true );
		if ( User::is_only_instructor() && ! $can_instructors_modify_instructors ) {
			return;
		}

		$instructor_ids = Input::post( 'course_instructor_ids', array(), Input::TYPE_ARRAY );
		$instructor_ids = array_filter(
			$instructor_ids,
			function( $id ) {
				return is_numeric( $id );
			}
		);

		if ( 0 === count( $instructor_ids ) ) {
			return;
		}

		// remove all existing instructors from the course.
		global $wpdb;
		$wpdb->delete(
			$wpdb->usermeta,
			array(
				'meta_key'   => '_tutor_instructor_course_id',
				'meta_value' => $course_id,
			)
		);

		foreach ( $instructor_ids as $instructor_id ) {
			add_user_meta( $instructor_id, '_tutor_instructor_course_id', $course_id );
		}
	}

	/**
	 * Extend course details response
	 *
	 * @since 3.0.0
	 *
	 * @param array $data response data.
	 *
	 * @return array
	 */
	public function extend_course_details_response( array $data ) {
		$course_id = $data['ID'];

		global $wpdb;

		$instructor_ids = CourseModel::get_course_instructor_ids( $course_id );

		$users = array();
		if ( count( $instructor_ids ) > 0 ) {
			$users = get_users(
				array(
					'include' => $instructor_ids,
					'fields'  => array( 'ID', 'display_name', 'user_email', 'user_login' ),
				)
			);

			foreach ( $users as $user ) {
				$user->avatar_url = get_avatar_url( $user->ID );
			}
		}

		$data['course_instructors'] = $users;

		return $data;
	}

	/**
	 * Change the main instructor of the course
	 *
	 * @since v2.0.7
	 *
	 * @param int $post_id  current post id (course).
	 *
	 * @return void
	 */
	public static function change_main_instructor( int $post_id ): void {
		global $wpdb;
		$author_id = Input::post( 'post_author_override', 0, Input::TYPE_INT );
		$table     = $wpdb->posts;

		if ( $author_id ) {
			if ( current_user_can( 'manage_options' ) ) {
				$where = array(
					'ID' => $post_id,
				);
				$data  = array(
					'post_author' => $author_id,
				);
				do_action( 'tutor_before_change_main_instructor', $post_id, $author_id );
				$update = QueryHelper::update( $table, $data, $where );
				/**
				 * Update course lesson author id so that
				 * new author can edit lesson from wp editor
				 *
				 * @since v2.1.0
				 */
				self::update_course_content_author( $post_id, $author_id );

				do_action( 'tutor_after_change_main_instructor', $post_id, $author_id, $update );
			}
		}
	}

	/**
	 * Update course content author id typically
	 * after change main instructor
	 *
	 * Note: For now only lesson author id is updating.
	 *
	 * @since v2.1.0
	 *
	 * @param int $course_id  course id.
	 * @param int $author_id  new author id.
	 *
	 * @return bool
	 */
	public static function update_course_content_author( int $course_id, int $author_id ): bool {
		global $wpdb;
		$response   = false;
		$lesson_ids = tutor_utils()->get_course_content_ids_by(
			tutor()->lesson_post_type,
			tutor()->course_post_type,
			$course_id
		);
		if ( is_array( $lesson_ids ) && count( $lesson_ids ) ) {
			$ids      = implode( ',', $lesson_ids );
			$response = QueryHelper::update_where_in(
				$wpdb->posts,
				array( 'post_author' => $author_id ),
				$ids
			);
		}
		return $response;
	}

}
