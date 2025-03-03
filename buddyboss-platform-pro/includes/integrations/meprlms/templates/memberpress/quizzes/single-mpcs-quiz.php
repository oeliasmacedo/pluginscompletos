<?php
/**
 * Template for single quiz page for memberpress courses.
 *
 * This template can be overridden by copying it to yourtheme/memberpress/quizzes/single-mpcs-quiz.php.
 *
 * @since 2.6.30
 *
 * @package BuddyBoss\MemberpressLMS
 */

use memberpress\quizzes\models;
use memberpress\quizzes\helpers;
use memberpress\courses;

// Load header.
get_header(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped.

// Start the Loop.
while ( have_posts() ) :
	the_post();
	global $post;

	$quiz           = new models\Quiz( $post->ID );
	$quiz_available = $quiz->is_available();
	?>
	<div class="entry entry-content">
		<div class="columns col-gapless" style="flex-grow: 1;">
			<div id="mpcs-sidebar" class="column col-3 col-md-12 pl-0">

				<?php
				echo courses\helpers\Courses::get_classroom_sidebar( $post ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				?>

			</div>
			<div id="mpcs-main" class="mpcs-main column col-9 col-md-12 mpcs-inner-page-main">
				<?php setup_postdata( $post->ID ); ?>
				<?php if ( is_active_sidebar( 'mpcs_classroom_lesson_header' ) ) : ?>
					<div id="primary-sidebar" class="primary-sidebar widget-area" role="complementary">
						<?php dynamic_sidebar( 'mpcs_classroom_lesson_header' ); ?>
					</div>
				<?php endif; ?>

				<?php
				if ( 'enabled' === $quiz->course()->lesson_title ) {
					printf( '<h1 class="entry-title">%s</h1>', esc_html( get_the_title() ) );
				}
				?>

				<?php
				if ( $quiz_available ) {
					?>
					<div class="mpcs-main-content"><?php the_content(); ?></div>
					<?php
				} else {
					$button_class = 'btn btn-green is-purple';
					require MeprView::file( '/lessons/lesson_locked' );
				}
				?>

				<div class="mepr-rl-footer-widgets">
					<?php if ( is_active_sidebar( 'mpcs_classroom_lesson_footer' ) ) : ?>
						<div id="primary-sidebar" class="primary-sidebar widget-area" role="complementary">
							<?php dynamic_sidebar( 'mpcs_classroom_lesson_footer' ); ?>
						</div>
					<?php endif; ?>
					<?php if ( is_active_sidebar( 'mepr_rl_global_footer' ) ) : ?>
						<div id="mepr-rl-global-footer-widget" class="mepr-rl-global-footer-widget widget-area"
							role="complementary">
							<?php dynamic_sidebar( 'mepr_rl_global_footer' ); ?>
						</div>
					<?php endif; ?>
				</div>

			</div>
		</div>
	</div>
	<?php
	wp_reset_postdata();
endwhile; // End the loop.
get_footer(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
