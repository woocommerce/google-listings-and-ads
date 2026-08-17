<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\SearchConsole;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\ExceptionTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\SiteVerification;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\ContainerAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces\ContainerAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Client;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Psr\Http\Client\ClientExceptionInterface;
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Class Connection
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\SearchConsole
 */
class Connection implements ContainerAwareInterface, MerchantCenterAwareInterface, OptionsAwareInterface {

	use ContainerAwareTrait;
	use ExceptionTrait;
	use MerchantCenterAwareTrait;
	use OptionsAwareTrait;

	/** @var string The remote connection is active, but no property has been selected/verified yet. */
	public const STATE_INCOMPLETE = 'incomplete';

	/** @var string A property was selected but its verification has since been lost. */
	public const STATE_ACTION_NEEDED = 'action-needed';

	/** @var string A property is selected and verified. */
	public const STATE_CONNECTED = 'connected';

	/** @var string No connection has ever been established, or it was explicitly disconnected. */
	public const STATE_DISCONNECTED = 'disconnected';

	/** @var string A previously working connection is no longer authorized. */
	public const STATE_RECONNECT = 'reconnect';

	/** @var string The initial connection attempt itself failed. */
	public const STATE_CONNECTION_FAILED = 'connection-failed';

	/**
	 * Default shape of the `search_console` option, mirroring Site Verification's
	 * flat-array shape but with the extra fields this connection needs to track.
	 *
	 * @var array
	 */
	protected const DEFAULT_CONNECTION_DATA = [
		'property'      => null,
		'property_type' => null,
		'verified'      => SiteVerification::VERIFICATION_STATUS_UNVERIFIED,
		'state'         => null,
	];

	/**
	 * Get the stored Search Console connection data.
	 *
	 * @return array
	 */
	public function get_connection_data(): array {
		return $this->options->get( OptionsInterface::SEARCH_CONSOLE, self::DEFAULT_CONNECTION_DATA );
	}

	/**
	 * Update the stored Search Console connection data.
	 *
	 * Merges the given fields onto the existing stored data (or the defaults, if
	 * nothing has been stored yet) so callers can update a subset of fields.
	 *
	 * @param array $data The connection data fields to update.
	 *
	 * @return bool
	 */
	public function update_connection_data( array $data ): bool {
		return $this->options->update(
			OptionsInterface::SEARCH_CONSOLE,
			array_merge( $this->get_connection_data(), $data )
		);
	}

	/**
	 * Clear the stored Search Console connection data, returning it to its default state.
	 *
	 * @return bool
	 */
	public function clear_connection_data(): bool {
		return $this->options->delete( OptionsInterface::SEARCH_CONSOLE );
	}

	/**
	 * Whether the Google authorization prompt can be skipped.
	 *
	 * The merchant already granted WooCommerce Connect Server's OAuth grant during
	 * Merchant Center setup, so if they're already connected to Merchant Center
	 * there's no need to show the prompt again to connect Search Console.
	 *
	 * @return bool
	 */
	public function should_skip_auth(): bool {
		return $this->merchant_center->is_connected();
	}

	/**
	 * Get the connection URL for performing a connection redirect.
	 *
	 * @param string $return_url The return URL.
	 *
	 * @return string
	 * @throws Exception When a ClientException is caught or the response doesn't contain the oauthUrl.
	 */
	public function connect( string $return_url ): string {
		try {
			/** @var Client $client */
			$client = $this->container->get( Client::class );
			$result = $client->post(
				$this->get_connection_url(),
				[
					'body' => wp_json_encode(
						[
							'returnUrl' => $return_url,
						]
					),
				]
			);

			$response = json_decode( $result->getBody()->getContents(), true );
			if ( 200 === $result->getStatusCode() && ! empty( $response['oauthUrl'] ) ) {
				return $response['oauthUrl'];
			}

			do_action( 'woocommerce_gla_guzzle_invalid_response', $response, __METHOD__ );

			throw new Exception( __( 'Unable to connect Search Console account', 'google-listings-and-ads' ) );
		} catch ( ClientExceptionInterface $e ) {
			do_action( 'woocommerce_gla_guzzle_client_exception', $e, __METHOD__ );

			throw new Exception( __( 'Unable to connect Search Console account', 'google-listings-and-ads' ) );
		}
	}

