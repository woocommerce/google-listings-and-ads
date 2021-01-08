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
}
