<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\Options\MerchantAccountState;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\Options;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\TosAccepted;
use DateTime;
use DateTimeZone;
use Exception;
use Google_Service_ShoppingContent as ShoppingContent;
use Google\Ads\GoogleAds\Lib\V6\GoogleAdsClient;
use Google\ApiCore\ApiException;
use GuzzleHttp\Client;
use Psr\Container\ContainerInterface;
use Psr\Http\Client\ClientExceptionInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Class Proxy
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google
 */
class Proxy {

	use OptionsAwareTrait;

	/**
	 * @var ContainerInterface
	 */
	protected $container;

	/**
	 * Proxy constructor.
	 *
	 * @param ContainerInterface $container
	 */
	public function __construct( ContainerInterface $container ) {
		$this->set_options_object( $container->get( OptionsInterface::class ) );
		$this->container = $container;
	}

	/**
	 * Get merchant IDs associated with the connected Merchant Center account.
	 *
	 * @return int[]
	 * @throws Exception When an Exception is caught.
	 */
	public function get_merchant_ids(): array {
		try {
			/** @var Client $client */
			$client   = $this->container->get( Client::class );
			$result   = $client->get( $this->get_manager_url( 'merchant-accounts' ) );
			$response = json_decode( $result->getBody()->getContents(), true );
			$ids      = [];

			if ( 200 === $result->getStatusCode() && is_array( $response ) ) {
				foreach ( $response as $id ) {
					$ids[] = $id;
				}
			}

			return $ids;
		} catch ( ClientExceptionInterface $e ) {
			do_action( 'gla_guzzle_client_exception', $e, __METHOD__ );

			/* translators: %s Error message */
			throw new Exception( sprintf( __( 'Error retrieving accounts: %s', 'google-listings-and-ads' ), $e->getMessage() ) );
		}
	}

	/**
	 * Create a new Merchant Center account.
	 *
	 * @return int
	 * @throws Exception When an Exception is caught or we receive an invalid response.
	 */
	public function create_merchant_account(): int {
		try {
			$user = wp_get_current_user();
			$tos  = $this->mark_tos_accepted( 'google-mc', $user->user_email );
			if ( ! $tos->accepted() ) {
				throw new Exception( __( 'Unable to log accepted TOS', 'google-listings-and-ads' ) );
			}

			/** @var Client $client */
			$client = $this->container->get( Client::class );
			$result = $client->post(
				$this->get_manager_url( 'create-merchant' ),
				[
					'body' => json_encode(
						[
							'name'       => $this->new_account_name(),
							'websiteUrl' => apply_filters( 'woocommerce_gla_site_url', site_url() ),
						]
					),
				]
			);

			$response = json_decode( $result->getBody()->getContents(), true );

			if ( 200 === $result->getStatusCode() && isset( $response['id'] ) ) {
				$id = absint( $response['id'] );
				$this->update_merchant_id( $id );
				return $id;
			}

			do_action( 'gla_guzzle_invalid_response', $response, __METHOD__ );

			$error = $response['message'] ?? __( 'Invalid response when creating account', 'google-listings-and-ads' );
			throw new Exception( $error, $result->getStatusCode() );
		} catch ( ClientExceptionInterface $e ) {
			do_action( 'gla_guzzle_client_exception', $e, __METHOD__ );

			/* translators: %s Error message */
			throw new Exception( sprintf( __( 'Error creating account: %s', 'google-listings-and-ads' ), $e->getMessage() ) );
		}
	}

	/**
	 * Link an existing Merchant Center account.
	 *
	 * @param int $id Existing account ID.
	 *
	 * @return int
	 */
	public function link_merchant_account( int $id ): int {
		$this->update_merchant_id( $id );

		return $id;
	}

