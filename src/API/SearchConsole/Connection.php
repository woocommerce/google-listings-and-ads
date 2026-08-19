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
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Exception\BadResponseException;
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

	/** @var string The remote status check failed transiently (e.g. a 5xx or network error); the persisted state is left untouched. */
	public const STATE_TRANSIENT_ERROR = 'transient-error';

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

	/** @var SitesService */
	protected $sites_service;

	/** @var VerificationService */
	protected $verification_service;

	/**
	 * Connection constructor.
	 *
	 * @param SitesService        $sites_service
	 * @param VerificationService $verification_service
	 */
	public function __construct( SitesService $sites_service, VerificationService $verification_service ) {
		$this->sites_service        = $sites_service;
		$this->verification_service = $verification_service;
	}

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

			// A BadResponseException always carries a response and its status code; other
			// ClientExceptionInterface implementations (e.g. a connection timeout) don't.
			$status = $e instanceof BadResponseException ? $e->getResponse()->getStatusCode() : 0;

			throw new Exception( $this->client_exception_message( $e, __( 'Error retrieving status', 'google-listings-and-ads' ) ), $status );
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
	 * Only a 401/403 from the remote status check is treated as an authorization
	 * failure worth persisting as one of the states above. Any other failure (a
	 * 5xx, or a network-level failure with no status at all) is reported as
	 * STATE_TRANSIENT_ERROR without touching the persisted state, so a momentary
	 * outage can't misdiagnose a healthy connection as needing reconnection.
	 *
	 * STATE_INCOMPLETE and STATE_ACTION_NEEDED are stub branches only; real
	 * detection depends on property-selection and verification logic that
	 * lands separately.
	 *
	 * @return array
	 */
	public function get_connection_status(): array {
		$connection_data = $this->get_connection_data();

		try {
			$status = $this->get_status();
		} catch ( Exception $e ) {
			if ( ! in_array( $e->getCode(), [ 401, 403 ], true ) ) {
				return [ 'status' => self::STATE_TRANSIENT_ERROR ];
			}

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

		$matches = [];

		if ( empty( $connection_data['property'] ) ) {
			$matches = $this->resolve_property_and_verification();
		}

		$state = $this->resolve_local_state();

		if ( self::STATE_CONNECTED === $state ) {
			return array_merge( $status, [ 'status' => self::STATE_CONNECTED ] );
		}

		$response = array_merge( $status, [ 'status' => $state ] );

		return $matches ? array_merge( $response, [ 'matches' => $matches ] ) : $response;
	}

	/**
	 * Persist a merchant's explicit property choice — either selecting one of the
	 * candidates most recently returned as `matches` (a genuine multi-match, where
	 * auto-selection couldn't resolve to one), or explicitly creating a new
	 * property (the AC-028 "Create new" option, offered alongside a multi-match
	 * selector — distinct from the silent zero-match auto-create already handled
	 * by {@see self::resolve_property_and_verification()}).
	 *
	 * Never trusts a submitted `$site_url` on its own: re-fetches the current
	 * match list and requires the submitted URL to still appear there as usable,
	 * since the merchant's own Sites API access could have changed since the
	 * `matches` list was last returned.
	 *
	 * @param string|null $site_url The chosen property's `siteUrl`, or null to create a new one.
	 *
	 * @return array
	 * @throws SearchConsoleApiException On a non-2xx Sites API response.
	 * @throws Exception When `$site_url` is no longer a usable match.
	 */
	public function select_property( ?string $site_url = null ): array {
		if ( null === $site_url ) {
			$resolved = $this->sites_service->create_site();
		} else {
			$resolution = $this->sites_service->resolve_property();
			$resolved   = $this->find_usable_match( $resolution['matches'], $site_url );
		}

		$this->persist_resolved_property( $resolved );

		return [ 'status' => $this->resolve_local_state() ];
	}

	/**
	 * Trigger the META-tag verification flow for the currently selected property.
	 *
	 * @return array
	 * @throws Exception When no property has been selected yet, or verification fails.
	 */
	public function verify_property(): array {
		$connection_data = $this->get_connection_data();

		if ( empty( $connection_data['property'] ) ) {
			throw new Exception( __( 'No Search Console property has been selected yet.', 'google-listings-and-ads' ) );
		}

		$this->verification_service->verify( $connection_data['property'] );

		$this->update_connection_data( [ 'verified' => SiteVerification::VERIFICATION_STATUS_VERIFIED ] );

		return [ 'status' => $this->resolve_local_state() ];
	}

	/**
	 * Match, auto-select, or auto-create a property and resolve its verification
	 * status, persisting the outcome onto the stored connection data.
	 *
	 * Skipped entirely once `property` is already set, whether that came from
	 * this method's own auto-resolution or from a merchant's explicit
	 * multi-match selection (see {@see self::select_property()}). Until then,
	 * this re-runs on every status check — including the unresolved multi-match
	 * and API-failure cases below.
	 *
	 * @return array The domain-aligned property matches, non-empty only when more
	 *               than one usable property was found and nothing could be
	 *               auto-selected — the merchant must choose one.
	 */
	protected function resolve_property_and_verification(): array {
		try {
			$resolution = $this->sites_service->resolve_property();
		} catch ( SearchConsoleApiException $e ) {
			return [];
		}

		if ( null === $resolution['resolved'] ) {
			return $resolution['matches'];
		}

		$this->persist_resolved_property( $resolution['resolved'] );

		return [];
	}

	/**
	 * Persist a resolved (auto-selected, auto-created, or merchant-chosen)
	 * property and its verification status onto the stored connection data.
	 *
	 * @param array $resolved A `siteEntry`-shaped resource (`siteUrl`, `permissionLevel`).
	 */
	private function persist_resolved_property( array $resolved ): void {
		$this->update_connection_data(
			[
				'property'      => $resolved['siteUrl'],
				'property_type' => $this->sites_service->get_property_type( $resolved['siteUrl'] ),
				'verified'      => $this->verification_service->resolve_verification( $resolved ),
			]
		);
	}

	/**
	 * Find a still-usable match by `siteUrl` within a freshly-fetched match list.
	 *
	 * @param array[] $matches  Match entries, each with `siteUrl` and `usable`.
	 * @param string  $site_url The `siteUrl` the merchant chose.
	 *
	 * @return array The matching, usable entry.
	 * @throws Exception When no usable match with that `siteUrl` is found.
	 */
	private function find_usable_match( array $matches, string $site_url ): array {
		foreach ( $matches as $match ) {
			if ( $site_url === $match['siteUrl'] && ! empty( $match['usable'] ) ) {
				return $match;
			}
		}

		throw new Exception( __( 'The selected Search Console property is no longer available. Please try again.', 'google-listings-and-ads' ) );
	}

	/**
	 * Resolve this connection's state from locally stored connection data alone
	 * (no remote WCS status call), persisting the outcome.
	 *
	 * Used both by {@see self::get_connection_status()} (after a remote status
	 * check already confirmed the connection itself is active) and by
	 * {@see self::select_property()}/{@see self::verify_property()}, which have
	 * no reason to re-check remote connection status just to report the effect
	 * of a property/verification change they already made locally.
	 *
	 * @return string
	 */
	private function resolve_local_state(): string {
		$connection_data = $this->get_connection_data();

		$is_verified = ! empty( $connection_data['property'] )
			&& SiteVerification::VERIFICATION_STATUS_VERIFIED === $connection_data['verified'];

		$state = $is_verified
			? self::STATE_CONNECTED
			: ( ! empty( $connection_data['property'] ) ? self::STATE_ACTION_NEEDED : self::STATE_INCOMPLETE );

		$this->update_connection_data( [ 'state' => $state ] );

		return $state;
	}

	/**
	 * Get the Search Console connection URL.
	 *
	 * Path pending final confirmation with Woo; follows the established
	 * one-path-per-integration convention also used by
	 * `google/connection/youtube` and `google/connection/google-mc`.
	 *
	 * @return string
	 */
	protected function get_connection_url(): string {
		return "{$this->container->get( 'connect_server_root' )}google/connection/search-console";
	}
}
