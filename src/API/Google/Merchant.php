<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\Value\PositiveInteger;
use Google_Service_ShoppingContent as ShoppingService;
use Google_Service_ShoppingContent_Account as MC_Account;
use Google_Service_ShoppingContent_Product as Product;
use Google\Exception as GoogleException;
use Exception;
use Psr\Container\ContainerInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Class Merchant
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google
 */
class Merchant {

	/**
	 * The container object.
	 *
	 * @var ContainerInterface
	 */
	protected $container;

	/**
	 * The merchant ID.
	 *
	 * @var PositiveInteger
	 */
	protected $id;

	/**
	 * Merchant constructor.
	 *
	 * @param ContainerInterface $container
	 * @param PositiveInteger    $id
	 */
	public function __construct( ContainerInterface $container, PositiveInteger $id ) {
		$this->container = $container;
		$this->id        = $id;
	}

	/**
	 * Get the ID.
	 *
	 * @return int
	 */
	public function get_id(): int {
		return $this->id->get();
	}

	/**
	 * Set the ID.
	 *
	 * @param int $id
	 */
	public function set_id( int $id ): void {
		$this->id = new PositiveInteger( $id );
	}

	/**
	 * @return Product[]
	 */
	public function get_products(): array {
		/** @var ShoppingService $service */
		$service  = $this->container->get( ShoppingService::class );
		$products = $service->products->listProducts( $this->get_id() );
		$return   = [];

		while ( ! empty( $products->getResources() ) ) {
			foreach ( $products->getResources() as $product ) {
				$return[] = $product;
			}

			if ( ! empty( $products->getNextPageToken() ) ) {
				$products = $service->products->listProducts(
					$this->id,
					[ 'pageToken' => $products->getNextPageToken() ]
				);
			}
		}

		return $return;
	}


	/**
	 * Claim a website for the user's Merchant Center account.
	 *
	 * @return bool
	 * @throws Exception If the website claim fails.
	 */
	public function claimwebsite(): bool {
		/** @var ShoppingService $service */
		$service = $this->container->get( ShoppingService::class );

		try {
			$response = $service->accounts->claimwebsite( $this->get_id(), $this->get_id() );
		} catch ( GoogleException $e ) {
			do_action( 'gla_mc_client_exception', $e, __METHOD__ );
			$error_message = __( 'Unable to claim website.', 'google-listings-and-ads' );
			if ( 403 === $e->getCode() ) {
				$error_message = __( 'Website already claimed, use overwrite to complete.', 'google-listings-and-ads' );
			}
			throw new Exception( $error_message, $e->getCode() );

		}
		return true;
	}

	/**
	 * Retrieve the user's Merchant Center account information.
	 *
	 * @param int $id Optional - the Merchant Center account to retrieve
	 * @return MC_Account The user's Merchant Center account.
	 * @throws Exception If the account can't be retrieved.
	 */
	public function get_account( int $id = 0 ): MC_Account {
		/** @var ShoppingService $service */
		$service = $this->container->get( ShoppingService::class );
		$id      = $id ?: $this->get_id();

		try {
			$mc_account = $service->accounts->get( $id, $id );
		} catch ( GoogleException $e ) {
			do_action( 'gla_mc_client_exception', $e, __METHOD__ );
			throw new Exception( __( 'Unable to retrieve merchant center account.', 'google-listings-and-ads' ), $e->getCode() );
		}
		return $mc_account;
	}

	/**
	 * Update the provided Merchant Center account information.
	 *
	 * @param MC_Account $mc_account The Account data to update.
	 *
	 * @return MC_Account The user's Merchant Center account.
	 * @throws Exception If the account can't be retrieved.
	 */
	public function update_account( MC_Account $mc_account ): MC_Account {
		/** @var ShoppingService $service */
		$service = $this->container->get( ShoppingService::class );

		try {
			$mc_account = $service->accounts->update( $mc_account->getId(), $mc_account->getId(), $mc_account );
		} catch ( GoogleException $e ) {
			do_action( 'gla_mc_client_exception', $e, __METHOD__ );
			throw new Exception( __( 'Unable to retrieve merchant center account.', 'google-listings-and-ads' ), $e->getCode() );
		}
		return $mc_account;
	}
}