	/**
	 * Get the connected merchant account.
	 *
	 * @return array
	 */
	public function get_connected_merchant(): array {
		$id     = $this->get_merchant_id();
		$status = $id ? 'connected' : 'disconnected';

		foreach ( $this->options->get( OptionsInterface::MERCHANT_ACCOUNT_STATE, [] ) as $name => $step ) {
			if ( ! isset( $step['status'] ) || MerchantAccountState::MC_CREATION_STEP_DONE !== $step['status'] ) {
				$status = 'incomplete';
				$id     = 0;
				break;
			}
		}

		return [
			'id'     => $id,
			'status' => $status,
		];
	}

	/**
	 * Disconnect the connected merchant account.
	 */
	public function disconnect_merchant() {
		$this->update_merchant_id( 0 );

		// TODO: Cancel any active campaigns and remove product feeds when disconnecting.
	}

	/**
	 * Link Merchant Center account to MCA.
	 *
	 * @return bool
	 * @throws Exception When a ClientException is caught or we receive an invalid response.
	 */
	public function link_merchant_to_mca(): bool {
		try {
			/** @var Client $client */
			$client = $this->container->get( Client::class );
			$result = $client->post(
				$this->get_manager_url( 'link-merchant' ),
				[
					'body' => json_encode(
						[
							'accountId' => $this->get_merchant_id(),
						]
					),
				]
			);

			$response = json_decode( $result->getBody()->getContents(), true );

			if ( 200 === $result->getStatusCode() && isset( $response['status'] ) && 'success' === $response['status'] ) {
				return true;
			}

			do_action( 'gla_guzzle_invalid_response', $response, __METHOD__ );

			$error = $response['message'] ?? __( 'Invalid response when linking merchant to MCA', 'google-listings-and-ads' );
			throw new Exception( $error, $result->getStatusCode() );
		} catch ( ClientExceptionInterface $e ) {
			do_action( 'gla_guzzle_client_exception', $e, __METHOD__ );

			/* translators: %s Error message */
			throw new Exception( sprintf( __( 'Error linking merchant to MCA: %s', 'google-listings-and-ads' ), $e->getMessage() ) );
		}
	}

	/**
	 * Claim the website for a MCA.
	 *
	 * @param bool $overwrite To enable claim overwriting.
	 * @return bool
	 * @throws Exception When an Exception is caught or we receive an invalid response.
	 */
	public function claim_merchant_website( bool $overwrite = false ): bool {
		try {
			/** @var Client $client */
			$client = $this->container->get( Client::class );
			$result = $client->post(
				$this->get_manager_url( 'claim-website' ),
				[
					'body' => json_encode(
						[
							'accountId' => $this->get_merchant_id(),
							'overwrite' => $overwrite,
						]
					),
				]
			);

			$response = json_decode( $result->getBody()->getContents(), true );

			if ( 200 === $result->getStatusCode() && isset( $response['status'] ) && 'success' === $response['status'] ) {
				return true;
			}

			do_action( 'gla_guzzle_invalid_response', $response, __METHOD__ );

			$error = $response['message'] ?? __( 'Invalid response when claiming website', 'google-listings-and-ads' );
			throw new Exception( $error, $result->getStatusCode() );
		} catch ( ClientExceptionInterface $e ) {
			do_action( 'gla_guzzle_client_exception', $e, __METHOD__ );

			/* translators: %s Error message */
			throw new Exception( sprintf( __( 'Error claiming website: %s', 'google-listings-and-ads' ), $e->getMessage() ) );
		}
	}


	/**
	 * Get Ads IDs associated with the connected Google account.
	 *
	 * @return int[]
	 * @throws Exception When an ApiException is caught.
	 */
	public function get_ads_account_ids(): array {
		try {
			/** @var GoogleAdsClient $client */
			$client    = $this->container->get( GoogleAdsClient::class );
			$args      = [ 'headers' => $this->container->get( 'connect_server_auth_header' ) ];
			$customers = $client->getCustomerServiceClient()->listAccessibleCustomers( $args );
			$ids       = [];

			foreach ( $customers->getResourceNames() as $name ) {
				$ids[] = $this->parse_ads_id( $name );
			}

			return $ids;
		} catch ( ApiException $e ) {
			do_action( 'gla_ads_client_exception', $e, __METHOD__ );

			/* translators: %s Error message */
			throw new Exception( sprintf( __( 'Error retrieving accounts: %s', 'google-listings-and-ads' ), $e->getBasicMessage() ) );
		}
	}

