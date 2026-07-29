<?php
declare(strict_types = 1);
namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Coupon;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\MerchantApiException;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services\MapiDataSourcesService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services\MapiPromotionsService;
use Automattic\WooCommerce\GoogleListingsAndAds\Coupon\CouponSyncer;
use Automattic\WooCommerce\GoogleListingsAndAds\Coupon\CouponSyncerException;
use Automattic\WooCommerce\GoogleListingsAndAds\Coupon\CouponHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Coupon\CouponMetaHandler;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\TargetAudience;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\ContainerAwareUnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\CouponTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\SettingsTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\SyncStatus;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use WC_Coupon;

/**
 * Class CouponSyncerTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Coupon
 */
class CouponSyncerTest extends ContainerAwareUnitTest {

	use SettingsTrait;
	use CouponTrait;

	/** Message returned by the mocked Merchant API when a promotion fails to sync. */
	private const INTERNAL_ERROR_MSG = 'Internal error!';

	/** @var MockObject|MapiPromotionsService $promotions_service */
	protected $promotions_service;

	/** @var MockObject|MapiDataSourcesService $data_sources */
	protected $data_sources;

	/** @var MockObject|MerchantCenterService $merchant_center */
	protected $merchant_center;

	/** @var MockObject|TargetAudience $target_audience */
	protected $target_audience;

	/** @var MockObject|ValidatorInterface $validator */
	protected $validator;

	/** @var CouponMetaHandler $coupon_meta */
	protected $coupon_meta;

	/** @var CouponHelper $coupon_helper */
	protected $coupon_helper;

	/** @var CouponSyncer $coupon_syncer */
	protected $coupon_syncer;

	/** @var WC $wc */
	protected $wc;

	public function test_update_succeed() {
		$coupon = $this->create_ready_to_sync_coupon();
		$this->mock_promotions_service( $coupon );
		$this->validator->expects( $this->any() )
			->method( 'validate' )
			->willReturn( [] );

		$this->coupon_syncer->update( $coupon );

		$this->assertEquals( 1, did_action( 'woocommerce_gla_updated_coupon' ) );
		$this->assert_coupon_synced( $coupon );
	}

	public function test_update_fail() {
		$invalid_coupon = $this->create_ready_to_sync_coupon();
		$exist_coupon   = $this->create_ready_to_sync_coupon();
		$this->mock_promotions_service( $exist_coupon );
		$this->validator->expects( $this->any() )
			->method( 'validate' )
			->willReturn( [] );

		$this->coupon_syncer->update( $invalid_coupon );

		$this->assertEquals(
			1,
			did_action( 'woocommerce_gla_retry_update_coupons' )
		);
		$this->assert_coupon_has_errors( $invalid_coupon );
	}

	public function test_update_handles_data_source_resolution_failure() {
		// A Merchant API failure while resolving the promotion data source must be handled
		// as a coupon sync error, not escape update(), the same as an insert failure.
		$coupon = $this->create_ready_to_sync_coupon();
		$this->validator->expects( $this->any() )
			->method( 'validate' )
			->willReturn( [] );
		$this->data_sources->expects( $this->any() )
			->method( 'ensure_promotion_data_source_for' )
			->willThrowException(
				new MerchantApiException(
					CouponSyncer::INTERNAL_ERROR_CODE,
					[ 'error' => [ 'message' => self::INTERNAL_ERROR_MSG ] ],
					'ensure_promotion_data_source_for'
				)
			);
		$this->promotions_service->expects( $this->never() )
			->method( 'insert_promotion' );

		$this->coupon_syncer->update( $coupon );

		$this->assertEquals(
			1,
			did_action( 'woocommerce_gla_retry_update_coupons' )
		);
		$this->assert_coupon_has_errors( $coupon );
	}

