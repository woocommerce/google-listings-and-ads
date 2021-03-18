<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Merchant;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseOptionsController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\Options;
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
class ProductsController extends BaseOptionsController {

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
	 * The time the statistics option should live.
	 */
	public const STATISTICS_LIFETIME = HOUR_IN_SECONDS;

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
			'mc/product-statistics',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_product_statistics_callback(),
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
	protected function get_product_statistics_callback(): callable {
		return function() {
			return $this->get_product_status_stats();
		};
	}

	/**
	 * Get the global product status statistics array.
	 *
	 * @return array
	 * @throws Exception If the Merchant account can't be retrieved.
	 */
	protected function get_product_status_stats(): array {

		// Use the cached values if valid.
		$product_statistics = $this->options->get( Options::MC_PRODUCT_STATISTICS );
		$timestamp          = $product_statistics['timestamp'] ?? 0;
		$statistics         = $product_statistics['product_statistics'] ?? false;
		if ( $statistics && $timestamp >= time() - self::STATISTICS_LIFETIME ) {
			jplog('cached');
			return $statistics;
		}

		$product_stats = [
			'active'      => 0,
			'expiring'    => 0,
			'pending'     => 0,
			'disapproved' => 0,
			'not_synced'  => 0,
		];

		foreach ( $this->merchant->get_accountstatus()->getProducts() as $product ) {
			/** @var \Google_Service_ShoppingContent_AccountStatusProducts $product */
			$stats                         = $product->getStatistics();
			$product_stats['active']      += intval( $stats->getActive() );
			$product_stats['expiring']    += intval( $stats->getExpiring() );
			$product_stats['pending']     += intval( $stats->getPending() );
			$product_stats['disapproved'] += intval( $stats->getDisapproved() );
		}

		/** @var ProductRepository $product_repository */
		$product_repository          = $this->container->get( ProductRepository::class );
		$product_stats['not_synced'] = count( $product_repository->find_sync_pending_product_ids() );

		// Update the cached values
		$this->options->update(
			Options::MC_PRODUCT_STATISTICS,
			[
				'timestamp'          => time(),
				'product_statistics' => $product_stats,
			]
		);

		return $product_stats;

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
		return 'product_statistics';
	}
}
