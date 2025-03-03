<?php
/**
 * Course Controller
 *
 * Manage API for course
 *
 * @package TutorPro\RestAPI
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 2.6.0
 */

namespace TutorPro\RestAPI\Controllers;

use Exception;
use TUTOR\Course;
use Tutor\Helpers\ValidationHelper;
use TUTOR\Input;
use Tutor\Models\CourseModel;
use WP_REST_Request;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Course Controller
 */
class CourseController extends BaseController {

	/**
	 * Operation codes
	 *
	 * @since 2.6.0
	 *
	 * @var string
	 */
	public $operation = 'course';

	/**
	 * Fillable fields
	 *
	 * @since 2.6.0
	 *
	 * @var array
	 */
	private $fillable_fields = array(
		'post_author',
		'post_date',
		'post_date_gmt',
		'post_content',
		'post_title',
		'post_excerpt',
		'post_status',
		'comment_status',
		'post_password',
		'post_modified',
		'post_modified_gmt',
		'post_content_filtered',
		'additional_content',
		'video',
		'pricing',
		'course_level',
		'course_categories',
		'course_tags',
		'thumbnail_id',
		'enable_qna',
	);

	/**
	 * Required fields
	 *
	 * @since 2.6.0
	 *
	 * @var array
	 */
	private $required_fields = array(
		'post_author',
		'post_content',
		'post_title',
		'post_status',
		'course_level',
	);

	/**
	 * Course post type
	 *
	 * @since 2.6.0
	 *
	 * @var string
	 */
	private $post_type;

	/**
	 * Course levels
	 *
	 * @since 2.6.0
	 *
	 * @var array
	 */
	private $course_levels;

	/**
	 * Course class instance.
	 *
	 * @since 3.0.0
	 *
	 * @var \TUTOR\Course
	 */
	private $course_cls;

	/**
	 * Initialize props
	 *
	 * @since 2.6.0
	 */
	public function __construct() {
		parent::__construct();

		$this->post_type     = tutor()->course_post_type;
		$this->course_levels = tutor_utils()->course_levels();
		$this->course_cls    = new Course( false );

	}

