<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Product;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\ValidateInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\AttributeManager;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use WC_Product;
use WC_Product_Variable;
use WC_Product_Variation;

defined( 'ABSPATH' ) || exit;

/**
 * Class ProductFactory
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Product
 */
class ProductFactory {

	use ValidateInterface;

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
	 *
	 * @throws InvalidValue When the product is a variation and its parent does not exist.
	 */
	public function create( WC_Product $product, string $target_country ): WCProductAdapter {
		// We do not support syncing the parent variable product. Each variation is synced individually instead.
		$this->validate_not_instanceof( $product, WC_Product_Variable::class );

		$attributes = $this->attribute_manager->get_all_values( $product );

		$parent_product = null;
		// merge with parent's attributes if it's a variation product
		if ( $product instanceof WC_Product_Variation ) {
			$parent_product    = $this->wc->get_product( $product->get_parent_id() );
			$parent_attributes = $this->attribute_manager->get_all_values( $parent_product );
			$attributes        = array_merge( $parent_attributes, $attributes );
		}

		return new WCProductAdapter(
			[
				'wc_product'        => $product,
				'parent_wc_product' => $parent_product,
				'targetCountry'     => $target_country,
				'gla_attributes'    => $attributes,
			]
		);
	}
}
