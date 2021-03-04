<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Product;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\Validator\GooglePriceConstraint;
use Automattic\WooCommerce\GoogleListingsAndAds\Validator\Validatable;
use DateInterval;
use Google_Service_ShoppingContent_Price;
use Google_Service_ShoppingContent_Product;
use Google_Service_ShoppingContent_ProductShippingDimension;
use Google_Service_ShoppingContent_ProductShippingWeight;
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
class WCProductAdapter extends Google_Service_ShoppingContent_Product implements Validatable {
	public const AVAILABILITY_IN_STOCK     = 'in stock';
	public const AVAILABILITY_OUT_OF_STOCK = 'out of stock';

	public const IMAGE_SIZE_FULL = 'full';

	public const CHANNEL_ONLINE = 'online';

	public const DESTINATION_SHOPPING_ADS  = 'Shopping ads';
	public const DESTINATION_FREE_LISTINGS = 'Free listings';

	/**
	 * @var WC_Product WooCommerce product object
	 */
	protected $wc_product;

	/**
	 * @var bool Whether tax is excluded from product price
	 */
	protected $tax_excluded;

	/**
	 * Initialize this object's properties from an array.
	 *
	 * @param array $array Used to seed this object's properties.
	 *
	 * @return void
	 *
	 * @throws InvalidValue When a WooCommerce product is not provided or it is invalid.
	 */
	public function mapTypes( $array ) {
		if ( empty( $array['wc_product'] ) || ! $array['wc_product'] instanceof WC_Product ) {
			throw InvalidValue::not_instance_of( WC_Product::class, 'wc_product' );
		}

		$this->wc_product = $array['wc_product'];
		$this->map_woocommerce_product();

		// Google doesn't expect this field, so it's best to remove it
		unset( $array['wc_product'] );

		parent::mapTypes( $array );
	}

	/**
	 * Map the WooCommerce product attributes to the current class.
	 *
	 * @return void
	 */
	protected function map_woocommerce_product() {
		$dimension_unit = apply_filters( 'woocommerce_gla_dimension_unit', get_option( 'woocommerce_dimension_unit' ) );
		$weight_unit    = apply_filters( 'woocommerce_gla_weight_unit', get_option( 'woocommerce_weight_unit' ) );

		// set target country
		$base_country = WC()->countries->get_base_country();
		$this->setTargetCountry( $base_country );

		// tax is excluded from price in US and CA
		$this->tax_excluded = in_array( $base_country, [ 'US', 'CA' ], true );
		$this->tax_excluded = apply_filters( 'woocommerce_gla_tax_excluded', $this->tax_excluded );

		$this->setChannel( self::CHANNEL_ONLINE );

		// todo: this is temporary, modify or remove this when the GTIN, MPN etc. functionalities are implemented.
		$this->setIdentifierExists( false );

		$content_language = empty( get_locale() ) ? 'en' : strtolower( substr( get_locale(), 0, 2 ) ); // ISO 639-1.
		$this->setContentLanguage( $content_language );

		$this->setIncludedDestinations( [ self::DESTINATION_SHOPPING_ADS, self::DESTINATION_FREE_LISTINGS ] );

		$this->map_wc_general_attributes()
			 ->map_wc_product_image( self::IMAGE_SIZE_FULL )
			 ->map_wc_availability()
			 ->map_wc_shipping_dimensions( $dimension_unit )
			 ->map_wc_shipping_weight( $weight_unit )
			 ->map_wc_prices();
	}

	/**
	 * Map the general WooCommerce product attributes.
	 *
	 * @return $this
	 */
	protected function map_wc_general_attributes() {
		$this->setOfferId( $this->wc_product->get_id() );

		$this->setTitle( $this->wc_product->get_title() );
		$this->setDescription( $this->get_wc_product_description() );
		$this->setLink( $this->wc_product->get_permalink() );

		// set item group id for variations and variable products
		if ( $this->is_variation() ) {
			$parent_product = wc_get_product( $this->wc_product->get_parent_id() );
			$this->setItemGroupId( $parent_product->get_id() );
		} elseif ( $this->is_variable() ) {
			$this->setItemGroupId( $this->wc_product->get_id() );
		}

		return $this;
	}

