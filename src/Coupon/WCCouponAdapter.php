<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Coupon;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\WCProductAdapter;
use Automattic\WooCommerce\GoogleListingsAndAds\Validator\Validatable;
use DateInterval;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use WC_DateTime;
use WC_Coupon;

defined( 'ABSPATH' ) || exit();

/**
 * Class WCCouponAdapter
 *
 * Adapts a WooCommerce coupon to a Merchant API Promotion resource. The promotion
 * detail lives under `attributes`; get_promotion() assembles the resource shape
 * that promotions:insert expects.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Coupon
 */
class WCCouponAdapter implements Validatable {
	use PluginHelper;

	public const CHANNEL_ONLINE = 'ONLINE';

	public const PRODUCT_APPLICABILITY_ALL_PRODUCTS = 'ALL_PRODUCTS';

	public const PRODUCT_APPLICABILITY_SPECIFIC_PRODUCTS = 'SPECIFIC_PRODUCTS';

	public const OFFER_TYPE_GENERIC_CODE = 'GENERIC_CODE';

	public const PROMOTION_DESTINATION_ADS = 'SHOPPING_ADS';

	public const PROMOTION_DESTINATION_FREE_LISTING = 'FREE_LISTINGS';

	public const WC_DISCOUNT_TYPE_PERCENT = 'percent';

	public const WC_DISCOUNT_TYPE_FIXED_CART = 'fixed_cart';

	public const WC_DISCOUNT_TYPE_FIXED_PRODUCT = 'fixed_product';

	public const COUPON_VALUE_TYPE_MONEY_OFF = 'MONEY_OFF';

	public const COUPON_VALUE_TYPE_PERCENT_OFF = 'PERCENT_OFF';

	protected const DATE_TIME_FORMAT = 'Y-m-d H:i:s';

	public const COUNTRIES_WITH_FREE_SHIPPING_DESTINATION = [ 'BR', 'IT', 'ES', 'JP', 'NL', 'KR', 'US' ];

	/** @var int */
	protected $wc_coupon_id;

	/** @var string */
	protected $promotion_id = '';

	/** @var string */
	protected $content_language = '';

	/** @var string */
	protected $target_country = '';

	/** @var string */
	protected $redemption_channel = '';

	/** @var string */
	protected $offer_type = '';

	/** @var string */
	protected $generic_redemption_code = '';

	/** @var string */
	protected $coupon_value_type = '';

	/** @var string */
	protected $product_applicability = '';

	/** @var string */
	protected $long_title = '';

	/** @var string|null MAPI percentOff (int64 as string). */
	protected $percent_off = null;

	/** @var array|null MAPI Price { amountMicros, currencyCode }. */
	protected $money_off_amount = null;

	/** @var array|null MAPI Price. */
	protected $minimum_purchase_amount = null;

	/** @var array|null MAPI Price. */
	protected $limit_value = null;

	/** @var array MAPI Interval { startTime, endTime }. */
	protected $promotion_effective_time_period = [];

	/** @var string[] */
	protected $promotion_destinations = [];

	/** @var string[] */
	protected $item_id_inclusion = [];

	/** @var string[] */
	protected $item_id_exclusion = [];

	/** @var string[] */
	protected $product_type_inclusion = [];

	/** @var string[] */
	protected $product_type_exclusion = [];

	/**
	 * WCCouponAdapter constructor.
	 *
	 * @param array $properties Requires 'wc_coupon' (WC_Coupon) and 'targetCountry' (string).
	 *
	 * @throws InvalidValue When a WooCommerce coupon is not provided or it is invalid.
	 */
	public function __construct( array $properties ) {
		if ( empty( $properties['wc_coupon'] ) || ! $properties['wc_coupon'] instanceof WC_Coupon ) {
			throw InvalidValue::not_instance_of( WC_Coupon::class, 'wc_coupon' );
		}

		$this->target_country = (string) ( $properties['targetCountry'] ?? '' );

		$wc_coupon          = $properties['wc_coupon'];
		$this->wc_coupon_id = $wc_coupon->get_id();
		$this->map_woocommerce_coupon( $wc_coupon, $this->get_coupon_destinations( $properties ) );
	}