	public function test_update_retries_once_with_a_re_resolved_promotion_data_source() {
		// A promotion data source deleted on the Google side after it was resolved must be
		// re-resolved and the upsert retried, not left as a coupon error.
		$coupon = $this->create_ready_to_sync_coupon();
		$this->validator->expects( $this->any() )
			->method( 'validate' )
			->willReturn( [] );

		$this->data_sources->expects( $this->exactly( 2 ) )
			->method( 'ensure_promotion_data_source_for' )
			->willReturnOnConsecutiveCalls( 'dataSources/stale', 'dataSources/fresh' );
		$this->data_sources->expects( $this->once() )
			->method( 'forget_promotion_data_source_for' );

		$seen_sources = [];
		$this->promotions_service->expects( $this->exactly( 2 ) )
			->method( 'insert_promotion' )
			->willReturnCallback(
				function ( string $data_source ) use ( &$seen_sources ) {
					$seen_sources[] = $data_source;

					if ( 1 === count( $seen_sources ) ) {
						throw new MerchantApiException(
							404,
							[ 'error' => [ 'message' => '[dataSource] Data source with id 999 was not found.' ] ],
							'insert_promotion'
						);
					}

					return [ 'promotionId' => 'promo-1' ];
				}
			);

		$this->coupon_syncer->update( $coupon );

		$this->assertSame( [ 'dataSources/stale', 'dataSources/fresh' ], $seen_sources );
		$this->assertEquals( 0, did_action( 'woocommerce_gla_retry_update_coupons' ) );
	}

	public function test_update_does_not_retry_on_non_5xx_error() {
		$coupon = $this->create_ready_to_sync_coupon();
		$this->validator->expects( $this->any() )
			->method( 'validate' )
			->willReturn( [] );
		$this->data_sources->expects( $this->any() )
			->method( 'ensure_promotion_data_source_for' )
			->willReturn( 'datasources/v1/accounts/12345/dataSources/67890' );
		$this->promotions_service->expects( $this->any() )
			->method( 'insert_promotion' )
			->willThrowException(
				new MerchantApiException(
					400,
					[ 'error' => [ 'message' => 'Bad request!' ] ],
					'insert_promotion'
				)
			);

		$this->coupon_syncer->update( $coupon );

		// A non-5xx error is flagged on the coupon but must not trigger a retry.
		$this->assertEquals(
			0,
			did_action( 'woocommerce_gla_retry_update_coupons' )
		);
		$reloaded_coupon = new WC_Coupon( $coupon->get_id() );
		$this->assertEquals(
			[ 400 => 'Bad request!' ],
			$this->coupon_meta->get_errors( $reloaded_coupon )
		);
		$this->assertEquals(
			SyncStatus::HAS_ERRORS,
			$this->coupon_meta->get_sync_status( $reloaded_coupon )
		);
	}

	public function test_delete_succeed() {
		$coupon = $this->create_ready_to_delete_coupon();
		$this->mock_promotions_service( $coupon );

		$this->coupon_syncer->delete(
			$this->generate_delete_coupon_entry( $coupon )
		);

		$this->assertEquals(
			1,
			did_action( 'woocommerce_gla_deleted_promotions' )
		);
		$this->assert_coupon_unsynced( $coupon );
	}

	public function test_delete_fail() {
		$invalid_coupon = $this->create_ready_to_delete_coupon();
		$exist_coupon   = $this->create_ready_to_delete_coupon();
		$this->mock_promotions_service( $exist_coupon );

		$this->coupon_syncer->delete(
			$this->generate_delete_coupon_entry( $invalid_coupon )
		);

		$this->assertEquals(
			1,
			did_action( 'woocommerce_gla_deleted_promotions' )
		);
		$this->assertEquals(
			1,
			did_action( 'woocommerce_gla_retry_delete_coupons' )
		);
	}

	protected function assert_coupon_synced( $coupon ) {
		$reloaded_coupon = new WC_Coupon( $coupon->get_id() );
		$this->assertTrue(
			$this->coupon_helper->is_coupon_synced( $reloaded_coupon )
		);
	}

	protected function assert_coupon_unsynced( $coupon ) {
		$reloaded_coupon = new WC_Coupon( $coupon->get_id() );
		$this->assertFalse(
			$this->coupon_helper->is_coupon_synced( $reloaded_coupon )
		);
	}

