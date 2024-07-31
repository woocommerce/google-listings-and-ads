<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Integration;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Conditional;
use Automattic\Jetpack\Connection\Tokens;
use Automattic\Jetpack\Connection\Manager as Connection_Manager;
use Automattic\Jetpack\Connection\Nonce_Handler;
use Jetpack_Signature;
use Jetpack_Options;

defined( 'ABSPATH' ) || exit;

/**
 * Class JetpackWPCOM
 *
 * Initializes the Jetpack function required to connect the WPCOM App.
 * This class can be deleted when the jetpack-connection package includes these functions.
 *
 * The majority of these class methods have been copied from the Jetpack class.
 *
 * @see https://github.com/Automattic/jetpack/blob/trunk/projects/plugins/jetpack/class.jetpack.php
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Integration
 */
class JetpackWPCOM implements Service, Registerable, Conditional {

	/**
	 * Verified data for JSON authorization request
	 *
	 * @var array
	 */
	public $json_api_authorization_request = [];

	/**
	 * Connection manager.
	 *
	 * @var Automattic\Jetpack\Connection\Manager
	 */
	protected $connection_manager;

	/**
	 * Initialize all active integrations.
	 */
	public function register(): void {
		add_action( 'login_form_jetpack_json_api_authorization', [ $this, 'login_form_json_api_authorization' ] );

		// This filter only simulates the Jetpack version for the test connection response, and it can be any value greater than 1.2.3.
		add_filter(
			'jetpack_xmlrpc_test_connection_response',
			function () {
				return '9.5';
			}
		);
	}

	/**
	 * Check if this class is required based on the presence of the Jetpack class.
	 *
	 * @return bool Whether the class is needed.
	 */
	public static function is_needed(): bool {
		return ! class_exists( 'Jetpack' );
	}

	/**
	 * Handles the login action for Authorizing the JSON API
	 *
	 * @see https://github.com/Automattic/jetpack/blob/6066d7181f78bdec7c355d8b2152733f4691e8a9/projects/plugins/jetpack/class.jetpack.php#L5301
	 */
	public function login_form_json_api_authorization() {
		$this->verify_json_api_authorization_request();
		add_action( 'wp_login', [ $this, 'store_json_api_authorization_token' ], 10, 2 );
		add_action( 'login_message', [ $this, 'login_message_json_api_authorization' ] );
		add_action( 'login_form', [ $this, 'preserve_action_in_login_form_for_json_api_authorization' ] );
		add_filter( 'site_url', [ $this, 'post_login_form_to_signed_url' ], 10, 3 );
	}

	/**
	 * If someone logs in to approve API access, store the Access Code in usermeta.
	 *
	 * @param string  $user_login Unused.
	 * @param WP_User $user User logged in.
	 *
	 * @see https://github.com/Automattic/jetpack/blob/6066d7181f78bdec7c355d8b2152733f4691e8a9/projects/plugins/jetpack/class.jetpack.php#L5349
	 */
	public function store_json_api_authorization_token( $user_login, $user ) {
		add_filter( 'login_redirect', [ $this, 'add_token_to_login_redirect_json_api_authorization' ], 10, 3 );
		add_filter( 'allowed_redirect_hosts', [ $this, 'allow_wpcom_public_api_domain' ] );
		$token = wp_generate_password( 32, false );
		update_user_meta( $user->ID, 'jetpack_json_api_' . $this->json_api_authorization_request['client_id'], $token );
	}

