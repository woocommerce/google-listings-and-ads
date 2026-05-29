<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Product;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\ValidateInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Integration\WPML;
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
	 * @var WPML
	 */
	protected $wpml;

	/**
	 * ProductFactory constructor.
	 *
	 * @param AttributeManager $attribute_manager
	 * @param WC               $wc
	 * @param WPML             $wpml
	 */
	public function __construct( AttributeManager $attribute_manager, WC $wc, WPML $wpml ) {
		$this->attribute_manager = $attribute_manager;
		$this->wc                = $wc;
		$this->wpml              = $wpml;
	}

	/**
	 * @param WC_Product $product
	 * @param string     $target_country
	 * @param array      $mapping_rules The mapping rules setup by the user
	 *
	 * @return WCProductAdapter
	 *
	 * @throws InvalidValue When the product is a variation and its parent does not exist.
	 */
	public function create( WC_Product $product, string $target_country, array $mapping_rules ): WCProductAdapter {
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
				'mapping_rules'     => $mapping_rules,
			]
		);
	}

	/**
	 * Creates a WCProductAdapter for a specific language-currency pair in multilingual mode.
	 *
	 * Sets feedLabel, contentLanguage, and currency overrides so that each adapter represents
	 * one language × currency entry for Google's multi-feed data source.
	 *
	 * @param WC_Product $product       The WooCommerce product to adapt.
	 * @param string     $target_country ISO 3166-1 alpha-2 country code (required by Google API).
	 * @param array      $mapping_rules  Attribute mapping rules configured by the merchant.
	 * @param string     $feed_label     Feed label string, e.g. "en-USD".
	 * @param string     $language       ISO 639-1 language code, e.g. "en".
	 * @param string     $currency       ISO 4217 currency code, e.g. "USD".
	 *
	 * @return WCProductAdapter
	 *
	 * @throws InvalidValue When the product is a variable product or a variation without a valid parent.
	 */
	public function create_for_market( WC_Product $product, string $target_country, array $mapping_rules, string $feed_label, string $language, string $currency ): WCProductAdapter {
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
				'wc_product'               => $product,
				'parent_wc_product'        => $parent_product,
				'targetCountry'            => $target_country,
				'gla_attributes'           => $attributes,
				'mapping_rules'            => $mapping_rules,
				'feed_label_override'      => $feed_label,
				'content_language_override' => $language,
				'currency_override'        => $currency,
				'wpml'                     => $this->wpml,
			]
		);
	}
}