	/**
	 * Handle course create API request
	 *
	 * @since 2.6.0
	 *
	 * @param WP_REST_Request $request request obj.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function create( WP_REST_Request $request ) {
		$errors = array();

		// Get params and sanitize it.
		$params = Input::sanitize_array(
			$request->get_params(),
			array(
				'post_content'             => 'wp_kses_post',
				'course_benefits'          => 'esc_textarea',
				'course_target_audience'   => 'esc_textarea',
				'course_material_includes' => 'esc_textarea',
				'course_requirements'      => 'esc_textarea',
			)
		);

		// Extract fillable fields.
		$params = array_intersect_key( $params, array_flip( $this->fillable_fields ) );

		$params['post_type'] = $this->post_type;

		// Set empty value if required fields not set.
		$this->setup_required_fields( $params, $this->required_fields );

		// Validate request.
		$validation = $this->validate( $params );
		if ( ! $validation->success ) {
			$errors = $validation->errors;
		}

		// Validate video source if user set video.
		$this->course_cls->validate_video_source( $params, $errors );

		// Validate WC product.
		$this->course_cls->validate_price( $params, $errors );

		// Set course categories and tags.
		$this->course_cls->prepare_course_cats_tags( $params, $errors );

		if ( ! empty( $errors ) ) {
			return $this->response(
				$this->code_create,
				__( 'Course create failed', 'tutor-pro' ),
				$errors,
				$this->client_error_code
			);
		}

		// Course meta fields.
		try {
			$this->prepare_create_post_meta( $params );
		} catch ( \Throwable $th ) {
			return $this->response(
				$this->code_create,
				__( 'Course create failed', 'tutor-pro' ),
				$th->getMessage(),
				$this->client_error_code
			);
		}

		$post_id = wp_insert_post( $params );
		if ( is_wp_error( $post_id ) ) {

			update_post_meta( $post_id, '_tutor_enable_qa', $params['enable_qna'] ?? 'no' );
			return $this->response(
				$this->code_create,
				__( 'Course create failed', 'tutor-pro' ),
				$post_id->get_error_message(),
				$this->server_error_code
			);
		} else {
			// Set course cats & tags.
			$this->course_cls->setup_course_categories_tags( $post_id, $params );

			// Update course thumb.
			if ( isset( $params['thumbnail_id'] ) ) {
				set_post_thumbnail( $post_id, $params['thumbnail_id'] );
			}

			return $this->response(
				$this->code_create,
				__( 'Course created successfully', 'tutor-pro' ),
				$post_id
			);
		}
	}

	/**
	 * Handle course update API request
	 *
	 * @since 2.6.0
	 *
	 * @param WP_REST_Request $request request obj.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function update( WP_REST_Request $request ) {
		$errors = array();

		// Get params and sanitize it.
		$params = Input::sanitize_array(
			$request->get_params(),
			array(
				'post_content'             => 'wp_kses_post',
				'course_benefits'          => 'esc_textarea',
				'course_target_audience'   => 'esc_textarea',
				'course_material_includes' => 'esc_textarea',
				'course_requirements'      => 'esc_textarea',
			)
		);

		// Extract fillable fields.
		$params       = array_intersect_key( $params, array_flip( $this->fillable_fields ) );
		$params['ID'] = $request->get_param( 'id' );

		// Validate request.
		$validation = $this->validate( $params );
		if ( ! $validation->success ) {
			$errors = $validation->errors;
		}

		// Validate video source if user set video.
		$this->course_cls->validate_video_source( $params, $errors );

		// Validate WC product.
		$this->course_cls->validate_price( $params, $errors );

		// Prepare course cats & tags.
		$this->course_cls->prepare_course_cats_tags( $params, $errors );

		if ( ! empty( $errors ) ) {
			return $this->response(
				$this->code_update,
				__( 'Course update failed', 'tutor-pro' ),
				$errors,
				$this->client_error_code
			);
		}

		// Course meta fields.
		try {
			$this->course_cls->prepare_update_post_meta( $params );
		} catch ( \Throwable $th ) {
			return $this->response(
				$this->code_update,
				__( 'Course update failed', 'tutor-pro' ),
				$th->getMessage(),
				$this->client_error_code
			);
		}

		$post_id = wp_update_post( $params, false, false );
		if ( is_wp_error( $post_id ) ) {
			return $this->response(
				$this->code_update,
				__( 'Course update failed', 'tutor-pro' ),
				$post_id->get_error_message(),
				$this->server_error_code
			);
		} else {
			$this->course_cls->setup_course_categories_tags( $post_id, $params );

			// Update course thumb.
			if ( isset( $params['thumbnail_id'] ) ) {
				set_post_thumbnail( $post_id, $params['thumbnail_id'] );
			}

			$this->prepare_update_post_meta( $params );
			return $this->response(
				$this->code_update,
				__( 'Course update successfully', 'tutor-pro' ),
				$post_id
			);
		}
	}

	/**
	 * Prepare course meta data for update
	 *
	 * @since 2.6.0
	 *
	 * @param array $params params.
	 *
	 * @throws Exception Throw new exception.
	 *
	 * @return mixed
	 */
	private function prepare_update_post_meta( $params ) {
		$post_id = (int) $params['ID'];

		$additional_content = isset( $params['additional_content'] ) ? $params['additional_content'] : array();

		if ( ! empty( $additional_content ) ) {

			$course_benefits = isset( $additional_content['course_benefits'] ) ? $additional_content['course_benefits'] : '';

			$course_target_audience = isset( $additional_content['course_target_audience'] ) ? $additional_content['course_target_audience'] : '';

			$course_duration = isset( $additional_content['course_duration'] ) ? array(
				'hours'   => $additional_content['course_duration']['hours'] ?? '',
				'minutes' => $additional_content['course_duration']['minutes'] ?? '',
			) : array();

			$course_materials = isset( $additional_content['course_material_includes'] ) ? $additional_content['course_material_includes'] : '';

			$course_requirements = isset( $additional_content['course_requirements'] ) ? $additional_content['course_requirements'] : '';

			if ( '' !== $course_benefits ) {
				update_post_meta( $post_id, '_tutor_course_benefits', $course_benefits );
			}

			if ( '' !== $course_requirements ) {
				update_post_meta( $post_id, '_tutor_course_requirements', $course_requirements );
			}

			if ( '' !== $course_target_audience ) {
				update_post_meta( $post_id, '_tutor_course_target_audience', $course_target_audience );
			}

			if ( '' !== $course_materials ) {
				update_post_meta( $post_id, '_tutor_course_material_includes', $course_materials );
			}

			if ( ! empty( $course_duration ) ) {
				update_post_meta( $post_id, '_course_duration', $course_duration );
			}
		}

		if ( isset( $params['video'] ) ) {
			$this->video_params['source'] = $params['video']['source_type'];

			$this->video_params[ 'source_' . $params['video']['source_type'] ] = $params['video']['source'];
			update_post_meta( $post_id, '_video', $this->video_params );
		}

		if ( isset( $params['pricing'] ) && ! empty( $params['pricing'] ) ) {
			try {
				if ( isset( $params['pricing']['type'] ) ) {
					update_post_meta( $post_id, '_tutor_course_price_type', $params['pricing']['type'] );
				}
				if ( isset( $params['pricing']['product_id'] ) ) {
					update_post_meta( $post_id, '_tutor_course_product_id', $params['pricing']['product_id'] );
				}
			} catch ( \Throwable $th ) {
				throw new Exception( $th->getMessage() );
			}
		}

		if ( isset( $params['course_level'] ) ) {
			update_post_meta( $post_id, '_tutor_course_level', $params['course_level'] );
		}

		if ( isset( $params['enable_qna'] ) ) {
			update_post_meta( $post_id, '_tutor_enable_qa', $params['enable_qna'] );
		}
	}

