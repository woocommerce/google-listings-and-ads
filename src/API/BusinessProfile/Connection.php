<?php
// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception messages are context-neutral data; escape only at the eventual output boundary.
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\ExceptionTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\ContainerAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces\ContainerAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Client;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Psr\Http\Client\ClientExceptionInterface;
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Class Connection
 *
 * Grants Business Profile access on the shared Google connection, and reports
 * or clears the merchant's connected location.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile
 */
class Connection implements ContainerAwareInterface {

	use ContainerAwareTrait;
	use ExceptionTrait;

	/** @var string A location is connected. */
	public const STATUS_CONNECTED = 'connected';

	/** @var string No location is connected yet, or the merchant disconnected it. */
	public const STATUS_DISCONNECTED = 'disconnected';

	/**
	 * The OAuth scope for Business Profile access, requested as an additional
	 * scope on the shared Google connection.
	 *
	 * @var string
	 */
	public const SCOPE_BUSINESS_PROFILE = 'https://www.googleapis.com/auth/business.manage';

	/** @var LocationService */
	private $location_service;

	/**
	 * Connection constructor.
	 *
	 * @param LocationService $location_service
	 */
	public function __construct( LocationService $location_service ) {
		$this->location_service = $location_service;
	}

	/**
	 * Get the connection URL for performing a connection redirect.
	 *
	 * Business Profile has no OAuth connection of its own. It asks for its scope
	 * on the shared Google connection that Merchant Center and Ads already use.
	 *
	 * A `loginHint` makes Google preselect the already connected account on the
	 * consent screen. The merchant can still switch accounts there.
	 *
	 * @param string $return_url The return URL.
	 * @param string $login_hint Suggested Google account to use for the scope grant.
	 *
	 * @return string
	 * @throws Exception When a ClientException is caught or the response doesn't contain the oauthUrl.
	 */
	public function connect( string $return_url, string $login_hint = '' ): string {
		try {
			$body = [
				'returnUrl'        => $return_url,
				'additionalScopes' => [ self::SCOPE_BUSINESS_PROFILE ],
			];

			if ( ! empty( $login_hint ) ) {
				$body['loginHint'] = $login_hint;
			}

			/** @var Client $client */
			$client = $this->container->get( Client::class );
			$result = $client->post(
				$this->get_connection_url(),
				[
					'body' => wp_json_encode( $body ),
				]
			);

			$response = json_decode( $result->getBody()->getContents(), true );
			if ( 200 === $result->getStatusCode() && ! empty( $response['oauthUrl'] ) ) {
				return $response['oauthUrl'];
			}

			do_action( 'woocommerce_gla_guzzle_invalid_response', $response, __METHOD__ );

			throw new Exception( __( 'Unable to connect Google Business Profile account', 'google-listings-and-ads' ) );
		} catch ( ClientExceptionInterface $e ) {
			do_action( 'woocommerce_gla_guzzle_client_exception', $e, __METHOD__ );

			throw new Exception( __( 'Unable to connect Google Business Profile account', 'google-listings-and-ads' ) );
		}
	}

	/**
	 * Disconnect the Business Profile location.
	 *
	 * Purely local. The connection URL is shared with Merchant Center and Ads, so
	 * a remote DELETE would tear down that shared connection too. The Google
	 * grant stays in place until the merchant revokes it in their Google Account.
	 *
	 * @return string
	 */
	public function disconnect(): string {
		$this->location_service->clear_selected_location();

		return __( 'Successfully disconnected.', 'google-listings-and-ads' );
	}

	/**
	 * Get the status of the connection.
	 *
	 * Reads the stored location. Makes no Business Profile API request.
	 *
	 * @return array {
	 *     @type string $status    One of the self::STATUS_* constants.
	 *     @type string $id        The connected location's ID.
	 *     @type string $accountId The account the location is listed under.
	 *     @type string $title     The location's name.
	 *     @type string $address   The location's address on one line.
	 *     @type string $placeId   The location's Google Maps place ID.
	 *     @type string $mapsUri   Link to the location on Google Maps.
	 * }
	 * @throws Exception When a ClientException is caught or the response contains an error.
	 */
	public function get_status(): array {
		if ( ! $this->is_scope_granted() ) {
			return [ 'status' => self::STATUS_DISCONNECTED ];
		}

		$location = $this->location_service->get_selected_location();

		if ( null === $location ) {
			return [ 'status' => self::STATUS_DISCONNECTED ];
		}

		return array_merge( [ 'status' => self::STATUS_CONNECTED ], $location );
	}

	/**
	 * Whether the shared Google connection currently carries the Business Profile scope.
	 *
	 * @return bool
	 * @throws Exception When a ClientException is caught or the response contains an error.
	 */
	private function is_scope_granted(): bool {
		try {
			/** @var Client $client */
			$client   = $this->container->get( Client::class );
			$result   = $client->get( $this->get_connection_url() );
			$response = json_decode( $result->getBody()->getContents(), true );

			if ( 200 === $result->getStatusCode() ) {
				$scopes = $response['scope'] ?? [];

				return is_array( $scopes ) && in_array( self::SCOPE_BUSINESS_PROFILE, $scopes, true );
			}

			do_action( 'woocommerce_gla_guzzle_invalid_response', $response, __METHOD__ );

			$message = $response['message'] ?? __( 'Invalid response when retrieving status', 'google-listings-and-ads' );
			throw new Exception( $message, $result->getStatusCode() );
		} catch ( ClientExceptionInterface $e ) {
			do_action( 'woocommerce_gla_guzzle_client_exception', $e, __METHOD__ );

			throw new Exception( $this->client_exception_message( $e, __( 'Error retrieving status', 'google-listings-and-ads' ) ) );
		}
	}

	/**
	 * Get the shared Google connection URL.
	 *
	 * @return string
	 */
	private function get_connection_url(): string {
		return "{$this->container->get( 'connect_server_root' )}google/connection/google-mc";
	}
}
