<?php
/**
 * Handle Ajax Request.
 *
 * @package TutorPro\CourseBundle
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 2.2.0
 */

namespace TutorPro\CourseBundle;

use TUTOR\Course;
use Tutor\Helpers\HttpHelper;
use TUTOR\Input;
use Tutor\Models\CourseModel;
use Tutor\Traits\JsonResponse;
use TutorPro\CourseBundle\CustomPosts\CourseBundle;
use TutorPro\CourseBundle\CustomPosts\ManagePostMeta;
use TutorPro\CourseBundle\Models\BundleModel;

/**
 * Ajax Class.
 *
 * @since 2.2.0
 */
class Ajax {

	use JsonResponse;

	/**
	 * Register hooks.
	 *
	 * @since 2.2.0
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'wp_ajax_tutor_add_course_to_bundle', array( $this, 'add_course_to_bundle' ) );
		add_action( 'wp_ajax_tutor_create_course_bundle', array( $this, 'ajax_create_course_bundle' ) );
		add_action( 'wp_ajax_tutor_get_course_bundle_data', array( $this, 'ajax_get_bundle_data' ) );
	}

	/**
	 * Get course bundle data
	 *
	 * All the course bundle related data will be returned.
	 *
	 * @since 2.2.0
	 *
	 * @return void send wp_json response
	 */
	public function add_course_to_bundle() {
		// Validate nonce.
		tutor_utils()->checking_nonce();

		// Post data.
		$bundle_id   = Input::post( 'ID', 0, Input::TYPE_INT );
		$course_id   = Input::post( 'course_id', 0, Input::TYPE_INT );
		$user_action = Input::post( 'user_action', '', Input::TYPE_STRING );

		// Check user permission.
		if ( ! Utils::current_user_can_update_bundle( $bundle_id ) ) {
			$this->json_response(
				tutor_utils()->error_message(),
				null,
				HttpHelper::STATUS_UNAUTHORIZED
			);
		}

		if ( ! $bundle_id || CourseBundle::POST_TYPE !== get_post_type( $bundle_id ) ) {
			$this->json_response(
				tutor_utils()->error_message( 'validation_error' ),
				__( 'Invalid bundle id or post type', 'tutor-pro' ),
				HttpHelper::STATUS_BAD_REQUEST
			);
		}

		$total_enrolled = BundleModel::get_total_bundle_sold( $bundle_id );

		if ( $total_enrolled ) {
			$this->response_bad_request(
				__( 'You cannot add/remove course(s) from a course bundle with enrolled students as it may disrupt the learning experience.', 'tutor-pro' )
			);
		}

		// Course id  to add on the bundle.
		if ( $course_id ) {
			// Remove course from bundle if user action is remove.
			if ( 'remove_course' === $user_action ) {
				BundleModel::remove_course_from_bundle( $course_id, $bundle_id );
			} else {
				$course_ids = BundleModel::get_bundle_course_ids( $bundle_id );
				if ( ! in_array( $course_id, $course_ids ) ) {
					// Add course to the bundle.
					$course_ids[] = $course_id;
					$update       = BundleModel::update_bundle_course_ids( $bundle_id, $course_ids );

					// If bundle course update failed.
					if ( ! $update ) {
						$this->json_response(
							__( 'Course could not added to the bundle.', 'tutor-pro' ),
							null,
							HttpHelper::STATUS_INTERNAL_SERVER_ERROR
						);
					}

					// Do action.
					do_action( 'tutor_course_bundle_course_added', $bundle_id, $course_id );
				} else {
					$this->json_response(
						__( 'Course already added to the bundle.', 'tutor-pro' ),
						null,
						HttpHelper::STATUS_BAD_REQUEST
					);
				}
			}
		}

		$bundle_data = BundleModel::get_bundle_data( $bundle_id );

		$regular_price = BundleModel::get_bundle_regular_price( $bundle_id );
		$sale_price    = tutor_utils()->get_raw_course_price( $bundle_id )->sale_price;

		if ( $sale_price > $regular_price ) {

			update_post_meta( $bundle_id, Course::COURSE_SALE_PRICE_META, 0 );
			$bundle_data['subtotal_sale_price']     = '';
			$bundle_data['subtotal_raw_sale_price'] = '';

		}

		$this->json_response(
			'remove_course' === $user_action ?
			__( 'Course removed from the bundle.', 'tutor-pro' ) :
			__( 'Course added to the bundle.', 'tutor-pro' ),
			$bundle_data
		);
	}