	/**
	 * Prepare course meta data for update
	 *
	 * @param array $params params.
	 *
	 * @return void
	 */
	private function prepare_create_post_meta( $params ) {
		$additional_content = isset( $params['additional_content'] ) ? $params['additional_content'] : array();

		$course_benefits = isset( $additional_content['course_benefits'] ) ? $additional_content['course_benefits'] : '';

		$course_target_audience = isset( $additional_content['course_target_audience'] ) ? $additional_content['course_target_audience'] : '';

		$course_duration = isset( $additional_content['course_duration'] ) ? array(
			'hours'   => $additional_content['course_duration']['hours'] ?? '',
			'minutes' => $additional_content['course_duration']['minutes'] ?? '',
		) : array();

		$course_materials = isset( $additional_content['course_material_includes'] ) ? $additional_content['course_material_includes'] : '';

		$course_requirements = isset( $additional_content['course_requirements'] ) ? $additional_content['course_requirements'] : '';

		if ( isset( $params['video'] ) ) {
			$this->video_params['source'] = $params['video']['source_type'];

			$this->video_params[ 'source_' . $params['video']['source_type'] ] = $params['video']['source'];
			$_POST['video'] = $this->video_params;
		}

		$pricing = isset( $params['pricing'] ) ? array(
			'type'       => $params['pricing']['type'] ?? $this->course_cls::PRICE_TYPE_FREE,
			'product_id' => (int) $params['pricing']['product_id'] ?? -1,
		) : array(
			'type'       => $this->course_cls::PRICE_TYPE_FREE,
			'product_id' => -1,
		);

		// Setup global $_POST array.
		$_POST['_tutor_course_additional_data_edit'] = true;

		$_POST['tutor_course_price_type']  = $pricing['type'];
		$_POST['course_duration']          = $course_duration;
		$_POST['tutor_course_price_type']  = $pricing['type'];
		$_POST['_tutor_course_product_id'] = $pricing['product_id'];
		$_POST['_tutor_course_level']      = $params['course_level'];
		$_POST['course_benefits']          = $course_benefits;
		$_POST['course_requirements']      = $course_requirements;
		$_POST['course_target_audience']   = $course_target_audience;
		$_POST['course_material_includes'] = $course_materials;

		// Set course price.
		if ( -1 !== $pricing['product_id'] ) {
			$product = wc_get_product( $pricing['product_id'] );
			if ( is_a( $product, 'WC_Product' ) ) {
				$regular_price = $product->get_regular_price();
				$sale_price    = $product->get_sale_price();

				$_POST['course_price']      = $regular_price;
				$_POST['course_sale_price'] = $sale_price;
			}
		}
	}