	/**
	 * Assemble the Merchant API Promotion resource for this coupon.
	 *
	 * @return array
	 */
	public function get_promotion(): array {
		$attributes = [
			'offerType'                    => $this->offer_type,
			'genericRedemptionCode'        => $this->generic_redemption_code,
			'couponValueType'              => $this->coupon_value_type,
			'longTitle'                    => $this->long_title,
			'productApplicability'         => $this->product_applicability,
			'promotionDestinations'        => $this->promotion_destinations,
			'promotionEffectiveTimePeriod' => $this->promotion_effective_time_period,
		];

		if ( self::COUPON_VALUE_TYPE_PERCENT_OFF === $this->coupon_value_type && null !== $this->percent_off ) {
			$attributes['percentOff'] = $this->percent_off;
		}
		if ( self::COUPON_VALUE_TYPE_MONEY_OFF === $this->coupon_value_type && null !== $this->money_off_amount ) {
			$attributes['moneyOffAmount'] = $this->money_off_amount;
		}
		if ( null !== $this->minimum_purchase_amount ) {
			$attributes['minimumPurchaseAmount'] = $this->minimum_purchase_amount;
		}
		if ( null !== $this->limit_value ) {
			$attributes['limitValue'] = $this->limit_value;
		}
		if ( ! empty( $this->item_id_inclusion ) ) {
			$attributes['itemIdInclusion'] = $this->item_id_inclusion;
		}
		if ( ! empty( $this->item_id_exclusion ) ) {
			$attributes['itemIdExclusion'] = $this->item_id_exclusion;
		}
		if ( ! empty( $this->product_type_inclusion ) ) {
			$attributes['productTypeInclusion'] = $this->product_type_inclusion;
		}
		if ( ! empty( $this->product_type_exclusion ) ) {
			$attributes['productTypeExclusion'] = $this->product_type_exclusion;
		}

		return [
			'promotionId'       => $this->promotion_id,
			'contentLanguage'   => $this->content_language,
			'targetCountry'     => $this->target_country,
			// redemptionChannel is a repeated field in the Merchant API schema, so send an array.
			'redemptionChannel' => [ $this->redemption_channel ],
			'attributes'        => $attributes,
		];
	}

	/**
	 * Map the WooCommerce coupon attributes to the Merchant API promotion fields.
	 *
	 * @param WC_Coupon $wc_coupon
	 * @param string[]  $destinations The destinations the coupon applies to.
	 *
	 * @return void
	 */
	protected function map_woocommerce_coupon( WC_Coupon $wc_coupon, array $destinations ) {
		$this->redemption_channel     = self::CHANNEL_ONLINE;
		$this->promotion_destinations = $destinations;

		$this->content_language = empty( get_locale() ) ? 'en' : strtolower( substr( get_locale(), 0, 2 ) ); // ISO 639-1.

		$this->map_wc_coupon_id( $wc_coupon )
			->map_wc_general_attributes( $wc_coupon )
			->map_wc_usage_restriction( $wc_coupon );
	}

	/**
	 * Map the WooCommerce coupon ID.
	 *
	 * @param WC_Coupon $wc_coupon
	 *
	 * @return $this
	 */
	protected function map_wc_coupon_id( WC_Coupon $wc_coupon ): WCCouponAdapter {
		$this->promotion_id = "{$this->get_slug()}_{$wc_coupon->get_id()}";

		return $this;
	}

	/**
	 * Map the general WooCommerce coupon attributes.
	 *
	 * @param WC_Coupon $wc_coupon
	 *
	 * @return $this
	 */
	protected function map_wc_general_attributes( WC_Coupon $wc_coupon ): WCCouponAdapter {
		$this->offer_type              = self::OFFER_TYPE_GENERIC_CODE;
		$this->generic_redemption_code = $wc_coupon->get_code();

		$coupon_amount = $wc_coupon->get_amount();
		if ( $wc_coupon->is_type( self::WC_DISCOUNT_TYPE_PERCENT ) ) {
			$this->coupon_value_type = self::COUPON_VALUE_TYPE_PERCENT_OFF;
			$percent_off             = (int) round( floatval( $coupon_amount ) );
			$this->percent_off       = (string) $percent_off;
			$this->long_title        = sprintf( '%d%% off', $percent_off );
		} elseif ( $wc_coupon->is_type( [ self::WC_DISCOUNT_TYPE_FIXED_CART, self::WC_DISCOUNT_TYPE_FIXED_PRODUCT ] ) ) {
			$this->coupon_value_type = self::COUPON_VALUE_TYPE_MONEY_OFF;
			$this->money_off_amount  = $this->map_mapi_price( $coupon_amount );
			$this->long_title        = sprintf( '%d %s off', $coupon_amount, get_woocommerce_currency() );
		}

		$this->promotion_effective_time_period = $this->get_wc_coupon_effective_dates( $wc_coupon );

		return $this;
	}

