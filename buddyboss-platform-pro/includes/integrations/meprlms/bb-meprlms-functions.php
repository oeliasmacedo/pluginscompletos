<?php
/**
 * MemberpressLMS integration helpers.
 *
 * @since 2.6.30
 *
 * @package BuddyBossPro\Integration\MemberpressLMS
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use memberpress\courses\models as models;
use memberpress\courses\helpers as helpers;

/**
 * Returns MemberpressLMS Integration url.
 *
 * @since 2.6.30
 *
 * @param string $path Path to meprlms integration.
 *
 * @return string
 */
function bb_meprlms_integration_url( $path = '' ) {
	return trailingslashit( bb_platform_pro()->integration_url ) . 'meprlms/' . trim( $path, '/\\' );
}

/**
 * Returns MemberpressLMS Integration path.
 *
 * @since 2.6.30
 *
 * @param string $path Path to meprlms integration.
 *
 * @return string
 */
function bb_meprlms_integration_path( $path = '' ) {
	return trailingslashit( bb_platform_pro()->integration_dir ) . 'meprlms/' . trim( $path, '/\\' );
}

/**
 * Get MemberpressLMS settings.
 *
 * @since 2.6.30
 *
 * @param string $keys    Optional. Get setting by key.
 * @param string $default Optional. Default value if value or setting not available.
 *
 * @return array|string
 */
function bb_get_meprlms_settings( $keys = '', $default = '' ) {
	$settings = bp_get_option( 'bb-meprlms', array() );

	if ( ! empty( $keys ) ) {
		if ( is_string( $keys ) ) {
			$keys = explode( '.', $keys );
		}

		foreach ( $keys as $key ) {
			if ( isset( $settings[ $key ] ) ) {
				$settings = $settings[ $key ];
			} else {
				return $default;
			}
		}
	} elseif ( empty( $settings ) ) {
		$settings = array();
	}

	/**
	 * Filters MemberpressLMS get settings.
	 *
	 * @since 2.6.30
	 *
	 * @param array  $settings Settings of meprlms.
	 * @param string $keys     Optional. Get setting by key.
	 * @param string $default  Optional. Default value if value or setting not available.
	 */
	return apply_filters( 'bb_get_meprlms_settings', $settings, $keys, $default );
}

/**
 * Checks if MemberpressLMS enable.
 *
 * @since 2.6.30
 *
 * @param integer $default MemberpressLMS enabled by default.
 *
 * @return bool Is MemberpressLMS enabled or not.
 */
function bb_meprlms_enable( $default = 0 ) {

	/**
	 * Filters MemberpressLMS enabled settings.
	 *
	 * @since 2.6.30
	 *
	 * @param integer $default MemberpressLMS enabled by default.
	 */
	return (bool) apply_filters( 'bb_meprlms_enable', bb_get_meprlms_settings( 'bb-meprlms-enable', $default ) );
}

/**
 * Function to return all MemberpressLMS post types.
 *
 * @since 2.6.30
 *
 * @return array
 */
function bb_meprlms_get_post_types() {
	if ( ! class_exists( 'memberpress\courses\helpers\Courses' ) ) {
		return array();
	}

	$meprlms_post_types = array(
		'mpcs-course',
		'mpcs-lesson',
	);

	if ( class_exists( 'memberpress\assignments\models\Assignment' ) ) {
		$meprlms_post_types[] = 'mpcs-assignment';
	}

	if ( class_exists( 'memberpress\quizzes\models\Quiz' ) ) {
		$meprlms_post_types[] = 'mpcs-quiz';
	}

	/**
	 * Filters for MemberpressLMS post types.
	 *
	 * @since 2.6.30
	 *
	 * @param array $meprlms_post_types MemberpressLMS post types.
	 */
	return apply_filters( 'bb_meprlms_get_post_types', $meprlms_post_types );
}

