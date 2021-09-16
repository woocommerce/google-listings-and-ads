<?php
declare(strict_types = 1);
namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Coupon;

use Automattic\WooCommerce\GoogleListingsAndAds\Google\InvalidCouponEntry;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\GooglePromotionService;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\Coupon\CouponHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Coupon\CouponMetaHandler;
use Automattic\WooCommerce\GoogleListingsAndAds\Coupon\CouponSyncer;
use Automattic\WooCommerce\GoogleListingsAndAds\Coupon\CouponSyncerException;
use Automattic\WooCommerce\GoogleListingsAndAds\Coupon\WCCouponAdapter;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\ContainerAwareUnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\CouponTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\SettingsTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\SyncStatus;
use Google\Service\ShoppingContent\Promotion as GooglePromotion;
use PHPUnit\Framework\MockObject\MockObject;
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
        $coupon_syncer = new CouponSyncer( 
            $this->google_service,
            $this->coupon_helper,
            $this->validator,
            $this->merchant_center,
            $this->wc );

        $this->coupon_syncer->update( $coupon );
       
        $this->assert_successfull_update_results( 
            $coupon,
            $updated_promotions,
            $invalid_promotions );
    }

    public function test_update_fail() {
        $invalid_coupon = $this->create_ready_to_sync_coupon();
        $exist_coupon = $this->create_ready_to_sync_coupon();
        $this->mock_google_service( $exist_coupon );
        $coupon_syncer = new CouponSyncer( 
            $this->google_service,
            $this->coupon_helper,
            $this->validator,
            $this->merchant_center,
            $this->wc );

        $this->coupon_syncer->update( $invalid_coupon );
        
        $this->assert_failed_update_results( 
            $invalid_coupon = $invalid_coupon );
    }
 
    protected function assert_successfull_update_results( $updated_coupon ) {
        $this->assertEquals( 1, did_action( 'woocommerce_gla_updated_coupon' ) );

        $reloaded_coupon = new WC_Coupon( $updated_coupon->get_id() );
        $this->assertTrue( 
            $this->coupon_helper->is_coupon_synced( $reloaded_coupon ) );
    }

    protected function assert_failed_update_results() {
        $this->assertEquals( 
            1,
            did_action( 'woocommerce_gla_retry_update_coupons' ) );
        
        $reloaded_coupon = new WC_Coupon( $updated_coupon->get_id() );
        $this->assertNotEmpty( $this->meta_meta->get_errors( $reloaded_coupon ) );
        $this->assertEquals( 
            SyncStatus::HAS_ERRORS,
            $this->product_meta->get_sync_status( $reloaded_coupon ) );
    }

    /*
     * public function test_delete() {
     * // $deleted_products: products that were successfully synced and then deleted from Merchant Center
     * // $rejected_products: products that were synced but deleting them resulted in errors and were rejected by Google API
     * [
     * $deleted_products,
     * $rejected_products
     * ] = $this->create_multiple_simple_product_sets(2, 2);
     *
     * $this->mock_google_service($deleted_products, $rejected_products);
     *
     * $products = array_merge($deleted_products, $rejected_products);
     *
     * // first we mark all products as synced
     * array_walk(
     * $products,
     * function (WC_Product $product) {
     * $this->product_helper->mark_as_synced(
     * $product,
     * $this->generate_google_product_mock());
     * });
     *
     * $results = $this->product_syncer->delete($products);
     * $this->assert_delete_results_are_valid(
     * $results,
     * $deleted_products,
     * $rejected_products);
     * }
     *
     * protected function assert_delete_results_are_valid(
     * $results,
     * $deleted_products,
     * $rejected_products) {
     * $this->assertEquals(
     * 1,
     * did_action('woocommerce_gla_batch_deleted_products'));
     * $this->assertEquals(
     * 1,
     * did_action('woocommerce_gla_batch_retry_delete_products'));
     *
     * $this->assertCount(count($deleted_products), $results->get_products());
     * foreach ($results->get_products() as $product_entry) {
     * $wc_product = wc_get_product($product_entry->get_wc_product_id());
     * // product is no longer synced if delete succeeds
     * $this->assertFalse(
     * $this->product_helper->is_product_synced($wc_product));
     * }
     *
     * $this->assertCount(count($rejected_products), $results->get_errors());
     * foreach ($results->get_errors() as $error_entry) {
     * $wc_product = wc_get_product($error_entry->get_wc_product_id());
     * $this->assertNotEmpty($error_entry->get_errors());
     * // product remains synced if delete failed
     * $this->assertTrue(
     * $this->product_helper->is_product_synced($wc_product));
     * }
     * }
     *
     * public function test_delete_removes_google_id_of_not_found_products() {
     * // $deleted_products: products that were successfully synced and then deleted from Merchant Center
     * // $not_found_products: products that were synced but deleting them resulted in a not-found error and were rejected by Google API
     * [
     * $deleted_products,
     * $not_found_products
     * ] = $this->create_multiple_simple_product_sets(2, 2);
     *
     * $this->google_service->expects($this->once())
     * ->method('delete_batch')
     * ->willReturnCallback(
     * function (array $product_entries) use (
     * $deleted_products,
     * $not_found_products) {
     * $errors = [];
     * $entries = [];
     * foreach ($product_entries as $product_entry) {
     * if (isset(
     * $deleted_products[$product_entry->get_wc_product_id()])) {
     * $entries[] = new BatchProductEntry(
     * $product_entry->get_wc_product_id(),
     * null);
     * } elseif (isset(
     * $not_found_products[$product_entry->get_wc_product_id()])) {
     * $errors[] = new BatchInvalidProductEntry(
     * $product_entry->get_wc_product_id(),
     * $product_entry->get_product_id(),
     * [
     * GoogleProductService::NOT_FOUND_ERROR_REASON => 'Not Found!'
     * ]);
     * }
     * }
     *
     * return new BatchProductResponse($entries, $errors);
     * });
     *
     * $products = array_merge($deleted_products, $not_found_products);
     *
     * // first we mark all products as synced
     * array_walk(
     * $products,
     * function (WC_Product $product) {
     * $this->product_helper->mark_as_synced(
     * $product,
     * $this->generate_google_product_mock());
     * });
     *
     * $results = $this->product_syncer->delete($products);
     *
     * $this->assertCount(2, $results->get_products());
     * foreach ($results->get_products() as $product_entry) {
     * $wc_product = wc_get_product($product_entry->get_wc_product_id());
     * // product is no longer synced if delete succeeds
     * $this->assertFalse(
     * $this->product_helper->is_product_synced($wc_product));
     * }
     *
     * $this->assertCount(2, $results->get_errors());
     * foreach ($results->get_errors() as $error_entry) {
     * $wc_product = wc_get_product($error_entry->get_wc_product_id());
     * $this->assertNotEmpty($error_entry->get_errors());
     * // product is no longer synced if Google API returns Not Found error for it
     * $this->assertFalse(
     * $this->product_helper->is_product_synced($wc_product));
     * }
     * }
     *
     * public function test_update_fails_if_merchant_center_not_setup() {
     * $product = WC_Helper_Product::create_simple_product();
     *
     * $merchant_center = $this->createMock(MerchantCenterService::class);
     * $merchant_center->expects($this->any())
     * ->method('is_connected')
     * ->willReturn(false);
     * $this->product_syncer = new ProductSyncer(
     * $this->google_service,
     * $this->batch_helper,
     * $this->product_helper,
     * $merchant_center,
     * $this->wc);
     *
     * $this->expectException(ProductSyncerException::class);
     * $this->product_syncer->update([
     * $product
     * ]);
     * }
     *
     * public function test_update_by_batch_requests_fails_if_merchant_center_not_setup() {
     * $product = WC_Helper_Product::create_simple_product();
     *
     * $merchant_center = $this->createMock(MerchantCenterService::class);
     * $merchant_center->expects($this->any())
     * ->method('is_connected')
     * ->willReturn(false);
     * $this->product_syncer = new ProductSyncer(
     * $this->google_service,
     * $this->batch_helper,
     * $this->product_helper,
     * $merchant_center,
     * $this->wc);
     *
     * $this->expectException(ProductSyncerException::class);
     * $this->product_syncer->update_by_batch_requests(
     * [
     * new BatchProductRequestEntry(
     * $product->get_id(),
     * $this->generate_adapted_product($product))
     * ]);
     * }
     *
     * public function test_delete_fails_if_merchant_center_not_setup() {
     * $product = WC_Helper_Product::create_simple_product();
     *
     * $merchant_center = $this->createMock(MerchantCenterService::class);
     * $merchant_center->expects($this->any())
     * ->method('is_connected')
     * ->willReturn(false);
     * $this->product_syncer = new ProductSyncer(
     * $this->google_service,
     * $this->batch_helper,
     * $this->product_helper,
     * $merchant_center,
     * $this->wc);
     *
     * $this->expectException(ProductSyncerException::class);
     * $this->product_syncer->delete([
     * $product
     * ]);
     * }
     *
     * public function test_delete_by_batch_requests_fails_if_merchant_center_not_setup() {
     * $product = WC_Helper_Product::create_simple_product();
     *
     * $merchant_center = $this->createMock(MerchantCenterService::class);
     * $merchant_center->expects($this->any())
     * ->method('is_connected')
     * ->willReturn(false);
     * $this->product_syncer = new ProductSyncer(
     * $this->google_service,
     * $this->batch_helper,
     * $this->product_helper,
     * $merchant_center,
     * $this->wc);
     *
     * $this->expectException(ProductSyncerException::class);
     * $this->product_syncer->delete_by_batch_requests(
     * [
     * new BatchProductIDRequestEntry(
     * $product->get_id(),
     * $this->generate_google_id($product))
     * ]);
     * }
     *
     * public function test_update_by_batch_requests_throws_exception_if_google_api_call_fails() {
     * $product = WC_Helper_Product::create_simple_product();
     *
     * $this->google_service->expects($this->any())
     * ->method('insert_batch')
     * ->willThrowException(new GoogleException());
     *
     * $this->expectException(ProductSyncerException::class);
     * $this->product_syncer->update_by_batch_requests(
     * [
     * new BatchProductRequestEntry(
     * $product->get_id(),
     * $this->generate_adapted_product($product))
     * ]);
     * }
     *
     * public function test_delete_by_batch_requests_throws_exception_if_google_api_call_fails() {
     * $product = WC_Helper_Product::create_simple_product();
     *
     * $this->google_service->expects($this->any())
     * ->method('delete_batch')
     * ->willThrowException(new GoogleException());
     *
     * $this->expectException(ProductSyncerException::class);
     * $this->product_syncer->delete_by_batch_requests(
     * [
     * new BatchProductIDRequestEntry(
     * $product->get_id(),
     * $this->generate_google_id($product))
     * ]);
     * }
     */
    protected function mock_google_service_succeed( WC_Coupon $coupon ): void {
        $callback = function ( $promotion ) use ($coupon ) {
            if ( $promotion->getPromotionId() == $coupon->get_id() ) {
                $google_promotion = $this->generate_google_promotion_mock( 
                    $coupon->get_id() );
                return $google_promotion;
            } else {
                throw new GoogleException( 
                    GooglePromotionService::INTERNAL_ERROR_REASON );
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