	/**
	 * Create course bundle.
	 *
	 * @since 3.2.0
	 *
	 * @return void send wp_json response
	 */
	public function ajax_create_course_bundle() {
		tutor_utils()->checking_nonce();
		if ( ! Utils::current_user_can_create_bundle() ) {
			$this->json_response(
				tutor_utils()->error_message(),
				null,
				HttpHelper::STATUS_UNAUTHORIZED
			);
		}

		$post = Input::sanitize_array(
			$_POST,
			array(
				'post_content'    => 'wp_kses_post',
				'course_benefits' => 'sanitize_textarea_field',
			)
		);

		$post['post_type']  = CourseBundle::POST_TYPE;
		$post['post_title'] = Input::post( 'post_title', __( 'New Bundle', 'tutor-pro' ), Input::TYPE_STRING );
		$post['post_name']  = Input::post( 'post_name', 'new-bundle', Input::TYPE_STRING );
		$sale_price         = Input::post( 'sale_price', 0, Input::TYPE_NUMERIC );

		if ( isset( $post['ID'] ) ) {

			if ( CourseBundle::POST_TYPE !== get_post_type( $post['ID'] ) ) {
				$this->json_response(
					__( 'Invalid bundle id or post type', 'tutor-pro' ),
					null,
					HttpHelper::STATUS_BAD_REQUEST
				);
			}

			$bundle_id     = $post['ID'];
			$regular_price = BundleModel::get_bundle_regular_price( $bundle_id );
			if ( $sale_price > $regular_price ) {
				$this->json_response(
					__( 'Sale price can not be greater than regular price', 'tutor-pro' ),
					null,
					HttpHelper::STATUS_BAD_REQUEST
				);
			}
		}

		$insert = wp_insert_post( $post );
		if ( is_wp_error( $insert ) ) {
			$this->json_response(
				$insert->get_error_message(),
				null,
				HttpHelper::STATUS_INTERNAL_SERVER_ERROR
			);
		}

		if ( isset( $post['course_ids'] ) ) {
			BundleModel::update_bundle_course_ids( $insert, $post['course_ids'] );
		}

		if ( isset( $post['thumbnail_id'] ) ) {
			set_post_thumbnail( $insert, $post['thumbnail_id'] );
		}

		if ( isset( $post['source'] ) && 'frontend' === $post['source'] ) {
			$edit_url = Utils::construct_front_url( 'edit', $insert );
		} else {
			$edit_url = Utils::construct_page_url( 'edit', $insert );
		}

		$this->json_response(
			__( 'Course Bundle updated successfully', 'tutor-pro' ),
			$edit_url,
		);

	}

	/**
	 * Get course bundle data.
	 *
	 * @since 3.2.0
	 *
	 * @return void send wp_json response
	 */
	public function ajax_get_bundle_data() {
		tutor_utils()->checking_nonce();
		$bundle_id = Input::post( 'bundle_id', 0, Input::TYPE_INT );
		if ( ! $bundle_id ) {
			$this->json_response(
				__( 'Invalid bundle id', 'tutor-pro' ),
				null,
				HttpHelper::STATUS_BAD_REQUEST
			);
		}

		$bundle_course = get_post( $bundle_id );
		$bundle_data   = BundleModel::get_bundle_data( $bundle_id );
		if ( is_a( $bundle_course, 'WP_Post' ) ) {
			$bundle_course->post_name = urldecode( $bundle_course->post_name );
			$bundle_course->details   = $bundle_data;
		} else {
			$this->json_response(
				__( 'Invalid bundle id', 'tutor-pro' ),
				null,
				HttpHelper::STATUS_BAD_REQUEST
			);
		}

		! empty( get_post_meta( $bundle_id, ManagePostMeta::BUNDLE_RIBBON_META_KEY, true ) ) ? $bundle_course->ribbon_type       = get_post_meta( $bundle_id, ManagePostMeta::BUNDLE_RIBBON_META_KEY, true ) : null;
		! empty( get_post_meta( $bundle_id, Course::COURSE_SELLING_OPTION_META, true ) ) ? $bundle_course->course_selling_option = get_post_meta( $bundle_id, Course::COURSE_SELLING_OPTION_META, true ) : null;

		$editors = tutor_utils()->get_editor_list( $bundle_id );

		$bundle_course->course_benefits = get_post_meta( $bundle_id, CourseModel::BENEFITS_META_KEY, true );
		$bundle_course->preview_link    = get_preview_post_link( $bundle_id );
		$bundle_course->thumbnail_id    = get_post_meta( $bundle_id, '_thumbnail_id', true );
		$bundle_course->thumbnail       = get_the_post_thumbnail_url( $bundle_id );
		$bundle_course->total_enrolled  = BundleModel::get_total_bundle_sold( $bundle_id );
		$bundle_course->editors         = array_values( $editors );
		$bundle_course->editor_used     = tutor_utils()->get_editor_used( $bundle_id );

		$this->json_response(
			__( 'Success', 'tutor-pro' ),
			$bundle_course
		);
	}
}
