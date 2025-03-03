<?php
/**
 * Student details template
 *
 * @package TutorPro\Addon
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 1.9.9
 */

use TUTOR\Input;

$user            = wp_get_current_user();
$student_id      = Input::get( 'student_id', 0 );
$student_details = get_userdata( $student_id );
if ( ! $student_id || ! $student_details ) {
	esc_html_e( 'Invalid student', 'tutor-pro' );
	return;
}
$courses = tutor_utils()->get_courses_by_student_instructor_id( $student_id, $user->ID );
?>

<div class="analytics-student-details tutor-user-public-profile tutor-user-public-profile-pp-circle">
	<a class="tutor-btn tutor-btn-ghost" href="<?php echo esc_url( tutor_utils()->tutor_dashboard_url() . 'analytics/students' ); ?>">
		<i class="tutor-icon-previous tutor-mr-8" area-hidden="true"></i> <?php esc_html_e( 'Back', 'tutor-pro' ); ?>
	</a>

	<div class="photo-area">
		<div class="cover-area">
			<div style="background-image:url(<?php echo esc_url( tutor_utils()->get_cover_photo_url( $student_id ) ); ?>); height: 268px"></div>
			<div></div>
		</div>
		<div class="pp-area">
			<div class="profile-pic" style="background-image:url(<?php echo esc_url( get_avatar_url( $student_id, array( 'size' => 150 ) ) ); ?>)">
			</div>
			<div class="profile-name tutor-color-white">
				<h3 class="analytics-profile-name">
				   <?php echo esc_html( $student_details->display_name ); ?>
				</h3>
				<div class="analytics-profile-authormeta">
					<span class="tutor-fs-7 ">
						<span class="">
						   <?php esc_html_e( 'Email: ', 'tutor-pro' ); ?>
						</span>
						<span class="tutor-fs-7 tutor-fw-medium">
							<?php echo esc_html( $student_details->user_email ); ?>
						</span>
					</span>
					<span  class="tutor-fs-7">
						<span>
							<?php esc_html_e( 'Registration Date:', 'tutor-pro' ); ?>
						</span>
						<span class="tutor-fs-7 tutor-fw-medium">
							<?php echo esc_html( tutor_i18n_get_formated_date( $student_details->user_registered ) ); ?>
						</span>
					</span>
				</div>
			</div>
		</div>
	</div>

	<?php if ( count( $courses ) ) : ?>
		<div class="tutor-analytics-widget">
			<div class="tutor-analytics-widget-title tutor-fs-5 tutor-fw-medium tutor-color-black tutor-mb-16">
				<?php esc_html_e( 'Course Overview', 'tutor-pro' ); ?>
			</div>

			<div class="tutor-table-responsive">
				<table class="tutor-table  tutor-table-analytics-student-details">
					<thead>
						<th>
							<?php esc_html_e( 'Date', 'tutor-pro' ); ?>
						</th>
						<th>
							<?php esc_html_e( 'Course', 'tutor-pro' ); ?>
						</th>
						<th>
							<?php esc_html_e( 'Progress', 'tutor-pro' ); ?>
						</th>
						<th></th>
					</thead>

					<tbody>
						<?php
							$course_ids       = array_column( $courses, 'ID' );
							$course_meta_data = tutor_utils()->get_course_meta_data( $course_ids );
						?>

						<?php foreach ( $courses as $course ) : ?>
							<?php
								$completed_count = tutor_utils()->get_course_completed_percent( $course->ID, $student_id );

								$total_lessons = isset( $course_meta_data[ $course->ID ] ) ? $course_meta_data[ $course->ID ]['lesson'] : 0;

								$completed_lessons = tutor_utils()->get_completed_lesson_count_by_course( $course->ID, $student_id );

								$total_assignments    = isset( $course_meta_data[ $course->ID ] ) ? $course_meta_data[ $course->ID ]['tutor_assignments'] : 0;
								$completed_assignment = tutor_utils()->get_completed_assignment( $course->ID, $student_id );

								$total_quiz     = isset( $course_meta_data[ $course->ID ] ) ? $course_meta_data[ $course->ID ]['tutor_quiz'] : 0;
								$completed_quiz = tutor_utils()->get_completed_quiz( $course->ID, $student_id );
							?>
							<tr>
								<td class="tutor-td-middle">
									<?php echo esc_html( tutor_i18n_get_formated_date( $course->post_date ) ); ?>
								</td>

								<td class="tutor-td-middle">
									<div class="tutor-color-black td-course tutor-fs-6 tutor-fw-medium">
										<span>
											<?php echo esc_html( $course->post_title ); ?>
										</span>
										<div class="tutor-meta tutor-mt-8">
											<span>
												<span class="tutor-meta-key"><?php esc_html_e( 'Lesson: ', 'tutor-pro' ); ?></span>
												<span class="tutor-meta-value"><?php echo esc_html( $completed_lessons . '/' . $total_lessons ); ?></span>
											</span>

											<span>
												<span class="tutor-meta-key"><?php esc_html_e( 'Assignment: ', 'tutor-pro' ); ?></span>
												<span class="tutor-meta-value"><?php echo esc_html( $completed_assignment . '/' . $total_assignments ); ?></span>
											</span>
											
											<span>
												<span class="tutor-meta-key"><?php esc_html_e( 'Quiz: ', 'tutor-pro' ); ?></span>
												<span class="tutor-meta-value"><?php echo esc_html( $completed_quiz . '/' . $total_quiz ); ?></span>
											</span>
										</div>
									</div>
								</td>

								<td class="tutor-td-middle">
									<div class="tutor-d-flex tutor-align-center">
										<div class="tutor-progress-bar" style="min-width: 50px; --tutor-progress-value: <?php echo esc_attr( $completed_count ); ?>%">
											<div class="tutor-progress-value" area-hidden="true"></div>
										</div>

										<div class="tutor-ml-12">
											<?php echo esc_html( $completed_count ); ?>%
										</div>
									</div>
								</td>

								<td class="tutor-td-middle">
									<button type="button" id="open_progress_modal" class="analytics_view_course_progress tutor-btn tutor-btn-outline-primary tutor-btn-sm tutor-text-nowrap" data-course_id="<?php echo esc_attr( $course->ID ); ?>" data-total_progress="<?php echo esc_attr( $completed_count ); ?>" data-total_lesson="<?php echo esc_attr( $total_lessons ); ?>" data-completed_lesson="<?php echo esc_attr( $completed_lessons ); ?>" data-total_assignment="<?php echo esc_attr( $total_assignments ); ?>" data-completed_assignment="<?php echo esc_attr( $completed_assignment ); ?>" data-total_quiz="<?php echo esc_attr( $total_quiz ); ?>" data-completed_quiz="<?php echo esc_attr( $completed_quiz ); ?>" data-student_id="<?php echo esc_attr( $student_id ); ?>">
										<?php esc_html_e( 'View Progress', 'tutor-pro' ); ?>
									</button>
								</td>
							</tr>
						<?php endforeach ?>
					</tbody>
				</table>
			</div>
		</div>

		<div id="modal-course-overview" class="modal-course-overview tutor-modal">
			<div class="tutor-modal-overlay"></div>
			<div class="tutor-modal-window tutor-modal-window-lg">
				<div class="tutor-modal-content tutor-modal-content-white">
					<button class="tutor-iconic-btn tutor-modal-close-o" data-tutor-modal-close>
						<span class="tutor-icon-times" area-hidden="true"></span>
					</button>

					<div class="tutor-modal-body" id="tutor_progress_modal_content">
						<div class="tutor-mt-48">
							<img class="tutor-d-inline-block" src="<?php echo esc_url( tutor()->url . 'assets/images/icon-trash.svg' ); ?>" />
						</div>

						<div class="tutor-fs-3 tutor-fw-medium tutor-color-black tutor-mb-12"><?php esc_html_e( 'Delete This Question?', 'tutor-pro' ); ?></div>
						<div class="tutor-fs-6 tutor-color-muted"><?php esc_html_e( 'All the replies also will be deleted.', 'tutor-pro' ); ?></div>
						
						<div class="tutor-d-flex tutor-justify-center tutor-my-48">
							<button data-tutor-modal-close class="tutor-btn tutor-btn-outline-primary">
								<?php esc_html_e( 'Cancel', 'tutor-pro' ); ?>
							</button>
							<button class="tutor-btn tutor-btn-primary tutor-list-ajax-action tutor-ml-20" data-request_data='{"question_id":<?php echo esc_attr( isset( $qna ) && is_object( $qna ) ? $qna->comment_ID : '' ); ?>,"action":"tutor_delete_dashboard_question"}' data-delete_element_id="<?php echo esc_html( isset( $row_id ) ? $row_id : '' ); ?>">
								<?php esc_html_e( 'Yes, Delete This', 'tutor-pro' ); ?>
							</button>
						</div>
					</div>
				</div>
			</div>
		</div>
	<?php else : ?>
		<?php tutor_utils()->tutor_empty_state( tutor_utils()->not_found_text() ); ?>
	<?php endif; ?>
</div>
