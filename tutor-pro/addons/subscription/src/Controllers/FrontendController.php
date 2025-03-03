<?php
/**
 * Handler for Frontend Subscription.
 *
 * @package TutorPro\Subscription
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 3.0.0
 */

namespace TutorPro\Subscription\Controllers;

use TUTOR\Course;
use Tutor\Helpers\DateTimeHelper;
use Tutor\Helpers\QueryHelper;
use TUTOR\Input;
use Tutor\Models\OrderModel;
use TutorPro\Subscription\Models\PlanModel;
use TutorPro\Subscription\Models\SubscriptionModel;
use TutorPro\Subscription\Utils;

/**
 * FrontendController Class.
 *
 * @since 3.0.0
 */
class FrontendController {
	/**
	 * Plan model
	 *
	 * @var PlanModel
	 */
	private $plan_model;

	/**
	 * Subscription model.
	 *
	 * @var SubscriptionModel
	 */
	private $subscription_model;

	/**
	 * Register hooks and dependencies
	 *
	 * @since 3.0.0
	 */
	public function __construct() {
		$this->plan_model         = new PlanModel();
		$this->subscription_model = new SubscriptionModel();

		add_filter( 'get_tutor_course_price', array( $this, 'set_course_price' ), 10, 2 );
		add_filter( 'tutor_course_loop_add_to_cart_button', array( $this, 'course_loop_add_to_cart_button' ), 10, 2 );

		add_filter( 'tutor/course/single/entry-box/purchasable', array( $this, 'show_subscription_plans' ), 12, 2 );
		add_action( 'tutor_course/single/entry/after', array( $this, 'subscription_expire_info' ), 9 );

		add_filter( 'tutor_after_order_history_menu', array( $this, 'register_subscription_menu' ) );
		add_action( 'load_dashboard_template_part_from_other_location', array( $this, 'subscription_view' ) );

		// Course archive.
		add_filter( 'tutor_pro_certificate_access', array( $this, 'certificate_access_for_plan' ), 10, 2 );

		add_filter( 'tutor_pro_check_course_expiry', array( $this, 'bypass_course_expiry_check' ), 11, 2 );

		add_filter( 'is_course_purchasable', array( $this, 'update_is_course_purchaseable' ), 10, 2 );
		add_filter( 'tutor_enroll_now_link_attrs', array( $this, 'add_subscription_attr_for_enrollment' ), 10, 2 );
		add_filter( 'tutor/course/single/entry-box/free', array( $this, 'course_free_access_control' ), 10, 2 );
		add_filter( 'tutor/course/single/entry-box/is_enrolled', array( $this, 'handle_enrolled_state' ), 10, 2 );
		add_action( 'tutor_after_enrolled', array( $this, 'handle_after_enrollment_completed' ), 10, 3 );
		add_action( 'tutor/course/single/content/before/all', array( $this, 'handle_course_content_access' ), 100, 2 );
	}


	/**
	 * Update is course purchaseable.
	 *
	 * @since 3.2.0
	 *
	 * @param bool $is_purchaseable is purchaseable.
	 * @param int  $course_id course id.
	 *
	 * @return bool
	 */
	public function update_is_course_purchaseable( $is_purchaseable, $course_id ) {
		if ( $this->subscription_model->has_course_access( $course_id ) ) {
			return false;
		}

		return $is_purchaseable;
	}

	/**
	 * Handle enrolled state
	 *
	 * @since 3.2.0
	 *
	 * @param string $html html.
	 * @param int    $course_id course id.
	 *
	 * @return string
	 */
	public function handle_enrolled_state( $html, $course_id ) {
		$user_id = get_current_user_id();

		if ( $this->subscription_model->is_enrolled_by_subscription( $course_id, $user_id )
			&& ! $this->subscription_model->has_course_access( $course_id, $user_id )
		) {
			// Cancel the enrollment.
			$enrollment = tutor_utils()->is_enrolled( $course_id, $user_id );
			if ( $enrollment ) {
				tutor_utils()->update_enrollments( 'cancelled', array( $enrollment->ID ), false );
				wp_safe_redirect( get_the_permalink( $course_id ) );
				exit;
			}
		}

		return $html;
	}