/**
 * Function to get published MemberpressLMS courses.
 *
 * @since 2.6.30
 *
 * @param array $args Array of args.
 *
 * @return false|mixed|null|object
 */
function bb_meprlms_get_courses( $args = array() ) {
	if ( ! class_exists( 'memberpress\courses\helpers\Courses' ) ) {
		return false;
	}

	$r = bp_parse_args(
		$args,
		array(
			'fields'         => 'all',
			'post_type'      => 'mpcs-course',
			'post_status'    => array( 'publish', 'private' ),
			'orderby'        => 'ID',
			'order'          => 'DESC',
			'paged'          => 1,
			'posts_per_page' => 10,
			's'              => '',
		)
	);

	if ( $r['s'] ) {
		add_filter( 'posts_search', 'bb_meprlms_search_by_title_only', 500, 2 );
	}

	/**
	 * Apply filters for course arguments.
	 *
	 * @since 2.6.30
	 *
	 * @param array $r Array of args.
	 */
	$r = apply_filters( 'bb_meprlms_get_courses_args', $r );

	$results = new WP_Query( $r );

	if ( $r['s'] ) {
		remove_filter( 'posts_search', 'bb_meprlms_search_by_title_only', 500 );
	}

	/**
	 * Apply filters for course results.
	 *
	 * @since 2.6.30
	 *
	 * @param object $results WP_Query object.
	 * @param array  $r       Array of args.
	 */
	return apply_filters( 'bb_get_meprlms_courses', $results, $r );
}

/**
 * Get profile courses slug.
 *
 * @since 2.6.30
 *
 * @return string
 */
function bb_meprlms_profile_courses_slug() {

	/**
	 * Apply filters for profile courses slug.
	 *
	 * @since 2.6.30
	 *
	 * @param string $slug Defaults to 'courses'.
	 */
	return apply_filters( 'bb_meprlms_profile_courses_slug', 'courses' );
}

/**
 * Get profile user courses slug.
 *
 * @since 2.6.30
 *
 * @return string
 */
function bb_meprlms_profile_user_courses_slug() {

	/**
	 * Apply filters for get profile user courses slug.
	 *
	 * @since 2.6.30
	 *
	 * @param string $slug Defaults to 'user-courses'.
	 */
	return apply_filters( 'bb_meprlms_profile_user_courses_slug', 'user-courses' );
}

/**
 * Get profile instructor courses slug.
 *
 * @since 2.6.30
 *
 * @return string
 */
function bb_meprlms_profile_instructor_courses_slug() {

	/**
	 * Apply filters for get profile instructor courses slug.
	 *
	 * @since 2.6.30
	 *
	 * @param string $slug Defaults to 'instructor-courses'.
	 */
	return apply_filters( 'bb_meprlms_profile_instructor_courses_slug', 'instructor-courses' );
}

/**
 * Get Memberpress LMS user accessible course ids.
 *
 * @since 2.6.30
 *
 * @param int $user_id user id.
 *
 * @return array User accessible courses ids..
 */
