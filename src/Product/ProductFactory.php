<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Product;

use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\AttributeManager;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use WC_Product;
use WC_Product_Variation;

defined( 'ABSPATH' ) || exit;

/**
 * Class ProductFactory
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Product
 */
class ProductFactory {

	/**
	 * @var AttributeManager
	 */
	protected $attribute_manager;

	/**
	 * @var WC
	 */
	protected $wc;

	/**
	 * ProductFactory constructor.
	 *
	 * @param AttributeManager $attribute_manager
	 * @param WC               $wc
	 */
	public function __construct( AttributeManager $attribute_manager, WC $wc ) {
		$this->attribute_manager = $attribute_manager;
		$this->wc                = $wc;
	}

	/**
	 * @param WC_Product $product
	 * @param string     $target_country
	 *
	 * @return WCProductAdapter
	 */
	public function create( WC_Product $product, string $target_country ): WCProductAdapter {
		$attributes = $this->attribute_manager->get_all_values( $product );

		// merge with parent's attributes if it's a variation product
		if ( $product instanceof WC_Product_Variation && ! empty( $product->get_parent_id() ) ) {
			$parent            = $this->wc->get_product( $product->get_parent_id() );
			$parent_attributes = $this->attribute_manager->get_all_values( $parent );
			$attributes        = array_merge( $parent_attributes, $attributes );
		}

		return new WCProductAdapter(
			[
				'wc_product'     => $product,
				'targetCountry'  => $target_country,
				'gla_attributes' => $attributes,
			]
		);
	}
}
