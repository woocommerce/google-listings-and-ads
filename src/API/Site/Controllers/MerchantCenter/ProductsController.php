<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Merchant;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseOptionsController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\Options;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\ProductStatistics;
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
	 * @throws Exception If the Merchant account status can't be retrieved.
	 */
	protected function get_product_status_stats(): array {
		return $this->container->get( ProductStatistics::class )->get();

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