function bb_meprlms_get_user_course_ids( $user_id = 0 ) {
	if ( empty( $user_id ) ) {
		$user_id = bp_displayed_user_id();
	}

	$course_ids = array();
	if (
		class_exists( 'memberpress\courses\models\Course' ) &&
		class_exists( 'memberpress\courses\helpers\Options' ) &&
		class_exists( 'MeprOptions' ) &&
		class_exists( 'MeprUser' ) &&
		class_exists( 'MeprRule' )
	) {
		$options      = get_option( 'mpcs-options' );
		$sort_options = array(
			'alphabetically' => array(
				'orderby' => 'title',
			),
			'last-updated'   => array(
				'orderby' => 'modified',
			),
			'publish-date'   => array(
				'orderby' => 'date',
			),
		);

		$mpcs_sort_order           = helpers\Options::val( $options, 'courses-sort-order', 'alphabetically' );
		$mpcs_sort_order_direction = helpers\Options::val( $options, 'courses-sort-order-direction', 'ASC' );

		if ( ! in_array( $mpcs_sort_order_direction, array( 'ASC', 'DESC' ), true ) ) {
			$mpcs_sort_order_direction = 'ASC';
		}

		$sort_option = $sort_options[ $mpcs_sort_order ] ?? $sort_options['default'];

		$post_args = array(
			'post_type'      => models\Course::$cpt,
			'post_status'    => 'publish',
			'posts_per_page' => '-1',
			'orderby'        => $sort_option['orderby'],
			'order'          => $mpcs_sort_order_direction,
		);

		$courses = get_posts( $post_args );
		if ( $user_id ) {
			$mepr_user = new MeprUser( $user_id );

			if ( ! user_can( $user_id, 'administrator' ) ) {
				$courses = array_filter(
					$courses,
					function ( $course ) use ( $mepr_user ) {
						return false === MeprRule::is_locked_for_user( $mepr_user, $course );
					}
				);
			}
		}

		$course_ids = array_map(
			function ( $c ) {
				return is_object( $c ) ? $c->ID : $c['ID'];
			},
			$courses
		);

		if ( empty( $course_ids ) ) {
			$course_ids = array( 0 );
		}
	}

	return $course_ids;
}

/**
 * Get Memberpress LMS courses accessible by user.
 *
 * @since 2.6.30
 *
 * @param int    $user_id        user id.
 * @param string $post_status    post status.
 * @param int    $paged          page number.
 * @param int    $posts_per_page post per page.
 * @param array  $filters        additional filters with key value for \WP_Query.
 *
 * @return array|object User accessible courses WP Query.
 */
function bb_meprlms_get_user_courses( $user_id = 0, $post_status = 'publish', $paged = 1, $posts_per_page = 0, $filters = array() ) {

	if ( empty( $user_id ) ) {
		$user_id = bp_displayed_user_id();
	}

	$user_courses = array();
	$course_ids   = bb_meprlms_get_user_course_ids( $user_id );

	if (
		! empty( $course_ids ) &&
		class_exists( 'memberpress\courses\models\Course' ) &&
		class_exists( 'memberpress\courses\helpers\Options' )
	) {

		if ( empty( $posts_per_page ) ) {
			$options        = get_option( 'mpcs-options' );
			$posts_per_page = (int) helpers\Options::val( $options, 'courses-per-page', 10 );
		}

		$paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : $paged;

		if ( empty( $filters ) && get_query_var( 's' ) ) {
			$filters['s'] = get_query_var( 's' );
		}

		$course_args = array(
			'post_type'      => models\Course::$cpt,
			'post_status'    => $post_status,
			'posts_per_page' => $posts_per_page,
			'paged'          => $paged,
			'orderby'        => 'post__in',
			'order'          => 'ASC',
			'post__in'       => $course_ids,
		);

		if ( ! empty( $filters ) ) {
			$keys = array_keys( $course_args );
			foreach ( $filters as $key => $value ) {
				if ( ! in_array( $key, $keys, true ) ) {
					$course_args[ $key ] = $value;
				}
			}
		}

		$user_courses = new WP_Query( $course_args );
	}

	/**
	 * Apply filters for get profile instructor courses slug.
	 *
	 * @since 2.6.30
	 *
	 * @param bool|WP_Query $user_courses   Get the accessible courses by user.
	 * @param int            $user_id        user id.
	 * @param string         $post_status    post status.
	 * @param int            $paged          page no.
	 * @param int            $posts_per_page post per page.
	 * @param array          $filters        additional filters with key value for \WP_Query.
	 */
	return apply_filters( 'bb_meprlms_get_user_courses', $user_courses, $user_id, $post_status, $paged, $posts_per_page, $filters );
}

