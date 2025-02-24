<?php //phpcs:disable
/**
 * Manage Update
 *
 * @package TutorPro\Updater
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 1.0.0
 */

namespace TutorPRO\ThemeumUpdater;

use TUTOR\Input;

if ( ! class_exists('TutorPRO\ThemeumUpdater\Update') ) {
	/**
	 * Class Update
	 */
	class Update
	{
		private $meta;
		private $product_slug;
		private $url_slug;
		private $license_field_name;
		private $nonce_field_name;
		private $api_end_point = 'https://tutorlms.com/wp-json/themeum-products/v1/';
		private $error_message_key;
		private $themeum_response_data;
		public $is_valid;
		const LICENSE_TRANSIENT_KEY = 'tutor_license_transient_key';

		/**
		 * Constructor
		 *
		 * @param array $meta meta.
		 */
		public function __construct( $meta ) {
			$this->meta               = $meta;
			$this->product_slug       = strtolower( $this->meta['product_slug'] );
			$this->url_slug           = $this->product_slug . '-license';
			$this->license_field_name = $this->url_slug . '-key';
			$this->nonce_field_name   = $this->url_slug . '-nonce';
			$this->error_message_key  = 'themeum_update_error_' . $this->meta['product_basename'];

			$license        = $this->get_license();
			$this->is_valid = $license && $license['activated'];

			if ( ! isset( $this->meta['is_product_free'] ) || true !== $this->meta['is_product_free'] ) {
				add_action( 'tutor_after_settings_menu', array( $this, 'add_license_page' ) );
				add_action( 'admin_init', array( $this, 'check_license_key' ) );
			}

			$force_check        = isset( $this->meta['force_update_check'] ) && true === $this->meta['force_update_check'];
			$update_hook_prefix = $force_check ? '' : 'pre_set_';

			if ( 'plugin' === $this->meta['product_type'] ) {
				add_filter( 'plugins_api', array( $this, 'plugin_info' ), 20, 3 );
				add_filter( $update_hook_prefix . 'site_transient_update_plugins', array( $this, 'check_for_update' ) );
				add_action( 'in_plugin_update_message-' . $this->meta['product_basename'], array( $this, 'custom_update_message' ), 10, 2 );
				add_action( 'wp_ajax_delete_tutor_license', array( $this, 'delete_tutor_license' ) );
				add_action( 'wp_ajax_update_tutor_license', array( $this, 'update_tutor_license' ) );
				add_action( 'wp_ajax_tutor_oauth_check', array( $this, 'tutor_oauth_check' ) );
				add_filter( 'upgrader_pre_download', array( $this, 'check_plugin_license_before_update' ), 10, 3 );
			} elseif ( 'theme' === $this->meta['product_type'] ) {
				add_filter( $update_hook_prefix . 'site_transient_update_themes', array( $this, 'check_for_update' ) );
			}
		}

		/**
		 * tutor oauth check
		 *
		 * @return array
		 */
		public function tutor_oauth_check() {
			tutor_utils()->check_nonce();
			tutor_utils()->check_current_user_capability();
			$license_key = Input::post( 'license_key' );
			$site_url    = get_site_url();
			try {
				$params = array(
					'body'    => array(
						'license_key'  => $license_key,
						'website_url' => $site_url,
					),
					'headers' => array(
						'Secret-Key' => 't344d5d71sae7dcb546b8cf55e594808',
					),
				);

				$is_authorize_response = wp_remote_post(
					$this->api_end_point . 'oauth/authorize',
					$params
				);
				
				if ( ! is_wp_error( $is_authorize_response ) ) {
					$is_authorize_response_body = $is_authorize_response['body'];
					$is_authorize = json_decode( $is_authorize_response_body );
					return wp_send_json( $is_authorize );
				}
			} catch ( \Throwable $th ) {
				return wp_send_json_error( __('Something went wrong!', 'tutor-pro'), 400 );
			}
		}

		/**
		 * Delete tutor license
		 *
		 * @return array
		 */
		public function delete_tutor_license() {
			tutor_utils()->check_nonce();
			try {
				$license_deleted = delete_option( $this->meta['license_option_key'] );
				if ( $license_deleted ) {
					delete_transient( self::LICENSE_TRANSIENT_KEY );
					wp_send_json(
						array(
							'status_code' => 200,
							'response'     => __( 'License removed.', 'tutor-pro' ),
							'url' 		  => admin_url('admin.php?page=tutor-pro-license'),
						),
						200
					);
				} else {
					wp_send_json_error( false, 400 );
				}
			} catch ( \Throwable $th ) {
				wp_send_json_error( false, 400 );
			}
		}


		/**
		 * Update tutor license
		 *
		 * @return  array | mixed
		 */
		public function update_tutor_license() {
			tutor_utils()->check_nonce();
			tutor_utils()->check_current_user_capability();
			$updated_license_key = Input::post( 'updated_license_key' );
			$site_url = get_site_url();
			try {
				$license_updated = $this->update_license( $updated_license_key );
				return wp_send_json( $license_updated );
			} catch ( \Throwable $th ) {
				wp_send_json_error( false, 400 );
			}
		}


		/**
		 * Update license key
		 *
		 * $key string license key
		 * 
		 * @return  mixed
		 */
		public function update_license( $key ) {
			$license_option = get_option( $this->meta['license_option_key'], null );
			$license = maybe_unserialize( $license_option );
			$license = is_array( $license ) ? $license : array();

			$site_url = get_site_url();
			if ( ! empty( $license ) ) {
				$license['license_key'] = $key;
				$verify = $this->verify_license( $license );
				return $verify;
			} else {
				return false;
			}
		}


		/**
		 * Custom update message.
		 *
		 * @param mixed $plugin_data plugin data.
		 * @param mixed $response response.
		 *
		 * @return void
		 */
		public function custom_update_message( $plugin_data, $response ) {
			if ( ! $response->package ) {
				$error_message = get_option( $this->error_message_key );
				echo $error_message ? ' ' . wp_kses_post( $error_message ) . '' : '';
			}
		}

		/**
		 * Add page.
		 *
		 * @return void
		 */
		public function add_license_page() {
			add_submenu_page( $this->meta['parent_menu'], $this->meta['menu_title'], $this->meta['menu_title'], $this->meta['menu_capability'], $this->url_slug, array($this, 'license_form') );
		}

		/**
		 * License Form
		 *
		 * @return void
		 */
		public function license_form() {
			$license          = $this->get_license();
			$field_name       = $this->license_field_name;
			$nonce_field_name = $this->nonce_field_name;
			$product_title    = $this->meta['product_title'];
			$header_content   = $this->meta['header_content'];

			include __DIR__ . '/license-form.php';
		}

		/**
		 * Get update information
		 *
		 * @return array|bool|mixed|object
		 */
		public function check_for_update_api() {
			if ( $this->themeum_response_data ) {
				// Use runtime cache.
				return $this->themeum_response_data;
			}

			$license_info  = get_option( $this->meta['license_option_key'], null );
			$license_key   = $license_info ? $license_info['license_key'] ?? '' : '';
			$access_token  = $license_info ? $license_info['access_token'] ?? '' : '';
			$site_url = get_site_url();

			if ( !empty( $access_token ) ) {
				$end_point = 'plugin-update-status';
			} else {
				$end_point = 'check-update';
			}

			$params = array(
				'body'    => array(
					'license_key'  => $license_key,
					'product_slug' => $this->product_slug,
					'website_url'  => $site_url
				),
				'headers' => $this->get_api_header( $access_token )
			);

			// Make the POST request.
			$is_free     = isset( $this->meta['is_product_free'] ) && true === $this->meta['is_product_free'];
			$request     = wp_remote_post( $this->api_end_point . '' . $end_point, $params );

			$response_data = array();
			
			// Check if response is valid.
			if ( ! is_wp_error( $request ) || wp_remote_retrieve_response_code( $request ) === 200 ) {
				$response_data = json_decode( $request['body'] );
			}

			$this->themeum_response_data = $response_data;
			return $this->themeum_response_data;
		}

		/**
		 * Check license key.
		 *
		 * @return void
		 */
		public function check_license_key() {
			if ( isset( $_GET['authorize_token'] ) ) {
				$authorize_token = $_GET['authorize_token'];
				$redirect_url = home_url() . '/wp-admin/admin.php?page=tutor-pro-license';
				try {
					$api_call = wp_remote_post(
						$this->api_end_point . 'oauth/tokens',
						array(
							'body'    => array(
								"grant_type" => "authorize",
								"token" => $authorize_token
							),
							'headers' => array(
								'Secret-Key' => 't344d5d71sae7dcb546b8cf55e594808',
							),
						)
					);
					$license_info = array(
						'access_token' 		=> '',
						'refresh_token' 	=> '',
						'tokens_expires_at' => '',
						'activated'         => false,
						'license_key'       => '',
						'customer_name'     => '',
						'expires_at'        => '',
						'activated_at'      => '',
						'license_type'      => '',
					);
					if ( ! is_wp_error( $api_call ) ) {
						$response_body = $api_call['body'];
						$response      = json_decode( $response_body );
						if ( 200 === $response->status ) {
							$license_info = array_combine( array_keys( $license_info ), array_values( (array) $response->body_response ) );
							update_option( $this->meta['license_option_key'], $license_info, false );
							set_transient( self::LICENSE_TRANSIENT_KEY, $license_info, DAY_IN_SECONDS );
							$this->is_valid = true;
							tutor_utils()->redirect_to( $redirect_url );
						}
						if ( 400 === $response->status ) {
							tutor_utils()->redirect_to( $redirect_url, __('', 'tutor-pro'), 'error');
						}
					} else {
						tutor_utils()->redirect_to( $redirect_url, __('Something went wrong!!', 'tutor-pro'), 'error');
					}
				} catch (\Throwable $th) {
					tutor_utils()->redirect_to( $redirect_url, __('Something went wrong!!', 'tutor-pro'), 'error');
				}
			}
		}

		/**
		 * Get plugin info from server.
		 *
		 * @param mixed  $res response.
		 * @param string $action action name.
		 * @param mixed  $args args.
		 *
		 * @return bool|\stdClass
		 */
		public function plugin_info( $res, $action, $args ) {

			// do nothing if this is not about getting plugin information.
			if ( 'plugin_information' !== $action ) {
				return false;
			}

			// do nothing if it is not our plugin.
			if ( $this->product_slug !== $args->slug && $this->meta['product_basename'] !== $args->slug ) {
				return $res;
			}

			$remote = $this->check_for_update_api();

			if ( ! is_wp_error( $remote ) ) {
				$res               = new \stdClass();
				$res->name         = $remote->body_response->plugin_name;
				$res->slug         = $this->product_slug;
				$res->version      = $remote->body_response->version;
				$res->last_updated = $remote->body_response->updated_at;
				$res->sections     = array(
					'changelog' => $remote->body_response->change_log,
				);
				return $res;
			}
			return false;
		}

		/**
		 * Check for update.
		 *
		 * @param mixed $transient transient.
		 *
		 * @return mixed
		 */
		public function check_for_update( $transient ) {
			$base_name = $this->meta['product_basename'];
			$request_body = $this->check_for_update_api();
			if ( 200 === $request_body->status ) {
				if ( version_compare($this->meta['current_version'], $request_body->body_response->version, '<') ) {
					$update_info = array(
						'new_version' => $request_body->body_response->version,
						'package'     => $request_body->body_response->download_url,
						'tested'      => $request_body->body_response->tested_wp_version,
						'slug'        => $base_name,
						'url'         => $request_body->body_response->download_url,
					);
					$transient->response[$base_name] = 'plugin' === $this->meta['product_type'] ? (object) $update_info : $update_info;
				}
			}
			return $transient;
		}

		/**
		 * Get license.
		 *
		 * @param string|null $option_key option key.
		 *
		 * @return mixed
		 */
		private function get_license( $option_key = null ) {
			! $option_key ? $option_key = $this->meta['license_option_key'] : 0;
			$license_option             = get_option( $option_key, null );
			
			$license = maybe_unserialize( $license_option );
			$license = is_array( $license ) ? $license : array();

			$site_url = get_site_url();
			$license_info = array();

			$is_license_page = Input::get('page') === 'tutor-pro-license' ? true : false;

			if ( $is_license_page ) {
				if(! empty( $license['access_token'] ) ) {
					$end_point = 'verify-license';
				} else {
					$end_point = 'check-license';
				}
				$license_info = get_transient( self::LICENSE_TRANSIENT_KEY );
				if ( false === $license_info ) {
					$api_call = wp_remote_post(
						$this->api_end_point . $end_point,
						array(
							'body'    => array(
								'license_key' => $license['license_key'] ?? '',
								'website_url' => $site_url,
							),
							'headers' => $this->get_api_header( $license['access_token'] ?? '' )
						)
					);
					$status_code = wp_remote_retrieve_response_code( $api_call );
					if ( ! is_wp_error( $api_call ) ) {
						$response_body = $api_call['body'];
						$response      = json_decode( $response_body );
						$response_msg = '';
						$license_options_data = get_option( $this->meta['license_option_key'] );
						if ( 200 === $status_code ) {
							if( $license_options_data ) {
								$license_options_data['activated'] = true;
								$license_options_data['customer_name'] = $response->body_response->customer_name;
								$license_options_data['expires_at'] = $response->body_response->expires_at;
								$license_options_data['activated_at'] = $response->body_response->activated_at;
								$license_options_data['license_type'] = $response->body_response->license_type;
							}
							update_option( $this->meta['license_option_key'], $license_options_data, false );
							set_transient( self::LICENSE_TRANSIENT_KEY, $license_options_data, DAY_IN_SECONDS );
						} elseif ( 401 === $status_code ) {
							if ( property_exists( $response, 'code' ) && $response->code == 'expired_token' ) {
								$oauth_api_call = wp_remote_post(
									$this->api_end_point . 'oauth/tokens',
									array(
										'body'    => array(
											"grant_type" => "refresh",
											"token" => $license['refresh_token'] ?? ''
										),
										'headers' => array(
											'Secret-Key' => 't344d5d71sae7dcb546b8cf55e594808',
										)
									)
								);
								$response_body = $oauth_api_call['body'];
								$response      = json_decode( $response_body );
								if ( 200 === $response->status ) {
									if( $license_options_data ) {
										$license_options_data['access_token'] = $response->body_response->access_token;
										$license_options_data['refresh_token'] = $response->body_response->refresh_token;
										$license_options_data['tokens_expires_at'] = $response->body_response->tokens_expires_at;
									}
									update_option( $this->meta['license_option_key'], $license_options_data, false );
									delete_transient( self::LICENSE_TRANSIENT_KEY );
								}
							} else {
								delete_option( $this->meta['license_option_key'] );
								delete_transient( self::LICENSE_TRANSIENT_KEY );
							}
						} else {
							if ( ! empty( $license_options_data['activated'] ) ) {
								$license_options_data['activated'] = false;
							}
							update_option( $this->meta['license_option_key'], $license_options_data, false );
							set_transient( self::LICENSE_TRANSIENT_KEY, $license_options_data, DAY_IN_SECONDS );
						}
					}
				}
				$data = get_option( $this->meta['license_option_key'] );
				if( $data && !empty( $license_info['activated'] ) ) {
					$data['activated'] = $license_info['activated'];
				}
				update_option( $this->meta['license_option_key'], $data, false );
			}

			$license_option_key = get_option( $this->meta['license_option_key'] );
			if( $license_option_key ) {
				return get_option( $this->meta['license_option_key'] );
			} else {
				return null;
			}
			return get_option( $this->meta['license_option_key'] );
		}

		/**
		 * Api header description
		 *
		 * @param   string  $token  $token description
		 */
		public function get_api_header( $token = '' ) {
			$headers = array();
			if( ! empty( $token ) ) {
				$headers =  array(
					'Authorization' => 'Bearer ' . $token,
				);
			} else {
				$headers =  array(
					'Secret-Key' => 't344d5d71sae7dcb546b8cf55e594808',
				);
			}
			return $headers;
		}

		/**
		 * Verify license description
		 *
		 * @param   string  $license  license description
		 *
		 * @return  mixed
		 */
		public function verify_license( $license ) {
			$site_url = get_site_url();
			if ( ! empty( $license['access_token'] ) ) {
				$end_point = 'verify-license';
			} else {
				$end_point = 'check-license';
			}
			$api_call = wp_remote_post(
				$this->api_end_point . $end_point,
				array(
					'body'    => array(
						'license_key' => $license['license_key'] ?? '',
						'website_url' => $site_url,
					),
					'headers' => $this->get_api_header( $license['access_token'] ?? '' )
				)
			);

			$status_code = wp_remote_retrieve_response_code( $api_call );

			if ( ! is_wp_error( $api_call ) ) {
				$response_body = $api_call['body'];
				$response      = json_decode( $response_body );
				$response_msg = '';

				if ( 200 === $status_code ) {
					$license_options_data = get_option( $this->meta['license_option_key'] );
					if( $license_options_data ) {
						$license_options_data['activated'] = true;
						$license_options_data['license_key'] = $license['license_key'] ?? '';
						$license_options_data['customer_name'] = $response->body_response->customer_name;
						$license_options_data['expires_at'] = $response->body_response->expires_at;
						$license_options_data['activated_at'] = $response->body_response->activated_at;
						$license_options_data['license_type'] = $response->body_response->license_type;
					}
					update_option( $this->meta['license_option_key'], $license_options_data, false );
					set_transient( self::LICENSE_TRANSIENT_KEY, $license_options_data, DAY_IN_SECONDS );
				} elseif ( 401 === $status_code ) {
					if( property_exists( $response, 'code' ) && $response->code == 'expired_token' ) {
						$oauth_api_call = wp_remote_post(
							$this->api_end_point . 'oauth/tokens',
							array(
								'body'    => array(
									"grant_type" => "refresh",
									"token" => $license['refresh_token']
								),
								'headers' => array(
									'Secret-Key' => 't344d5d71sae7dcb546b8cf55e594808',
								)
							)
						);
						$response_body = $oauth_api_call['body'];
						$response      = json_decode( $response_body );
						if ( 200 === $response->status ) {
							$license_options_data = get_option( $this->meta['license_option_key'] );
							if($license_options_data) {
								$license_options_data['license_key'] = $license['license_key'];
								$license_options_data['access_token'] = $response->body_response->access_token;
								$license_options_data['refresh_token'] = $response->body_response->refresh_token;
								$license_options_data['tokens_expires_at'] = $response->body_response->tokens_expires_at;
							}
							update_option( $this->meta['license_option_key'], $license_options_data, false );
							delete_transient( self::LICENSE_TRANSIENT_KEY );
						}
					}
					if ( property_exists( $response, 'code' ) && $response->code == 'invalid_token' ) { 
						delete_option( $this->meta['license_option_key'] );
						delete_transient( self::LICENSE_TRANSIENT_KEY );
					}
				}
				return $response;
			}
		}

		/**
		 * Check plugin license before update description
		 *
		 * @param   boolean      $reply      $reply description
		 * @param   string       $package    Download uri
		 * @param   WP_Upgrader  $upgrader   The WP_Upgrader instance.
		 *
		 * @return  mixed        
		 */
		public function check_plugin_license_before_update( $reply, $package, $upgrader ) {
			if ( is_object( $upgrader ) && property_exists( $upgrader->skin, 'plugin_info' ) ) {
				if ( false === strpos( $upgrader->skin->plugin_info['TextDomain'] ?? '', $this->product_slug ) ) {
					return $reply;
				}
				$response = $this->check_for_update_api();
				if ( 200 !== $response->status ) {
					return new \WP_Error(
						'license_required',
						__('A valid license key is required to update Tutor LMS Pro. Please enter your license key in the plugin settings.', 'tutor-pro')
					);
				}
			}
			return $reply;
		}
	}
}