	/**
	 * Return the effective time period (MAPI Interval) for the WooCommerce coupon.
	 *
	 * @param WC_Coupon $wc_coupon
	 *
	 * @return array MAPI Interval { startTime, endTime }.
	 */
	protected function get_wc_coupon_effective_dates( WC_Coupon $wc_coupon ): array {
		$start_date = $this->get_wc_coupon_start_date( $wc_coupon );

		$end_date = $wc_coupon->get_date_expires();
		// If there is no expiring date, set it to the maximum effective days Google allows.
		// See https://support.google.com/merchants/answer/2906014.
		if ( empty( $end_date ) ) {
			$end_date = clone $start_date;
			$end_date->add( new DateInterval( 'P183D' ) );
		}

		// If the coupon is already expired, expire the promotion immediately after the start date.
		if ( $end_date < $start_date ) {
			$end_date = clone $start_date;
			$end_date->add( new DateInterval( 'PT1S' ) );
		}

		return $this->build_interval( $start_date, $end_date );
	}

	/**
	 * Return the start date for the WooCommerce coupon.
	 *
	 * @param WC_Coupon $wc_coupon
	 *
	 * @return WC_DateTime
	 */
	protected function get_wc_coupon_start_date( WC_Coupon $wc_coupon ): WC_DateTime {
		$post_time = get_post_time( self::DATE_TIME_FORMAT, true, $wc_coupon->get_id(), false );
		if ( ! empty( $post_time ) ) {
			return new WC_DateTime( $post_time );
		}

		return new WC_DateTime();
	}

	/**
	 * Build a MAPI Interval from a start and end date, formatted as RFC 3339.
	 *
	 * @param WC_DateTime $start_date
	 * @param WC_DateTime $end_date
	 *
	 * @return array
	 */
	protected function build_interval( WC_DateTime $start_date, WC_DateTime $end_date ): array {
		return [
			'startTime' => $start_date->format( 'c' ),
			'endTime'   => $end_date->format( 'c' ),
		];
	}

	/**
	 * Map the WooCommerce coupon usage restriction.
	 *
	 * @param WC_Coupon $wc_coupon
	 *
	 * @return $this
	 */
	protected function map_wc_usage_restriction( WC_Coupon $wc_coupon ): WCCouponAdapter {
		$minimal_spend = $wc_coupon->get_minimum_amount();
		if ( ! empty( $minimal_spend ) ) {
			$this->minimum_purchase_amount = $this->map_mapi_price( $minimal_spend );
		}

		$maximal_spend = $wc_coupon->get_maximum_amount();
		if ( ! empty( $maximal_spend ) ) {
			$this->limit_value = $this->map_mapi_price( $maximal_spend );
		}

		$has_product_restriction = false;
		$get_offer_id            = function ( int $product_id ) {
			return WCProductAdapter::get_google_product_offer_id( $this->get_slug(), $product_id );
		};

		$wc_product_ids = $wc_coupon->get_product_ids();
		if ( ! empty( $wc_product_ids ) ) {
			$has_product_restriction = true;
			$this->item_id_inclusion = array_map( $get_offer_id, $wc_product_ids );
		}

		// A brand inclusion restriction overrides the product inclusion restriction,
		// matching the current coupon discount behaviour in WooCommerce.
		$wc_product_ids_in_brand = $this->get_product_ids_in_brand( $wc_coupon );
		if ( ! empty( $wc_product_ids_in_brand ) ) {
			$has_product_restriction = true;
			$this->item_id_inclusion = array_map( $get_offer_id, $wc_product_ids_in_brand );
		}

		$wc_excluded_product_ids          = $wc_coupon->get_excluded_product_ids();
		$wc_excluded_product_ids_in_brand = $this->get_product_ids_in_brand( $wc_coupon, true );
		if ( ! empty( $wc_excluded_product_ids ) || ! empty( $wc_excluded_product_ids_in_brand ) ) {
			$this->item_id_exclusion = array_values(
				array_unique(
					array_merge(
						array_map( $get_offer_id, $wc_excluded_product_ids ),
						array_map( $get_offer_id, $wc_excluded_product_ids_in_brand )
					)
				)
			);
			$has_product_restriction = true;
		}

		$wc_product_categories = $wc_coupon->get_product_categories();
		if ( ! empty( $wc_product_categories ) ) {
			$has_product_restriction      = true;
			$this->product_type_inclusion = WCProductAdapter::convert_product_types( $wc_product_categories );
		}

		$wc_excluded_product_categories = $wc_coupon->get_excluded_product_categories();
		if ( ! empty( $wc_excluded_product_categories ) ) {
			$has_product_restriction      = true;
			$this->product_type_exclusion = WCProductAdapter::convert_product_types( $wc_excluded_product_categories );
		}

		$this->product_applicability = $has_product_restriction
			? self::PRODUCT_APPLICABILITY_SPECIFIC_PRODUCTS
			: self::PRODUCT_APPLICABILITY_ALL_PRODUCTS;

		return $this;
	}

