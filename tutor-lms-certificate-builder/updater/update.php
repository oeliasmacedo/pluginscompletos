<?php
    namespace TutorCB\ThemeumUpdater;

    if( !class_exists('TutorCB\ThemeumUpdater\Update') ) {

        class Update {

            private $meta;
            private $product_slug;
            private $url_slug;
            private $license_field_name;
            private $nonce_field_name;
            private $api_end_point = 'https://tutorlms.com/wp-json/themeum-products/v1/';
            private $error_message_key;
            private $themeum_response_data;
            public $is_valid;

            function __construct( $meta ) {

                $this->meta               = $meta;
                $this->product_slug       = strtolower( $this->meta['product_slug'] );
                $this->url_slug           = $this->product_slug . '-license';
                $this->license_field_name = $this->url_slug . '-key';
                $this->nonce_field_name   = $this->url_slug . '-nonce';
                $this->error_message_key  = 'themeum_update_error_' . $this->meta['product_basename'];

                $license = $this->get_license();
                $this->is_valid = $license && $license['activated'];

                if(!isset($this->meta['is_product_free']) || $this->meta['is_product_free']!==true) {
                    add_action( 'admin_enqueue_scripts', array( $this, 'license_page_asset_enqueue' ) );
                    add_action( 'admin_menu', array( $this, 'add_license_page' ), 20 );
                    add_action( 'admin_init', array( $this, 'check_license_key' ) );
                    add_action( 'admin_notices', array( $this, 'show_invalid_license_notice' ) );
                }

                $force_check = isset( $this->meta['force_update_check'] ) && $this->meta['force_update_check']===true;
                $update_hook_prefix = $force_check ? '' : 'pre_set_';

                if($this->meta['product_type'] == 'plugin') {
                    add_filter( 'plugins_api', array( $this, 'plugin_info' ), 20, 3 );
                    add_filter( $update_hook_prefix . 'site_transient_update_plugins', array( $this, 'check_for_update' ) );
                    add_action( "in_plugin_update_message-".$this->meta['product_basename'], array( $this, 'custom_update_message' ), 10, 2 );
                }
                else if($this->meta['product_type']=='theme') {
		            add_filter( $update_hook_prefix . 'site_transient_update_themes', array( $this, 'check_for_update' ) );
                }
            }

            public function custom_update_message($plugin_data, $response) {

                if(!$response->package) {
                    $error_message = get_option( $this->error_message_key );
                    echo $error_message ? ' ' . $error_message . '' : '';
                }
            }

            public function license_page_asset_enqueue() {

                $css_url = $this->meta['updater_url'] . 'license-form.css';

                if( isset( $_GET['page'] ) && $_GET['page'] == $this->url_slug){
                    wp_enqueue_style( $this->url_slug . '-css', $css_url );
                }
            }

            public function add_license_page() {
                add_submenu_page($this->meta['parent_menu'], $this->meta['menu_title'], $this->meta['menu_title'], $this->meta['menu_capability'], $this->url_slug, array($this, 'license_form'));
            }

            public function license_form() {

                $license           = $this->get_license();
                $field_name        = $this->license_field_name;
                $nonce_field_name  = $this->nonce_field_name;
                $product_title     = $this->meta['product_title'];
                $header_content    = $this->meta['header_content'];

                include __DIR__ . '/license-form.php';
            }

            /**
             * @return array|bool|mixed|object
             *
             * Get update information
             */
            public function check_for_update_api() {
                if($this->themeum_response_data) {
                    // Use runtime cache
                    return $this->themeum_response_data;
                }

                $license_info = $this->get_license();
                $license_key  = $license_info ? $license_info['license_key'] : '';

                $params = array(
                    'body' => array(
                        'license_key'  => $license_key,
                        'product_slug' => $this->product_slug,
                    ),
                    'headers' => array(
                        'Secret-Key' => 't344d5d71sae7dcb546b8cf55e594808'
                    )
                );

                // Make the POST request
                $access_slug = 'check-update';
                return wp_remote_post( $this->api_end_point . $access_slug, $params );
            }

            public function check_license_key() {

                if ( isset($_GET['page']) && $_GET['page']==$this->url_slug && !empty($_POST[$this->license_field_name])) {
                    if (!check_admin_referer($this->nonce_field_name)) {
                        return;
                    }

                    $key  = sanitize_text_field($_POST[$this->license_field_name]);
                    $unique_ip = $_SERVER['REMOTE_ADDR'];
                    $blog = get_home_url();

                    $api_call = wp_remote_post(
                        $this->api_end_point . 'validator',
                        array(
                            'body'          => array(
                                'blog_url'      => $blog,
                                'license_key'   => $key,
                                'action'        => 'check_license_key_api',
                                'blog_ip'       => $unique_ip,
                                'product_info'  => array('unique_id' => $this->product_slug),
                            )
                        )
                    );

                    if (!is_wp_error($api_call)) {
                        $response_body = $api_call['body'];
                        $response = json_decode($response_body);

                        $response_msg = '';
                        if (!empty($response->data->msg)) {
                            $response_msg = $response->data->msg;
                        }

                        if ($response->success) {
                            $license_info = array(
                                'activated'     => true,
                                'license_key'   => $key,
                                'license_to'    => $response->data->license_info->customer_name,
                                'expires_at'    => $response->data->license_info->expires_at,
                                'activated_at'  => $response->data->license_info->activated_at,
                                'license_type'  => $response->data->license_info->license_type,
                                'msg'  => $response_msg,
                            );
                        } else {
                            //License is invalid
                            $license_info = array(
                                'activated'     => false,
                                'license_key'   => $key,
                                'license_to'    => '',
                                'expires_at'    => '',
                                'license_type'  => '',
                                'msg'  => $response_msg,
                            );
                        }

                        update_option($this->meta['license_option_key'], $license_info);
                    } else {
                        $error_string = $api_call->get_error_message();
                        echo '<div id="message" class="error"><p>' . $error_string . '</p></div>';
                    }
                }
            }

            /**
             * @param $res
             * @param $action
             * @param $args
             *
             * @return bool|\stdClass
             *
             * Get the plugin info from server
             */

            function plugin_info($res, $action, $args) {

                // do nothing if this is not about getting plugin information
                if ($action !== 'plugin_information'){
                    return false;
                }

                // do nothing if it is not our plugin
                if ($this->product_slug !== $args->slug && $this->meta['product_basename']!==$args->slug){
                    return $res;
                }

                $response = $this->check_for_update_api();

                if (!is_wp_error($response)) {
                    $response    = json_decode( wp_remote_retrieve_body( $response ) );
                    $status_code = $response->status;
                    if (200 === $status_code) {
                        $body = $response->body_response;

                        $res = new \stdClass();
                        $res->name = $body->plugin_name;
                        $res->slug = $this->product_slug;
                        $res->version = $body->version;
                        $res->last_updated = $body->updated_at;
                        $res->sections = array(
                            'changelog' => $body->change_log,
                        );
                        return $res;
                    } else {
                        return false;
                    }
                }
                return false;
            }

            /**
             * @param $transient
             *
             * @return mixed
             */
            public function check_for_update($transient) {

                $base_name = $this->meta['product_basename'];

                $response = $this->check_for_update_api();

                if ( is_wp_error( $response ) ) {
                    return $transient;
                } else {
                    $response    = json_decode( wp_remote_retrieve_body( $response ) );
                    $status_code = $response->status;

                    if ( 200 === $status_code ) {
                        $body = $response->body_response;

                        $update_info = array(
                            'new_version'   => $body->version,
                            'package'       => $body->download_url,
                            'tested'        => $body->tested_wp_version,
                            'slug'          => $base_name,
                            'url'           => $body->url,
                        );

                        // Compare version.
                        $version = $update_info['new_version'];
                        if ( version_compare( $version, TUTOR_CB_VERSION, '>' ) ) {
                            $transient->response[$base_name] = $this->meta['product_type']=='plugin' ? (object)$update_info : $update_info;
                        }
                    }
                }
                return $transient;
            }

            public function show_invalid_license_notice() {
                if (!$this->is_valid) {
                    $class = 'notice notice-error';
                    $message = sprintf(__('There is an error with your %s License. Automatic update has been turned off, %s Please check license %s', $this->url_slug),
                                        $this->meta['product_title'], " <a href='" . admin_url( 'admin.php?page=' . $this->url_slug ) . "'>", '</a>');

                    printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), $message);
                }
            }

            private function get_license() {
                $license_option = get_option('tutor_license_info', null);

                if(!$license_option) {
                    // Not submitted yet
                    return null;
                }

                $license = maybe_unserialize($license_option);
                $license = is_array($license) ? $license : array();

                $keys = array( 'activated', 'license_key', 'license_to', 'expires_at', 'license_type', 'msg' );
                foreach($keys as $key) {
                    $license[$key] = !empty( $license[$key] ) ? $license[$key] : null;
                }

                return $license;
            }
        }
    }
?>