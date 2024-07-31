<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\WP;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Middleware;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\AccountService;
use Automattic\WooCommerce\GoogleListingsAndAds\HelperTraits\Utilities as UtilitiesTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\ContainerAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces\ContainerAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Deactivateable;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\Jetpack;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Psr\Container\ContainerExceptionInterface;
use Jetpack_Options;
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Class OAuthService
 * This class implements a service to handle WordPress.com OAuth.
 *
 * @since 2.8.0
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\WP
 */
class OAuthService implements Service, OptionsAwareInterface, Deactivateable, ContainerAwareInterface {

	use OptionsAwareTrait;
	use UtilitiesTrait;
	use ContainerAwareTrait;

	public const WPCOM_API_URL = 'https://public-api.wordpress.com';
	public const AUTH_URL      = '/oauth2/authorize';
	public const RESPONSE_TYPE = 'code';
	public const SCOPE         = 'wc-partner-access';

	public const STATUS_APPROVED    = 'approved';
	public const STATUS_DISAPPROVED = 'disapproved';
	public const STATUS_ERROR       = 'error';

	public const ALLOWED_STATUSES = [
		self::STATUS_APPROVED,
		self::STATUS_DISAPPROVED,
		self::STATUS_ERROR,
	];

	/**
	 * Returns WordPress.com OAuth authorization URL.
	 * https://developer.wordpress.com/docs/oauth2/
	 *
	 * The full auth URL example:
	 *
	 * https://public-api.wordpress.com/oauth2/authorize?
	 * client_id=CLIENT_ID&
	 * redirect_uri=PARTNER_REDIRECT_URL&
	 * response_type=code&
	 * blog=BLOD_ID&
	 * scope=wc-partner-access&
	 * state=URL_SAFE_BASE64_ENCODED_STRING
	 *
	 * State is a URL safe base64 encoded string.
	 * E.g.
	 * state=bm9uY2UtMTIzJnJlZGlyZWN0X3VybD1odHRwcyUzQSUyRiUyRm1lcmNoYW50LXNpdGUuZXhhbXBsZS5jb20lMkZ3cC1hZG1pbiUyRmFkbWluLnBocCUzRnBhZ2UlM0R3Yy1hZG1pbiUyNnBhdGglM0QlMkZnb29nbGUlMkZzZXR1cC1tYw
	 *
	 * The decoded content of state is a URL query string where the value of its parameter "store_url" is being URL encoded.
	 * E.g.
	 * nonce=nonce-123&store_url=https%3A%2F%2Fmerchant-site.example.com%2Fwp-admin%2Fadmin.php%3Fpage%3Dwc-admin%26path%3D%2Fgoogle%2Fsetup-mc
	 *
	 * where its URL decoded version is:
	 * nonce=nonce-123&store_url=https://merchant-site.example.com/wp-admin/admin.php?page=wc-admin&path=/google/setup-mc
	 *
	 * @param string $path A URL parameter for the path within GL&A page, which will be added in the merchant redirect URL.
	 *
	 * @return string Auth URL.
	 * @throws ContainerExceptionInterface When get_data_from_google throws an exception.
	 */
	public function get_auth_url( string $path ): string {
		$google_data = $this->get_data_from_google();

		$store_url = urlencode_deep( admin_url( "admin.php?page=wc-admin&path={$path}" ) );

		$state = $this->base64url_encode(
			build_query(
				[
					'nonce'     => $google_data['nonce'],
					'store_url' => $store_url,
				]
			)
		);

		$auth_url = esc_url_raw(
			add_query_arg(
				[
					'blog'          => Jetpack_Options::get_option( 'id' ),
					'client_id'     => $google_data['client_id'],
					'redirect_uri'  => $google_data['redirect_uri'],
					'response_type' => self::RESPONSE_TYPE,
					'scope'         => self::SCOPE,
					'state'         => $state,
				],
				$this->get_wpcom_api_url( self::AUTH_URL )
			)
		);

		return $auth_url;
	}

	/**
	 * Get a WPCOM REST API URl concatenating the endpoint with the API Domain
	 *
	 * @param string $endpoint The endpoint to get the URL for
	 *
	 * @return string The WPCOM endpoint with the domain.
	 */
	protected function get_wpcom_api_url( string $endpoint ): string {
		return self::WPCOM_API_URL . $endpoint;
	}

