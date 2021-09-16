<?php
declare(strict_types = 1);
namespace Automattic\WooCommerce\GoogleListingsAndAds\Coupon;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\Condition;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\SizeSystem;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\SizeType;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\AgeGroup;
use Automattic\WooCommerce\GoogleListingsAndAds\Validator\GooglePriceConstraint;
use Automattic\WooCommerce\GoogleListingsAndAds\Validator\Validatable;
use DateInterval;
use Google\Service\ShoppingContent\PriceAmount as GooglePriceAmount;
use Google\Service\ShoppingContent\Promotion as GooglePromotion;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use WC_DateTime;
use WC_Coupon;
defined( 'ABSPATH' ) || exit();

/**
 * Class WCCouponAdapter
 *
 * This class adapts the WooCommerce coupon class to the Google's Promotion class by mapping their attributes.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Coupon
 */
class WCCouponAdapter extends GooglePromotion implements Validatable {
    use PluginHelper;

    public const CHANNEL_ONLINE = 'ONLINE';

    public const PRODUCT_APPLICABILITY_ALL_PRODUCTS = 'ALL_PRODUCTS';

    public const PRODUCT_APPLICABILITY_PRODUCT_SPECIFIC = 'PRODUCT_SPECIFIC';

    public const OFFER_TYPE_GENERIC_CODE = 'GENERIC_CODE';

    public const WC_DISCOUNT_TYPE_PERCENT = 'percent';

    public const WC_DISCOUNT_TYPE_FIXED_CART = 'fixed_cart';

    public const WC_DISCOUNT_TYPE_FIXED_PRODUCT = 'fixed_product';

    public const COUPON_VALUE_TYPE_MONEY_OFF = 'MONEY_OFF';

    public const COUPON_VALUE_TYPE_PERCENT_OFF = 'PERCENT_OFF';

    /**
     * Initialize this object's properties from an array.
     *
     * @param array $array
     *            Used to seed this object's properties.
     *            
     * @return void
     *
     * @throws InvalidValue When a WooCommerce coupon is not provided or it is invalid.
     */
    public function mapTypes( $array ) {
        if ( 
            empty( $array['wc_coupon'] ) ||
            ! $array['wc_coupon'] instanceof WC_Coupon ) {
            throw InvalidValue::not_instance_of( WC_Coupon::class, 'wc_coupon' );
        }

        $wc_coupon = $array['wc_coupon'];
        $this->map_woocommerce_coupon( $wc_coupon );

        // Google doesn't expect extra fields, so it's best to remove them
        unset( $array['wc_coupon'] );

        parent::mapTypes( $array );
    }

    /**
     * Map the WooCommerce coupon attributes to the current class.
     *
     * @return void
     */
    protected function map_woocommerce_coupon( WC_Coupon $wc_coupon ) {
        $this->setRedemptionChannel( self::CHANNEL_ONLINE );

        $content_language = empty( get_locale() ) ? 'en' : strtolower( 
            substr( get_locale(), 0, 2 ) ); // ISO 639-1.
        $this->setContentLanguage( $content_language );

        $this->map_wc_coupon_id( $wc_coupon )
            ->map_wc_general_attributes( $wc_coupon )
            ->map_wc_usage_restriction( $wc_coupon );
    }

    /**
     * Map the WooCommerce coupon ID.
     *
     * @return $this
     */
    protected function map_wc_coupon_id( WC_Coupon $wc_coupon ): WCCouponAdapter {
        $coupon_id = "{$this->get_slug()}_{$wc_coupon->get_id()}";
        $this->setPromotionId( $coupon_id );

        return $this;
    }

    /**
     * Map the general WooCommerce coupon attributes.
     *
     * @return $this
     */
    protected function map_wc_general_attributes( WC_Coupon $wc_coupon ): WCCouponAdapter {
        $this->setOfferType( self::OFFER_TYPE_GENERIC_CODE );
        $this->setGenericRedemptionCode( $wc_coupon->get_code() );

        $coupon_amount = $wc_coupon->get_amount();
        if ( $wc_coupon->is_type( self::WC_DISCOUNT_TYPE_PERCENT ) ) {
            $this->setCouponValueType( self::COUPON_VALUE_TYPE_PERCENT_OFF );
            $this->setPercentOff( round( $coupon_amount ) );
        } else if ( $wc_coupon->is_type( 
            [
                self::WC_DISCOUNT_TYPE_FIXED_CART,
                self::WC_DISCOUNT_TYPE_FIXED_PRODUCT] ) ) {
            $this->setCouponValueType( self::COUPON_VALUE_TYPE_MONEY_OFF );
            $this->setMoneyOffAmount( 
                $this->map_google_price_amount( $coupon_amount ) );
        }

        $this->setPromotionEffectiveDates( 
            $this->get_wc_coupon_effective_dates( 
                $wc_coupon->get_date_expires() ) );

        return $this;
    }