	/**
	 * Disconnect from the Search Console account.
	 *
	 * Clears the locally stored connection data upfront, even if the remote
	 * disconnect call below fails, so a merchant who asks to disconnect never
	 * gets stuck with a stale property or verification state.
	 *
	 * @return string
	 */
	public function disconnect(): string {
		$this->clear_connection_data();

		try {
			/** @var Client $client */
			$client = $this->container->get( Client::class );
			$result = $client->delete( $this->get_connection_url() );

			return $result->getBody()->getContents();
		} catch ( ClientExceptionInterface $e ) {
			do_action( 'woocommerce_gla_guzzle_client_exception', $e, __METHOD__ );

			return $e->getMessage();
		} catch ( Exception $e ) {
			do_action( 'woocommerce_gla_exception', $e, __METHOD__ );

			return $e->getMessage();
		}
	}

	/**
	 * Get the status of the connection.
	 *
	 * @return array
	 * @throws Exception When a ClientException is caught or the response contains an error.
	 */
	public function get_status(): array {
		try {
			/** @var Client $client */
			$client   = $this->container->get( Client::class );
			$result   = $client->get( $this->get_connection_url() );
			$response = json_decode( $result->getBody()->getContents(), true );

			if ( 200 === $result->getStatusCode() ) {
				return $response;
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
	 * Resolve the merchant's current connection state.
	 *
	 * Reuses the same remote status check as {@see self::get_status()} but layers
	 * the locally persisted `state` (this connection's history) on top of it to
	 * tell apart the states that produce an identical remote error:
	 * - STATE_RECONNECT: the connection previously reached STATE_CONNECTED and
	 *   has since become unauthorized (expired).
	 * - STATE_CONNECTION_FAILED: the connection never reached STATE_CONNECTED,
	 *   so this is the initial attempt failing.
	 *
	 * STATE_INCOMPLETE and STATE_ACTION_NEEDED are stub branches only; real
	 * detection depends on the property-selection and verification logic owned
	 * by the sibling ticket (GOOWOO-943).
	 *
	 * @return array
	 */
	public function get_connection_status(): array {
		$connection_data = $this->get_connection_data();

		try {
			$status = $this->get_status();
		} catch ( Exception $e ) {
			$state = self::STATE_CONNECTED === $connection_data['state']
				? self::STATE_RECONNECT
				: self::STATE_CONNECTION_FAILED;

			$this->update_connection_data( [ 'state' => $state ] );

			return [ 'status' => $state ];
		}

		if ( self::STATE_CONNECTED !== ( $status['status'] ?? '' ) ) {
			$this->update_connection_data( [ 'state' => self::STATE_DISCONNECTED ] );

			return array_merge( $status, [ 'status' => self::STATE_DISCONNECTED ] );
		}

		$is_verified = ! empty( $connection_data['property'] )
			&& SiteVerification::VERIFICATION_STATUS_VERIFIED === $connection_data['verified'];

		if ( $is_verified ) {
			$this->update_connection_data( [ 'state' => self::STATE_CONNECTED ] );

			return array_merge( $status, [ 'status' => self::STATE_CONNECTED ] );
		}

		$state = ! empty( $connection_data['property'] ) ? self::STATE_ACTION_NEEDED : self::STATE_INCOMPLETE;

		$this->update_connection_data( [ 'state' => $state ] );

		return array_merge( $status, [ 'status' => $state ] );
	}

	/**
	 * Get the Search Console connection URL.
	 *
	 * Path pending final confirmation with Woo (GOOWOO-880); follows the
	 * established one-path-per-integration convention also used by
	 * `google/connection/youtube` and `google/connection/google-mc`.
	 *
	 * @return string
	 */
	protected function get_connection_url(): string {
		return "{$this->container->get( 'connect_server_root' )}google/connection/search-console";
	}
}