	/**
	 * Delete course
	 *
	 * @since 2.6.0
	 *
	 * @param WP_REST_Request $request params.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete( WP_REST_Request $request ) {
		$course_id    = $request->get_param( 'id' );
		$trash_course = wp_update_post(
			array(
				'ID'          => $course_id,
				'post_status' => 'trash',
			)
		);

		try {
			if ( $trash_course ) {
				return $this->response(
					$this->code_delete,
					__( 'Course trashed successfully', 'tutor-pro' ),
					$course_id
				);
			} else {
				return $this->response(
					$this->code_delete,
					__( 'Course trash failed', 'tutor-pro' ),
					'',
					$this->client_error_code
				);
			}
		} catch ( \Throwable $th ) {
			return $this->response(
				$this->code_delete,
				__( 'Course trash failed', 'tutor-pro' ),
				$th->getMessage(),
				$this->server_error_code
			);
		}
	}

	/**
	 * Course mark as complete
	 *
	 * @since 2.6.0
	 *
	 * @param WP_REST_Request $request params.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function course_mark_complete( WP_REST_Request $request ) {
		$params = Input::sanitize_array( $request->get_params() );

		$required_fields = array( 'course_id', 'student_id' );

		foreach ( $required_fields as $field ) {
			if ( ! isset( $params[ $field ] ) ) {
				$params[ $field ] = '';
			}
		}

		// Validate request.
		$validation = $this->validate( $params );
		if ( ! $validation->success ) {
			return $this->response(
				$this->mark_complete,
				__( 'Course mark as complete failed', 'tutor-pro' ),
				$validation->errors,
				$this->client_error_code
			);
		}

		$is_enrolled = tutor_utils()->is_enrolled( $params['course_id'], $params['student_id'] );
		if ( ! $is_enrolled ) {
			return $this->response(
				$this->mark_complete,
				__( 'Course mark as complete failed', 'tutor-pro' ),
				__( 'Student is not enrolled on the give course', 'tutor-pro' ),
				$this->client_error_code
			);
		}

		$can_complete_course = CourseModel::can_complete_course( $params['course_id'], $params['student_id'] );
		if ( $can_complete_course ) {
			$complete = CourseModel::mark_course_as_completed( $params['course_id'], $params['student_id'] );
			if ( $complete ) {
				return $this->response(
					$this->mark_complete,
					__( 'Course mark as completed', 'tutor-pro' ),
				);
			} else {
				return $this->response(
					$this->mark_complete,
					__( 'Course mark as complete failed', 'tutor-pro' ),
					'',
					$this->client_error_code
				);
			}
		} else {
			return $this->response(
				$this->mark_complete,
				__( 'Course mark as complete failed', 'tutor-pro' ),
				__( 'Bad request', 'tutor-pro' ),
				$this->client_error_code
			);
		}
	}

	/**
	 * Validate data
	 *
	 * @since 2.6.0
	 *
	 * @param array $data form data.
	 *
	 * @return object
	 */
	protected function validate( array $data ): object {
		$levels = implode( ',', array_keys( $this->course_levels ) );

		$validation_rules = array(
			'ID'           => 'required|numeric',
			'course_id'    => 'required|numeric',
			'student_id'   => 'required|numeric',
			'post_author'  => 'user_exists',
			'post_content' => 'required',
			'post_title'   => 'required',
			'post_status'  => 'required',
			'course_level' => "required|match_string:{$levels}",
		);

		// Skip validation rules for not available fields in data.
		foreach ( $validation_rules as $key => $value ) {
			if ( ! array_key_exists( $key, $data ) ) {
				unset( $validation_rules[ $key ] );
			}
		}

		return ValidationHelper::validate( $validation_rules, $data );
	}

}