	/**
	 * Handle subscription enrollment
	 *
	 * @since 3.2.0
	 *
	 * @param int $course_id course id.
	 * @param int $user_id user id.
	 * @param int $enrolled_id enrollment id.
	 *
	 * @return void
	 */
	public function handle_after_enrollment_completed( $course_id, $user_id, $enrolled_id ) {
		if ( Course::PRICE_TYPE_PAID === tutor_utils()->price_type( $course_id ) && Input::has( 'tutor_subscription_enrollment' ) ) {
			$user_active_subscriptions = $this->subscription_model->get_user_active_subscriptions( get_current_user_id() );
			if ( count( $user_active_subscriptions ) ) {
				$user_latest_subscription_id = (int) $user_active_subscriptions[0]->id;
				update_post_meta( $enrolled_id, $this->subscription_model::SUBSCRIPTION_ENROLLMENT_META, $user_latest_subscription_id );
			}
		}
	}

	/**
	 * Check active subscription before course content access.
	 *
	 * @since 3.2.0
	 *
	 * @param int $course_id  current course id.
	 * @param int $content_id course content like lesson, quiz etc.
	 *
	 * @return void
	 */
	public function handle_course_content_access( $course_id, $content_id ) {

		$user_id                  = get_current_user_id();
		$has_course_access        = tutor_utils()->has_user_course_content_access();
		$is_enrolled              = tutor_utils()->is_enrolled( $course_id, $user_id );
		$enrolled_by_subscription = $this->subscription_model->is_enrolled_by_subscription( $course_id );
		$is_preview_enabled       = tutor()->lesson_post_type === get_post_type( $content_id ) ? (bool) get_post_meta( $content_id, '_is_preview', true ) : false;

		if ( $has_course_access || ( $is_enrolled && ! $enrolled_by_subscription ) || $is_preview_enabled ) {
			return;
		}

		if ( $this->subscription_model->is_enrolled_by_subscription( $course_id )
		&& ! $this->subscription_model->has_course_access( $course_id ) ) {
			wp_safe_redirect( get_permalink( $course_id ) );
			exit;
		}

	}

	/**
	 * Free enrol to course when subscription is active.
	 *
	 * @since 3.2.0
	 *
	 * @param string $html html.
	 * @param int    $course_id course id.
	 *
	 * @return string
	 */
	public function course_free_access_control( $html, $course_id ) {
		if ( Course::PRICE_TYPE_PAID === tutor_utils()->price_type( $course_id ) && $this->subscription_model->has_course_access( $course_id ) ) {
			ob_start();
			include Utils::template_path( 'single/entry-box-free.php' );
			return ob_get_clean();
		}

		return $html;
	}

	/**
	 * Add subscription enrollment attribute to enroll now link.
	 *
	 * @since 3.2.0
	 *
	 * @param array $attr associate array with key value.
	 * @param int   $course_id course id.
	 *
	 * @return string
	 */
	public function add_subscription_attr_for_enrollment( $attr, $course_id ) {
		if ( Course::PRICE_TYPE_PAID === tutor_utils()->price_type( $course_id )
			&& $this->subscription_model->has_course_access( $course_id ) ) {
			$attr['data-subscription-enrollment'] = true;
		}

		return $attr;
	}

