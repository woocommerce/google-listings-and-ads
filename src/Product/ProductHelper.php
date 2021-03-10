<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Product;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use WC_Product;
use WC_Product_Variation;

defined( 'ABSPATH' ) || exit;

/**
 * Class ProductHelper
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Product
 */
class ProductHelper implements Service {

	/**
	 * @var ProductMetaHandler
	 */
	protected $meta_handler;

	/**
	 * ProductHelper constructor.
	 *
	 * @param ProductMetaHandler $meta_handler
	 */
	public function __construct( ProductMetaHandler $meta_handler ) {
		$this->meta_handler = $meta_handler;
	}

	/**
	 * @param WC_Product $product
	 *
	 * @return string[] An array of Google product IDs stored for each WooCommerce product
	 */
	public function get_synced_google_product_ids( WC_Product $product ) {
		return $this->meta_handler->get_google_ids( $product->get_id() );
	}

	/**
	 * @param WC_Product $product
	 *
	 * @return string
	 */
	public function is_product_synced( WC_Product $product ) {
		$synced_at  = $this->meta_handler->get_synced_at( $product->get_id() );
		$google_ids = $this->meta_handler->get_google_ids( $product->get_id() );

		return ! empty( $synced_at ) && ! empty( $google_ids );
	}

	/**
	 * @param WC_Product $wc_product
	 *
	 * @return string
	 */
	public function get_visibility( WC_Product $wc_product ): string {
		$visibility = $this->meta_handler->get_visibility( $wc_product->get_id() );
		if ( $wc_product instanceof WC_Product_Variation ) {
			// todo: we might need to define visibility per variation later.
			$visibility = $this->meta_handler->get_visibility( $wc_product->get_parent_id() );
		}

		return $visibility;
	}

	/**
	 * @param WC_Product  $product
	 * @param string|null $target_country
	 *
	 * @return WCProductAdapter
	 */
	public static function generate_adapted_product( WC_Product $product, string $target_country = null ): WCProductAdapter {
		return new WCProductAdapter(
			[
				'wc_product'    => $product,
				'targetCountry' => $target_country,
			]
		);
	}
}