	/**
	 * Create a new Google Ads account.
	 *
	 * @return int
	 * @throws Exception When a ClientException is caught or we receive an invalid response.
	 */
	public function create_ads_account(): int {
		try {
			$user = wp_get_current_user();
			$tos  = $this->mark_tos_accepted( 'google-ads', $user->user_email );
			if ( ! $tos->accepted() ) {
				throw new Exception( __( 'Unable to log accepted TOS', 'google-listings-and-ads' ) );
			}

			/** @var Client $client */
			$client = $this->container->get( Client::class );
			$result = $client->post(
				$this->get_manager_url( 'US/create-customer' ),
				[
					'body' => json_encode(
						[
							'descriptive_name' => $this->new_account_name(),
							'currency_code'    => get_woocommerce_currency(),
							'time_zone'        => $this->get_site_timezone_string(),
						]
					),
				]
			);

			$response = json_decode( $result->getBody()->getContents(), true );

			if ( 200 === $result->getStatusCode() && isset( $response['resourceName'] ) ) {
				$id = $this->parse_ads_id( $response['resourceName'] );
				$this->update_ads_id( $id );
				return $id;
			}

			do_action( 'gla_guzzle_invalid_response', $response, __METHOD__ );

			$error = $response['message'] ?? __( 'Invalid response when creating account', 'google-listings-and-ads' );
			throw new Exception( $error, $result->getStatusCode() );
		} catch ( ClientExceptionInterface $e ) {
			do_action( 'gla_guzzle_client_exception', $e, __METHOD__ );

			/* translators: %s Error message */
			throw new Exception( sprintf( __( 'Error creating account: %s', 'google-listings-and-ads' ), $e->getMessage() ) );
		}
	}

	/**
	 * Link an existing Google Ads account.
	 *
	 * @param int $id Existing account ID.
	 *
	 * @return int
	 * @throws Exception When a ClientException is caught or we receive an invalid response.
	 */
	public function link_ads_account( int $id ): int {
		try {
			/** @var Client $client */
			$client = $this->container->get( Client::class );
			$result = $client->post(
				$this->get_manager_url( 'link-customer' ),
				[
					'body' => json_encode(
						[
							'client_customer' => $id,
						]
					),
				]
			);

			$response = json_decode( $result->getBody()->getContents(), true );
			$name     = "customers/{$id}";

			if ( 200 === $result->getStatusCode() && isset( $response['resourceName'] ) && 0 === strpos( $response['resourceName'], $name ) ) {
				$this->update_ads_id( $id );
				return $id;
			}

			do_action( 'gla_guzzle_invalid_response', $response, __METHOD__ );

			$error = $response['message'] ?? __( 'Invalid response when linking account', 'google-listings-and-ads' );
			throw new Exception( $error, $result->getStatusCode() );
		} catch ( ClientExceptionInterface $e ) {
			do_action( 'gla_guzzle_client_exception', $e, __METHOD__ );

			/* translators: %s Error message */
			throw new Exception( sprintf( __( 'Error linking account: %s', 'google-listings-and-ads' ), $e->getMessage() ) );
		}
	}

	/**
	 * Get the connected ads account.
	 *
	 * @return array
	 */
	public function get_connected_ads_account(): array {
		$id = intval( $this->options->get( Options::ADS_ID ) );

		return [
			'id'     => $id,
			'status' => $id ? 'connected' : 'disconnected',
		];
	}

	/**
	 * Disconnect the connected ads account.
	 */
	public function disconnect_ads_account() {
		$this->update_ads_id( 0 );
	}