	protected function assert_coupon_has_errors( $coupon ) {
		$reloaded_coupon = new WC_Coupon( $coupon->get_id() );
		$this->assertNotEmpty(
			$this->coupon_meta->get_errors( $reloaded_coupon )
		);
		$this->assertEquals(
			[ CouponSyncer::INTERNAL_ERROR_CODE => self::INTERNAL_ERROR_MSG ],
			$this->coupon_meta->get_errors( $reloaded_coupon )
		);
		$this->assertEquals(
			SyncStatus::HAS_ERRORS,
			$this->coupon_meta->get_sync_status( $reloaded_coupon )
		);
	}

	/**
	 * Mock the Merchant API promotion services so that inserting the given coupon
	 * succeeds and any other coupon fails with an internal error.
	 *
	 * @param WC_Coupon $coupon
	 */
	protected function mock_promotions_service( WC_Coupon $coupon ): void {
		$this->data_sources->expects( $this->any() )
			->method( 'ensure_promotion_data_source_for' )
			->willReturn( 'datasources/v1/accounts/12345/dataSources/67890' );

		$callback = function ( $data_source, $promotion ) use ( $coupon ) {
			if ( ( $promotion['attributes']['genericRedemptionCode'] ?? null ) === $coupon->get_code() ) {
				return [ 'promotionId' => sprintf( 'gla_%d', $coupon->get_id() ) ];
			}

			throw new MerchantApiException(
				CouponSyncer::INTERNAL_ERROR_CODE,
				[ 'error' => [ 'message' => self::INTERNAL_ERROR_MSG ] ],
				'insert_promotion'
			);
		};

		$this->promotions_service->expects( $this->any() )
			->method( 'insert_promotion' )
			->willReturnCallback( $callback );
	}

	/**
	 * Function to return an instance of CouponSyncer.
	 *
	 * @param object[] $args
	 */
	private function get_coupon_syncer( $args = [] ): CouponSyncer {
		$args['promotions_service'] = $args['promotions_service'] ?? $this->promotions_service;
		$args['data_sources']       = $args['data_sources'] ?? $this->data_sources;
		$args['coupon_helper']      = $args['coupon_helper'] ?? $this->coupon_helper;
		$args['validator']          = $args['validator'] ?? $this->validator;
		$args['merchant_center']    = $args['merchant_center'] ?? $this->merchant_center;
		$args['target_audience']    = $args['target_audience'] ?? $this->target_audience;
		$args['wc']                 = $args['wc'] ?? $this->wc;

		return new CouponSyncer(
			$args['promotions_service'],
			$args['data_sources'],
			$args['coupon_helper'],
			$args['validator'],
			$args['merchant_center'],
			$args['target_audience'],
			$args['wc']
		);
	}

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->merchant_center = $this->createMock( MerchantCenterService::class );
		$this->merchant_center->expects( $this->any() )
			->method( 'is_ready_for_syncing' )
			->willReturn( true );
		$this->merchant_center->expects( $this->any() )
			->method( 'should_push' )
			->willReturn( true );
		$this->merchant_center->expects( $this->any() )
			->method( 'is_promotion_supported_country' )
			->willReturn( true );

		$this->target_audience = $this->createMock( TargetAudience::class );
		$this->target_audience->expects( $this->any() )
			->method( 'get_main_target_country' )
			->willReturn( $this->get_sample_target_country() );

		$this->promotions_service = $this->createMock( MapiPromotionsService::class );
		$this->data_sources       = $this->createMock( MapiDataSourcesService::class );
		$this->validator          = $this->createMock( ValidatorInterface::class );

		$this->coupon_meta   = $this->container->get( CouponMetaHandler::class );
		$this->coupon_helper = $this->container->get( CouponHelper::class );
		$this->wc            = $this->container->get( WC::class );
		$this->coupon_syncer = $this->get_coupon_syncer();
	}
}
