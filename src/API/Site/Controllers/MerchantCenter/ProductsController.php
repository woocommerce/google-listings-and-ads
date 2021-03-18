<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Merchant;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use Exception;
use Psr\Container\ContainerInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Class ProductsController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter
 */
class ProductsController extends BaseController {

	/**
	 * The merchant object.
	 *
	 * @var Merchant
	 */
	protected $merchant;

	/**
	 * The container object.
	 *
	 * @var ContainerInterface
	 */
	protected $container;

	/**
	 * ProductsController constructor.
	 *
	 * @param ContainerInterface $container
	 */
	public function __construct( ContainerInterface $container ) {
		parent::__construct( $container->get( RESTServer::class ) );
		$this->merchant  = $container->get( Merchant::class );
		$this->container = $container;
	}

	/**
	 * Register rest routes with WordPress.
	 */
	public function register_routes(): void {
		$this->register_route(
			'mc/product-summary',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_product_summary_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
			]
		);
	}

	/**
	 * Get the callback function for returning supported countries.
	 *
	 * @return callable
	 */
	protected function get_product_summary_callback(): callable {
		return function() {
			return $this->get_product_status_stats();
		};
	}

	/**
	 * Get the global product status summary array.
	 *
	 * @return array
	 * @throws Exception If the Merchant account can't be retrieved.
	 */
	protected function get_product_status_stats(): array {
		$account_stats = [
			'active'      => 0,
			'expiring'    => 0,
			'pending'     => 0,
			'disapproved' => 0,
			'not_synced'  => 0,
		];

		foreach ( $this->merchant->get_accountstatus()->getProducts() as $product ) {
			/** @var \Google_Service_ShoppingContent_AccountStatusProducts $product */
			$stats                         = $product->getStatistics();
			$account_stats['active']      += intval( $stats->getActive() );
			$account_stats['expiring']    += intval( $stats->getExpiring() );
			$account_stats['pending']     += intval( $stats->getPending() );
			$account_stats['disapproved'] += intval( $stats->getDisapproved() );
		}

		/** @var ProductRepository $product_repository */
		$product_repository          = $this->container->get( ProductRepository::class );
		$account_stats['not_synced'] = count( $product_repository->find_sync_pending_product_ids() );

		return $account_stats;

	}

	/**
	 * Get the item schema properties for the controller.
	 *
	 * @return array
	 */
	protected function get_schema_properties(): array {
		return [];
	}

	/**
	 * Get the item schema name for the controller.
	 *
	 * Used for building the API response schema.
	 *
	 * @return string
	 */
	protected function get_schema_title(): string {
		return 'product_summary';
	}
}
