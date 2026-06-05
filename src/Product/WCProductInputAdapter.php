<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Product;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Models\ProductInput;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use WC_Product;
use WC_Product_Variation;

defined( 'ABSPATH' ) || exit;

/**
 * Class WCProductInputAdapter
 *
 * Builds a Merchant API ProductInput directly from a WooCommerce product.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Product
 */
class WCProductInputAdapter {

	use PluginHelper;

	public const AVAILABILITY_IN_STOCK     = 'in_stock';
	public const AVAILABILITY_OUT_OF_STOCK = 'out_of_stock';
	public const AVAILABILITY_BACKORDER    = 'backorder';

	public const IMAGE_SIZE_FULL = 'full';

	/** @var WC_Product */
	protected $wc_product;

	/** @var WC_Product|null Parent product when $wc_product is a variation. */
	protected $parent_wc_product;

	/** @var string */
	protected $offer_id;

	/** @var string */
	protected $content_language;

	/** @var string */
	protected $feed_label;

	/** @var bool */
	protected $tax_excluded = false;

	/** @var array */
	protected $attributes = [];

	/** @var string[] Target countries to add shipping entries for. */
	protected $shipping_countries = [];

	/**
	 * WCProductInputAdapter constructor.
	 *
	 * @param WC_Product      $product            The WooCommerce product.
	 * @param string          $target_country     Feed label.
	 * @param WC_Product|null $parent_product     Parent product, required when $product is a variation.
	 * @param string[]        $shipping_countries Additional target countries to add shipping entries for.
	 */
	public function __construct( WC_Product $product, string $target_country, ?WC_Product $parent_product = null, array $shipping_countries = [] ) {
		$this->wc_product         = $product;
		$this->parent_wc_product  = $parent_product;
		$this->feed_label         = $target_country;
		$this->shipping_countries = $shipping_countries;
		$this->tax_excluded       = $this->resolve_tax_excluded();

		$this->map_identity();
		$this->map_general_attributes();
		$this->map_images();
		$this->map_availability();
		$this->map_price();
		$this->map_shipping();
	}

	/**
	 * Return the assembled ProductInput.
	 *
	 * @return ProductInput
	 */
	public function get_product_input(): ProductInput {
		return new ProductInput(
			$this->offer_id,
			$this->content_language,
			$this->feed_label,
			$this->attributes
		);
	}

	/**
	 * Resolve the product identity (offer id and content language).
	 */
	protected function map_identity(): void {
		$this->offer_id         = $this->get_offer_id( $this->wc_product->get_id() );
		$this->content_language = empty( get_locale() ) ? 'en' : strtolower( substr( get_locale(), 0, 2 ) );
	}

	/**
	 * Build the Merchant Center offer id for a WooCommerce product id.
	 *
	 * @param int $product_id
	 *
	 * @return string
	 */
	protected function get_offer_id( int $product_id ): string {
		/** This filter is documented in src/Product/WCProductAdapter.php */
		return apply_filters( 'woocommerce_gla_get_google_product_offer_id', "{$this->get_slug()}_{$product_id}", $product_id );
	}

	/**
	 * Map the general product attributes (title, description, link, item group).
	 */
	protected function map_general_attributes(): void {
		$this->attributes['title']       = $this->wc_product->get_title();
		$this->attributes['description'] = $this->get_description();
		$this->attributes['link']        = $this->wc_product->get_permalink();

		if ( $this->is_variation() ) {
			$this->attributes['itemGroupId'] = (string) $this->parent_wc_product->get_id();
		}
	}

