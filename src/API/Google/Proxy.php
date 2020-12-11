<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Exception;
use Google_Service_ShoppingContent as ShoppingContent;
use Psr\Container\ContainerInterface;

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
				$ids[] = $account->getMerchantID();
			}

			return $ids;
		} catch ( Exception $e ) {
			return $ids;
		}
	}

	public function check_tos_accepted() {

	}
}
