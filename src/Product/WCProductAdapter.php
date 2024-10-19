<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Product;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\AttributeMapping\AttributeMappingHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\Condition;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\SizeSystem;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\SizeType;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\AgeGroup;
use Automattic\WooCommerce\GoogleListingsAndAds\Validator\GooglePriceConstraint;
use Automattic\WooCommerce\GoogleListingsAndAds\Validator\ImageUrlConstraint;
use Automattic\WooCommerce\GoogleListingsAndAds\Validator\Validatable;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\Price as GooglePrice;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\Product as GoogleProduct;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\ProductShipping as GoogleProductShipping;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\ProductShippingDimension as GoogleProductShippingDimension;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\ProductShippingWeight as GoogleProductShippingWeight;
use DateInterval;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use WC_DateTime;
use WC_Product;
use WC_Product_Variable;
use WC_Product_Variation;

defined( 'ABSPATH' ) || exit;

/**
 * Class WCProductAdapter
 *
 * This class adapts the WooCommerce Product class to the Google's Product class by mapping their attributes.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Product
 */
class WCProductAdapter extends GoogleProduct implements Validatable {
	use PluginHelper;

	public const AVAILABILITY_IN_STOCK     = 'in_stock';
	public const AVAILABILITY_OUT_OF_STOCK = 'out_of_stock';
	public const AVAILABILITY_BACKORDER    = 'backorder';
	public const AVAILABILITY_PREORDER     = 'preorder';

	public const IMAGE_SIZE_FULL = 'full';

	public const CHANNEL_ONLINE = 'online';

	/**
	 * @var WC_Product WooCommerce product object
	 */
	protected $wc_product;

	/**
	 * @var WC_Product WooCommerce parent product object if $wc_product is a variation
	 */
	protected $parent_wc_product;

	/**
	 * @var bool Whether tax is excluded from product price
	 */
	protected $tax_excluded;

	/**
	 * @var array Product category ids
	 */
	protected $product_category_ids;

	/**
	 * Initialize this object's properties from an array.
	 *
	 * @param array $properties Used to seed this object's properties.
	 *
	 * @return void
	 *
	 * @throws InvalidValue When a WooCommerce product is not provided or it is invalid.
	 */
	public function mapTypes( $properties ) {
		if ( empty( $properties['wc_product'] ) || ! $properties['wc_product'] instanceof WC_Product ) {
			throw InvalidValue::not_instance_of( WC_Product::class, 'wc_product' );
		}

		// throw an exception if the parent product isn't provided and this is a variation
		if ( $properties['wc_product'] instanceof WC_Product_Variation &&
			( empty( $properties['parent_wc_product'] ) || ! $properties['parent_wc_product'] instanceof WC_Product_Variable )
		) {
			throw InvalidValue::not_instance_of( WC_Product_Variable::class, 'parent_wc_product' );
		}

		if ( empty( $properties['targetCountry'] ) ) {
			throw InvalidValue::is_empty( 'targetCountry' );
		}

		$this->wc_product        = $properties['wc_product'];
		$this->parent_wc_product = $properties['parent_wc_product'] ?? null;

		$mapping_rules  = $properties['mapping_rules'] ?? [];
		$gla_attributes = $properties['gla_attributes'] ?? [];

		// Google doesn't expect extra fields, so it's best to remove them
		unset( $properties['wc_product'] );
		unset( $properties['parent_wc_product'] );
		unset( $properties['gla_attributes'] );
		unset( $properties['mapping_rules'] );

		parent::mapTypes( $properties );
		$this->map_woocommerce_product();
		$this->map_attribute_mapping_rules( $mapping_rules );
		$this->map_gla_attributes( $gla_attributes );
		$this->map_gtin();

		// Allow users to override the product's attributes using a WordPress filter.
		$this->override_attributes();
	}

	/**
	 * Map the WooCommerce product attributes to the current class.
	 *
	 * @return void
	 */
	protected function map_woocommerce_product() {
		$this->setChannel( self::CHANNEL_ONLINE );

		$content_language = empty( get_locale() ) ? 'en' : strtolower( substr( get_locale(), 0, 2 ) ); // ISO 639-1.
		$this->setContentLanguage( $content_language );

		$this->map_wc_product_id()
			->map_wc_general_attributes()
			->map_product_categories()
			->map_wc_product_image( self::IMAGE_SIZE_FULL )
			->map_wc_availability()
			->map_wc_product_shipping()
			->map_wc_prices();
	}