	/**
	 * Build the product description. Ported from WCProductAdapter to preserve behavior.
	 *
	 * @return string
	 */
	protected function get_description(): string {
		$use_short_description = apply_filters( 'woocommerce_gla_use_short_description', false );

		$description = ! empty( $this->wc_product->get_description() ) && ! $use_short_description
			? $this->wc_product->get_description()
			: $this->wc_product->get_short_description();

		if ( $this->is_variation() ) {
			$parent_description = ! empty( $this->parent_wc_product->get_description() ) && ! $use_short_description
				? $this->parent_wc_product->get_description()
				: $this->parent_wc_product->get_short_description();
			$new_line           = ! empty( $description ) && ! empty( $parent_description ) ? PHP_EOL : '';
			$description        = $parent_description . $new_line . $description;
		}

		$apply_shortcodes = apply_filters( 'woocommerce_gla_product_description_apply_shortcodes', false, $this->wc_product );
		$description      = $apply_shortcodes ? do_shortcode( $description ) : strip_shortcodes( $description );

		$description = mb_convert_encoding( $description, 'UTF-8', 'UTF-8' );
		$description = preg_replace( '/[\x00-\x08\x0B\x0C\x0E-\x1F\x80-\x9F]/u', '', $description );

		$kses_allowed_tags = array_fill_keys( array_keys( wp_kses_allowed_html( 'post' ) ), [] );
		$description       = wp_kses( $description, $kses_allowed_tags );
		$description       = mb_substr( $description, 0, 5000, 'utf-8' );

		return apply_filters( 'woocommerce_gla_product_attribute_value_description', $description, $this->wc_product );
	}

	/**
	 * Map the product images.
	 */
	protected function map_images(): void {
		$image_id          = $this->wc_product->get_image_id();
		$gallery_image_ids = $this->wc_product->get_gallery_image_ids() ?: [];

		if ( $this->is_variation() ) {
			$image_id              = $image_id ?? $this->parent_wc_product->get_image_id();
			$parent_gallery_images = $this->parent_wc_product->get_gallery_image_ids() ?: [];
			$gallery_image_ids     = ! empty( $gallery_image_ids ) ? $gallery_image_ids : $parent_gallery_images;
		}

		// Promote the first gallery image to the main image when none is set.
		if ( empty( $image_id ) && ! empty( $gallery_image_ids[0] ) ) {
			$image_id = $gallery_image_ids[0];
			unset( $gallery_image_ids[0] );
		}

		$image_link = wp_get_attachment_image_url( $image_id, self::IMAGE_SIZE_FULL, false );
		if ( ! empty( $image_link ) ) {
			$this->attributes['imageLink'] = $image_link;
		}

		$gallery_links = array_map(
			function ( $gallery_image_id ) {
				return wp_get_attachment_image_url( $gallery_image_id, self::IMAGE_SIZE_FULL, false );
			},
			$gallery_image_ids
		);
		$gallery_links = array_values( array_unique( array_filter( $gallery_links ), SORT_REGULAR ) );
		$gallery_links = array_slice( $gallery_links, 0, 10 );

		if ( ! empty( $gallery_links ) ) {
			$this->attributes['additionalImageLinks'] = $gallery_links;
		}
	}

	/**
	 * Map the stock availability.
	 */
	protected function map_availability(): void {
		if ( ! $this->wc_product->is_in_stock() ) {
			$availability = self::AVAILABILITY_OUT_OF_STOCK;
		} elseif ( $this->wc_product->is_on_backorder( 1 ) ) {
			$availability = self::AVAILABILITY_BACKORDER;
		} else {
			$availability = self::AVAILABILITY_IN_STOCK;
		}

		$this->attributes['availability'] = $availability;
	}

	/**
	 * Map the regular price, applying tax inclusion/exclusion rules.
	 */
	protected function map_price(): void {
		$regular_price = $this->wc_product->get_regular_price();
		if ( '' === $regular_price ) {
			return;
		}

		$price = $this->tax_excluded
			? wc_get_price_excluding_tax( $this->wc_product, [ 'price' => $regular_price ] )
			: wc_get_price_including_tax( $this->wc_product, [ 'price' => $regular_price ] );

		$price = apply_filters( 'woocommerce_gla_product_attribute_value_price', $price, $this->wc_product, $this->tax_excluded );

		$this->attributes['price'] = $this->to_money( (float) $price, get_woocommerce_currency() );
	}

