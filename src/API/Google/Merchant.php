<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\Value\PositiveInteger;
use Google_Service_ShoppingContent as ShoppingService;
use Google_Service_ShoppingContent_Product as Product;
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
	public function get_id() {
		return $this->id->get();
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
}
