<?php
/**
 * License Form
 *
 * @package TutorPro\Updater
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 1.0.0
 */

$value       = '';
$license_key = '';

$license_activated    = '';
$license_to           = '';
$license_type         = '';
$license_expires_at   = '';
$license_activated_at = '';

if ( $license ) {
	$value                = $license['license_key'];
	$license_key          = preg_replace( '/[^\-]/i', '*', $license['license_key'] );
	$last_dash_index      = strrpos( $license_key, '-' );
	$license_key          = substr( $license_key, 0, $last_dash_index ) . substr( $license['license_key'], $last_dash_index );
	$license_activated    = $license['activated'];
	$license_to           = isset( $license['customer_name'] ) ? $license['customer_name'] : $license['license_to'] ?? '';
	$license_type         = $license['license_type'] ?? '';
	$license_expires_at   = $license['expires_at'] ?? '';
	$license_activated_at = $license['activated_at'] ?? '';
}

?>
<div class="tutor-license-page-wrapper">
	<?php if ( null === $license ) : ?>
	<div class="tutor-license-card tutor-license-empty-card">
		<div class="tutor-row">
			<div class="tutor-col-sm-5">
				<div>
					<?php require_once dirname( __DIR__ ) . '/assets/images/icons/key.svg'; ?>
					<h6 class="tutor-fs-4 tutor-color-black tutor-mb-8 tutor-mt-32"><?php esc_html_e( 'Verify License', 'tutor-pro' ); ?></h6>
					<p class="tutor-fs-6 tutor-color-subdued tutor-m-0"><?php esc_html_e( 'to use Tutor LMS Pro', 'tutor-pro' ); ?></p>
				</div>
			</div>
			<div class="tutor-col-sm-7">
				<form method="post" id="tutor-license-key-form">
					<?php wp_nonce_field( $nonce_field_name ); ?>
					<div class="tutor-form-group">
						<label class="tutor-d-block tutor-fs-7 tutor-color-secondary tutor-mb-4">
							<?php esc_html_e( 'License Key', 'tutor-pro' ); ?>
						</label>
						<input type="text" name="<?php echo esc_attr( $field_name ); ?>" class="tutor-form-control">
						<div class="tutor-fs-7 tutor-color-secondary tutor-mt-12">
							<?php
							printf(
								// Translators: %s will be replaced with a link to get the license key.
								esc_html__( 'If you\'ve already purchased a Tutor LMS Pro license, please paste your code here. %s', 'tutor-pro' ),
								'<a href="https://tutorlms.com/account/subscriptions/licenses" target="_blank">' . esc_html__( 'Get the license key', 'tutor-pro' ) . '</a>'
							);
							?>
						</div>
					</div>
					<div class="tutor-text-right">
						<button type="submit" class="tutor-btn tutor-btn-primary">
							<?php esc_html_e( 'Verify License', 'tutor-pro' ); ?>
						</button>
					</div>
				</form>
			</div>
		</div>
	</div>
	<?php else : ?>
	<div class="tutor-license-card tutor-license-active-card">
		<div class="tutor-d-flex tutor-align-center tutor-justify-between tutor-flex-column tutor-flex-sm-row tutor-gap-2 tutor-mb-40">
			<?php require_once dirname( __DIR__ ) . '/assets/images/tutor-lms-logo.svg'; ?>

			<?php if ( true === (bool) $license_activated ) : ?>
			<div class="tutor-license-status tutor-license-active">
				<?php require_once dirname( __DIR__ ) . '/assets/images/icons/checked-filled.svg'; ?>
				<?php esc_html_e( 'License is activated', 'tutor-pro' ); ?>
			</div>
			<?php else : ?>
			<div class="tutor-license-status tutor-license-expired">
				<?php require_once dirname( __DIR__ ) . '/assets/images/icons/clock-fill.svg'; ?>
				<?php esc_html_e( 'License is inactive', 'tutor-pro' ); ?>
			</div>
			<?php endif; ?>
		</div>

		<div class="tutor-license-info">
			<div class="tutor-license-info-item">
				<div class="tutor-license-info-item-label">
					<?php require_once dirname( __DIR__ ) . '/assets/images/icons/handshake-outline.svg'; ?>
					<?php esc_html_e( 'Licensed to', 'tutor-pro' ); ?>
				</div>
				<div class="tutor-license-info-item-value">
					<?php echo esc_html( $license_to ); ?>
				</div>
			</div>
			<div class="tutor-license-info-item">
				<div class="tutor-license-info-item-label">
					<?php require_once dirname( __DIR__ ) . '/assets/images/icons/google-doc.svg'; ?>
					<?php esc_html_e( 'License Type', 'tutor-pro' ); ?>
				</div>
				<div class="tutor-license-info-item-value">
					<?php echo esc_html( ucwords( $license_type ) ); ?>
				</div>
			</div>
			<div class="tutor-license-info-item">
				<div class="tutor-license-info-item-label">
					<?php require_once dirname( __DIR__ ) . '/assets/images/icons/clock-light.svg'; ?>
					<?php esc_html_e( 'Expiration Date', 'tutor-pro' ); ?>
				</div>
				<div class="tutor-license-info-item-value">
					<?php echo ! $license_expires_at ? 'Never' : esc_html( mysql2date( 'M d, Y', $license_expires_at ) ); ?>
				</div>
			</div>
		</div>

		<div class="tutor-license-key-wrapper">
			<div class="tutor-license-key-icon" style="align-self: start;">
				<img src="<?php echo esc_url( tutor_pro()->url . '/assets/images/icons/key-outline.svg' ); ?>" alt="Key icon">
			</div>
			<div class="tutor-license-key-content">
				<h6 class="tutor-fs-6 tutor-color-black tutor-m-0"><?php esc_html_e( 'License Key', 'tutor-pro' ); ?></h6>
				<div class="tutor-fs-6 tutor-color-subdued"><?php echo esc_html( $license_key ); ?></div>
				<div class="tutor-fs-7 tutor-color-hints">Added on <?php echo esc_html( mysql2date( 'M d, Y', $license_activated_at ) ); ?></div>
			</div>
			<div class="tutor-license-key-buttons">
				<button id="tutor-license-delete-button" class="tutor-btn tutor-color-danger" data-tutor-modal-target="tutor-license-remove-confirm-popup">
					<?php esc_html_e( 'Remove', 'tutor-pro' ); ?>
				</button>
				<button id="tutor-license-edit-button" class="tutor-btn tutor-btn-primary">
					<?php esc_html_e( 'Edit', 'tutor-pro' ); ?>
				</button>
			</div>
		</div>

		<div class="tutor-license-key-form-wrapper tutor-mt-12 tutor-d-none">
			<div class="tutor-license-key-icon">
				<img src="<?php echo esc_url( tutor_pro()->url . '/assets/images/icons/key-outline.svg' ); ?>" alt="Key icon">
			</div>
			<form method="post" id="tutor-license-key-form" class="tutor-license-update-form">
				<?php wp_nonce_field( $nonce_field_name ); ?>
				<div class="tutor-license-key-content">
					<h6 class="tutor-fs-6 tutor-color-black tutor-m-0"><?php esc_html_e( 'License Key', 'tutor-pro' ); ?></h6>
					<input id="update-input-tutor-pro-license-key" type="text" name="<?php echo esc_attr( $field_name ); ?>" value="<?php echo esc_attr( $value ); ?>" class="tutor-form-control">
				</div>
				<div class="tutor-license-key-buttons">
					<button type="button" id="tutor-license-edit-cancel-button" class="tutor-btn tutor-color-subdued">
						<?php esc_html_e( 'Cancel', 'tutor-pro' ); ?>
					</button>
					<button id="tutor-license-update-button" type="submit" class="tutor-btn tutor-btn-primary">
						<?php esc_html_e( 'Update', 'tutor-pro' ); ?>
					</button>
				</div>
			</form>
		</div>

		<p class="tutor-color-secondary tutor-mt-12 tutor-mb-0" style="font-size: 13px; max-width: 400px;">
			<?php
			printf(
				// Translators: %s will be replaced with a link to get the license key.
				esc_html__( 'If you have already purchased a Tutor Pro license, please paste your code here. %s', 'tutor-pro' ),
				'<a href="https://tutorlms.com/account/subscriptions/licenses" target="_blank">' . esc_html__( 'Get the license key', 'tutor-pro' ) . '</a>'
			);
			?>
		</p>
	</div>
	<?php endif; ?>
