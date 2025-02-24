<?php
/**
 * Init Tutor Pro
 *
 * @package TutorPro
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 1.0.0
 */

namespace TUTOR_PRO;

use TUTOR\Input;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Init Class
 *
 * @since 1.0.0
 */
class Init {

	/**
	 * The user's license data is saved against this option key
	 *
	 * @since 3.2.0
	 */
	const TUTOR_LICENSE_OPT_KEY = 'tutor_license_info';

	//phpcs:disable Squiz.Commenting.VariableComment.Missing
	public $version = TUTOR_PRO_VERSION;
	public $path;
	public $url;
	public $basename;

	private $admin;
	private $assets;
	private $general;
	private $quiz;

	private $upgrader;
	private $google_meet;

	private $course_duplicator;
	private $instructor_percentage;
	private $enrollment_expiry;
	private $dashboard;
	private $shortcode;
	private $updater;

	private $email_verification;
	private $device_management;
	private $instructor;
	private $rest_api;
	//phpcs:enable Squiz.Commenting.VariableComment.Missing

	/**
	 * Register hooks and dependencies.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function __construct() {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$this->path     = plugin_dir_path( TUTOR_PRO_FILE );
		$this->url      = plugin_dir_url( TUTOR_PRO_FILE );
		$this->basename = plugin_basename( TUTOR_PRO_FILE );

		if ( is_plugin_active( 'tutor/tutor.php' ) && $this->is_tutor_core_has_req_verion() ) {
			add_action( 'tutor_loaded', array( $this, 'load_constructors_asset' ) );
		}

		if ( ! is_plugin_active( 'tutor/tutor.php' ) ) {
			add_action( 'admin_notices', array( $this, 'required_version_notice' ) );
		}

		if ( is_plugin_active( 'tutor/tutor.php' ) && ! $this->is_tutor_core_has_req_verion() ) {
			add_action( 'admin_notices', array( $this, 'required_version_notice' ) );
		}

		if ( class_exists( 'GoogleMeet' ) ) {
			require_once tutor_pro()->path . 'addons/google-meet/google-meet.php';
		}
	}

	/**
	 * Load constructors assets.
	 *
	 * @since 3.2.0
	 *
	 * License check added before loading Pro files, assets etc
	 * User 's license data must be store in database to use Pro plugin
	 * regardless of activate status. If no license data available then
	 * user must have to enter valid license key to activate Pro plugin
	 *
	 * @return void
	 */
	public function load_constructors_asset() {
		spl_autoload_register( array( $this, 'loader' ) );

		$this->admin  = new Admin();
		$this->assets = new Assets();

		$has_license = get_option( self::TUTOR_LICENSE_OPT_KEY, false );

		// Init license feature.
		$this->init_license();

		// If license not available return.
		if ( is_admin() && false === $has_license && ! tutor_is_local_env() ) {
			$current_page = Input::get( 'page' );
			if ( 'tutor-pro-license' !== $current_page ) {
				add_action( 'admin_notices', array( $this, 'license_notice' ) );
			}

			add_filter(
				'tutor_pro_flag',
				function () {
					return '';
				}
			);

			add_filter(
				'tutor_localize_data',
				array( $this, 'remove_pro_url' )
			);

			add_filter(
				'tutor_course_builder_localized_data',
				array( $this, 'remove_pro_url' )
			);

			return;
		}

		$this->includes();

		spl_autoload_register( array( $this, 'loader' ) );

		$this->admin  = new Admin();
		$this->assets = new Assets();

		add_action( 'tutor_course_create_button', __CLASS__ . '::render_button' );

		// @since 3.0.0
		$tutorai = tutor_pro()->path . 'tutorai/TutorAI.php';
		if ( file_exists( $tutorai ) ) {
			require_once $tutorai;
		}

		$tutor_pro_ecommerce = tutor_pro()->path . 'ecommerce/Init.php';
		if ( file_exists( $tutor_pro_ecommerce ) ) {
			require_once $tutor_pro_ecommerce;
		}

		// Autoloader.
		spl_autoload_register( array( $this, 'loader' ) );

		do_action( 'tutor_pro_before_load' );

		// Load Component from Class.
		$this->admin    = new Admin();
		$this->assets   = new Assets();
		$this->general  = new General();
		$this->upgrader = new Upgrader();
		$this->quiz     = new Quiz();

		$this->course_duplicator     = new Course_Duplicator();
		$this->instructor_percentage = new Instructor_Percentage();
		$this->enrollment_expiry     = new Enrollment_Expiry();
		$this->dashboard             = new Dashboard();
		$this->shortcode             = new Shortcode();
		$this->instructor            = new Instructor();
		$this->device_management     = new DeviceManagement();
		$this->email_verification    = new EmailVerification();
		$this->rest_api              = new RestAPI();

		new Filters();
		new ContentSecurity();
		new ContentDuplicator();
		new NotificationPreference();

		$this->load_addons();

		do_action( 'tutor_pro_loaded' );
	}