	/**
	 * Calls an API by Google via WCS to get required information in order to form an auth URL.
	 *
	 * @return array{client_id: string, redirect_uri: string, nonce: string} An associative array contains required information that is retrived from Google.
	 * client_id:    Google's WPCOM app client ID, will be used to form the authorization URL.
	 * redirect_uri: A Google's URL that will be redirected to when the merchant approve the app access. Note that it needs to be matched with the Google WPCOM app client settings.
	 * nonce:        A string returned by Google that we will put it in the auth URL and the redirect_uri. Google will use it to verify the call.
	 * @throws ContainerExceptionInterface When get_sdi_auth_params throws an exception.
	 */
	protected function get_data_from_google(): array {
		/** @var Middleware $middleware */
		$middleware = $this->container->get( Middleware::class );
		$response   = $middleware->get_sdi_auth_params();
		$nonce      = $response['nonce'];
		$this->options->update( OptionsInterface::GOOGLE_WPCOM_AUTH_NONCE, $nonce );
		return [
			'client_id'    => $response['clientId'],
			'redirect_uri' => $response['redirectUri'],
			'nonce'        => $nonce,
		];
	}

	/**
	 * Perform a remote request for revoking OAuth access for the current user.
	 *
	 * @return string The body of the response
	 * @throws Exception If the remote request fails.
	 */
	public function revoke_wpcom_api_auth(): string {
		$args = [
			'method'  => 'DELETE',
			'timeout' => 30,
			'url'     => $this->get_wpcom_api_url( '/wpcom/v2/sites/' . Jetpack_Options::get_option( 'id' ) . '/wc/partners/google/revoke-token' ),
			'user_id' => get_current_user_id(),
		];

		$request = $this->container->get( Jetpack::class )->remote_request( $args );

		if ( is_wp_error( $request ) ) {

			/**
			 * When the WPCOM token has been revoked with errors.
			 *
			 * @event revoke_wpcom_api_authorization
			 * @property int status The status of the request.
			 * @property string error The error message.
			 * @property int|null blog_id The blog ID.
			 */
			do_action(
				'woocommerce_gla_track_event',
				'revoke_wpcom_api_authorization',
				[
					'status'  => 400,
					'error'   => $request->get_error_message(),
					'blog_id' => Jetpack_Options::get_option( 'id' ),
				]
			);

			throw new Exception( $request->get_error_message(), 400 );
		} else {
			$body   = wp_remote_retrieve_body( $request );
			$status = wp_remote_retrieve_response_code( $request );

			if ( ! $status || $status !== 200 ) {
				$data    = json_decode( $body, true );
				$message = $data['message'] ?? 'Error revoking access to WPCOM.';

				/**
				*
				* When the WPCOM token has been revoked with errors.
				*
				* @event revoke_wpcom_api_authorization
				* @property int status The status of the request.
				* @property string error The error message.
				* @property int|null blog_id The blog ID.
				 */
				do_action(
					'woocommerce_gla_track_event',
					'revoke_wpcom_api_authorization',
					[
						'status'  => $status,
						'error'   => $message,
						'blog_id' => Jetpack_Options::get_option( 'id' ),
					]
				);

				throw new Exception( $message, $status );
			}

			/**
			* When the WPCOM token has been revoked successfully.
			*
			* @event revoke_wpcom_api_authorization
			* @property int status The status of the request.
			* @property int|null blog_id The blog ID.
			 */
			do_action(
				'woocommerce_gla_track_event',
				'revoke_wpcom_api_authorization',
				[
					'status'  => 200,
					'blog_id' => Jetpack_Options::get_option( 'id' ),
				]
			);

			$this->container->get( AccountService::class )->reset_wpcom_api_authorization_data();
			return $body;
		}
	}

	/**
	 * Deactivate the service.
	 *
	 * Revoke token on deactivation.
	 */
	public function deactivate(): void {
		// Try to revoke the token on deactivation. If no token is available, it will throw an exception which we can ignore.
		try {
			$this->revoke_wpcom_api_auth();
		} catch ( Exception $e ) {
			do_action(
				'woocommerce_gla_error',
				sprintf( 'Error revoking the WPCOM token: %s', $e->getMessage() ),
				__METHOD__
			);
		}
	}
}