/**
 * Get Memberpress LMS instructor created courses.
 *
 * @since 2.6.30
 *
 * @param int    $instructor_id  Instructor User ID.
 * @param string $post_status    post status.
 * @param int    $paged          page number.
 * @param int    $posts_per_page post per page.
 * @param array  $filters        additional filters with key value for \WP_Query.
 *
 * @return array|object Instructor courses WP Query.
 */
function bb_meprlms_get_instructor_courses( $instructor_id = 0, $post_status = 'publish', $paged = 1, $posts_per_page = 0, $filters = array() ) {
	if ( empty( $instructor_id ) ) {
		$instructor_id = bp_displayed_user_id();
	}

	$instructor_courses = array();

	if ( class_exists( 'memberpress\courses\models\Course' ) && class_exists( 'memberpress\courses\helpers\Options' ) && user_can( $instructor_id, 'administrator' ) ) {
		$options      = get_option( 'mpcs-options' );
		$sort_options = array(
			'alphabetically' => array(
				'orderby' => 'title',
			),
			'last-updated'   => array(
				'orderby' => 'modified',
			),
			'publish-date'   => array(
				'orderby' => 'date',
			),
		);

		$mpcs_sort_order           = helpers\Options::val( $options, 'courses-sort-order', 'alphabetically' );
		$mpcs_sort_order_direction = helpers\Options::val( $options, 'courses-sort-order-direction', 'ASC' );

		if ( ! in_array( $mpcs_sort_order_direction, array( 'ASC', 'DESC' ), true ) ) {
			$mpcs_sort_order_direction = 'ASC';
		}

		$sort_option = $sort_options[ $mpcs_sort_order ] ?? $sort_options['default'];

		if ( empty( $posts_per_page ) ) {
			$posts_per_page = (int) helpers\Options::val( $options, 'courses-per-page', 10 );
		}

		$paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : $paged;

		if ( empty( $filters ) && get_query_var( 's' ) ) {
			$filters['s'] = get_query_var( 's' );
		}

		$course_args = array(
			'post_type'      => models\Course::$cpt,
			'post_status'    => $post_status,
			'author'         => $instructor_id,
			'paged'          => $paged,
			'posts_per_page' => $posts_per_page,
			'orderby'        => $sort_option['orderby'],
			'order'          => $mpcs_sort_order_direction,
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'key'   => '_mpcs_course_status',
				'value' => 'enabled',
			),
		);

		if ( ! empty( $filters ) ) {
			$keys = array_keys( $course_args );
			foreach ( $filters as $key => $value ) {
				if ( ! in_array( $key, $keys, true ) ) {
					$course_args[ $key ] = $value;
				}
			}
		}

		$instructor_courses = new WP_Query( $course_args );
	}

	/**
	 * Apply filters for get instructors courses.
	 *
	 * @since 2.6.30
	 *
	 * @param array|null|object $instructor_courses Get courses by a instructor.
	 * @param int               $instructor_id      Instructor id.
	 * @param string            $post_status        post status.
	 * @param int               $paged              page no.
	 * @param int               $posts_per_page     post per page.
	 * @param array             $filters            additional filters with key value for \WP_Query.
	 */
	return apply_filters( 'bb_meprlms_get_instructor_courses', $instructor_courses, $instructor_id, $post_status, $paged, $posts_per_page, $filters );
}

/**
 * Function to load the instance of the class BB_MeprLMS_Group_Table.
 *
 * @since 2.6.30
 *
 * @return null|BB_MeprLMS_Groups|void
 */
function bb_load_meprlms_group() {
	if ( class_exists( 'BB_MeprLMS_Groups' ) ) {
		return BB_MeprLMS_Groups::instance();
	}
}

/**
 * Function to add notices when courses empty for Memberpress LMS.
 *
 * @since 2.6.30
 *
 * @param array $messages Array of feedback messages.
 *
 * @return array
 */