	/**
	 * Auto-Load class and the files
	 *
	 * @since 1.0.0
	 *
	 * @param string $class_name class name.
	 *
	 * @return void
	 */
	private function loader( $class_name ) {
		if ( ! class_exists( $class_name ) ) {
			$class_name = preg_replace(
				array( '/([a-z])([A-Z])/', '/\\\/' ),
				array( '$1$2', DIRECTORY_SEPARATOR ),
				$class_name
			);

			$class_name = str_replace( 'TUTOR_PRO' . DIRECTORY_SEPARATOR, 'classes' . DIRECTORY_SEPARATOR, $class_name );
			$file_name  = $this->path . $class_name . '.php';

			if ( file_exists( $file_name ) && is_readable( $file_name ) ) {
				require_once $file_name;
			}
		}
	}

	/**
	 * Run the plugin.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function run() {
		do_action( 'tutor_pro_before_run' );

		register_activation_hook( TUTOR_PRO_FILE, array( $this, 'tutor_pro_activate' ) );

		do_action( 'tutor_pro_after_run' );
	}

	/**
	 * Do some task during plugin activation
	 *
	 * @since 1.0.0
	 */
	public function tutor_pro_activate() {
		$version = get_option( 'tutor_pro_version' );
		// Save Option.
		if ( ! $version ) {
			update_option( 'tutor_pro_version', TUTOR_PRO_VERSION );
		}

		$this->migrate_membership_model();
		$this->assignment_meta_generate();
	}


	/**
	 * Migrate membership model
	 *
	 * @since 1.9.4
	 */
	private function migrate_membership_model() {
		$migrated  = get_option( 'tutor_pmpro_model_migrated', false );
		$old_model = get_option( 'tutor_pmpro_membership_model', null );

		if ( $migrated || ! $old_model || ! tutor_utils()->has_pmpro() ) {
			// Already migrated
			// or old one not saved yet
			// PM pro not available.
			return;
		}

		global $wpdb;
		$ids                      = $wpdb->get_col( "SELECT id FROM {$wpdb->pmpro_membership_levels}" );
		! is_array( $ids ) ? $ids = array() : 0;

		foreach ( $ids as $level_id ) {
			update_pmpro_membership_level_meta( $level_id, 'tutor_pmpro_membership_model', $old_model );
		}

		update_option( 'tutor_pmpro_model_migrated', true );
	}


	/**
	 * Includes helper files.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function includes() {
		include tutor_pro()->path . 'includes/functions.php';
	}

	/**
	 * Load addons
	 *
	 * @since 1.0.0
	 * @since 3.0.0 Added hook for each addon loaded.
	 *
	 * @return void
	 */
	public function load_addons() {

		$addons_dir = array_filter( glob( tutor_pro()->path . 'addons' . DIRECTORY_SEPARATOR . '*' ), 'is_dir' );
		if ( count( $addons_dir ) > 0 ) {
			foreach ( $addons_dir as $key => $value ) {
				$addon_dir_name = str_replace( dirname( $value ) . DIRECTORY_SEPARATOR, '', $value );
				$file_name      = tutor_pro()->path . 'addons' . DIRECTORY_SEPARATOR . $addon_dir_name . DIRECTORY_SEPARATOR . $addon_dir_name . '.php';
				if ( file_exists( $file_name ) ) {
					include_once $file_name;

					/**
					 * Hook for each addon that triggers when the addon is loaded.
					 * Example: tutor_email_addon_loaded
					 *          tutor_auth_addon_loaded
					 *
					 * @since 3.0.0
					 */
					$hook_prefix = 'tutor_';
					$addon_hook  = str_replace( '-', '_', $addon_dir_name . '_addon_loaded' );
					$addon_hook  = strpos( $addon_hook, $hook_prefix ) !== 0 ? $hook_prefix . $addon_hook : $addon_hook;
					do_action( $addon_hook, $file_name );
				}
			}
		}
	}

