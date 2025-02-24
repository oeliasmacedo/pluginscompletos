<?php
/**
 * Template for showing course specific plans.
 *
 * @package TutorPro\Subscription
 * @subpackage Templates
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 3.0.0
 * @since 3.2.0 membership plan logic added.
 */

use TUTOR\Course;
use Tutor\Ecommerce\CartController;
use Tutor\Ecommerce\CheckoutController;
use Tutor\Ecommerce\Tax;
use Tutor\Models\CartModel;
use TutorPro\Subscription\Models\PlanModel;
use TutorPro\Subscription\Models\SubscriptionModel;
use TutorPro\Subscription\Settings;

$course_id          = get_the_ID();
$is_logged_in       = is_user_logged_in();
$user_id            = get_current_user_id();
$plan_model         = new PlanModel();
$subscription_model = new SubscriptionModel();

$required_loggedin_class = Course::SELLING_OPTION_SUBSCRIPTION !== $selling_option ? 'tutor-native-add-to-cart' : '';
if ( ! $is_logged_in ) {
	$required_loggedin_class = apply_filters( 'tutor_enroll_required_login_class', 'tutor-open-login-modal' );
}

$checkout_link      = CheckoutController::get_page_url();
$can_cancel_anytime = (bool) tutor_utils()->get_option( 'subscription_cancel_anytime', true );
$course_plans       = $plan_model->get_subscription_plans( $course_id );
$lowest_plan_price  = $plan_model->get_lowest_plan_price( $course_plans );

$lowest_membership_plan_price = $plan_model->get_lowest_plan_price( $active_membership_plans );

$show_price_with_tax = Tax::show_price_with_tax();
$user_logged_in      = is_user_logged_in();
$tax_rate            = $user_logged_in ? Tax::get_user_tax_rate() : 0;
$is_only_membership  = $has_active_membership_plans && Course::SELLING_OPTION_SUBSCRIPTION === $selling_option && ! tutor_utils()->count( $course_plans );

if ( $is_only_membership ) {
	?>
	<div class="tutor-subscription-plans">
		<div class="tutor-d-flex tutor-justify-between tutor-align-center tutor-mb-16">
			<h3 class="tutor-fs-4 tutor-fw-medium tutor-color-primary"><?php esc_html_e( 'Membership', 'tutor-pro' ); ?></h3>
			<div id="tutor-subscription-start-from" class="tutor-d-flex tutor-align-center tutor-gap-4px">
				<div class="tutor-fs-7 tutor-color-hints"><?php esc_html_e( 'Start from', 'tutor-pro' ); ?></div>
				<div class="tutor-fs-6 tutor-fw-bold tutor-color-black"><?php tutor_print_formatted_price( $lowest_membership_plan_price ); ?></div>
			</div>
		</div>
		<a href="<?php echo esc_url( Settings::get_pricing_page_url() ); ?>" class="tutor-btn tutor-btn-primary tutor-btn-lg tutor-btn-block">
			<?php esc_html_e( 'View Pricing', 'tutor-pro' ); ?>
		</a>
	</div>
	<?php
	return;
}

if ( Course::SELLING_OPTION_SUBSCRIPTION === $selling_option && ! tutor_utils()->count( $course_plans ) && ! $has_active_membership_plans ) {
	tutor_alert( __( 'Not pricing data found!', 'tutor-pro' ) );
	return;
}

?>