	/**
	 * Map the shipping attributes.
	 */
	protected function map_shipping(): void {
		$is_virtual = $this->is_virtual();

		$countries = array_values( array_unique( array_filter( array_merge( [ $this->feed_label ], $this->shipping_countries ) ) ) );

		$shipping = [];
		foreach ( $countries as $country ) {
			$entry = [ 'country' => $country ];

			// Virtual products override any country shipping cost with zero.
			if ( $is_virtual ) {
				$entry['price'] = $this->to_money( 0.0, get_woocommerce_currency() );
			}

			$shipping[] = $entry;
		}

		if ( ! empty( $shipping ) ) {
			$this->attributes['shipping'] = $shipping;
		}

		// Dimensions, weight and label only apply to physical products.
		if ( $is_virtual ) {
			return;
		}

		$this->map_shipping_dimensions();
		$this->map_shipping_weight();

		$shipping_class = $this->wc_product->get_shipping_class();
		if ( ! empty( $shipping_class ) ) {
			$this->attributes['shippingLabel'] = $shipping_class;
		}
	}

	/**
	 * Map the shipping dimensions when length, width and height are all set.
	 */
	protected function map_shipping_dimensions(): void {
		$unit = apply_filters( 'woocommerce_gla_dimension_unit', get_option( 'woocommerce_dimension_unit' ) );
		if ( ! in_array( $unit, [ 'in', 'cm' ], true ) ) {
			$unit = 'cm';
		}

		$length = wc_get_dimension( (float) $this->wc_product->get_length(), $unit );
		$width  = wc_get_dimension( (float) $this->wc_product->get_width(), $unit );
		$height = wc_get_dimension( (float) $this->wc_product->get_height(), $unit );

		if ( $length > 0 && $width > 0 && $height > 0 ) {
			$this->attributes['shippingLength'] = [
				'value' => $length,
				'unit'  => $unit,
			];
			$this->attributes['shippingWidth']  = [
				'value' => $width,
				'unit'  => $unit,
			];
			$this->attributes['shippingHeight'] = [
				'value' => $height,
				'unit'  => $unit,
			];
		}
	}

	/**
	 * Map the shipping weight.
	 */
	protected function map_shipping_weight(): void {
		$weight = (float) $this->wc_product->get_weight();
		if ( $weight <= 0 ) {
			return;
		}

		$unit = apply_filters( 'woocommerce_gla_weight_unit', get_option( 'woocommerce_weight_unit' ) );
		if ( ! in_array( $unit, [ 'g', 'lbs', 'oz' ], true ) ) {
			$unit = 'g';
		}

		$weight = wc_get_weight( $weight, $unit );

		// Google Merchant Center uses lb rather than lbs.
		if ( 'lbs' === $unit ) {
			$unit = 'lb';
		}

		$this->attributes['shippingWeight'] = [
			'value' => $weight,
			'unit'  => $unit,
		];
	}

	/**
	 * Whether the product is virtual.
	 *
	 * @return bool
	 */
	protected function is_virtual(): bool {
		$is_virtual = $this->wc_product->is_virtual();

		/** This filter is documented in src/Product/WCProductAdapter.php */
		$is_virtual = apply_filters( 'woocommerce_gla_product_property_value_is_virtual', $is_virtual, $this->wc_product );

		return false !== $is_virtual;
	}

	/**
	 * Whether tax should be excluded from the price for this product's market.
	 *
	 * @return bool
	 */
	protected function resolve_tax_excluded(): bool {
		$tax_excluded = in_array( $this->feed_label, [ 'US', 'CA' ], true );

		return boolval( apply_filters( 'woocommerce_gla_tax_excluded', $tax_excluded ) );
	}

	/**
	 * Convert a decimal amount and currency into a MAPI money value.
	 *
	 * @param float  $value
	 * @param string $currency
	 *
	 * @return array
	 */
	protected function to_money( float $value, string $currency ): array {
		return [
			'amountMicros' => (string) (int) round( $value * 1000000 ),
			'currencyCode' => $currency,
		];
	}

	/**
	 * @return bool
	 */
	protected function is_variation(): bool {
		return $this->wc_product instanceof WC_Product_Variation;
	}
}