	/**
	 * Map a WooCommerce amount to a Merchant API Price { amountMicros, currencyCode }.
	 *
	 * @param string|float $wc_amount
	 *
	 * @return array
	 */
	protected function map_mapi_price( $wc_amount ): array {
		return [
			'amountMicros' => sprintf( '%.0f', (float) $wc_amount * 1000000 ),
			'currencyCode' => get_woocommerce_currency(),
		];
	}

	/**
	 * Expire the promotion by setting its effective end date to now (or immediately
	 * after the start date for a coupon scheduled in the future).
	 *
	 * @param WC_Coupon $wc_coupon
	 */
	public function disable_promotion( WC_Coupon $wc_coupon ) {
		$start_date = $this->get_wc_coupon_start_date( $wc_coupon );
		$end_date   = new WC_DateTime();

		if ( $start_date >= $end_date ) {
			$end_date = clone $start_date;
			$end_date->add( new DateInterval( 'PT1S' ) );
		}

		$this->promotion_effective_time_period = $this->build_interval( $start_date, $end_date );
	}

	/**
	 * @param ClassMetadata $metadata
	 */
	public static function load_validator_metadata( ClassMetadata $metadata ) {
		$metadata->addPropertyConstraint( 'target_country', new Assert\NotBlank() );
		$metadata->addPropertyConstraint( 'promotion_id', new Assert\NotBlank() );
		$metadata->addPropertyConstraint( 'generic_redemption_code', new Assert\NotBlank() );
		$metadata->addPropertyConstraint( 'product_applicability', new Assert\NotBlank() );
		$metadata->addPropertyConstraint( 'offer_type', new Assert\NotBlank() );
		$metadata->addPropertyConstraint( 'redemption_channel', new Assert\NotBlank() );
		$metadata->addPropertyConstraint( 'coupon_value_type', new Assert\NotBlank() );
	}

	/**
	 * @return int
	 */
	public function get_wc_coupon_id(): int {
		return $this->wc_coupon_id;
	}

	/**
	 * Get the destinations allowed per specific country.
	 *
	 * @param array $coupon_data The coupon data to get the allowed destinations.
	 *
	 * @return string[] The destinations, country based.
	 */
	private function get_coupon_destinations( array $coupon_data ): array {
		$destinations = [ self::PROMOTION_DESTINATION_ADS ];
		if ( isset( $coupon_data['targetCountry'] ) && in_array( $coupon_data['targetCountry'], self::COUNTRIES_WITH_FREE_SHIPPING_DESTINATION, true ) ) {
			$destinations[] = self::PROMOTION_DESTINATION_FREE_LISTING;
		}

		return apply_filters( 'woocommerce_gla_coupon_destinations', $destinations, $coupon_data );
	}

	/**
	 * Get the product IDs that belong to a brand.
	 *
	 * @param WC_Coupon $wc_coupon  The WC coupon object.
	 * @param bool      $is_exclude If the product IDs are for exclusion.
	 *
	 * @return int[] The product IDs that belong to a brand.
	 */
	private function get_product_ids_in_brand( WC_Coupon $wc_coupon, bool $is_exclude = false ) {
		$coupon_id = $wc_coupon->get_id();
		$meta_key  = $is_exclude ? 'exclude_product_brands' : 'product_brands';

		$brand_term_ids = get_post_meta( $coupon_id, $meta_key, true );

		if ( ! is_array( $brand_term_ids ) ) {
			return [];
		}

		$product_ids = [];
		foreach ( $brand_term_ids as $brand_term_id ) {
			$object_ids = get_objects_in_term( $brand_term_id, 'product_brand' );
			if ( is_wp_error( $object_ids ) ) {
				continue;
			}
			$product_ids = array_merge( $product_ids, $object_ids );
		}

		return $product_ids;
	}
}
