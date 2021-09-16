<?php
declare(strict_types = 1);
namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait;

use PHPUnit\Framework\MockObject\MockObject;
use WC_Coupon;

/**
 * Trait Coupon
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait
 */
trait CouponTrait {
    use SettingsTrait;

    /**
     * Generates and returns a mock of a simple WC_Coupon object
     *
     * @return MockObject|WC_Coupon
     */
    public function generate_simple_coupon_mock() {
        $coupon = $this->createMock( WC_Coupon::class );

        $coupon->expects( $this->any() )
            ->method( 'get_id' )
            ->willReturn( rand() );

        return $coupon;
    }

    /**
     * Creates a simple valid WC_Coupon object
     *
     * @return WC_Coupon
     */
    public function create_ready_to_sync_coupon() {
        $coupon = new WC_Coupon();
        $coupon->set_code( 'ready_to_sync_coupon' + rand() );
        $coupon->save();

        return $coupon;
    }

    /**
     * Generates and returns a mock of a Google promotion object
     *
     * @param string|null $id
     * @param string|null $target_country
     *
     * @return MockObject|GooglePromotion
     */
    public function generate_google_promotion_mock( 
        $id = null,
        $target_country = null ) {
        $promotion = $this->createMock( GooglePromotion::class );

        $target_country = $target_country ?: $this->get_sample_target_country();
        $id = $id ?: "online:en:{$target_country}:gla_" . rand();

        $product->expects( $this->any() )
            ->method( 'getId' )
            ->willReturn( $id );
        $product->expects( $this->any() )
            ->method( 'getTargetCountry' )
            ->willReturn( $target_country );

        return $product;
    }
}