	/**
	 * Set price for course subscription plan.
	 *
	 * @since 3.0.0
	 *
	 * @param string $price price.
	 * @param int    $course_id course id.
	 *
	 * @return string
	 */
	public function set_course_price( $price, $course_id ) {
		if ( $this->subscription_model->has_course_access( $course_id ) ) {
			// Set the course as free for enrollment.
			return null;
		}

		$selling_option = Course::get_selling_option( $course_id );

		if ( ! in_array( $selling_option, array( Course::SELLING_OPTION_BOTH, Course::SELLING_OPTION_SUBSCRIPTION ), true ) ) {
			return $price;
		}

		$plan_model        = new PlanModel();
		$course_plans      = $plan_model->get_subscription_plans( $course_id );
		$lowest_price_plan = $plan_model->get_lowest_price_plan( $course_plans );

		if ( ! $lowest_price_plan ) {
			return $price;
		} else {
			ob_start();
			include Utils::template_path( 'loop/subscription-price.php' );
			return ob_get_clean();
		}
	}

	/**
	 * Change add to cart button in course loop
	 *
	 * @since 3.0.0
	 *
	 * @param string $html html.
	 * @param int    $course_id course id.
	 *
	 * @return string
	 */
	public function course_loop_add_to_cart_button( $html, $course_id ) {
		$selling_option = Course::get_selling_option( $course_id );

		if ( ! in_array( $selling_option, array( Course::SELLING_OPTION_BOTH, Course::SELLING_OPTION_SUBSCRIPTION ), true ) ) {
			return $html;
		}

		ob_start();
		$url = get_the_permalink( $course_id );

		?>
		<a href="<?php echo esc_url( $url ); ?>" class="tutor-btn tutor-btn-outline-primary tutor-btn-md tutor-btn-block">
			<?php esc_html_e( 'View Details', 'tutor-pro' ); ?>
		</a>
		<?php
		return ob_get_clean();
	}

	/**
	 * Show subscription plans.
	 *
	 * @since 3.0.0
	 *
	 * @param string $html price html.
	 * @param int    $course_id course id.
	 *
	 * @return string
	 */
	public function show_subscription_plans( $html, $course_id ) {
		$selling_option              = Course::get_selling_option( $course_id );
		$active_membership_plans     = $this->plan_model->get_membership_plans( PlanModel::STATUS_ACTIVE );
		$has_active_membership_plans = count( $active_membership_plans ) > 0;

		if ( ! in_array( $selling_option, array( Course::SELLING_OPTION_BOTH, Course::SELLING_OPTION_SUBSCRIPTION ), true )
			&& ! $has_active_membership_plans ) {
			return $html;
		}

		ob_start();
		$template = Utils::template_path( 'single/subscription-plans.php' );
		include_once $template;
		return ob_get_clean();
	}

	/**
	 * Show subscription expire info.
	 *
	 * @since 3.0.0
	 *
	 * @param int $course_id course id.
	 *
	 * @return void|null
	 */
	public function subscription_expire_info( $course_id ) {
		if ( $this->subscription_model->is_enrolled_by_subscription( $course_id )
			&& $this->subscription_model->has_course_access( $course_id )
		) {
			$user_id                   = get_current_user_id();
			$user_active_subscriptions = $this->subscription_model->get_user_active_subscriptions( $user_id );
			if ( count( $user_active_subscriptions ) ) {
				$subscription = $user_active_subscriptions[0];
				remove_all_filters( 'tutor_course/single/entry/after' );

				$plan     = $this->plan_model->get_plan( $subscription->plan_id );
				$validity = '';
				if ( PlanModel::PAYMENT_ONETIME === $plan->payment_type ) {
					$validity = __( 'Lifetime', 'tutor-pro' );
				} else {
					if ( ! empty( $subscription->next_payment_date_gmt ) ) {
						$validity = DateTimeHelper::get_gmt_to_user_timezone_date( $subscription->next_payment_date_gmt, 'd M, Y' );
					}
				}

				echo '<div class="enrolment-expire-info tutor-fs-7 tutor-color-muted tutor-d-flex tutor-align-center tutor-mt-12">
					<i class="tutor-icon-calender-line tutor-mr-8"></i> ' .
						wp_kses_post(
							sprintf(
								/* translators: %1$s: opening tag, %2$s: date, %3$s: closing tag */
								__( 'Subscription validity: %1$s%2$s%3$s', 'tutor-pro' ),
								'<span class="tutor-ml-4">',
								$validity,
								'</span>'
							)
						)
					. '</div >';
				return;
			}
		} else {
			if ( $this->subscription_model->is_enrolled_by_subscription( $course_id ) ) {
				remove_all_filters( 'tutor_course/single/entry/after' );
				echo '';
				return;
			}
		}
	}