    /**
     * Return the effective dates for the WooCommerce couopon.
     *
     *
     * @return WC_DateTime|null
     */
    protected function get_wc_coupon_effective_dates( $expiring_date ): ?string {
        // TODO: Get start effective date when allows to upload coupons that start in the future.
        $now = new WC_DateTime();
        $end_date = $expiring_date;

        // If there is no expiring date, set to promotion maximumal effective days allowed by Google.\
        // Refer to https://support.google.com/merchants/answer/2906014?hl=en
        if ( empty( $expiring_date ) ) {
            $end_date = $now->add(new DateInterval('P183D'));
        }

        // If the coupon is already expired. set the effective date to a past period.
        if ( ! empty( $end_date ) && $end_date < $now ) {
            $end_date = $now;
            $now = $end_date->sub(new DateInterval('P1D'));
        }
        return sprintf( '%s/%s', (string) $now, (string) $end_date );
    }

    /**
     * Map the WooCommerce coupon usage restriction.
     *
     * @return $this
     */
    protected function map_wc_usage_restriction( WC_Coupon $wc_coupon ): WCCouponAdapter {
        $minimal_spend = $wc_coupon->get_minimum_amount();
        if ( isset( $minimal_spend ) ) {
            $this->setMinimumPurchaseAmount( 
                $this->map_google_price_amount( $minimal_spend ) );
        }

        $maximal_spend = $wc_coupon->get_maximum_amount();
        if ( isset( $maximal_spend ) ) {
            $this->setLimitValue( 
                $this->map_google_price_amount( $maximal_spend ) );
        }

        $has_product_restriction = false;
        $wc_product_ids = $wc_coupon->get_product_ids();
        if ( ! empty( $wc_product_ids ) ) {
            $has_product_restriction = true;
            $this->setItemId( $wc_product_ids );
        }

        $wc_exclued_product_ids = $wc_coupon->get_excluded_product_ids();
        if ( ! empty( $wc_exclued_product_ids ) ) {
            $has_product_restriction = true;
            $this->setItemId( $wc_exclued_product_ids );
        }

        $wc_product_categories = $wc_coupon->get_product_categories();
        if ( ! empty( $wc_product_categories ) ) {
            $has_product_restriction = true;
            // TODO: add proudct category resriction mappings.
        }

        $wc_exclued_product_categories = $wc_coupon->get_excluded_product_categories();
        if ( ! empty( $wc_exclued_product_categories ) ) {
            $has_product_restriction = true;
            // TODO: add proudct category resriction mappings.
        }

        if ( $has_product_restriction ) {
            $this->setProductApplicability( 
                self::PRODUCT_APPLICABILITY_PRODUCT_SPECIFIC );
        } else {
            $this->setProductApplicability( 
                self::PRODUCT_APPLICABILITY_ALL_PRODUCTS );
        }
        
        return $this;
    }

    /**
     * Map WooCommerce price number to Google price structure.
     *
     * @return GooglePriceAmount
     */
    protected function map_google_price_amount( $wc_amount ): GooglePriceAmount {
        return new GooglePriceAmount( 
            ['currency' => get_woocommerce_currency(),'value' => $wc_amount] );
    }

    /**
     *
     * @param ClassMetadata $metadata
     */
    public static function load_validator_metadata( ClassMetadata $metadata ) {
        $metadata->addPropertyConstraint( 
            'genericRedemptionCode',
            new Assert\NotBlank() );
    }

    /**
     * Update google promotion for a deleted or disabled WooCommerce coupon.
     *
     * phpcs:disable WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
     */
    public function disableCoupon() {
        $end_date = new WC_DateTime();
        $start_date = $end_date;
        $this->setPromotionEffectiveDates( 
            sprintf( '%s/%s', (string) $start_date, (string) $end_date ) );
    }

    /**
     *
     * @param string $targetCountry
     *            phpcs:disable WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
     */
    public function setTargetCountry( $targetCountry ) {
        // set the new target country
        parent::setTargetCountry( $targetCountry );
    }
}