function bb_meprlms_nouveau_feedback_messages( $messages ) {
	$user_same = bp_displayed_user_id() === bp_loggedin_user_id();

	$messages['meprlms-courses-loop-none'] = array(
		'type'    => 'info',
		'message' => __( 'No courses found!', 'buddyboss-pro' ),
	);

	$messages['meprlms-accessible-courses-loop-none'] = array(
		'type'    => 'info',
		'message' => $user_same ? __( 'You have no access to any courses yet!', 'buddyboss-pro' ) : __( 'This member has not access to any courses yet!', 'buddyboss-pro' ),
	);

	$messages['meprlms-created-courses-loop-none'] = array(
		'type'    => 'info',
		'message' => $user_same ? __( 'You have not created any courses yet!', 'buddyboss-pro' ) : __( 'This member has not created any courses yet!', 'buddyboss-pro' ),
	);

	return $messages;
}

/**
 * Search SQL filter for matching against post title only.
 *
 * @since 2.6.30
 *
 * @param string   $search   Search string.
 * @param WP_Query $wp_query WP Query object.
 *
 * @return string
 */
function bb_meprlms_search_by_title_only( $search, $wp_query ) {
	if ( ! empty( $search ) && ! empty( $wp_query->query_vars['search_terms'] ) ) {
		global $wpdb;

		$q = $wp_query->query_vars;
		$n = ! empty( $q['exact'] ) ? '' : '%';

		$search = array();

		foreach ( (array) $q['search_terms'] as $term ) {
			$search[] = $wpdb->prepare( "$wpdb->posts.post_title LIKE %s", $n . $wpdb->esc_like( $term ) . $n );
		}

		if ( ! is_user_logged_in() ) {
			$search[] = "$wpdb->posts.post_password = ''";
		}

		$search = ' AND ' . implode( ' AND ', $search );
	}

	return $search;
}

/**
 * Utility function to add template paths based on context.
 *
 * @since 2.6.30
 *
 * @param array  $paths          Existing template paths.
 * @param string $relative_path  Relative path for template integration.
 *
 * @return array Modified template paths.
 */
function bb_meprlms_add_template_paths( $paths, $relative_path ) {
	$stylesheet_path = get_stylesheet_directory();
	$template_path   = get_template_directory();
	$is_child_theme  = is_child_theme();
	$template_paths  = array();

	if ( $is_child_theme ) {
		$template_paths[] = $template_path . '/buddyboss/' . $relative_path;
	}

	$template_paths[] = $stylesheet_path . '/buddyboss/' . $relative_path;
	$template_paths[] = bb_meprlms_integration_path( '/templates/' . $relative_path );

	return array_merge( $template_paths, $paths );
}

/**
 * Get the correct template path if it exists.
 *
 * @since 2.6.30
 *
 * @param string $template_name The name of the template file.
 * @param string $relative_path Relative path name.
 *
 * @return string|false The full path to the template if it exists, or false if not.
 */
function bb_meprlms_get_template_path( $template_name, $relative_path = 'courses' ) {
	$stylesheet_path = get_stylesheet_directory();
	$template_path   = get_template_directory();
	$is_child_theme  = is_child_theme();
	$template_paths  = array();

	if ( $is_child_theme ) {
		$template_paths[] = $template_path . '/memberpress/' . $relative_path . '/' . $template_name;
	}

	$template_paths[] = $stylesheet_path . '/memberpress/' . $relative_path . '/' . $template_name;
	$template_paths[] = bb_meprlms_integration_path( '/templates/memberpress/' . $relative_path . '/' . $template_name );

	// Return the first valid template path found.
	foreach ( $template_paths as $path ) {
		if ( $path && file_exists( $path ) ) {
			return $path;
		}
	}

	return false;
}

/**
 * Get the course search form.
 *
 * @since 2.6.30
 *
 * @return string Search form html.
 */