	/**
	 * Overrides the product attributes by applying a filter and setting the provided values.
	 *
	 * @since 1.4.0
	 */
	protected function override_attributes() {
		/**
		 * Filters the list of overridden attributes to set for this product.
		 *
		 * Note: This filter takes precedence over any other filter that modify products attributes. Including
		 *       `woocommerce_gla_product_attribute_value_{$attribute_id}` defined in self::map_gla_attributes.
		 *
		 * @param array            $attributes An array of values for the product properties. All properties of the
		 *                                     `\Google\Service\ShoppingContent\Product` class can be set by providing
		 *                                     the property name as key and its value as array item.
		 *                                     For example:
		 *                                     [ 'imageLink' => 'https://example.com/image.jpg' ] overrides the product's
		 *                                     main image.
		 *
		 * @param WC_Product       $wc_product The WooCommerce product object.
		 * @param WCProductAdapter $this       The Adapted Google product object. All WooCommerce product properties
		 *                                     are already mapped to this object.
		 *
		 * @see \Google\Service\ShoppingContent\Product for the list of product properties that can be overriden.
		 * @see WCProductAdapter::map_gla_attributes for the docuementation of `woocommerce_gla_product_attribute_value_{$attribute_id}`
		 *                                           filter, which allows modifying some attributes such as GTIN, MPN, etc.
		 *
		 * @since 1.4.0
		 */
		$attributes = apply_filters( 'woocommerce_gla_product_attribute_values', [], $this->wc_product, $this );

		if ( ! empty( $attributes ) ) {
			parent::mapTypes( $attributes );
		}
	}

	/**
	 * Map the general WooCommerce product attributes.
	 *
	 * @return $this
	 */
	protected function map_wc_general_attributes() {
		$this->setTitle( $this->wc_product->get_title() );
		$this->setDescription( $this->get_wc_product_description() );
		$this->setLink( $this->wc_product->get_permalink() );

		// set item group id for variations
		if ( $this->is_variation() ) {
			$this->setItemGroupId( $this->parent_wc_product->get_id() );
		}
		return $this;
	}

	/**
	 * Map WooCommerce product categories to Google product types.
	 *
	 * @return $this
	 */
	protected function map_product_categories() {
		// set product type using merchants defined product categories
		$base_product_id = $this->is_variation() ? $this->parent_wc_product->get_id() : $this->wc_product->get_id();

		// Fetch only selected term ids without parents.
		$this->product_category_ids = wc_get_product_term_ids( $base_product_id, 'product_cat' );

		if ( ! empty( $this->product_category_ids ) ) {
			$google_product_types = self::convert_product_types( $this->product_category_ids );
			do_action(
				'woocommerce_gla_debug_message',
				sprintf(
					'Product category (ID: %s): %s.',
					$base_product_id,
					json_encode( $google_product_types )
				),
				__METHOD__
			);
			$google_product_types = array_slice( $google_product_types, 0, 10 );
			$this->setProductTypes( $google_product_types );
		}
		return $this;
	}
	/**
	 * Covert WooCommerce product categories to product_type, which follows Google requirements:
	 * https://support.google.com/merchants/answer/6324406?hl=en#
	 *
	 * @param int[] $category_ids
	 *
	 * @return array
	 */
	public static function convert_product_types( $category_ids ): array {
		$product_types = [];
		foreach ( array_unique( $category_ids ) as $category_id ) {
			if ( ! is_int( $category_id ) ) {
				continue;
			}

			$product_type = self::get_product_type_by_id( $category_id );
			array_push( $product_types, $product_type );
		}

		return $product_types;
	}

	/**
	 * Return category names including ancestors, separated by ">"
	 *
	 * @param int $category_id
	 *
	 * @return string
	 */
	protected static function get_product_type_by_id( int $category_id ): string {
		$category_names = [];
		do {
			$term = get_term_by( 'id', $category_id, 'product_cat', 'ARRAY_A' );
			array_push( $category_names, $term['name'] );
			$category_id = $term['parent'];
		} while ( ! empty( $term['parent'] ) );

		return implode( ' > ', array_reverse( $category_names ) );
	}

	/**
	 * Map the WooCommerce product ID.
	 *
	 * @return $this
	 */
	protected function map_wc_product_id(): WCProductAdapter {
		$this->setOfferId( self::get_google_product_offer_id( $this->get_slug(), $this->wc_product->get_id() ) );
		return $this;
	}

	/**
	 *
	 * @param string $slug
	 * @param int    $product_id
	 * @return string
	 */
	public static function get_google_product_offer_id( string $slug, int $product_id ): string {
		/**
		 * Filters a WooCommerce product ID to be used as the Merchant Center product ID.
		 *
		 * @param string $mc_product_id Default generated Merchant Center product ID.
		 * @param int    $product_id    WooCommerce product ID.
		 * @since 2.4.6
		 *
		 * @return string Merchant Center product ID corresponding to the given WooCommerce product ID.
		 */
		return apply_filters( 'woocommerce_gla_get_google_product_offer_id', "{$slug}_{$product_id}", $product_id );
	}