	/**
	 * Assignment meta generate for backward compatibility
	 *
	 * This method will update meta key for assignment total mark & pass mark
	 * required for query data
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	public function assignment_meta_generate(): void {
		global $wpdb;
		$already_updated = get_option( 'tutor_assignment_meta_key_updated' );
		if ( ! $already_updated ) {
			$assignments = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT post.ID, m1.meta_value
						FROM {$wpdb->posts} AS post
						INNER JOIN {$wpdb->postmeta} as m1
							ON m1.post_id = post.ID
							AND m1.meta_key = 'assignment_option'
						WHERE post.post_type = %s
					",
					'tutor_assignments'
				)
			);
			$this->update_assignment_meta( $assignments );
		}
	}

	/**
	 * Update assignment meta keys with value
	 *
	 * @since 2.0.0
	 *
	 * @param array $assignments | assignments array of objects.
	 *
	 * @return bool
	 */
	public function update_assignment_meta( $assignments ): bool {
		foreach ( $assignments as $assignment ) {
			$assignment_id   = $assignment->ID;
			$assignment_info = unserialize( $assignment->meta_value );
			$total_mark      = isset( $assignment_info['total_mark'] ) ? $assignment_info['total_mark'] : '';
			$pass_mark       = isset( $assignment_info['pass_mark'] ) ? $assignment_info['pass_mark'] : '';
			if ( '' !== $total_mark ) {
				update_post_meta( $assignment_id, '_tutor_assignment_total_mark', $total_mark );
			}
			if ( '' !== $pass_mark ) {
				update_post_meta( $assignment_id, '_tutor_assignment_pass_mark', $pass_mark );
			}
			add_option( 'tutor_assignment_meta_key_updated', true );
		}
		return true;
	}

	/**
	 * Check whether Tutor core has required version installed
	 *
	 * @since 2.0.0
	 *
	 * @return bool if has return true otherwise false
	 */
	public function is_tutor_core_has_req_verion() {
		$file_path              = WP_PLUGIN_DIR . '/tutor/tutor.php';
		$plugin_data            = get_file_data(
			$file_path,
			array(
				'Version' => 'Version',
			)
		);
		$tutor_version          = $plugin_data['Version'];
		$tutor_core_req_version = TUTOR_CORE_REQ_VERSION;
		$is_compatible          = version_compare( $tutor_version, $tutor_core_req_version, '>=' );
		return $is_compatible ? true : false;
	}

	/**
	 * Tutor core required version message
	 *
	 * @since 2.0.0
	 */
	public function tutor_core_version_compare() {
		$tutor_file = WP_PLUGIN_DIR . '/tutor/tutor.php';
		if ( file_exists( $tutor_file ) && defined( 'TUTOR_VERSION' ) ) {
			if ( ! $this->is_tutor_core_has_req_verion() ) {
				add_action( 'admin_notices', array( $this, 'required_version_notice' ) );
			}
		}
	}

