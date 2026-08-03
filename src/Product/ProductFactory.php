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
	 * @var WPML|null
	 */
	protected ?WPML $wpml = null;

	/**
	 * ProductFactory constructor.
	 *
	 * @param AttributeManager $attribute_manager
	 * @param WC               $wc
	 * @param WPML|null        $wpml Optional WPML integration used for currency conversion when
	 *                               a currency_override is supplied to create(). Null is allowed
	 *                               so existing callers that pre-date the WPML dependency keep working.
	 */
	public function __construct( AttributeManager $attribute_manager, WC $wc, ?WPML $wpml = null ) {
		$this->attribute_manager = $attribute_manager;
		$this->wc                = $wc;
		$this->wpml              = $wpml;
	}

	/**
	 * @param WC_Product  $product
	 * @param string      $target_country
	 * @param array       $mapping_rules     The mapping rules setup by the user
	 * @param string      $feed_label        Optional feed label.
	 * @param string      $language          Optional ISO 639-1 language code.
	 * @param string|null $currency_override Optional ISO 4217 currency code to override the store currency.
	 *
	 * @return WCProductAdapter
	 *
	 * @throws InvalidValue When the product is a variation and its parent does not exist.
	 */
	public function create(
		WC_Product $product,
		string $target_country,
		array $mapping_rules,
		string $feed_label = '',
		string $language = '',
		?string $currency_override = null
	): WCProductAdapter {
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

		$adapted = new WCProductAdapter(
			[
				'wc_product'        => $product,
				'parent_wc_product' => $parent_product,
				'targetCountry'     => $target_country,
				'gla_attributes'    => $attributes,
				'mapping_rules'     => $mapping_rules,
				'wpml'              => $this->wpml,
				'currency_override' => $currency_override ?? '',
			]
		);

		if ( $feed_label ) {
			$adapted->set_feed_label( $feed_label );
		}
		if ( $language ) {
			$adapted->set_language( $language );
		}

		return $adapted;
	}
}
