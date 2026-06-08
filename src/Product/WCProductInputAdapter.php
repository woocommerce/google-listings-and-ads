<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Product;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Models\ProductInput;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\AttributeMapping\AttributeMappingHelper;
use WC_Product;
use WC_Product_Variable;
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

	/** @var bool */
	protected $tax_excluded = false;

	/** @var array */
	protected $attributes = [];

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
	 * @param string          $target_country     Feed label.
	 * @param WC_Product|null $parent_product     Parent product, required when $product is a variation.
	 * @param string[]        $shipping_countries Additional target countries to add shipping entries for.
	 * @param array           $gla_attributes     Per-product Google attribute values.
	 * @param array           $mapping_rules      Attribute mapping rules to apply.
	 *
	 * @throws InvalidValue When $product is a variation and a valid parent product is not provided.
	 */
	public function __construct( WC_Product $product, string $target_country, ?WC_Product $parent_product = null, array $shipping_countries = [], array $gla_attributes = [], array $mapping_rules = [] ) {
		$this->wc_product         = $product;
		$this->parent_wc_product  = $parent_product;
		$this->feed_label         = $target_country;
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
		$this->map_shipping();
		$this->map_product_types();

		// Order matters: per-product values override rules, gtin overrides those, the override filter wins.
		$this->map_attribute_mapping_rules();
		$this->map_gla_attributes();
		$this->map_gtin();
		$this->override_attributes();
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

		$categories = explode( ',', (string) ( $rule['categories'] ?? '' ) );
		$contains   = ! empty( array_intersect( $categories, $this->product_category_ids ) );

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
		if ( ! $separator ) {
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
		/** This filter is documented in src/Product/WCProductAdapter.php */
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
