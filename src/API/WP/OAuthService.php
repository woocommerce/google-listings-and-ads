<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\WP;

use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\AccountService;
use Automattic\WooCommerce\GoogleListingsAndAds\HelperTraits\Utilities as UtilitiesTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\ContainerAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces\ContainerAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Deactivateable;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\Jetpack;
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

	public const STATUS_APPROVED    = 'approved';
	public const STATUS_DISAPPROVED = 'disapproved';
	public const STATUS_ERROR       = 'error';

	public const ALLOWED_STATUSES = [
		self::STATUS_APPROVED,
		self::STATUS_DISAPPROVED,
		self::STATUS_ERROR,
	];

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
			// Attempt to revoke the WPCOM token if setup is complete.
			if ( $this->is_jetpack_connected() ) {
				$this->revoke_wpcom_api_auth();
			}
		} catch ( Exception $e ) {
			do_action(
				'woocommerce_gla_error',
				sprintf( 'Error revoking the WPCOM token: %s', $e->getMessage() ),
				__METHOD__
			);
		}
	}
}