	/**
	 * Make sure the POSTed request is handled by the same action.
	 *
	 * @see https://github.com/Automattic/jetpack/blob/6066d7181f78bdec7c355d8b2152733f4691e8a9/projects/plugins/jetpack/class.jetpack.php#L5336
	 */
	public function preserve_action_in_login_form_for_json_api_authorization() {
		$http_host   = isset( $_SERVER['HTTP_HOST'] ) ? wp_unslash( $_SERVER['HTTP_HOST'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- escaped with esc_url below.
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- escaped with esc_url below.
		echo "<input type='hidden' name='action' value='jetpack_json_api_authorization' />\n";
		echo "<input type='hidden' name='jetpack_json_api_original_query' value='" . esc_url( set_url_scheme( $http_host . $request_uri ) ) . "' />\n";
	}

	/**
	 * Make sure the login form is POSTed to the signed URL so we can reverify the request.
	 *
	 * @param string $url Redirect URL.
	 * @param string $path Path.
	 * @param string $scheme URL Scheme.
	 *
	 * @see https://github.com/Automattic/jetpack/blob/trunk/projects/plugins/jetpack/class.jetpack.php#L5318
	 */
	public function post_login_form_to_signed_url( $url, $path, $scheme ) {
		if ( 'wp-login.php' !== $path || ( 'login_post' !== $scheme && 'login' !== $scheme ) ) {
			return $url;
		}
		$query_string = isset( $_SERVER['QUERY_STRING'] ) ? wp_unslash( $_SERVER['QUERY_STRING'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$parsed_url   = wp_parse_url( $url );
		$url          = strtok( $url, '?' );
		$url          = "$url?{$query_string}";
		if ( ! empty( $parsed_url['query'] ) ) {
			$url .= "&{$parsed_url['query']}";
		}

		return $url;
	}

	/**
	 * Add the Access Code details to the public-api.wordpress.com redirect.
	 *
	 * @param string  $redirect_to URL.
	 * @param string  $original_redirect_to URL.
	 * @param WP_User $user WP_User for the redirect.
	 *
	 * @return string
	 *
	 * @see https://github.com/Automattic/jetpack/blob/6066d7181f78bdec7c355d8b2152733f4691e8a9/projects/plugins/jetpack/class.jetpack.php#L5401
	 */
	public function add_token_to_login_redirect_json_api_authorization( $redirect_to, $original_redirect_to, $user ) {
		return add_query_arg(
			urlencode_deep(
				[
					'jetpack-code'    => get_user_meta( $user->ID, 'jetpack_json_api_' . $this->json_api_authorization_request['client_id'], true ),
					'jetpack-user-id' => (int) $user->ID,
					'jetpack-state'   => $this->json_api_authorization_request['state'],
				]
			),
			$redirect_to
		);
	}

	/**
	 * Add public-api.wordpress.com to the safe redirect allowed list - only added when someone allows API access.
	 * To be used with a filter of allowed domains for a redirect.
	 *
	 * @param array $domains Allowed WP.com Environments.
	 *
	 * @see https://github.com/Automattic/jetpack/blob/6066d7181f78bdec7c355d8b2152733f4691e8a9/projects/plugins/jetpack/class.jetpack.php#L5363
	 */
	public function allow_wpcom_public_api_domain( $domains ) {
		$domains[] = 'public-api.wordpress.com';
		return $domains;
	}

	/**
	 * Check if the redirect is encoded.
	 *
	 * @param string $redirect_url Redirect URL.
	 *
	 * @return bool If redirect has been encoded.
	 *
	 * @see https://github.com/Automattic/jetpack/blob/6066d7181f78bdec7c355d8b2152733f4691e8a9/projects/plugins/jetpack/class.jetpack.php#L5375
	 */
	public static function is_redirect_encoded( $redirect_url ) {
		return preg_match( '/https?%3A%2F%2F/i', $redirect_url ) > 0;
	}

	/**
	 * HTML for the JSON API authorization notice.
	 *
	 * @return string
	 *
	 * @see https://github.com/Automattic/jetpack/blob/6066d7181f78bdec7c355d8b2152733f4691e8a9/projects/plugins/jetpack/class.jetpack.php#L5603
	 */
	public function login_message_json_api_authorization() {
		return '<p class="message">' . sprintf(
			/* translators: Name/image of the client requesting authorization */
			esc_html__( '%s wants to access your site’s data. Log in to authorize that access.', 'google-listings-and-ads' ),
			'<strong>' . esc_html( $this->json_api_authorization_request['client_title'] ) . '</strong>'
		) . '<img src="' . esc_url( $this->json_api_authorization_request['client_image'] ) . '" /></p>';
	}

	/**
	 * Verifies the request by checking the signature
	 *
	 * @param null|array $environment Value to override $_REQUEST.
	 *
	 * @see https://github.com/Automattic/jetpack/blob/trunk/projects/plugins/jetpack/class.jetpack.php#L5422
	 */
	public function verify_json_api_authorization_request( $environment = null ) {
		$environment = $environment === null
			? $_REQUEST // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- nonce verification handled later in function.
			: $environment;

		list( $env_token,, $env_user_id ) = explode( ':', $environment['token'] );
		$token                            = ( new Tokens() )->get_access_token( $env_user_id, $env_token );
		if ( ! $token || empty( $token->secret ) ) {
			wp_die( esc_html__( 'You must connect your Jetpack plugin to WordPress.com to use this feature.', 'google-listings-and-ads' ) );
		}

		$die_error = __( 'Someone may be trying to trick you into giving them access to your site. Or it could be you just encountered a bug :).  Either way, please close this window.', 'google-listings-and-ads' );

		// Host has encoded the request URL, probably as a result of a bad http => https redirect.
		if ( self::is_redirect_encoded( esc_url_raw( wp_unslash( $_GET['redirect_to'] ) ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotValidated -- no site changes, we're erroring out.
			/**
			 * Jetpack authorisation request Error.
			 */
			do_action( 'jetpack_verify_api_authorization_request_error_double_encode' );
			$die_error = sprintf(
				/* translators: %s is a URL */
				__( 'Your site is incorrectly double-encoding redirects from http to https. This is preventing Jetpack from authenticating your connection. Please visit our <a href="%s">support page</a> for details about how to resolve this.', 'google-listings-and-ads' ),
				esc_url( 'https://jetpack.com/support/double-encoding/' )
			);
		}

		$jetpack_signature = new Jetpack_Signature( $token->secret, (int) Jetpack_Options::get_option( 'time_diff' ) );

		if ( isset( $environment['jetpack_json_api_original_query'] ) ) {
			$signature = $jetpack_signature->sign_request(
				$environment['token'],
				$environment['timestamp'],
				$environment['nonce'],
				'',
				'GET',
				$environment['jetpack_json_api_original_query'],
				null,
				true
			);
		} else {
			$signature = $jetpack_signature->sign_current_request(
				[
					'body'   => null,
					'method' => 'GET',
				]
			);
		}

		if ( ! $signature ) {
			wp_die(
				wp_kses(
					$die_error,
					[
						'a' => [
							'href' => [],
						],
					]
				)
			);
		} elseif ( is_wp_error( $signature ) ) {
			wp_die(
				wp_kses(
					$die_error,
					[
						'a' => [
							'href' => [],
						],
					]
				)
			);
		} elseif ( ! hash_equals( $signature, $environment['signature'] ) ) {
			if ( is_ssl() ) {
				// If we signed an HTTP request on the Jetpack Servers, but got redirected to HTTPS by the local blog, check the HTTP signature as well.
				$signature = $jetpack_signature->sign_current_request(
					[
						'scheme' => 'http',
						'body'   => null,
						'method' => 'GET',
					]
				);
				if ( ! $signature || is_wp_error( $signature ) || ! hash_equals( $signature, $environment['signature'] ) ) {
					wp_die(
						wp_kses(
							$die_error,
							[
								'a' => [
									'href' => [],
								],
							]
						)
					);
				}
			} else {
				wp_die(
					wp_kses(
						$die_error,
						[
							'a' => [
								'href' => [],
							],
						]
					)
				);
			}
		}

		$timestamp = (int) $environment['timestamp'];
		$nonce     = stripslashes( (string) $environment['nonce'] );

		if ( ! $this->connection_manager ) {
			$this->connection_manager = new Connection_Manager();
		}

		if ( ! ( new Nonce_Handler() )->add( $timestamp, $nonce ) ) {
			// De-nonce the nonce, at least for 5 minutes.
			// We have to reuse this nonce at least once (used the first time when the initial request is made, used a second time when the login form is POSTed).
			$old_nonce_time = get_option( "jetpack_nonce_{$timestamp}_{$nonce}" );
			if ( $old_nonce_time < time() - 300 ) {
				wp_die( esc_html__( 'The authorization process expired. Please go back and try again.', 'google-listings-and-ads' ) );
			}
		}

		$data         = json_decode( base64_decode( stripslashes( $environment['data'] ) ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		$data_filters = [
			'state'        => 'opaque',
			'client_id'    => 'int',
			'client_title' => 'string',
			'client_image' => 'url',
		];

		foreach ( $data_filters as $key => $sanitation ) {
			if ( ! isset( $data->$key ) ) {
				wp_die(
					wp_kses(
						$die_error,
						[
							'a' => [
								'href' => [],
							],
						]
					)
				);
			}

			switch ( $sanitation ) {
				case 'int':
					$this->json_api_authorization_request[ $key ] = (int) $data->$key;
					break;
				case 'opaque':
					$this->json_api_authorization_request[ $key ] = (string) $data->$key;
					break;
				case 'string':
					$this->json_api_authorization_request[ $key ] = wp_kses( (string) $data->$key, [] );
					break;
				case 'url':
					$this->json_api_authorization_request[ $key ] = esc_url_raw( (string) $data->$key );
					break;
			}
		}

		if ( empty( $this->json_api_authorization_request['client_id'] ) ) {
			wp_die(
				wp_kses(
					$die_error,
					[
						'a' => [
							'href' => [],
						],
					]
				)
			);
		}
	}
}