	/**
	 * Get the description for the WooCommerce product.
	 *
	 * @return string
	 */
	protected function get_wc_product_description(): string {
		/**
		 * Filters whether the short product description should be used for the synced product.
		 *
		 * @param bool $use_short_description
		 */
		$use_short_description = apply_filters( 'woocommerce_gla_use_short_description', false );

		$description = ! empty( $this->wc_product->get_description() ) && ! $use_short_description ?
			$this->wc_product->get_description() :
			$this->wc_product->get_short_description();

		// prepend the parent product description to the variation product
		if ( $this->is_variation() ) {
			$parent_description = ! empty( $this->parent_wc_product->get_description() ) && ! $use_short_description ?
				$this->parent_wc_product->get_description() :
				$this->parent_wc_product->get_short_description();
			$new_line           = ! empty( $description ) && ! empty( $parent_description ) ? PHP_EOL : '';
			$description        = $parent_description . $new_line . $description;
		}

		/**
		 * Filters whether the shortcodes should be applied for product descriptions when syncing a product or be stripped out.
		 *
		 * @since 1.4.0
		 *
		 * @param bool       $apply_shortcodes Shortcodes are applied if set to `true` and stripped out if set to `false`.
		 * @param WC_Product $wc_product       WooCommerce product object.
		 */
		$apply_shortcodes = apply_filters( 'woocommerce_gla_product_description_apply_shortcodes', false, $this->wc_product );
		if ( $apply_shortcodes ) {
			// Apply active shortcodes
			$description = do_shortcode( $description );
		} else {
			// Strip out active shortcodes
			$description = strip_shortcodes( $description );
		}

		// Strip out invalid unicode.
		$description = mb_convert_encoding( $description, 'UTF-8', 'UTF-8' );
		$description = preg_replace(
			'/[\x00-\x08\x0B\x0C\x0E-\x1F\x80-\x9F]/u',
			'',
			$description
		);

		// Strip out invalid HTML tags (e.g. script, style, canvas, etc.) along with attributes of all tags.
		$valid_html_tags   = array_keys( wp_kses_allowed_html( 'post' ) );
		$kses_allowed_tags = array_fill_keys( $valid_html_tags, [] );
		$description       = wp_kses( $description, $kses_allowed_tags );

		// Trim the description if it's more than 5000 characters.
		$description = mb_substr( $description, 0, 5000, 'utf-8' );

		/**
		 * Filters the product's description.
		 *
		 * @param string     $description Product description.
		 * @param WC_Product $wc_product  WooCommerce product object.
		 */
		return apply_filters( 'woocommerce_gla_product_attribute_value_description', $description, $this->wc_product );
	}

	/**
	 * Map the WooCommerce product images.
	 *
	 * @param string $image_size
	 *
	 * @return $this
	 */
	protected function map_wc_product_image( string $image_size ) {
		$image_id          = $this->wc_product->get_image_id();
		$gallery_image_ids = $this->wc_product->get_gallery_image_ids() ?: [];

		// check if we can use the parent product image if it's a variation
		if ( $this->is_variation() ) {
			$image_id              = $image_id ?? $this->parent_wc_product->get_image_id();
			$parent_gallery_images = $this->parent_wc_product->get_gallery_image_ids() ?: [];
			$gallery_image_ids     = ! empty( $gallery_image_ids ) ? $gallery_image_ids : $parent_gallery_images;
		}

		// use a gallery image as the main product image if no main image is available
		if ( empty( $image_id ) && ! empty( $gallery_image_ids[0] ) ) {
			$image_id = $gallery_image_ids[0];

			// remove the recently set main image from the list of gallery images
			unset( $gallery_image_ids[0] );
		}

		// set main image
		$image_link = wp_get_attachment_image_url( $image_id, $image_size, false );
		$this->setImageLink( $image_link );

		// set additional images
		$gallery_image_links = array_map(
			function ( $gallery_image_id ) use ( $image_size ) {
				return wp_get_attachment_image_url( $gallery_image_id, $image_size, false );
			},
			$gallery_image_ids
		);
		// Uniquify the set of additional images
		$gallery_image_links = array_unique( $gallery_image_links, SORT_REGULAR );

		// Limit additional image links up to 10
		$gallery_image_links = array_slice( $gallery_image_links, 0, 10 );

		$this->setAdditionalImageLinks( $gallery_image_links );

		return $this;
	}

	/**
	 * Map the general WooCommerce product attributes.
	 *
	 * @return $this
	 */
	protected function map_wc_availability() {
		if ( ! $this->wc_product->is_in_stock() ) {
			$availability = self::AVAILABILITY_OUT_OF_STOCK;
		} elseif ( $this->wc_product->is_on_backorder( 1 ) ) {
			$availability = self::AVAILABILITY_BACKORDER;
		} else {
			$availability = self::AVAILABILITY_IN_STOCK;
		}

		$this->setAvailability( $availability );

		return $this;
	}