function bb_meprlms_get_course_search_form() {
	global $wp;
	$action_url   = home_url( $wp->request );
	$action_url   = preg_replace( '#/page/\d+/?#', '/', $action_url );
	$placeholder  = esc_html__( 'Search..', 'buddyboss-pro' );
	$reader_text  = esc_html__( 'Search For.', 'buddyboss-pro' );
	$search_value = ( get_query_var( 's' ) ) ? get_query_var( 's' ) : '';

	return '<form method="get" id="bb_meprlms_courses_search_form" action="' . $action_url . '">
			<label>
				<span class="screen-reader-text">' . $reader_text . '</span>
				<input type="search" class="search-field-top" placeholder="' . $placeholder . '" value="' . $search_value . '" name="s">
			</label>
	</form>';
}

/**
 * Checks if MemberpressLMS course visibility enable.
 *
 * @since 2.6.30
 *
 * @param integer $default MemberpressLMS course visibility enabled by default.
 *
 * @return bool Is MemberpressLMS course visibility enabled or not.
 */
function bb_meprlms_course_visibility( $default = 1 ) {

	/**
	 * Filters MemberpressLMS course visibility enabled settings.
	 *
	 * @since 2.6.30
	 *
	 * @param integer $default MemberpressLMS course visibility enabled by default.
	 */
	return (bool) apply_filters( 'bb_meprlms_course_visibility', bb_get_meprlms_settings( 'bb-meprlms-course-visibility', $default ) );
}

/**
 * MemberpressLMS course activities.
 *
 * @since 2.6.30
 *
 * @param array $keys Optionals.
 *
 * @return array
 */
function bb_meprlms_course_activities( $keys = array() ) {
	$activities = array(
		'bb_meprlms_user_started_course'   => esc_html__( 'Group member started a course', 'buddyboss-pro' ),
		'bb_meprlms_user_completed_course' => esc_html__( 'Group member completes a course', 'buddyboss-pro' ),
		'bb_meprlms_user_completed_lesson' => esc_html__( 'Group member completes a lesson', 'buddyboss-pro' ),
	);

	if ( class_exists( 'memberpress\assignments\models\Assignment' ) ) {
		$activities['bb_meprlms_user_completed_assignment'] = esc_html__( 'Group member completed an assignment', 'buddyboss-pro' );
	}

	if ( class_exists( 'memberpress\quizzes\models\Quiz' ) ) {
		$activities['bb_meprlms_user_completed_quiz'] = esc_html__( 'Group member completed quiz', 'buddyboss-pro' );
	}

	$result_activities = ! empty( $keys ) ? array_intersect_key( $activities, $keys ) : $activities;

	/**
	 * Filters to get enabled MemberpressLMS courses activities.
	 *
	 * @since 2.6.30
	 *
	 * @param array|string $result_activities MemberpressLMS course activities.
	 */
	return apply_filters( 'bb_meprlms_course_activities', $result_activities );
}

/**
 * Function to get enabled MemberpressLMS courses activities.
 *
 * @since 2.6.30
 *
 * @param string $key MemberpressLMS course activity slug.
 *
 * @return array Is any MemberpressLMS courses activities enabled?
 */
function bb_get_enabled_meprlms_course_activities( $key = '' ) {

	$option_name = ! empty( $key ) ? 'bb-meprlms-course-activity.' . $key : 'bb-meprlms-course-activity';

	/**
	 * Filters to get enabled MemberpressLMS courses activities.
	 *
	 * @since 2.6.30
	 *
	 * @param array|string MemberpressLMS settings.
	 */
	return apply_filters( 'bb_get_enabled_meprlms_course_activities', bb_get_meprlms_settings( $option_name ) );
}

/**
 * Return inactive class.
 *
 * @since 2.6.30
 *
 * @return string class string.
 */
function bb_meprlms_get_inactive_class() {
	return bp_is_active( 'groups' ) ? '' : 'bb-inactive-field';
}