	/**
	 * Get the description for the WooCommerce product.
	 *
	 * @return string
	 */
	protected function get_wc_product_description(): string {
		$description = $this->wc_product->get_description() ?? $this->wc_product->get_short_description();

		// prepend the parent product description to the variation product
		if ( $this->is_variation() && ! empty( $this->wc_product->get_parent_id() ) ) {
			$parent_product     = wc_get_product( $this->wc_product->get_parent_id() );
			$parent_description = $parent_product->get_description() ?? $parent_product->get_short_description();
			$new_line           = ! empty( $description ) && ! empty( $parent_description ) ? PHP_EOL : '';
			$description        = $parent_description . $new_line . $description;
		}

		// Strip out invalid unicode.
		$description = preg_replace(
			'/[\x00-\x08\x0B\x0C\x0E-\x1F\x80-\x9F]/u',
			'',
			$description
		);

		return wp_strip_all_tags( $description );
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
		$gallery_image_ids = $this->wc_product->get_gallery_image_ids();

		// check if we can use the parent product image if it's a variation
		if ( $this->is_variation() ) {
			$parent_product    = wc_get_product( $this->wc_product->get_parent_id() );
			$image_id          = $image_id ?? $this->wc_product->get_image_id();
			$gallery_image_ids = ! empty( $gallery_image_ids ) ? $gallery_image_ids : $parent_product->get_gallery_image_ids();
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
		$this->setAdditionalImageLinks( $gallery_image_links );

		return $this;
	}

	/**
	 * Map the general WooCommerce product attributes.
	 *
	 * @return $this
	 */
	protected function map_wc_availability() {
		// todo: include 'preorder' status (maybe a new field for products / or using an extension?)
		$availability = $this->wc_product->is_in_stock() ? self::AVAILABILITY_IN_STOCK : self::AVAILABILITY_OUT_OF_STOCK;
		$this->setAvailability( $availability );

		return $this;
	}

	/**
	 * Map the measurements for the WooCommerce product.
	 *
	 * @param string $unit
	 *
	 * @return $this
	 */
	protected function map_wc_shipping_dimensions( $unit = 'cm' ) {
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
				new Google_Service_ShoppingContent_ProductShippingDimension(
					[
						'unit'  => $unit,
						'value' => $length,
					]
				)
			);
			$this->setShippingWidth(
				new Google_Service_ShoppingContent_ProductShippingDimension(
					[
						'unit'  => $unit,
						'value' => $width,
					]
				)
			);
			$this->setShippingHeight(
				new Google_Service_ShoppingContent_ProductShippingDimension(
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
	protected function map_wc_shipping_weight( $unit = 'g' ) {
		// Use g if the unit isn't supported.
		if ( ! in_array( $unit, [ 'g', 'lbs', 'oz' ], true ) ) {
			$unit = 'g';
		}

		$weight = wc_get_weight( $this->wc_product->get_weight(), $unit );
		$this->setShippingWeight(
			new Google_Service_ShoppingContent_ProductShippingWeight(
				[
					'unit'  => $unit,
					'value' => $weight,
				]
			)
		);

		return $this;
	}

	/**
	 * Map the prices (base and sale price) for the product.
	 *
	 * @return $this
	 */
	protected function map_wc_prices() {
		$this->map_wc_product_price( $this->wc_product );

		if ( $this->is_variable() ) {
			// use the cheapest child price for the main product
			$this->maybe_map_wc_children_prices();
		}

		return $this;
	}

	/**
	 * Map the prices of the item according to its child products.
	 *
	 * @return $this
	 */
	protected function maybe_map_wc_children_prices() {
		if ( ! $this->wc_product->has_child() ) {
			return $this;
		}

		$current_price = '' === $this->wc_product->get_regular_price() ?
			null :
			wc_get_price_including_tax( $this->wc_product, [ 'price' => $this->wc_product->get_regular_price() ] );

		$children = $this->wc_product->get_children();
		foreach ( $children as $child ) {
			$child_product = wc_get_product( $child );
			if ( ! $child_product ) {
				continue;
			}
			if ( ! self::is_wc_product_visible( $child_product ) ) {
				continue;
			}

			$child_price = '' === $child_product->get_regular_price() ?
				null :
				wc_get_price_including_tax( $child_product, [ 'price' => $child_product->get_regular_price() ] );

			if ( ( 0 === (int) $current_price ) && ( (int) $child_price > 0 ) ) {
				$this->map_wc_product_price( $child_product );
			} elseif ( ( $child_price > 0 ) && ( $child_price < $current_price ) ) {
				$this->map_wc_product_price( $child_product );
			}
		}

		return $this;
	}

	/**
	 * Whether the given WooCommerce product is visible in store.
	 *
	 * @param WC_Product $wc_product
	 *
	 * @return bool
	 */
	protected static function is_wc_product_visible( WC_Product $wc_product ): bool {
		if ( $wc_product instanceof WC_Product_Variation ) {
			return $wc_product->variation_is_visible();
		}

		return $wc_product->is_visible();
	}

	/**
	 * Map the prices (base and sale price) for a given WooCommerce product.
	 *
	 * @param WC_Product $product
	 *
	 * @return $this
	 */
	protected function map_wc_product_price( WC_Product $product ) {
		// set regular price
		$regular_price = $product->get_regular_price();
		if ( '' !== $regular_price ) {
			$price = $this->tax_excluded ?
				wc_get_price_excluding_tax( $product, [ 'price' => $regular_price ] ) :
				wc_get_price_including_tax( $product, [ 'price' => $regular_price ] );

			$this->setPrice(
				new Google_Service_ShoppingContent_Price(
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
	protected function map_wc_product_sale_price( WC_Product $product ) {
		// Grab the sale price of the base product. Some plugins (Dynamic
		// pricing as an example) filter the active price, but not the sale
		// price. If the active price < the regular price treat it as a sale
		// price.
		$regular_price = $product->get_regular_price();
		$sale_price    = $product->get_sale_price();
		$active_price  = $product->get_price();
		if ( ( empty( $sale_price ) && $active_price < $regular_price ) ||
			( ! empty( $sale_price ) && $active_price < $sale_price ) ) {
			$sale_price = $active_price;
		}

		// set sale price and sale effective date if any
		if ( '' !== $sale_price ) {
			$sale_price = $this->tax_excluded ?
				wc_get_price_excluding_tax( $product, [ 'price' => $sale_price ] ) :
				wc_get_price_including_tax( $product, [ 'price' => $sale_price ] );

			// If the sale price dates no longer apply, make sure we don't include a sale price.
			$now                 = new WC_DateTime();
			$sale_price_end_date = $product->get_date_on_sale_to();
			if ( empty( $sale_price_end_date ) || $sale_price_end_date >= $now ) {
				$this->setSalePrice(
					new Google_Service_ShoppingContent_Price(
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
	 * @return string
	 */
	protected function get_wc_product_sale_price_effective_date( WC_Product $product ): string {
		$start_date = $product->get_date_on_sale_from();
		$end_date   = $product->get_date_on_sale_to();

		$now = new WC_DateTime();
		// if we have a sale end date in the future, but no start date, set the start date to now()
		if ( ! empty( $end_date ) &&
			$end_date > $now &&
			empty( $start_date )
		) {
			$start_date = $now;
		}
		// if we have a sale start date in the past, but no end date, do not include the start date.
		if ( ! empty( $start_date ) &&
			$start_date < $now &&
			empty( $end_date )
		) {
			$start_date = null;
		}
		// if we have a start date in the future, but no end date, assume a one-day sale.
		if ( ! empty( $start_date ) &&
			$start_date > $now &&
			empty( $end_date )
		) {
			$end_date = clone $start_date;
			$end_date->add( new DateInterval( 'P1D' ) );
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
	 * Return whether the WooCommerce product is a variable.
	 *
	 * @return bool
	 */
	public function is_variable(): bool {
		return $this->wc_product instanceof WC_Product_Variable;
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
		$metadata->addPropertyConstraint( 'imageLink', new Assert\Url() );
		$metadata->addPropertyConstraint( 'additionalImageLinks', new Assert\All( [ 'constraints' => [ new Assert\Url() ] ] ) );
		$metadata->addGetterConstraint( 'price', new Assert\NotNull() );
		$metadata->addGetterConstraint( 'price', new GooglePriceConstraint() );
		$metadata->addGetterConstraint( 'salePrice', new GooglePriceConstraint() );
		$metadata->addConstraint( new Assert\Callback( 'validate_item_group_id' ) );
	}

	/**
	 * Used by the validator to check if the variable/variation product has an itemGroupId
	 *
	 * @param ExecutionContextInterface $context
	 */
	public function validate_item_group_id( ExecutionContextInterface $context ) {
		if ( ( $this->is_variable() || $this->is_variation() ) && empty( $this->getItemGroupId() ) ) {
			$context->buildViolation( 'ItemGroupId needs to be set for variable products.' )
					->atPath( 'itemGroupId' )
					->addViolation();
		}
	}

	/**
	 * @return WC_Product
	 */
	public function get_wc_product(): WC_Product {
		return $this->wc_product;
	}
}