	/**
	 * Map the shipping information for WooCommerce product.
	 *
	 * @return $this
	 */
	protected function map_wc_product_shipping(): WCProductAdapter {
		$this->add_shipping_country( $this->getTargetCountry() );

		if ( ! $this->is_virtual() ) {
			$dimension_unit = apply_filters( 'woocommerce_gla_dimension_unit', get_option( 'woocommerce_dimension_unit' ) );
			$weight_unit    = apply_filters( 'woocommerce_gla_weight_unit', get_option( 'woocommerce_weight_unit' ) );

			$this->map_wc_shipping_dimensions( $dimension_unit )
				->map_wc_shipping_weight( $weight_unit );
		}

		// Set the product's shipping class slug as the shipping label.
		$shipping_class = $this->wc_product->get_shipping_class();
		if ( ! empty( $shipping_class ) ) {
			$this->setShippingLabel( $shipping_class );
		}

		return $this;
	}

	/**
	 * Add a shipping country for the product.
	 *
	 * @param string $country
	 */
	public function add_shipping_country( string $country ): void {
		$product_shipping = [
			'country' => $country,
		];

		// Virtual products should override any country shipping cost.
		if ( $this->is_virtual() ) {
			$product_shipping['price'] = [
				'currency' => get_woocommerce_currency(),
				'value'    => 0,
			];
		}

		$new_shipping = [
			new GoogleProductShipping( $product_shipping ),
		];

		if ( ! $this->shipping_country_exists( $country ) ) {
			$current_shipping = $this->getShipping() ?? [];
			$this->setShipping( array_merge( $current_shipping, $new_shipping ) );
		}
	}

	/**
	 * Remove a shipping country from the product.
	 *
	 * @param string $country
	 *
	 * @since 1.2.0
	 */
	public function remove_shipping_country( string $country ): void {
		$product_shippings = $this->getShipping() ?? [];

		foreach ( $product_shippings as $index => $shipping ) {
			if ( $country === $shipping->getCountry() ) {
				unset( $product_shippings[ $index ] );
			}
		}

		$this->setShipping( $product_shippings );
	}