	/**
	 * Register frontend subscription menu.
	 *
	 * @since 3.0.0
	 *
	 * @param array $nav_items nav items.
	 *
	 * @return array
	 */
	public function register_subscription_menu( $nav_items ) {
		$nav_items = apply_filters( 'tutor_pro_before_subscription_menu', $nav_items );

		$nav_items['subscriptions'] = array(
			'title' => __( 'Subscriptions', 'tutor-pro' ),
			'icon'  => 'tutor-icon-subscription',
		);

		return apply_filters( 'tutor_pro_after_subscription_menu', $nav_items );
	}

	/**
	 * Show subscription view.
	 *
	 * @since 3.0.0
	 *
	 * @param string $template template.
	 *
	 * @return string
	 */
	public function subscription_view( $template ) {
		global $wp_query;
		$query_vars = $wp_query->query_vars;

		if ( isset( $query_vars['tutor_dashboard_page'] ) && 'subscriptions' === $query_vars['tutor_dashboard_page'] ) {
			if ( Input::get( 'id', 0, Input::TYPE_INT ) ) {
				$template = Utils::template_path( 'dashboard/subscription-details.php' );
				return $template;
			}

			$template = Utils::template_path( 'dashboard/subscriptions.php' );
			if ( file_exists( $template ) ) {
				return $template;
			}
		}

		return $template;
	}

	/**
	 * Certificate access for plan.
	 *
	 * @since 3.0.0
	 *
	 * @param bool   $has_access has access.
	 * @param object $completed_record course complete record object.
	 *
	 * @return bool
	 */
	public function certificate_access_for_plan( $has_access, $completed_record ) {
		if ( isset( $completed_record->course_id, $completed_record->completed_user_id ) ) {
			$course_id    = $completed_record->course_id;
			$user_id      = $completed_record->completed_user_id;
			$subscription = $this->subscription_model->is_any_course_plan_subscribed( $course_id, $user_id );
			if ( $subscription ) {
				$plan = $this->plan_model->get_plan( $subscription->plan_id );
				if ( $plan && ! $plan->provide_certificate ) {
					$has_access = false;
				}
			}
		}

		return $has_access;
	}

	/**
	 * Bypass course expiry check for enrolled by subscription plan.
	 *
	 * @since 3.0.0
	 *
	 * @param bool $bool true or false.
	 * @param int  $course_id course id.
	 *
	 * @return bool
	 */
	public function bypass_course_expiry_check( $bool, $course_id ) {
		$user_id = get_current_user_id();
		if ( ! $user_id || ! tutor_utils()->is_monetize_by_tutor() ) {
			return $bool;
		}

		$course_enrolled = tutor_utils()->is_enrolled( $course_id, $user_id );

		if ( $course_enrolled ) {
			$order_id = (int) get_post_meta( $course_enrolled->ID, '_tutor_enrolled_by_order_id', true );
			if ( $order_id ) {
				$order_details = ( new OrderModel() )->get_order_by_id( $order_id );
				if ( $order_details && OrderModel::TYPE_SUBSCRIPTION === $order_details->order_type ) {
					/**
					 * If user enrolled by subscription plan, then bypass course expiry check.
					 *
					 * @since 3.0.0
					 */
					return false;
				}
			}
		}

		return $bool;
	}
}
