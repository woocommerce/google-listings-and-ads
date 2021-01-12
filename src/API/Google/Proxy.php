<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\Value\TosAccepted;
use Exception;
use Google_Service_ShoppingContent as ShoppingContent;
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
	 */
	public function get_ads_account_ids(): array {
		$ids = [];
		try {
			/** @var Client $client */
			$client = $this->container->get( Client::class );
			$result = $client->get( $this->get_ads_url( 'customers:listAccessibleCustomers' ) );

			$accounts = json_decode( $result->getBody()->getContents(), true );
			if ( $accounts && is_array( $accounts['resourceNames'] ) ) {
				foreach ( $accounts['resourceNames'] as $account ) {
					$ids[] = absint( str_replace( 'customers/', '', $account ) );
				}
			}

			return $ids;
		} catch ( Exception $e ) {
			return $ids;
		}
	}

	/**
	 * Create a new Google Ads account.
	 *
	 * @param array $params Request paramaters.
	 *
	 * @return json|string
	 */
	public function create_ads_account( array $params ) {
		try {
			/** @var Client $client */
			$client = $this->container->get( Client::class );
			$result = $client->post(
				$this->get_ads_manager_url( 'US/create-customer' ),
				[
					'body' => json_encode( $params ),
				]
			);

			return json_decode( $result->getBody()->getContents(), true );
		} catch ( Exception $e ) {
			do_action( 'gla_guzzle_client_exception', $e, __METHOD__ );

			return $e->getMessage();
		}
	}

	/**
	 * Link an existing Google Ads account.
	 *
	 * @param int $id Existing account ID.
	 *
	 * @return json|string
	 */
	public function link_ads_account( int $id ) {
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

			return json_decode( $result->getBody()->getContents(), true );
		} catch ( Exception $e ) {
			do_action( 'gla_guzzle_client_exception', $e, __METHOD__ );

			return $e->getMessage();
		}
	}

	/**
	 * Determine whether the TOS have been accepted.
	 *
	 * @return TosAccepted
	 */
	public function check_tos_accepted(): TosAccepted {
		// todo: see about using the WooCommerce Services code here
		try {
			/** @var Client $client */
			$client = $this->container->get( Client::class );
			$result = $client->get( $this->get_tos_url() );

			return new TosAccepted( 200 === $result->getStatusCode(), $result->getBody()->getContents() );
		} catch ( ClientExceptionInterface $e ) {
			do_action( 'gla_guzzle_client_exception', $e, __METHOD__ );

			return new TosAccepted( false, $e->getMessage() );
		}
	}

	/**
	 * Record TOS acceptance for a particular email address.
	 *
	 * @param string $email
	 *
	 * @return TosAccepted
	 */
	public function mark_tos_accepted( string $email ): TosAccepted {
		// todo: see about using WooCommerce Services code here.
		try {
			/** @var Client $client */
			$client = $this->container->get( Client::class );
			$result = $client->post(
				$this->get_tos_url(),
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
	 * @return string
	 */
	protected function get_tos_url(): string {
		return $this->container->get( 'connect_server_root' ) . 'tos/google-mc';
	}

	/**
	 * Get the ads endpoint URL
	 *
	 * @param string $name Resource name.
	 *
	 * @return string
	 */
	protected function get_ads_url( string $name = '' ): string {
		$url = $this->container->get( 'connect_server_root' ) . 'google-ads/v6';
		return $name ? trailingslashit( $url ) . $name : $url;
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
}