	/**
	 * Admin notice for required Tutor core version
	 *
	 * @since 2.0.0
	 */
	public function required_version_notice() {
		?>
		<div class="notice notice-error tutor-install-notice" style="display:flex; tutor-justify: space-between; align-items: center;">
			<div class="tutor-install-notice-inner" style="display: flex; column-gap: 20px;">
				<div class="tutor-install-notice-icon">
					<img src="<?php echo esc_url( tutor_pro()->url . 'assets/images/tutor-logo.jpg' ); ?>" alt="">
				</div>
				<div class="tutor-install-notice-content">
					<h2><?php esc_html_e( 'WARNING: Tutor LMS Free plugin is required.', 'tutor-pro' ); ?></h2>
					<p>
						<?php
							$notice = __( 'It appears you have installed only the pro version of Tutor LMS. To ensure all features function correctly, please install the latest version of Tutor LMS Free.', 'tutor-pro' );
							echo esc_html( $notice );
						?>
					</p>
					<p style="color: #757C8E;">
						<?php esc_html_e( 'Note: Tutor LMS Pro requires the free version to be installed. Without it, Pro features will not be available.', 'tutor-pro' ); ?>
					</p>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render create course button
	 *
	 * @since 2.0.7
	 *
	 * @return void
	 */
	public static function render_button() {
		ob_start();
		?>
		<a href="#" class="tutor-btn tutor-btn-outline-primary tutor-create-new-course tutor-dashboard-create-course">
			<i class="tutor-icon-plus-square tutor-my-n4 tutor-mr-8"></i>
			<?php esc_html_e( 'Create a New Course', 'tutor-pro' ); ?>
		</a>
		<?php
		$btn = apply_filters( 'tutor_pro_create_new_course_button', ob_get_clean() );
		echo wp_kses_post( $btn );
	}

	public function init_license() {
		require_once dirname( __DIR__ ) . '/updater/update.php';
		$this->updater = new \TutorPRO\ThemeumUpdater\Update(
			array(
				'product_title'      => 'Tutor Pro',
				'product_slug'       => 'tutor-pro',
				'product_basename'   => tutor_pro()->basename,
				'product_type'       => 'plugin',
				'current_version'    => TUTOR_PRO_VERSION,

				'menu_title'         => __( 'License', 'tutor-pro' ),
				'parent_menu'        => 'tutor',
				'menu_capability'    => 'manage_tutor',
				'license_option_key' => 'tutor_license_info',

				'updater_url'        => tutor_pro()->url . 'updater/',
				'header_content'     => '<svg width="116" height="24" fill="none" xmlns="http://www.w3.org/2000/svg"><defs/><path d="M27.496 19.704V6.36h-4c-.452 0-.62-.288-.62-.988v-.62c0-.704.168-.992.62-.992H34.56c.456 0 .62.288.62.992v.62c0 .7-.208.988-.62.988h-3.964v13.344c0 .456-.372.704-1.24.704h-.62c-.868-.008-1.24-.248-1.24-.704zM53.148 14.292c0-3.924 1.944-6.072 5.6-6.072 3.656 0 5.576 2.148 5.576 6.072s-1.924 6.072-5.556 6.072-5.62-2.148-5.62-6.072zm8.18 0c0-2.52-.8-3.8-2.56-3.8s-2.604 1.28-2.604 3.8c0 2.52.8 3.8 2.604 3.8s2.56-1.292 2.56-3.8zM66 8.22h1.94a.324.324 0 01.332.332l.124 1.732c.576-.948 1.404-1.776 2.6-1.776 1.076 0 1.364.4 1.364 1.116 0 .456-.248 1.736-.66 1.736a8.735 8.735 0 00-1.156-.208c-1.076 0-1.82.952-2 1.28v7.272c0 .456-.372.704-1.28.704h-.372c-.868 0-1.24-.248-1.24-.704V8.552A.326.326 0 0166 8.22zM78.1 19.664V4.256c0-.332.288-.496.908-.496h.192c.66 0 .908.204.908.496v14.332h6.4c.332 0 .456.208.456.704v.372c0 .496-.124.7-.456.7H78.8a.611.611 0 01-.7-.7zM102.225 3.716h-.8a1.027 1.027 0 00-.988.4l-4.988 8.564-5-8.548c-.12-.248-.4-.4-.988-.4h-.828c-.66 0-.908.208-.908.496v15.64c0 .332.288.496.908.496h.168c.664 0 .912-.204.912-.496v-11.6c.007-.427-.008-.854-.044-1.28l.084-.04c.152.365.345.71.576 1.032L94.5 15.2a.448.448 0 00.492.292h.912a.576.576 0 00.496-.292l4.088-7.064c.208-.328.372-.66.58-1.032l.08.044c-.04.4-.04.864-.04 1.28v11.44c0 .332.288.496.908.496h.164c.664 0 .912-.204.912-.496v-15.6c.08-.344-.208-.552-.868-.552zM105.488 19.248c-.292-.204-.456-.4-.456-.576 0-.372.576-1.28.824-1.28.181.047.35.132.496.248a7.162 7.162 0 003.556.948c1.776 0 3.056-1.116 3.056-2.768 0-1.9-1.736-2.476-3.552-3.18-2-.8-4.092-1.528-4.092-4.628 0-2.52 2.068-4.336 5.2-4.336.992 0 2.52.288 3.308.828a.794.794 0 01.452.62c0 .368-.496 1.236-.744 1.236a1.438 1.438 0 01-.62-.288 5.09 5.09 0 00-2.436-.62c-1.776 0-3.016.952-3.016 2.644 0 1.692 1.488 2.148 3.18 2.8 2.108.8 4.464 1.736 4.464 4.96 0 2.8-2.068 4.544-5.2 4.544-2.028 0-3.68-.572-4.42-1.152zM51.828 17.888a5.25 5.25 0 01-.8.08c-.62 0-.992-.288-.992-1.28v-6.156h1.236a.632.632 0 00.62-.62V8.756a.636.636 0 00-.62-.62h-1.236V5.6a.636.636 0 00-.62-.616H47.68a.632.632 0 00-.604.616v2.52h-.8a.636.636 0 00-.62.62v1.156a.632.632 0 00.62.62h.8v6.212c0 2.644 1.448 3.6 3.304 3.6.704 0 2.068-.084 2.068-.992.04-.168-.208-1.448-.62-1.448zM43.856 8.18a.636.636 0 01.62.62v10.944a.636.636 0 01-.62.62h-1.572a.636.636 0 01-.62-.62v-1.156a4.798 4.798 0 01-3.6 1.776c-2.4 0-3.6-1.28-3.6-3.8V8.8a.636.636 0 01.62-.62h1.736a.632.632 0 01.632.62v7.352c0 1.116.576 1.736 1.776 1.736.992 0 1.816-.744 2.272-1.448V8.8a.631.631 0 01.62-.62h1.736z" fill="#092844"/><path fill-rule="evenodd" clip-rule="evenodd" d="M4.824 15.284a1.2 1.2 0 01-1.156-1.156v-2.644a1.156 1.156 0 112.312 0v2.644a1.128 1.128 0 01-1.156 1.156zM13.168 15.284A1.154 1.154 0 0112 14.128v-2.644a1.156 1.156 0 012.312 0v2.644a1.153 1.153 0 01-1.156 1.156" fill="#0049F8"/><path fill-rule="evenodd" clip-rule="evenodd" d="M2.512 9.5a2.928 2.928 0 012.56-1.692 2.948 2.948 0 012.852 3.016v5.412a1.084 1.084 0 002.148 0v-5.412a2.944 2.944 0 012.848-3.016 2.672 2.672 0 012.48 1.568A7.2 7.2 0 112.512 9.5zm4.544-7.1h4.048v1.564a8.905 8.905 0 00-2.064-.248c-.672.007-1.341.076-2 .208L7.056 2.4zm10.988 11.068c0-.248.04-.456.04-.704a9.077 9.077 0 00-4.624-7.888V2.4h1.74a1.2 1.2 0 000-2.4H2.924a1.264 1.264 0 00-1.2 1.24 1.2 1.2 0 001.2 1.2H4.7v2.44a9.028 9.028 0 00-3.264 12.8C4.8 23.38 13.912 23.96 17.012 24a1.2 1.2 0 00.744-.288 1.008 1.008 0 00.288-.744v-9.5z" fill="#0049F8"/></svg>',
			)
		);
	}

	/**
	 * Show admin notice for license activation
	 *
	 * @since 3.2.0
	 */
	public function license_notice() {
		?>
		<div class="tutor-license-notice notice notice-error notice-warning is-dismissible">
			<div class="tutor-d-flex tutor-justify-between">
				<div class="tutor-license-notice-icon">
					<svg width="144" height="32" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M105.714 25.347h7.519v1.55h-9.165V10.45h1.646v14.897ZM129.915 10.45v16.448h-1.645V12.94l-5.757 9.634h-.235l-5.756-9.634v13.958h-1.645V10.45h2.018l5.501 9.207 5.501-9.207h2.018ZM137.903 27.18c-1.503 0-2.792-.357-3.865-1.07-1.073-.712-1.821-1.695-2.244-2.948l1.41-.823c.297 1.02.846 1.814 1.644 2.385.8.572 1.825.858 3.078.858 1.222 0 2.174-.27 2.855-.81.683-.541 1.023-1.258 1.023-2.15 0-.862-.314-1.512-.94-1.951-.627-.438-1.66-.893-3.102-1.362-1.707-.565-2.835-1.019-3.383-1.364-1.254-.752-1.88-1.84-1.88-3.266 0-1.394.485-2.494 1.457-3.301.971-.806 2.169-1.21 3.595-1.21 1.284 0 2.397.333 3.336.998a6.056 6.056 0 0 1 2.091 2.55l-1.386.775c-.752-1.817-2.099-2.725-4.041-2.725-1.019 0-1.841.25-2.467.752-.628.501-.941 1.198-.941 2.09 0 .815.283 1.426.847 1.834.564.407 1.496.822 2.796 1.245.595.203 1.021.35 1.28.435.258.086.635.227 1.129.422.493.197.857.368 1.092.517.235.15.521.353.858.612.336.258.579.516.728.775.149.258.281.572.399.94s.176.764.176 1.186c0 1.395-.509 2.51-1.526 3.348-1.019.839-2.359 1.258-4.019 1.258ZM88.383 26.911V10.798h3.115v1.61h.052c1.402-2 3.687-2.05 4.518-2.077v3.428c-.831.051-1.662.26-2.415.649-1.064.57-1.947 1.92-1.947 4.465v8.064l-3.323-.026ZM83.537 18.864c0 2.741-2.212 4.97-4.932 4.97-2.719 0-4.93-2.229-4.93-4.97 0-2.74 2.212-4.97 4.93-4.97 2.72 0 4.931 2.23 4.931 4.97Zm.853-5.699a8.16 8.16 0 0 0-5.784-2.386 8.158 8.158 0 0 0-5.784 2.386 8 8 0 0 0-2.37 5.699 8 8 0 0 0 2.37 5.698 8.17 8.17 0 0 0 5.784 2.386 8.171 8.171 0 0 0 5.784-2.386 7.996 7.996 0 0 0 2.37-5.698c0-2.15-.842-4.174-2.37-5.699ZM66.783 26.898H63.46V13.813h-2.414v-3.038h2.414V3.874h3.324v6.9h2.908v3.039h-2.908v13.085ZM46.4 10.775v7.555c0 4.389 1.947 5.557 4.414 5.557 2.752 0 4.647-1.766 4.647-5.66v-7.452h3.324v16.123h-3.038v-2.362h-.078c-.908 1.532-2.986 2.362-5.219 2.362-2.025 0-3.79-.648-5.062-1.765-1.48-1.273-2.337-3.271-2.337-6.829v-7.529H46.4ZM37.885 26.943H34.56V13.801h-2.414v-3.037h2.414v-6.89h3.324v6.89h2.907v3.037h-2.907v13.142Z" fill="#000"/><path fill-rule="evenodd" clip-rule="evenodd" d="M6.738 19.843a1.507 1.507 0 0 1-1.433-1.433v-3.264c0-.79.643-1.434 1.433-1.434s1.434.644 1.434 1.434v3.262c0 .79-.593 1.433-1.336 1.433-.047.002-.098.002-.098.002ZM17.018 19.843c-.79 0-1.433-.593-1.433-1.382v-3.315c0-.79.644-1.434 1.433-1.434.79 0 1.434.644 1.434 1.434v3.262a1.438 1.438 0 0 1-1.434 1.435Z" fill="#0049F8"/><path fill-rule="evenodd" clip-rule="evenodd" d="M3.394 12.694a3.904 3.904 0 0 1 3.413-2.242c2.132.056 3.84 1.866 3.79 4v7.206c.105.8.852 1.335 1.652 1.224.64-.105 1.174-.584 1.224-1.224v-7.2c-.055-2.132 1.653-3.946 3.79-4 1.441 0 2.72.8 3.305 2.08 2.4 4.748.534 10.508-4.213 12.911-4.747 2.403-10.507.53-12.905-4.218-1.335-2.667-1.385-5.867-.056-8.537ZM9.423 3.2h5.386v2.081a11.993 11.993 0 0 0-2.77-.321c-.908 0-1.814.105-2.667.266l.05-2.026ZM24.09 17.974c0-.321.055-.584.055-.962 0-4.374-2.347-8.375-6.187-10.508V3.2h2.348c.907 0 1.602-.695 1.602-1.602C21.91.69 21.214 0 20.307 0H3.929C3.02.055 2.33.745 2.33 1.653c0 .907.695 1.602 1.602 1.602h2.348V6.51C.462 9.706-1.725 17.016 1.475 22.83c.161.266.267.479.429.745C6.439 31.203 18.599 31.948 22.706 32c.374 0 .695-.16 1.014-.373.266-.267.373-.64.373-1.014-.002.005-.002-12.639-.002-12.639Z" fill="#0049F8"/></svg>
				</div>
				<div class="tutor-d-flex tutor-align-center tutor-gap-4 tutor-pr-40">
					<div class="tutor-fs-5 tutor-color-black">
						<?php
							$notice = __( 'Connect the license key to access the pro features!', 'tutor-pro' );
							echo esc_html( $notice );
						?>
					</div>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=tutor-pro-license' ) ); ?>" class="tutor-btn tutor-btn-primary">
						<?php esc_html_e( 'Connect Now', 'tutor-pro' ); ?>
					</a>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Remove tutor_pro_url if there is no license available
	 *
	 * @since 3.2.0
	 *
	 * @param array $localize_data Default localize data.
	 *
	 * @return array
	 */
	public function remove_pro_url( $localize_data ) {
		$localize_data['tutor_pro_url'] = null;
		return $localize_data;
	}
}
