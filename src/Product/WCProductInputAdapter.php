<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Product;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Models\ProductInput;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\Integration\WPML;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\AttributeMapping\AttributeMappingHelper;
use DateInterval;
use WC_DateTime;
use WC_Product;
use WC_Product_Variable;
use WC_Product_Variation;
use WC_Tax;

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

	public const AVAILABILITY_IN_STOCK     = 'IN_STOCK';
	public const AVAILABILITY_OUT_OF_STOCK = 'OUT_OF_STOCK';
	public const AVAILABILITY_BACKORDER    = 'BACKORDER';

	public const IMAGE_SIZE_FULL = 'full';

	/**
	 * Supported per-product Google attribute ids (WooCommerce attribute keys).
	 * Translated to their MAPI keys and types in set_attribute().
	 */
	protected const SUPPORTED_ATTRIBUTES = [
		'gtin',
		'mpn',
		'brand',
		'condition',
		'gender',
		'size',
		'sizeSystem',
		'sizeType',
		'color',
		'material',
		'pattern',
		'ageGroup',
		'multipack',
		'isBundle',
		'availabilityDate',
		'adult',
	];

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

	/** @var string Country used for shipping entries and tax rules; may differ from the feed label. */
	protected $target_country;

	/** @var string Optional ISO 639-1 language override for the content language. */
	protected $language = '';

	/** @var string Optional ISO 4217 currency code overriding the store currency. */
	protected $currency_override = '';

	/** @var WPML|null WPML integration used for currency conversion when a currency override is set. */
	protected $wpml = null;

	/** @var float Fixed market exchange rate used when WPML conversion is unavailable; 0.0 when unset. */
	protected $exchange_rate = 0.0;

	/** @var bool */
	protected $tax_excluded = false;

	/** @var array */
	protected $attributes = [];

	/** @var array Merchant API custom attributes to attach to the product input. */
	protected $custom_attributes = [];

	/** @var string[] Target countries to add shipping entries for. */
	protected $shipping_countries = [];

	/** @var array Per-product Google attribute values keyed by attribute id. */
	protected $gla_attributes = [];

	/** @var array Attribute mapping rules to apply. */
	protected $mapping_rules = [];

	/** @var int[] Product (or parent) category term ids, used for rule conditions. */
	protected $product_category_ids = [];

	/**
	 * WCProductInputAdapter constructor.
	 *
	 * @param WC_Product      $product            The WooCommerce product.
	 * @param string          $target_country     Country used for shipping and tax rules; also the default feed label.
	 * @param WC_Product|null $parent_product     Parent product, required when $product is a variation.
	 * @param string[]        $shipping_countries Additional target countries to add shipping entries for.
	 * @param array           $gla_attributes     Per-product Google attribute values.
	 * @param array           $mapping_rules      Attribute mapping rules to apply.
	 * @param string          $feed_label         Optional feed label when it differs from the target country.
	 * @param string          $language           Optional ISO 639-1 language code overriding the site locale.
	 * @param string          $currency_override  Optional ISO 4217 currency code overriding the store currency.
	 * @param WPML|null       $wpml               WPML integration used for currency conversion when a currency override is set.
	 * @param float           $exchange_rate      Fixed market exchange rate used to convert prices when WPML conversion is unavailable; 0.0 when unset.
	 *
	 * @throws InvalidValue When $product is a variation and a valid parent product is not provided.
	 */
	public function __construct( WC_Product $product, string $target_country, ?WC_Product $parent_product = null, array $shipping_countries = [], array $gla_attributes = [], array $mapping_rules = [], string $feed_label = '', string $language = '', string $currency_override = '', ?WPML $wpml = null, float $exchange_rate = 0.0 ) {
		$this->wc_product         = $product;
		$this->parent_wc_product  = $parent_product;
		$this->target_country     = $target_country;
		$this->feed_label         = '' !== $feed_label ? $feed_label : $target_country;
		$this->language           = $language;
		$this->currency_override  = $currency_override;
		$this->wpml               = $wpml;
		$this->exchange_rate      = $exchange_rate;
		$this->shipping_countries = $shipping_countries;
		$this->gla_attributes     = $gla_attributes;
		$this->mapping_rules      = $mapping_rules;
		$this->tax_excluded       = $this->resolve_tax_excluded();

		if ( $this->is_variation() && ! $this->parent_wc_product instanceof WC_Product_Variable ) {
			throw InvalidValue::not_instance_of( WC_Product_Variable::class, 'parent_product' );
		}

		$base_product_id            = $this->is_variation() ? $this->parent_wc_product->get_id() : $this->wc_product->get_id();
		$this->product_category_ids = wc_get_product_term_ids( $base_product_id, 'product_cat' );

		$this->map_identity();
		$this->map_general_attributes();
		$this->map_images();
		$this->map_availability();
		$this->map_price();
		$this->map_sale_price();
		$this->map_shipping();
		$this->map_product_types();

		// Order matters: per-product values override rules, gtin overrides those, the override filter wins.
		$this->map_attribute_mapping_rules();
		$this->map_gla_attributes();
		$this->map_gtin();
		$this->override_attributes();

		// availability, condition, gender, ageGroup and sizeType (remapped to the plural
		// sizeTypes MAPI key by set_attribute()) are all Merchant API enums (IN_STOCK, NEW,
		// MALE, ADULT, REGULAR, etc.), but their values can arrive lowercase from a mapping
		// rule, merchant-configured attribute meta, or the override filter (e.g. the
		// pre-orders integration's `preorder`) — all of which store/accept the lowercase
		// option keys used by the admin UI. Uppercase them here regardless of source.
		$this->normalize_enum_attributes();
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
			$this->attributes,
			$this->custom_attributes
		);
	}

	/**
	 * Get the Merchant API custom attributes that will be attached to the product input.
	 *
	 * @return array
	 */
	public function get_custom_attributes(): array {
		return $this->custom_attributes;
	}

	/**
	 * Replace the Merchant API custom attributes attached to the product input.
	 *
	 * Each entry is a plain array matching the Merchant API CustomAttribute shape, e.g.
	 * `[ 'name' => 'foo', 'value' => 'bar' ]` or
	 * `[ 'name' => 'foo', 'groupValues' => [ [ 'name' => 'k', 'value' => 'v' ] ] ]`.
	 *
	 * @param array $custom_attributes
	 */
	public function set_custom_attributes( array $custom_attributes ): void {
		$this->custom_attributes = array_values( $custom_attributes );
	}

	/**
	 * Append a single Merchant API custom attribute to the product input.
	 *
	 * Intended for use from the `woocommerce_gla_product_attribute_values` filter, where the
	 * adapter is passed as the third argument, so extensions can attach custom attributes
	 * without modifying core GLA code.
	 *
	 * @param array $custom_attribute A plain array matching the Merchant API CustomAttribute shape.
	 */
	public function add_custom_attribute( array $custom_attribute ): void {
		$this->custom_attributes[] = $custom_attribute;
	}

	/**
	 * Resolve the product identity (offer id and content language).
	 */
	protected function map_identity(): void {
		$this->offer_id = $this->get_offer_id( $this->wc_product->get_id() );

		if ( '' !== $this->language ) {
			$this->content_language = $this->language;
			return;
		}

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
	 * Normalise availability, condition, gender, ageGroup and sizeTypes to the Merchant API's
	 * uppercase enum casing (e.g. IN_STOCK, NEW, MALE, ADULT, REGULAR). map_availability()
	 * already emits an uppercase value, but all of these can also arrive lowercase through
	 * attribute mapping or the override filter (e.g. the pre-orders integration's lowercase
	 * `preorder`, or the lowercase option keys the admin UI stores for the others), so every
	 * path needs uppercasing here rather than relying on the Merchant API normalising it.
	 * Note: sizeType (singular) is remapped to the plural sizeTypes array key by
	 * set_attribute(), which is why this operates on sizeTypes rather than sizeType.
	 */
	protected function normalize_enum_attributes(): void {
		foreach ( [ 'availability', 'condition', 'gender', 'ageGroup' ] as $attribute_id ) {
			if ( ! empty( $this->attributes[ $attribute_id ] ) && is_string( $this->attributes[ $attribute_id ] ) ) {
				$this->attributes[ $attribute_id ] = strtoupper( $this->attributes[ $attribute_id ] );
			}
		}

		if ( ! empty( $this->attributes['sizeTypes'] ) && is_array( $this->attributes['sizeTypes'] ) ) {
			$this->attributes['sizeTypes'] = array_map(
				function ( $size_type ) {
					return is_string( $size_type ) ? strtoupper( $size_type ) : $size_type;
				},
				$this->attributes['sizeTypes']
			);
		}
	}

	/**
	 * Map the regular price, applying the target country's tax
	 * inclusion/exclusion rules.
	 *
	 * With a currency override set, the price is always the converted value
	 * (WPML conversion when available, otherwise the market's fixed exchange
	 * rate); when no converted value is available the price is left unset, so
	 * a store-currency amount is never submitted under a non-store-currency
	 * feed label.
	 */
	protected function map_price(): void {
		$regular_price = $this->wc_product->get_regular_price();

		if ( '' !== $this->currency_override ) {
			$converted = null !== $this->wpml
				? $this->wpml->get_product_price_in_currency( $this->wc_product, $this->currency_override )
				: null;

			if ( null === $converted && $this->exchange_rate > 0 && '' !== $regular_price ) {
				$converted = (float) $regular_price * $this->exchange_rate;
			}

			// No price in the override currency: leave it unset so this currency's feed is skipped,
			// not emitted with the store-currency price mislabelled.
			if ( null === $converted ) {
				return;
			}

			$price = $this->apply_tax_for_target_country( (float) $converted );

			/** This filter is documented in src/Product/WCProductAdapter.php */
			$price = apply_filters( 'woocommerce_gla_product_attribute_value_price', $price, $this->wc_product, $this->tax_excluded );

			$this->attributes['price'] = $this->to_money( (float) $price, strtoupper( $this->currency_override ) );
			return;
		}

		if ( '' === $regular_price ) {
			return;
		}

		$price = $this->apply_tax_for_target_country( (float) $regular_price );

		$price = apply_filters( 'woocommerce_gla_product_attribute_value_price', $price, $this->wc_product, $this->tax_excluded );

		$this->attributes['price'] = $this->to_money( (float) $price, get_woocommerce_currency() );
	}

	/**
	 * Apply tax to a price amount per the target country's own rate.
	 *
	 * Product sync runs in a background context where WooCommerce's price
	 * helpers resolve the tax location to the store base address, never the
	 * entry's target country, so the rate is looked up for the target country
	 * directly. A country with no rate row in the tax tables yields zero tax,
	 * matching how WooCommerce treats an unconfigured location.
	 *
	 * @param float $price Price amount in the entry's currency, as entered
	 *                     (inclusive of the store base rate when the store
	 *                     enters prices inclusive of tax).
	 *
	 * @return float
	 */
	protected function apply_tax_for_target_country( float $price ): float {
		if ( ! $this->wc_product->is_taxable() ) {
			return $price;
		}

		// Prices entered inclusive of tax carry the store's base rate; remove
		// it to reach the net amount before the target country's rate applies.
		if ( wc_prices_include_tax() ) {
			$base_rates = WC_Tax::get_base_tax_rates( $this->wc_product->get_tax_class( 'unfiltered' ) );
			$price     -= array_sum( WC_Tax::calc_tax( $price, $base_rates, true ) );
		}

		if ( $this->tax_excluded ) {
			return round( $price, wc_get_price_decimals() );
		}

		$rates = WC_Tax::find_rates(
			[
				'country'   => $this->target_country,
				'tax_class' => $this->wc_product->get_tax_class(),
			]
		);
		$taxes = WC_Tax::calc_tax( $price, $rates, false );

		$taxes_total = 'yes' === get_option( 'woocommerce_tax_round_at_subtotal' )
			? array_sum( $taxes )
			: array_sum( array_map( 'wc_round_tax_total', $taxes ) );

		return round( $price + $taxes_total, wc_get_price_decimals() );
	}

	/**
	 * Map the sale price and sale price effective date, applying the target
	 * country's tax inclusion/exclusion rules.
	 *
	 * With a currency override set, the sale price is always the converted
	 * value (WPML conversion when available, otherwise the market's fixed
	 * exchange rate); when no converted value is available the sale price is
	 * left unset, so a store-currency amount is never submitted under a
	 * non-store-currency feed label. A sale that has already ended is never
	 * converted or sent, and the effective dates come from the base product.
	 */
	protected function map_sale_price(): void {
		// Grab the sale price of the base product. Some plugins (Dynamic pricing as an
		// example) filter the active price, but not the sale price. If the active price
		// < the regular price treat it as a sale price.
		$regular_price = $this->wc_product->get_regular_price();
		$sale_price    = $this->wc_product->get_sale_price();
		$active_price  = $this->wc_product->get_price();
		if (
			( empty( $sale_price ) && $active_price < $regular_price ) ||
			( ! empty( $sale_price ) && $active_price < $sale_price )
		) {
			$sale_price = $active_price;
		}

		if ( '' === $sale_price ) {
			return;
		}

		// If the sale price dates no longer apply, make sure we don't include a sale price.
		$now                 = new WC_DateTime();
		$sale_price_end_date = $this->wc_product->get_date_on_sale_to();
		if ( ! empty( $sale_price_end_date ) && $sale_price_end_date < $now ) {
			return;
		}

		if ( '' !== $this->currency_override ) {
			$converted = null !== $this->wpml
				? $this->wpml->get_product_sale_price_in_currency( $this->wc_product, $this->currency_override )
				: null;

			if ( null === $converted && $this->exchange_rate > 0 ) {
				$converted = (float) $sale_price * $this->exchange_rate;
			}

			// No sale price in the override currency: leave it unset so a
			// store-currency amount is never emitted under this feed label.
			if ( null === $converted ) {
				return;
			}

			$sale_price = $converted;
		}

		$sale_price = $this->apply_tax_for_target_country( (float) $sale_price );

		/** This filter is documented in src/Product/WCProductAdapter.php */
		$sale_price = apply_filters( 'woocommerce_gla_product_attribute_value_sale_price', $sale_price, $this->wc_product, $this->tax_excluded );

		$this->attributes['salePrice'] = $this->to_money( (float) $sale_price, $this->effective_currency() );

		$effective_date = $this->get_sale_price_effective_date();
		if ( null !== $effective_date ) {
			$this->attributes['salePriceEffectiveDate'] = $effective_date;
		}
	}

	/**
	 * Return the sale effective dates for the WooCommerce product as a Merchant API
	 * Interval (`startTime`/`endTime` RFC3339 timestamps), omitting either bound when
	 * unset. Date-branch logic ported from WCProductAdapter::get_wc_product_sale_price_effective_date.
	 *
	 * @return array|null
	 */
	protected function get_sale_price_effective_date(): ?array {
		$start_date = $this->wc_product->get_date_on_sale_from();
		$end_date   = $this->wc_product->get_date_on_sale_to();

		$now = new WC_DateTime();
		// if we have a sale end date in the future, but no start date, set the start date to now()
		if (
			! empty( $end_date ) &&
			$end_date > $now &&
			empty( $start_date )
		) {
			$start_date = $now;
		}
		// if we have a sale start date in the past, but no end date, do not include the start date.
		if (
			! empty( $start_date ) &&
			$start_date < $now &&
			empty( $end_date )
		) {
			$start_date = null;
		}
		// if we have a start date in the future, but no end date, assume a one-day sale.
		if (
			! empty( $start_date ) &&
			$start_date > $now &&
			empty( $end_date )
		) {
			$end_date = clone $start_date;
			$end_date->add( new DateInterval( 'P1D' ) );
		}

		if ( empty( $start_date ) && empty( $end_date ) ) {
			return null;
		}

		$effective_date = [];
		if ( ! empty( $start_date ) ) {
			$effective_date['startTime'] = (string) $start_date;
		}
		if ( ! empty( $end_date ) ) {
			$effective_date['endTime'] = (string) $end_date;
		}

		return $effective_date;
	}

	/**
	 * Map the product's categories to the productTypes attribute (capped at 10),
	 * ported from WCProductAdapter::map_product_categories.
	 */
	protected function map_product_types(): void {
		$product_types = [];
		foreach ( array_unique( $this->product_category_ids ) as $category_id ) {
			if ( ! is_int( $category_id ) ) {
				continue;
			}

			$path = $this->category_path( $category_id );
			if ( '' !== $path ) {
				$product_types[] = $path;
			}
		}

		if ( ! empty( $product_types ) ) {
			$this->attributes['productTypes'] = array_slice( $product_types, 0, 10 );
		}
	}

	/**
	 * Build the "Parent > Child" category path for a category term id.
	 *
	 * @param int $category_id
	 *
	 * @return string
	 */
	protected function category_path( int $category_id ): string {
		$names = [];
		do {
			$term = get_term_by( 'id', $category_id, 'product_cat', 'ARRAY_A' );
			if ( ! $term ) {
				break;
			}

			$names[]     = $term['name'];
			$category_id = (int) $term['parent'];
		} while ( ! empty( $term['parent'] ) );

		return implode( ' > ', array_reverse( $names ) );
	}

	/**
	 * Map the shipping attributes.
	 */
	protected function map_shipping(): void {
		$is_virtual = $this->is_virtual();

		$countries = array_values( array_unique( array_filter( array_merge( [ $this->target_country ], $this->shipping_countries ) ) ) );

		$shipping = [];
		foreach ( $countries as $country ) {
			$entry = [ 'country' => $country ];

			// Virtual products override any country shipping cost with zero.
			if ( $is_virtual ) {
				$entry['price'] = $this->to_money( 0.0, $this->effective_currency() );
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
	 * Map the per-product Google attribute values (brand, gtin, color, size, etc.).
	 */
	protected function map_gla_attributes(): void {
		foreach ( $this->gla_attributes as $attribute_id => $value ) {
			if ( ! in_array( $attribute_id, self::SUPPORTED_ATTRIBUTES, true ) ) {
				continue;
			}

			/** This filter is documented in src/Product/WCProductAdapter.php */
			$value = apply_filters( "woocommerce_gla_product_attribute_value_{$attribute_id}", $value, $this->wc_product );

			if ( null === $value || '' === $value ) {
				continue;
			}

			$this->set_attribute( $attribute_id, $value );
		}
	}

	/**
	 * Apply the custom attribute mapping rules, resolving each rule's source value
	 * for products that match the rule's category conditions.
	 */
	protected function map_attribute_mapping_rules(): void {
		foreach ( $this->mapping_rules as $rule ) {
			$attribute_id = $rule['attribute'] ?? '';
			if ( ! in_array( $attribute_id, self::SUPPORTED_ATTRIBUTES, true ) ) {
				continue;
			}

			if ( ! $this->rule_match_conditions( $rule ) ) {
				continue;
			}

			/** This filter is documented in src/Product/WCProductAdapter.php */
			$value = apply_filters(
				"woocommerce_gla_product_attribute_value_{$attribute_id}",
				$this->get_source( (string) ( $rule['source'] ?? '' ) ),
				$this->wc_product
			);

			if ( null === $value || '' === $value ) {
				continue;
			}

			$this->set_attribute( $attribute_id, $value );
		}
	}

	/**
	 * Whether the product matches a mapping rule's category conditions.
	 *
	 * @param array $rule
	 *
	 * @return bool
	 */
	protected function rule_match_conditions( array $rule ): bool {
		$condition_type = $rule['category_condition_type'] ?? '';

		if ( AttributeMappingHelper::CATEGORY_CONDITION_TYPE_ALL === $condition_type ) {
			return true;
		}

		$categories   = explode( ',', (string) ( $rule['categories'] ?? '' ) );
		$category_ids = array_map( 'strval', $this->product_category_ids );
		$contains     = ! empty( array_intersect( $categories, $category_ids ) );

		if ( AttributeMappingHelper::CATEGORY_CONDITION_TYPE_ONLY === $condition_type ) {
			return $contains;
		}

		return ! $contains;
	}

	/**
	 * Resolve a mapping rule source to its value: a product field, taxonomy term,
	 * custom attribute, or a static value.
	 *
	 * @param string $source
	 *
	 * @return string
	 */
	protected function get_source( string $source ): string {
		$separator = strpos( $source, ':' );
		if ( false === $separator ) {
			return $source;
		}

		$type  = substr( $source, 0, $separator );
		$value = substr( $source, $separator + 1 );

		switch ( $type ) {
			case 'product':
				return $this->get_product_field( $value );
			case 'taxonomy':
				return $this->get_product_taxonomy( $value );
			case 'attribute':
				return $this->get_custom_attribute( $value );
			default:
				return $source;
		}
	}

	/**
	 * Get a core product field value for a mapping rule source.
	 *
	 * @param string $field
	 *
	 * @return string
	 */
	protected function get_product_field( string $field ): string {
		if ( 'weight_with_unit' === $field ) {
			$weight = $this->wc_product->get_weight();
			return $weight ? $weight . ' ' . get_option( 'woocommerce_weight_unit' ) : '';
		}

		$getter = 'get_' . $field;
		if ( is_callable( [ $this->wc_product, $getter ] ) ) {
			$value = $this->wc_product->$getter();
			return is_scalar( $value ) ? (string) $value : '';
		}

		return '';
	}

	/**
	 * Get the first taxonomy term value for a mapping rule source.
	 *
	 * @param string $taxonomy
	 *
	 * @return string
	 */
	protected function get_product_taxonomy( string $taxonomy ): string {
		if ( $this->is_variation() ) {
			$values = $this->wc_product->get_attribute( $taxonomy );

			if ( ! $values ) {
				$values = $this->get_taxonomy_term_names( $this->wc_product->get_id(), $taxonomy );
			}

			if ( ! $values && $this->parent_wc_product instanceof WC_Product ) {
				$values = $this->parent_wc_product->get_attribute( $taxonomy );
				if ( ! $values ) {
					$values = $this->get_taxonomy_term_names( $this->parent_wc_product->get_id(), $taxonomy );
				}
			}

			if ( is_string( $values ) ) {
				$values = explode( ', ', $values );
			}
		} else {
			$values = $this->get_taxonomy_term_names( $this->wc_product->get_id(), $taxonomy );
		}

		if ( empty( $values ) || is_wp_error( $values ) ) {
			return '';
		}

		return (string) $values[0];
	}

	/**
	 * Get the term names for a product taxonomy.
	 *
	 * @param int    $product_id
	 * @param string $taxonomy
	 *
	 * @return string[]
	 */
	protected function get_taxonomy_term_names( int $product_id, string $taxonomy ): array {
		$terms = wc_get_product_terms( $product_id, $taxonomy );

		return wp_list_pluck( $terms, 'name' );
	}

	/**
	 * Get the first custom attribute or meta value for a mapping rule source.
	 *
	 * @param string $attribute_name
	 *
	 * @return string
	 */
	protected function get_custom_attribute( string $attribute_name ): string {
		$value = $this->wc_product->get_attribute( $attribute_name );

		if ( ! $value ) {
			$value = $this->wc_product->get_meta( $attribute_name );
		}

		if ( ! is_scalar( $value ) ) {
			return '';
		}

		$values = array_filter( array_map( 'trim', explode( WC_DELIMITER, (string) $value ) ) );

		return empty( $values ) ? '' : (string) reset( $values );
	}

	/**
	 * Apply the attribute-values override filter, which takes precedence over every
	 * other attribute mapping.
	 */
	protected function override_attributes(): void {
		/**
		 * Filters the attribute values to override on the Merchant API product input.
		 *
		 * Merchant API counterpart of the same filter in WCProductAdapter. Note: the
		 * third parameter is now a WCProductInputAdapter (Merchant API) rather than a
		 * WCProductAdapter (Content API), and overrides must use Merchant API attribute
		 * keys and value shapes.
		 *
		 * @param array                 $overrides  Attribute values keyed by Merchant API attribute key.
		 * @param WC_Product            $wc_product The WooCommerce product.
		 * @param WCProductInputAdapter $adapter    The product input adapter.
		 */
		$overrides = apply_filters( 'woocommerce_gla_product_attribute_values', [], $this->wc_product, $this );

		if ( is_array( $overrides ) ) {
			$this->attributes = array_merge( $this->attributes, $overrides );
		}
	}

	/**
	 * Set a single Google attribute, mapping it to its MAPI key and type.
	 *
	 * @param string $attribute_id
	 * @param mixed  $value
	 */
	protected function set_attribute( string $attribute_id, $value ): void {
		switch ( $attribute_id ) {
			case 'gtin':
				$this->attributes['gtins'] = [ (string) $value ];
				break;
			case 'sizeType':
				$this->attributes['sizeTypes'] = [ (string) $value ];
				break;
			case 'multipack':
				$this->attributes['multipack'] = (string) (int) $value;
				break;
			case 'isBundle':
			case 'adult':
				$this->attributes[ $attribute_id ] = wc_string_to_bool( $value );
				break;
			default:
				$this->attributes[ $attribute_id ] = (string) $value;
		}
	}

	/**
	 * Map the WooCommerce core global unique id (GTIN) when available. Ported from WCProductAdapter.
	 */
	protected function map_gtin(): void {
		// Core global unique ID field was added in WC 9.2.
		if ( ! method_exists( $this->wc_product, 'get_global_unique_id' ) ) {
			return;
		}

		$gtin = preg_replace( '/[^0-9]/', '', (string) $this->wc_product->get_global_unique_id() );
		if ( ! empty( $gtin ) ) {
			$this->attributes['gtins'] = [ $gtin ];
		}
	}

	/**
	 * Whether tax should be excluded from the price for this product's market.
	 *
	 * @return bool
	 */
	protected function resolve_tax_excluded(): bool {
		$tax_excluded = in_array( $this->target_country, [ 'US', 'CA' ], true );

		return boolval( apply_filters( 'woocommerce_gla_tax_excluded', $tax_excluded ) );
	}

	/**
	 * The currency this product input is priced in: the override when set, the store currency otherwise.
	 *
	 * @return string
	 */
	protected function effective_currency(): string {
		return '' !== $this->currency_override ? strtoupper( $this->currency_override ) : get_woocommerce_currency();
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
