<?php
declare(strict_types = 1);
namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Coupon;

use Automattic\WooCommerce\GoogleListingsAndAds\Google\GooglePromotionService;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\Coupon\CouponHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Coupon\CouponMetaHandler;
use Automattic\WooCommerce\GoogleListingsAndAds\Coupon\CouponSyncer;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\ContainerAwareUnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\CouponTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\SettingsTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\SyncStatus;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Google\Exception as GoogleException;
use WC_Coupon;

/**
 * Class CouponSyncerTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Coupon
 *         
 * @property MockObject|GooglePromotionService $google_service
 * @property MockObject|MerchantCenterService $merchant_center
 * @property MockObject|ValidatorInterface $validator
 * @property CouponMetaHandler $coupon_meta
 * @property CouponHelper $coupon_helper
 * @property WC $wc
 * @property CouponSyncer $coupon_syncer
 */
class CouponSyncerTest extends ContainerAwareUnitTest {

    use SettingsTrait;
    use CouponTrait;

    public function test_update_succeed() {
        $coupon = $this->create_ready_to_sync_coupon();
        $this->mock_google_service( $coupon );
        $this->validator->expects( $this->any() )
            ->method( 'validate' )
            ->willReturn( [] );

        $this->coupon_syncer->update( $coupon );

        $this->assertEquals( 1, did_action( 'woocommerce_gla_updated_coupon' ) );
        $this->assert_coupon_synced( $coupon );
    }

    public function test_update_fail() {
        $invalid_coupon = $this->create_ready_to_sync_coupon();
        $exist_coupon = $this->create_ready_to_sync_coupon();
        $this->mock_google_service( $exist_coupon );
        $this->validator->expects( $this->any() )
            ->method( 'validate' )
            ->willReturn( [] );

        $this->coupon_syncer->update( $invalid_coupon );

        $this->assertEquals( 
            1,
            did_action( 'woocommerce_gla_retry_update_coupons' ) );
        $this->assert_coupon_has_errors( $invalid_coupon );
    }

    public function test_delete_succeed() {
        $coupon = $this->create_ready_to_delete_coupon();
        $this->mock_google_service( $coupon );

        $this->coupon_syncer->delete( 
            $this->generate_delete_coupon_entry( $coupon ) );

        $this->assertEquals( 
            1,
            did_action( 'woocommerce_gla_deleted_promotions' ) );
        $this->assert_coupon_unsynced( $coupon );
    }

    public function test_delete_fail() {
        $invalid_coupon = $this->create_ready_to_delete_coupon();
        $exist_coupon = $this->create_ready_to_delete_coupon();
        $this->mock_google_service( $exist_coupon );

        $this->coupon_syncer->delete( 
            $this->generate_delete_coupon_entry( $invalid_coupon ) );

        $this->assertEquals( 
            1,
            did_action( 'woocommerce_gla_deleted_promotions' ) );
        $this->assertEquals( 
            1,
            did_action( 'woocommerce_gla_retry_delete_coupons' ) );
    }

    protected function assert_coupon_synced( $coupon ) {
        $reloaded_coupon = new WC_Coupon( $coupon->get_id() );
        $this->assertTrue( 
            $this->coupon_helper->is_coupon_synced( $reloaded_coupon ) );
    }

    protected function assert_coupon_unsynced( $coupon ) {
        $reloaded_coupon = new WC_Coupon( $coupon->get_id() );
        $this->assertFalse( 
            $this->coupon_helper->is_coupon_synced( $reloaded_coupon ) );
    }

    protected function assert_coupon_has_errors( $coupon ) {
        $reloaded_coupon = new WC_Coupon( $coupon->get_id() );
        $this->assertNotEmpty( 
            $this->coupon_meta->get_errors( $reloaded_coupon ) );
        $this->assertEquals(
            [ GooglePromotionService::INTERNAL_ERROR_CODE => GooglePromotionService::INTERNAL_ERROR_MSG ],
            $this->coupon_meta->get_errors( $reloaded_coupon ) );
        $this->assertEquals( 
            SyncStatus::HAS_ERRORS,
            $this->coupon_meta->get_sync_status( $reloaded_coupon ) );
    }

    protected function mock_google_service( WC_Coupon $coupon ): void {
        $callback = function ( $promotion ) use ($coupon ) {
            if ( $promotion->getGenericRedemptionCode() === $coupon->get_code() ) {
                $google_promotion = $this->generate_google_promotion_mock( 
                    $coupon_id = $coupon->get_id() );
                return $google_promotion;
            } else {
                throw new GoogleException( 
                    GooglePromotionService::INTERNAL_ERROR_MSG,
                    GooglePromotionService::INTERNAL_ERROR_CODE );
            }
        };

        $this->google_service->expects( $this->any() )
            ->method( 'create' )
            ->willReturnCallback( $callback );
    }

    /**
     * Runs before each test is executed.
     */
    public function setUp() {
        parent::setUp();
        $this->merchant_center = $this->createMock( 
            MerchantCenterService::class );
        $this->merchant_center->expects( $this->any() )
            ->method( 'is_connected' )
            ->willReturn( true );
        $this->merchant_center->expects( $this->any() )
            ->method( 'get_main_target_country' )
            ->willReturn( $this->get_sample_target_country() );
        $this->merchant_center->expects( $this->any() )
            ->method( 'is_promotion_supported_country' )
            ->willReturn( true );

        $this->google_service = $this->createMock( 
            GooglePromotionService::class );
        $this->validator = $this->createMock( ValidatorInterface::class );

        $this->coupon_meta = $this->container->get( CouponMetaHandler::class );
        $this->coupon_helper = $this->container->get( CouponHelper::class );
        $this->wc = $this->container->get( WC::class );
        $this->coupon_syncer = new CouponSyncer( 
            $this->google_service,
            $this->coupon_helper,
            $this->validator,
            $this->merchant_center,
            $this->wc );
    }
}