</div>

<div class="tutor-modal" id="tutor-license-remove-confirm-popup">
	<div class="tutor-modal-overlay"></div>
	<div class="tutor-modal-window">
		<div class="tutor-modal-content tutor-modal-content-white">
			<button class="tutor-iconic-btn tutor-modal-close-o" data-tutor-modal-close>
				<span class="tutor-icon-times" area-hidden="true"></span>
			</button>

			<div class="tutor-modal-body tutor-text-center">
				<div style="max-width:460px; margin: 44px auto;">
					<div class="tutor-fs-4 tutor-fw-medium tutor-color-black tutor-mb-12"><?php esc_html_e( 'Remove the License?', 'tutor-pro' ); ?></div>
					<div class="tutor-fs-6 tutor-color-muted"><?php esc_html_e( 'Removing the license will deactivate premium features of Tutor LMS. This action cannot be undone.', 'tutor-pro' ); ?></div>
					<div class="tutor-d-flex tutor-justify-center tutor-mt-28">
						<button class="tutor-btn tutor-color-secondary" data-tutor-modal-close>
							<?php esc_html_e( 'Cancel', 'tutor-pro' ); ?>
						</button>
						<button id="tutor-license-remove-action" class="tutor-btn tutor-btn-danger tutor-ml-16" data-tutor-modal-submit>
							<?php esc_html_e( 'Remove License', 'tutor-pro' ); ?>
						</button>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>