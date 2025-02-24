<?php
/**
 * Class BB_SSO_Auth
 *
 * Abstract class that provides the basic structure for handling authentication via Single Sign-On (SSO) providers.
 * It manages the provider ID, access token data, and defines methods that must be implemented by concrete SSO provider
 * classes.
 *
 * @since   2.6.30
 * @package BuddyBossPro/SSO
 */

abstract class BB_SSO_Auth {

	/**
	 * Provider ID.
	 *
	 * @since 2.6.30
	 *
	 * @var string The unique identifier for the SSO provider.
	 */
	protected $provider_id;

	/**
	 * Access token data.
	 *
	 * @since 2.6.30
	 *
	 * @var array The access token data for the SSO provider.
	 */
	protected $access_token_data;

	/**
	 * BB_SSO_Auth constructor.
	 *
	 * Initializes the SSO auth class with the given provider ID.
	 *
	 * @since 2.6.30
	 *
	 * @param string $provider_id The ID of the SSO provider.
	 */
	public function __construct( $provider_id ) {
		$this->provider_id = $provider_id;
	}

	/**
	 * Check for errors during the authentication process.
	 *
	 * @since 2.6.30
	 *
	 * @return void
	 */
	public function check_error() {
		// Check for errors.
	}

	/**
	 * Set the access token data for the SSO provider.
	 *
	 * @since 2.6.30
	 *
	 * @param string $access_token_data JSON-encoded access token data.
	 *
	 * @return void
	 */
	public function set_access_token_data( $access_token_data ) {
		$this->access_token_data = json_decode( $access_token_data, true );
	}

	/**
	 * Generate the URL required to initiate the authentication process.
	 *
	 * @since 2.6.30
	 *
	 * @return string The authentication URL.
	 */
	abstract public function create_auth_url();

	/**
	 * Perform the authentication process for the SSO provider.
	 *
	 * @since 2.6.30
	 *
	 * @return mixed The result of the authentication process.
	 */
	abstract public function authenticate();

	/**
	 * Retrieve data from the SSO provider's API.
	 *
	 * @since 2.6.30
	 *
	 * @param string $path     The API path to request data from.
	 * @param array  $data     Optional. Additional data to send with the request.
	 * @param bool   $endpoint Optional. Whether to use a custom endpoint.
	 *
	 * @return mixed The response from the API.
	 */
	abstract public function get( $path, $data = array(), $endpoint = false );

	/**
	 * Check if there is authentication data available.
	 *
	 * @since 2.6.30
	 *
	 * @return bool True if authentication data is available, false otherwise.
	 */
	abstract public function has_authenticate_data();

	/**
	 * Get the test URL for verifying the authentication process.
	 *
	 * @since 2.6.30
	 *
	 * @return string The test URL for authentication.
	 */
	abstract public function get_test_url();
}