<div class="tutor-subscription-plans <?php echo esc_attr( Course::SELLING_OPTION_SUBSCRIPTION === $selling_option ? 'subscriptions-only' : '' ); ?> <?php echo esc_attr( $has_active_membership_plans ? 'has-membership' : '' ); ?>">
	<h3 class="tutor-fs-4 tutor-fw-medium tutor-mb-16 tutor-color-primary"><?php esc_html_e( 'Price', 'tutor-pro' ); ?></h3>

	<div class="tutor-course-subscription-options <?php echo esc_attr( ( Course::SELLING_OPTION_SUBSCRIPTION !== $selling_option || $has_active_membership_plans ) ? 'tutor-card' : '' ); ?>">
		<?php
		$course_price_data = tutor_utils()->get_raw_course_price( $course_id );
		?>

		<?php if ( Course::SELLING_OPTION_SUBSCRIPTION !== $selling_option ) { ?>
		<label class="tutor-border-bottom tutor-p-16 tutor-d-flex tutor-items-center tutor-justify-between">
			<div class="tutor-d-flex tutor-align-start tutor-gap-1">
				<input type="radio" name="selling_option" value="one-time" checked class="tutor-form-check-input" autocomplete="off">
				<span class="tutor-fs-6 tutor-fw-medium tutor-color-black"><?php esc_html_e( 'One-time purchase', 'tutor-pro' ); ?></span>
			</div>

			<div>
				<div class="tutor-d-flex tutor-align-center tutor-gap-1">
					<div class="tutor-fs-6 tutor-fw-bold tutor-color-black"><?php tutor_print_formatted_price( $course_price_data->display_price ); ?></div>
					<?php if ( $course_price_data->sale_price ) : ?>
					<del class="tutor-fs-7 tutor-color-hints"><?php tutor_print_formatted_price( $course_price_data->regular_price ); ?></del>
					<?php endif; ?>
				</div>
				<?php if ( $show_price_with_tax && $tax_rate > 0 ) : ?>
				<div class="tutor-fs-7 tutor-color-subdued"><?php esc_html_e( 'Incl. tax', 'tutor-pro' ); ?></div>
				<?php endif; ?>
			</div>
		</label> 
		<?php } ?>

		<?php
		if ( tutor_utils()->count( $course_plans ) && ( Course::SELLING_OPTION_BOTH === $selling_option || ( Course::SELLING_OPTION_SUBSCRIPTION === $selling_option && $has_active_membership_plans ) ) ) {
			?>
			<label class="tutor-p-16 tutor-d-flex tutor-items-center tutor-justify-between">
				<div class="tutor-d-flex tutor-align-center tutor-gap-1">
					<input type="radio" name="selling_option" value="subscription" <?php echo esc_attr( ( Course::SELLING_OPTION_SUBSCRIPTION === $selling_option && $has_active_membership_plans ) ? 'checked' : '' ); ?>  class="tutor-form-check-input" autocomplete="off">
					<span class="tutor-fs-6 tutor-fw-medium tutor-color-black"><?php esc_html_e( 'Subscriptions', 'tutor-pro' ); ?></span>
				</div>
				<div id="tutor-subscription-start-from" class="tutor-d-flex tutor-align-center tutor-gap-4px">
					<div class="tutor-fs-7 tutor-color-hints"><?php esc_html_e( 'Start from', 'tutor-pro' ); ?></div>
					<div class="tutor-fs-6 tutor-fw-bold tutor-color-black"><?php tutor_print_formatted_price( $lowest_plan_price ); ?></div>
				</div>
			</label>
			<?php
		}
		?>

		<div class="tutor-subscription-plan-wrapper <?php echo esc_attr( ( Course::SELLING_OPTION_SUBSCRIPTION === $selling_option && ! $has_active_membership_plans ) ? '' : 'tutor-p-16 tutor-pt-0' ); ?> <?php echo esc_attr( Course::SELLING_OPTION_SUBSCRIPTION !== $selling_option ? 'tutor-d-none' : '' ); ?>">
			<div class="tutor-subscription-choose-plan"><?php esc_html__( 'Choose plan', 'tutor-pro' ); ?></div>
			<?php
			foreach ( $course_plans as $plan ) :
				$in_sale_price = $plan_model->in_sale_price( $plan );
				$display_price = $in_sale_price ? $plan->sale_price : $plan->regular_price;

				if ( $show_price_with_tax && $tax_rate > 0 && ! Tax::is_tax_included_in_price() ) {
					$tax_amount     = Tax::calculate_tax( $display_price, $tax_rate );
					$display_price += $tax_amount;
				}

				$features      = $plan_model->prepare_plan_features( $plan );
				$plan_buy_link = add_query_arg( array( 'plan' => $plan->id ), $checkout_link );
				?>

				<label class="tutor-course-subscription-plan <?php echo esc_attr( $plan->is_featured ? 'featured' : '' ); ?>"
					data-features="<?php echo esc_attr( wp_json_encode( $features ) ); ?>"
					data-plan-id="<?php echo esc_attr( $plan->id ); ?>"
					data-checkout-link="<?php echo esc_url( $plan_buy_link ); ?>"
				>
					<div class="tutor-subscription-header">
						<div class="tutor-d-flex tutor-align-center tutor-gap-1">
							<input type="radio" name="plan_id" value="<?php echo esc_attr( $plan->id ); ?>" class="tutor-form-check-input" autocomplete="off">
							<span class="tutor-fs-6 tutor-fw-medium tutor-color-black">
								<?php echo esc_html( $plan->plan_name ); ?>

								<?php
								if ( $plan->is_featured ) :
									?>
									<span class="tutor-subscription-featured-badge">
										<i class="tutor-icon-star-bold"></i>
									</span>
									<?php
									endif;
								?>
							</span>
						</div>

						<div class="tutor-ml-32 tutor-mt-4">
							<div>
								<strong class="tutor-subscription-price"><?php echo esc_html( tutor_get_formatted_price( $display_price ) ); ?></strong>
								<?php if ( $in_sale_price ) : ?>
									<span class="tutor-subscription-discount-price"><?php echo esc_html( tutor_get_formatted_price( $plan->regular_price ) ); ?></span>
								<?php endif; ?>
								<?php if ( PlanModel::PAYMENT_RECURRING === $plan->payment_type ) { ?>
								<span class="tutor-fs-6 tutor-color-subdued">
									<?php
									echo esc_html(
										$plan->recurring_value > 1
										? sprintf(
											/* translators: %s: value, %s: name */
											__( '/ %1$s %2$s', 'tutor-pro' ),
											$plan->recurring_value,
											$plan->recurring_interval . ( $plan->recurring_value > 1 ? 's' : '' )
										)
										:
										sprintf(
											/* translators: %s: recurring interval */
											__( '/ %1$s', 'tutor-pro' ),
											$plan->recurring_interval . ( $plan->recurring_value > 1 ? 's' : '' )
										)
									);
									?>
								</span>
								<?php } else { ?>
									<span class="tutor-fs-6 tutor-color-subdued">/ <?php esc_html_e( 'lifetime', 'tutor-pro' ); ?></span>
									<?php } ?>
							</div>
							<?php if ( $show_price_with_tax && $tax_rate > 0 ) : ?>
							<div class="tutor-fs-7 tutor-color-subdued"><?php esc_html_e( 'Incl. tax', 'tutor-pro' ); ?></div>
							<?php endif; ?>
						</div>
					</div>
				</label>
			<?php endforeach; ?>
		</div>

		<?php
		if ( $has_active_membership_plans ) {
			?>
			<label class="tutor-p-16 tutor-d-flex tutor-items-center tutor-justify-between <?php echo esc_attr( ( Course::SELLING_OPTION_ONE_TIME === $selling_option || ! tutor_utils()->count( $course_plans ) ) ? '' : 'tutor-border-top' ); ?>">
				<div class="tutor-d-flex tutor-align-center tutor-gap-1">
					<input type="radio" name="selling_option" value="membership" class="tutor-form-check-input" autocomplete="off">
					<span class="tutor-fs-6 tutor-fw-medium tutor-color-black"><?php esc_html_e( 'Memberships', 'tutor-pro' ); ?></span>
				</div>
				<div id="tutor-membership-start-from" class="tutor-d-flex tutor-align-center tutor-gap-4px">
					<div class="tutor-fs-7 tutor-color-hints"><?php esc_html_e( 'Start from', 'tutor-pro' ); ?></div>
					<div class="tutor-fs-6 tutor-fw-bold tutor-color-black"><?php tutor_print_formatted_price( $lowest_membership_plan_price ); ?></div>
				</div>
			</label>
		<?php } ?>
	</div>

	<div class="tutor-mt-20">
		<div class="tutor-course-subscription-buttons">
			<?php if ( $has_active_membership_plans ) : ?>
			<a href="<?php echo esc_url( Settings::get_pricing_page_url() ); ?>" id="tutor-membership-view-pricing" class="tutor-btn tutor-btn-primary tutor-btn-lg tutor-btn-block tutor-d-none">
				<?php esc_html_e( 'View Plans', 'tutor-pro' ); ?>
			</a>
			<?php endif; ?>

			<a href="#" class="tutor-btn tutor-btn-primary tutor-btn-lg tutor-btn-block tutor-subscription-buy-now <?php echo esc_attr( $required_loggedin_class ); ?> <?php echo esc_attr( Course::SELLING_OPTION_SUBSCRIPTION !== $selling_option ? 'tutor-d-none' : '' ); ?>">
				<?php esc_html_e( 'Buy Now', 'tutor-pro' ); ?>
			</a>

			<?php
			if ( Course::SELLING_OPTION_SUBSCRIPTION !== $selling_option ) {
				$is_course_in_user_cart = CartModel::is_course_in_user_cart( $user_id, $course_id );
				$cart_page_url          = CartController::get_page_url();
				?>
				<div class="tutor-subscription-add-to-cart-wrap">
				<?php if ( $is_course_in_user_cart ) { ?>
					<a href="<?php echo esc_url( $cart_page_url ? $cart_page_url : '#' ); ?>" class="tutor-btn tutor-btn-outline-primary tutor-btn-lg tutor-btn-block <?php echo esc_attr( $cart_page_url ? '' : 'tutor-cart-page-not-configured' ); ?>">
						<?php esc_html_e( 'View Cart', 'tutor-pro' ); ?>
					</a>
					<?php } else { ?>
					<button type="button" class="tutor-btn tutor-btn-primary tutor-btn-lg tutor-btn-block <?php echo esc_attr( $required_loggedin_class ); ?>" data-course-id="<?php echo esc_attr( $course_id ); ?>" data-course-single>
						<span class="tutor-icon-cart-line tutor-mr-8"></span>
						<span><?php esc_html_e( 'Add to Cart', 'tutor-pro' ); ?></span>
					</button>
					<?php } ?>
				</div>
				<?php } ?>
		</div>
		<div class="tutor-plan-feature-list <?php echo esc_attr( Course::SELLING_OPTION_SUBSCRIPTION !== $selling_option ? 'tutor-d-none' : '' ); ?>"></div>
	</div>
</div>