	/**
	 * Determine whether the TOS have been accepted.
	 *
	 * @param string $service Name of service.
	 *
	 * @return TosAccepted
	 */
	public function check_tos_accepted( string $service ): TosAccepted {
		// todo: see about using the WooCommerce Services code here
		try {
			/** @var Client $client */
			$client = $this->container->get( Client::class );
			$result = $client->get( $this->get_tos_url( $service ) );

			return new TosAccepted( 200 === $result->getStatusCode(), $result->getBody()->getContents() );
		} catch ( ClientExceptionInterface $e ) {
			do_action( 'gla_guzzle_client_exception', $e, __METHOD__ );

			return new TosAccepted( false, $e->getMessage() );
		}
	}

	/**
	 * Record TOS acceptance for a particular email address.
	 *
	 * @param string $service Name of service.
	 * @param string $email
	 *
	 * @return TosAccepted
	 */
	public function mark_tos_accepted( string $service, string $email ): TosAccepted {
		// todo: see about using WooCommerce Services code here.
		try {
			/** @var Client $client */
			$client = $this->container->get( Client::class );
			$result = $client->post(
				$this->get_tos_url( $service ),
				[
					'body' => json_encode(
						[
							'email' => $email,
						]
					),
				]
			);

			return new TosAccepted(
				200 === $result->getStatusCode(),
				$result->getBody()->getContents() ?? $result->getReasonPhrase()
			);
		} catch ( ClientExceptionInterface $e ) {
			do_action( 'gla_guzzle_client_exception', $e, __METHOD__ );

			return new TosAccepted( false, $e->getMessage() );
		}
	}

	/**
	 * Get the TOS endpoint URL
	 *
	 * @param string $service Name of service.
	 *
	 * @return string
	 */
	protected function get_tos_url( string $service ): string {
		$url = $this->container->get( 'connect_server_root' ) . 'tos';
		return $service ? trailingslashit( $url ) . $service : $url;
	}

	/**
	 * Get the manager endpoint URL
	 *
	 * @param string $name Resource name.
	 *
	 * @return string
	 */
	protected function get_manager_url( string $name = '' ): string {
		$url = $this->container->get( 'connect_server_root' ) . 'manager';
		return $name ? trailingslashit( $url ) . $name : $url;
	}

	/**
	 * Convert ads ID from a resource name to an int.
	 *
	 * @param string $name Resource name containing ID number.
	 *
	 * @return int
	 */
	protected function parse_ads_id( string $name ): int {
		return absint( str_replace( 'customers/', '', $name ) );
	}

	/**
	 * Get the Merchant Center ID.
	 *
	 * @return int
	 */
	protected function get_merchant_id(): int {
		return absint( $this->options->get( Options::MERCHANT_ID ) );
	}

	/**
	 * Update the Merchant Center ID to use for requests.
	 *
	 * @param int $id  Merchant ID number.
	 *
	 * @return bool
	 */
	protected function update_merchant_id( int $id ): bool {
		return $this->options->update( Options::MERCHANT_ID, $id );
	}

	/**
	 * Update the Ads ID to use for requests.
	 *
	 * @param int $id Ads ID number.
	 *
	 * @return bool
	 */
	protected function update_ads_id( int $id ): bool {
		return $this->options->update( Options::ADS_ID, $id );
	}

	/**
	 * Generate a descriptive name for a new account.
	 *
	 * @return string
	 */
	protected function new_account_name(): string {
		$site_title = get_bloginfo( 'name' );
		return $site_title;
	}

	/**
	 * Get a timezone string from WP Settings.
	 *
	 * @return string
	 */
	protected function get_site_timezone_string(): string {
		$timezone = wp_timezone_string();

		// Convert a timezone offset to the closest match.
		if ( false !== strpos( $timezone, ':' ) ) {
			list( $hours, $minutes ) = explode( ':', $timezone );

			$dst      = (int) ( new DateTime( 'now', new DateTimeZone( $timezone ) ) )->format( 'I' );
			$seconds  = $hours * 60 * 60 + $minutes * 60;
			$tz_name  = timezone_name_from_abbr( '', $seconds, $dst );
			$timezone = $tz_name !== false ? $tz_name : date_default_timezone_get();
		}

		if ( 'UTC' === $timezone ) {
			$timezone = 'Etc/GMT';
		}

		return $timezone;
	}
}
