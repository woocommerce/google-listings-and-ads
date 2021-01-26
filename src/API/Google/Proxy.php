<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\Options\Options;
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
		$this->container = $container;
	}

	/**
	 * Get merchant IDs associated with the connected Merchant Center account.
	 *
	 * @return int[]
	 */
	public function get_merchant_ids(): array {
		$ids = [];
		try {
			/** @var ShoppingContent $service */
			$service  = $this->container->get( ShoppingContent::class );
			$accounts = $service->accounts->authinfo();

			foreach ( $accounts->getAccountIdentifiers() as $account ) {

				$id = $account->getMerchantID();

				// $id can be NULL if it is a Multi Client Account (MCA)
				if ( $id ) {
					$ids[] = $id;
				}
			}

			return $ids;
		} catch ( Exception $e ) {
			return $ids;
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
				$this->get_ads_manager_url( 'US/create-customer' ),
				[
					'body' => json_encode(
						[
							'descriptive_name' => $this->new_ads_account_name(),
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

			throw new Exception( __( 'Invalid response when creating account', 'google-listings-and-ads' ) );
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
				$this->get_ads_manager_url( 'link-customer' ),
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

			throw new Exception( __( 'Invalid response when linking account', 'google-listings-and-ads' ) );
		} catch ( ClientExceptionInterface $e ) {
			do_action( 'gla_guzzle_client_exception', $e, __METHOD__ );

			/* translators: %s Error message */
			throw new Exception( sprintf( __( 'Error linking account: %s', 'google-listings-and-ads' ), $e->getMessage() ) );
		}
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
	 * Get the ads manager endpoint URL
	 *
	 * @param string $name Resource name.
	 *
	 * @return string
	 */
	protected function get_ads_manager_url( string $name = '' ): string {
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
	 * Update the Ads ID to use for requests.
	 *
	 * @param int $id Ads ID number.
	 *
	 * @return bool
	 */
	protected function update_ads_id( int $id ): bool {
		/** @var Options $options */
		$options = $this->container->get( OptionsInterface::class );
		return $options->update( Options::ADS_ID, $id );
	}

	/**
	 * Generate a descriptive name for a new ads account.
	 *
	 * @return string
	 */
	protected function new_ads_account_name(): string {
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