	/**
	 * @param string $country
	 *
	 * @return bool
	 */
	protected function shipping_country_exists( string $country ): bool {
		$current_shipping = $this->getShipping() ?? [];

		foreach ( $current_shipping as $shipping ) {
			if ( $country === $shipping->getCountry() ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Map the measurements for the WooCommerce product.
	 *
	 * @param string $unit
	 *
	 * @return $this
	 */
	protected function map_wc_shipping_dimensions( string $unit = 'cm' ): WCProductAdapter {
		$length = $this->wc_product->get_length();
		$width  = $this->wc_product->get_width();
		$height = $this->wc_product->get_height();

		// Use cm if the unit isn't supported.
		if ( ! in_array( $unit, [ 'in', 'cm' ], true ) ) {
			$unit = 'cm';
		}
		$length = wc_get_dimension( (float) $length, $unit );
		$width  = wc_get_dimension( (float) $width, $unit );
		$height = wc_get_dimension( (float) $height, $unit );

		if ( $length > 0 && $width > 0 && $height > 0 ) {
			$this->setShippingLength(
				new GoogleProductShippingDimension(
					[
						'unit'  => $unit,
						'value' => $length,
					]
				)
			);
			$this->setShippingWidth(
				new GoogleProductShippingDimension(
					[
						'unit'  => $unit,
						'value' => $width,
					]
				)
			);
			$this->setShippingHeight(
				new GoogleProductShippingDimension(
					[
						'unit'  => $unit,
						'value' => $height,
					]
				)
			);
		}

		return $this;
	}

	/**
	 * Map the weight for the WooCommerce product.
	 *
	 * @param string $unit
	 *
	 * @return $this
	 */
	protected function map_wc_shipping_weight( string $unit = 'g' ): WCProductAdapter {
		// Use g if the unit isn't supported.
		if ( ! in_array( $unit, [ 'g', 'lbs', 'oz' ], true ) ) {
			$unit = 'g';
		}

		$weight = wc_get_weight( $this->wc_product->get_weight(), $unit );

		// Use lb if the unit is lbs, since GMC uses lb.
		if ( 'lbs' === $unit ) {
			$unit = 'lb';
		}

		$this->setShippingWeight(
			new GoogleProductShippingWeight(
				[
					'unit'  => $unit,
					'value' => $weight,
				]
			)
		);

		return $this;
	}

	/**
	 * Sets whether tax is excluded from product price.
	 *
	 * @return $this
	 */
	protected function map_tax_excluded(): WCProductAdapter {
		// tax is excluded from price in US and CA
		$this->tax_excluded = in_array( $this->getTargetCountry(), [ 'US', 'CA' ], true );
		$this->tax_excluded = boolval( apply_filters( 'woocommerce_gla_tax_excluded', $this->tax_excluded ) );

		return $this;
	}

	/**
	 * Map the prices (base and sale price) for the product.
	 *
	 * @return $this
	 */
	protected function map_wc_prices(): WCProductAdapter {
		$this->map_tax_excluded();
		$this->map_wc_product_price( $this->wc_product );

		return $this;
	}

	/**
	 * Map the prices (base and sale price) for a given WooCommerce product.
	 *
	 * @param WC_Product $product
	 *
	 * @return $this
	 */
	protected function map_wc_product_price( WC_Product $product ): WCProductAdapter {
		// set regular price
		$regular_price = $product->get_regular_price();
		if ( '' !== $regular_price ) {
			$price = $this->tax_excluded ?
				wc_get_price_excluding_tax( $product, [ 'price' => $regular_price ] ) :
				wc_get_price_including_tax( $product, [ 'price' => $regular_price ] );

			/**
			 * Filters the calculated product price.
			 *
			 * @param float      $price        Calculated price of the product
			 * @param WC_Product $product      WooCommerce product
			 * @param bool       $tax_excluded Whether tax is excluded from product price
			 */
			$price = apply_filters( 'woocommerce_gla_product_attribute_value_price', $price, $product, $this->tax_excluded );

			$this->setPrice(
				new GooglePrice(
					[
						'currency' => get_woocommerce_currency(),
						'value'    => $price,
					]
				)
			);
		}
		// set sale price
		$this->map_wc_product_sale_price( $product );

		return $this;
	}

	/**
	 * Map the sale price and sale effective date for a given WooCommerce product.
	 *
	 * @param WC_Product $product
	 *
	 * @return $this
	 */
	protected function map_wc_product_sale_price( WC_Product $product ): WCProductAdapter {
		// Grab the sale price of the base product. Some plugins (Dynamic
		// pricing as an example) filter the active price, but not the sale
		// price. If the active price < the regular price treat it as a sale
		// price.
		$regular_price = $product->get_regular_price();
		$sale_price    = $product->get_sale_price();
		$active_price  = $product->get_price();
		if (
			( empty( $sale_price ) && $active_price < $regular_price ) ||
			( ! empty( $sale_price ) && $active_price < $sale_price )
		) {
			$sale_price = $active_price;
		}

		// set sale price and sale effective date if any
		if ( '' !== $sale_price ) {
			$sale_price = $this->tax_excluded ?
				wc_get_price_excluding_tax( $product, [ 'price' => $sale_price ] ) :
				wc_get_price_including_tax( $product, [ 'price' => $sale_price ] );

			/**
			 * Filters the calculated product sale price.
			 *
			 * @param float      $sale_price   Calculated sale price of the product
			 * @param WC_Product $product      WooCommerce product
			 * @param bool       $tax_excluded Whether tax is excluded from product price
			 */
			$sale_price = apply_filters( 'woocommerce_gla_product_attribute_value_sale_price', $sale_price, $product, $this->tax_excluded );

			// If the sale price dates no longer apply, make sure we don't include a sale price.
			$now                 = new WC_DateTime();
			$sale_price_end_date = $product->get_date_on_sale_to();
			if ( empty( $sale_price_end_date ) || $sale_price_end_date >= $now ) {
				$this->setSalePrice(
					new GooglePrice(
						[
							'currency' => get_woocommerce_currency(),
							'value'    => $sale_price,
						]
					)
				);

				$this->setSalePriceEffectiveDate( $this->get_wc_product_sale_price_effective_date( $product ) );
			}
		}

		return $this;
	}

	/**
	 * Return the sale effective dates for the WooCommerce product.
	 *
	 * @param WC_Product $product
	 *
	 * @return string|null
	 */
	protected function get_wc_product_sale_price_effective_date( WC_Product $product ): ?string {
		$start_date = $product->get_date_on_sale_from();
		$end_date   = $product->get_date_on_sale_to();

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

		return sprintf( '%s/%s', (string) $start_date, (string) $end_date );
	}

	/**
	 * Return whether the WooCommerce product is a variation.
	 *
	 * @return bool
	 */
	public function is_variation(): bool {
		return $this->wc_product instanceof WC_Product_Variation;
	}

	/**
	 * Return whether the WooCommerce product is virtual.
	 *
	 * @return bool
	 */
	public function is_virtual(): bool {
		$is_virtual = $this->wc_product->is_virtual();

		/**
		 * Filters the virtual property value of a product.
		 *
		 * @param bool       $is_virtual Whether a product is virtual
		 * @param WC_Product $product    WooCommerce product
		 */
		$is_virtual = apply_filters( 'woocommerce_gla_product_property_value_is_virtual', $is_virtual, $this->wc_product );

		return false !== $is_virtual;
	}

	/**
	 * @param ClassMetadata $metadata
	 */
	public static function load_validator_metadata( ClassMetadata $metadata ) {
		$metadata->addPropertyConstraint( 'offerId', new Assert\NotBlank() );
		$metadata->addPropertyConstraint( 'title', new Assert\NotBlank() );
		$metadata->addPropertyConstraint( 'description', new Assert\NotBlank() );

		$metadata->addPropertyConstraint( 'link', new Assert\NotBlank() );
		$metadata->addPropertyConstraint( 'link', new Assert\Url() );

		$metadata->addPropertyConstraint( 'imageLink', new Assert\NotBlank() );
		$metadata->addPropertyConstraint( 'imageLink', new ImageUrlConstraint() );
		$metadata->addPropertyConstraint(
			'additionalImageLinks',
			new Assert\All(
				[
					'constraints' => [ new ImageUrlConstraint() ],
				]
			)
		);

		$metadata->addGetterConstraint( 'price', new Assert\NotNull() );
		$metadata->addGetterConstraint( 'price', new GooglePriceConstraint() );
		$metadata->addGetterConstraint( 'salePrice', new GooglePriceConstraint() );

		$metadata->addConstraint( new Assert\Callback( 'validate_item_group_id' ) );
		$metadata->addConstraint( new Assert\Callback( 'validate_availability' ) );

		$metadata->addPropertyConstraint( 'gtin', new Assert\Regex( '/^\d{8}(?:\d{4,6})?$/' ) );
		$metadata->addPropertyConstraint( 'mpn', new Assert\Type( 'string' ) );
		$metadata->addPropertyConstraint( 'mpn', new Assert\Length( null, 0, 70 ) ); // maximum 70 characters

		$metadata->addPropertyConstraint(
			'sizes',
			new Assert\All(
				[
					'constraints' => [
						new Assert\Type( 'string' ),
						new Assert\Length( null, 0, 100 ), // maximum 100 characters
					],
				]
			)
		);
		$metadata->addPropertyConstraint( 'sizeSystem', new Assert\Choice( array_keys( SizeSystem::get_value_options() ) ) );
		$metadata->addPropertyConstraint( 'sizeType', new Assert\Choice( array_keys( SizeType::get_value_options() ) ) );

		$metadata->addPropertyConstraint( 'color', new Assert\Length( null, 0, 100 ) ); // maximum 100 characters
		$metadata->addPropertyConstraint( 'material', new Assert\Length( null, 0, 200 ) ); // maximum 200 characters
		$metadata->addPropertyConstraint( 'pattern', new Assert\Length( null, 0, 100 ) ); // maximum 200 characters

		$metadata->addPropertyConstraint( 'ageGroup', new Assert\Choice( array_keys( AgeGroup::get_value_options() ) ) );
		$metadata->addPropertyConstraint( 'adult', new Assert\Type( 'boolean' ) );

		$metadata->addPropertyConstraint( 'condition', new Assert\Choice( array_keys( Condition::get_value_options() ) ) );

		$metadata->addPropertyConstraint( 'multipack', new Assert\Type( 'integer' ) );
		$metadata->addPropertyConstraint( 'multipack', new Assert\PositiveOrZero() );

		$metadata->addPropertyConstraint( 'isBundle', new Assert\Type( 'boolean' ) );
	}

	/**
	 * Used by the validator to check if the variation product has an itemGroupId
	 *
	 * @param ExecutionContextInterface $context
	 */
	public function validate_item_group_id( ExecutionContextInterface $context ) {
		if ( $this->is_variation() && empty( $this->getItemGroupId() ) ) {
			$context->buildViolation( 'ItemGroupId needs to be set for variable products.' )
					->atPath( 'itemGroupId' )
					->addViolation();
		}
	}

	/**
	 * Used by the validator to check if the availability date is set for product available as `backorder` or
	 * `preorder`.
	 *
	 * @param ExecutionContextInterface $context
	 */
	public function validate_availability( ExecutionContextInterface $context ) {
		if (
			( self::AVAILABILITY_BACKORDER === $this->getAvailability() || self::AVAILABILITY_PREORDER === $this->getAvailability() ) &&
			empty( $this->getAvailabilityDate() )
		) {
			$context->buildViolation( 'Availability date is required if you set the product\'s availability to backorder or pre-order.' )
					->atPath( 'availabilityDate' )
					->addViolation();
		}
	}

	/**
	 * @return WC_Product
	 */
	public function get_wc_product(): WC_Product {
		return $this->wc_product;
	}

	/**
	 * @param array $attributes Attribute values
	 *
	 * @return $this
	 */
	protected function map_gla_attributes( array $attributes ): WCProductAdapter {
		$gla_attributes = [];
		foreach ( $attributes as $attribute_id => $attribute_value ) {
			if ( property_exists( $this, $attribute_id ) ) {
				/**
				 * Filters a product attribute's value.
				 *
				 * This only applies to the extra attributes defined in `AttributeManager::ATTRIBUTES`
				 * like GTIN, MPN, Brand, Size, etc. and it cannot modify other product attributes.
				 *
				 * This filter also cannot add or set a new attribute or modify one that isn't currently
				 * set for the product through WooCommerce's edit product page
				 *
				 * In order to override all product attributes and/or set new ones for the product use the
				 * `woocommerce_gla_product_attribute_values` filter.
				 *
				 * Note that the `woocommerce_gla_product_attribute_values` filter takes precedence over
				 * this filter, and it can be used to override any values defined here.
				 *
				 * @param mixed      $attribute_value The attribute's current value
				 * @param WC_Product $wc_product      The WooCommerce product object.
				 *
				 * @see AttributeManager::ATTRIBUTES for the list of attributes that their values can be modified using this filter.
				 * @see WCProductAdapter::override_attributes for the documentation of the `woocommerce_gla_product_attribute_values` filter.
				 */
				$gla_attributes[ $attribute_id ] = apply_filters( "woocommerce_gla_product_attribute_value_{$attribute_id}", $attribute_value, $this->get_wc_product() );
			}
		}

		parent::mapTypes( $gla_attributes );

		// Size
		if ( ! empty( $attributes['size'] ) ) {
			$this->setSizes( [ $attributes['size'] ] );
		}

		return $this;
	}

	/**
	 * Map the WooCommerce core global unique ID (GTIN) value if it's available.
	 *
	 * @since x.x.x
	 *
	 * @return $this
	 */
	protected function map_gtin(): WCProductAdapter {
		// compatibility-code "WC < 9.2" -- Core global unique ID field was added in 9.2
		if ( ! method_exists( $this->wc_product, 'get_global_unique_id' ) ) {
			return $this;
		}

		// avoid dashes and other unsupported format
		$global_unique_id = preg_replace( '/[^0-9]/', '', $this->wc_product->get_global_unique_id() );

		if ( ! empty( $global_unique_id ) ) {
			$this->setGtin( $global_unique_id );
		}

		return $this;
	}

	/**
	 * @param string $targetCountry
	 *
	 * phpcs:disable WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
	 */
	public function setTargetCountry( $targetCountry ) {
		// remove shipping for current target country
		$this->remove_shipping_country( $this->getTargetCountry() );

		// set the new target country
		parent::setTargetCountry( $targetCountry );

		// we need to reset the prices because tax is based on the country
		$this->map_wc_prices();

		// product shipping information is also country based
		$this->map_wc_product_shipping();
	}

	/**
	 * Performs the attribute mapping.
	 * This function applies rules setting values for the different attributes in the product.
	 *
	 * @param array $mapping_rules The set of rules to apply
	 */
	protected function map_attribute_mapping_rules( array $mapping_rules ) {
		$attributes = [];

		if ( empty( $mapping_rules ) ) {
			return $this;
		}

		foreach ( $mapping_rules as $mapping_rule ) {
			if ( $this->rule_match_conditions( $mapping_rule ) ) {
				$attribute_id                = $mapping_rule['attribute'];
				$attributes[ $attribute_id ] = $this->format_attribute(
					apply_filters(
						"woocommerce_gla_product_attribute_value_{$attribute_id}",
						$this->get_source( $mapping_rule['source'] ),
						$this->get_wc_product()
					),
					$attribute_id
				);
			}
		}

		parent::mapTypes( $attributes );

		// Size
		if ( ! empty( $attributes['size'] ) ) {
			$this->setSizes( [ $attributes['size'] ] );
		}

		return $this;
	}


	/**
	 * Get a source value for attribute mapping
	 *
	 * @param string $source The source to get the value
	 * @return string The source value for this product
	 */
	protected function get_source( string $source ) {
		$source_type = null;

		$type_separator = strpos( $source, ':' );

		if ( $type_separator ) {
			$source_type  = substr( $source, 0, $type_separator );
			$source_value = substr( $source, $type_separator + 1 );
		}

		// Detect if the source_type is kind of product, taxonomy or attribute. Otherwise, we take it the full source as a static value.
		switch ( $source_type ) {
			case 'product':
				return $this->get_product_field( $source_value );
			case 'taxonomy':
				return $this->get_product_taxonomy( $source_value );
			case 'attribute':
				return $this->get_custom_attribute( $source_value );
			default:
				return $source;
		}
	}

	/**
	 * Check if the current product match the conditions for applying the Attribute mapping rule.
	 * For now the conditions are just matching with the product category conditions.
	 *
	 * @param array $rule The attribute mapping rule
	 * @return bool True if the rule is applicable
	 */
	protected function rule_match_conditions( array $rule ): bool {
		$attribute               = $rule['attribute'];
		$category_condition_type = $rule['category_condition_type'];

		if ( $category_condition_type === AttributeMappingHelper::CATEGORY_CONDITION_TYPE_ALL ) {
			return true;
		}

		// size is not the real attribute, the real attribute is sizes
		if ( ! property_exists( $this, $attribute ) && $attribute !== 'size' ) {
			return false;
		}

		$categories                = explode( ',', $rule['categories'] );
		$contains_rules_categories = ! empty( array_intersect( $categories, $this->product_category_ids ) );

		if ( $category_condition_type === AttributeMappingHelper::CATEGORY_CONDITION_TYPE_ONLY ) {
			return $contains_rules_categories;
		}

		return ! $contains_rules_categories;
	}

	/**
	 * Get taxonomy source type for attribute mapping
	 *
	 * @param string $taxonomy The taxonomy to get
	 * @return string The taxonomy value
	 */
	protected function get_product_taxonomy( $taxonomy ) {
		$product = $this->get_wc_product();

		if ( $product->is_type( 'variation' ) ) {
			$values = $product->get_attribute( $taxonomy );

			if ( ! $values ) { // if taxonomy is not a global attribute (ie product_tag), attempt to get is with wc_get_product_terms
				$values = $this->get_taxonomy_term_names( $product->get_id(), $taxonomy );
			}

			if ( ! $values ) { // if the value is still not available at this point, we try to get it from the parent
				$parent = wc_get_product( $product->get_parent_id() );
				$values = $parent->get_attribute( $taxonomy );

				if ( ! $values ) {
					$values = $this->get_taxonomy_term_names( $parent->get_id(), $taxonomy );
				}
			}

			if ( is_string( $values ) ) {
				$values = explode( ', ', $values );
			}
		} else {
			$values = $this->get_taxonomy_term_names( $product->get_id(), $taxonomy );
		}

		if ( empty( $values ) || is_wp_error( $values ) ) {
			return '';
		}

		return $values[0];
	}

	/**
	 * Get product source type  for attribute mapping.
	 * Those are fields belonging to the product core data. Like title, weight, SKU...
	 *
	 * @param string $field The field to get
	 * @return string|null The field value (null if data is not available)
	 */
	protected function get_product_field( $field ) {
		$product = $this->get_wc_product();

		if ( 'weight_with_unit' === $field ) {
			$weight = $product->get_weight();
			return $weight ? $weight . ' ' . get_option( 'woocommerce_weight_unit' ) : null;
		}

		if ( is_callable( [ $product, 'get_' . $field ] ) ) {
			$getter = 'get_' . $field;
			return $product->$getter();
		}

		return '';
	}

	/**
	 *
	 * Formats the attribute for sending it via Google API
	 *
	 * @param string $value The value to format
	 * @param string $attribute_id The attribute ID for which this value belongs
	 * @return string|bool|int The attribute formatted based on theit attribute type
	 */
	protected function format_attribute( $value, $attribute_id ) {
		$attribute = AttributeMappingHelper::get_attribute_by_id( $attribute_id );

		if ( in_array( $attribute::get_value_type(), [ 'bool', 'boolean' ], true ) ) {
			return wc_string_to_bool( $value );
		}

		if ( in_array( $attribute::get_value_type(), [ 'int', 'integer' ], true ) ) {
			return (int) $value;
		}

		return $value;
	}

	/**
	 * Gets a custom attribute from a product
	 *
	 * @param string $attribute_name - The attribute name to get.
	 * @return string|null The attribute value or null if no value is found
	 */
	protected function get_custom_attribute( $attribute_name ) {
		$product = $this->get_wc_product();

		$attribute_value = $product->get_attribute( $attribute_name );

		if ( ! $attribute_value ) {
			$attribute_value = $product->get_meta( $attribute_name );
		}

		// We only support scalar values.
		if ( ! is_scalar( $attribute_value ) ) {
			return '';
		}

		$values = explode( WC_DELIMITER, (string) $attribute_value );
		$values = array_filter( array_map( 'trim', $values ) );
		return empty( $values ) ? '' : $values[0];
	}

	/**
	 * Get a taxonomy term names from a product using
	 *
	 * @param int    $product_id - The product ID to get the taxonomy term
	 * @param string $taxonomy - The taxonomy to get.
	 * @return string[] An array of term names.
	 */
	protected function get_taxonomy_term_names( $product_id, $taxonomy ) {
		$values = wc_get_product_terms( $product_id, $taxonomy );
		return wp_list_pluck( $values, 'name' );
	}
}
