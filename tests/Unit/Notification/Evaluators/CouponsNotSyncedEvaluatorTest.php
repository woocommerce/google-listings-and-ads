<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Notification\Evaluators;

use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\TargetAudience;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators\CouponsNotSyncedEvaluator;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationPriorities;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationSnoozeDurations;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class CouponsNotSyncedEvaluatorTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Notification\Evaluators
 */
class CouponsNotSyncedEvaluatorTest extends UnitTest {

	/** @var CouponsNotSyncedEvaluator $evaluator */
	protected $evaluator;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->evaluator = new CouponsNotSyncedEvaluator(
			$this->createMock( MerchantCenterService::class ),
			$this->createMock( TargetAudience::class )
		);
	}

	public function test_get_id() {
		$this->assertEquals( 'coupons-not-synced', $this->evaluator->get_id() );
	}

	public function test_get_priority() {
		$this->assertEquals( NotificationPriorities::COUPONS_NOT_SYNCED, $this->evaluator->get_priority() );
	}

	public function test_get_snooze_duration() {
		$this->assertEquals( NotificationSnoozeDurations::COUPONS_NOT_SYNCED, $this->evaluator->get_snooze_duration() );
	}

	public function test_should_show_when_merchant_has_coupon_and_none_synced() {
		$evaluator = $this->create_evaluator( true, true, false );

		$this->assertTrue( $evaluator->should_show() );
	}

	public function test_should_not_show_when_target_market_is_not_supported() {
		$evaluator = $this->create_evaluator( false, true, false );

		$evaluator->expects( $this->never() )->method( 'has_coupon' );
		$evaluator->expects( $this->never() )->method( 'has_synced_coupon' );

		$this->assertFalse( $evaluator->should_show() );
	}

	public function test_should_not_show_when_merchant_has_no_coupons() {
		$evaluator = $this->create_evaluator( true, false, false );

		$evaluator->expects( $this->never() )->method( 'has_synced_coupon' );

		$this->assertFalse( $evaluator->should_show() );
	}

	public function test_should_not_show_when_a_coupon_is_already_synced() {
		$evaluator = $this->create_evaluator( true, true, true );

		$this->assertFalse( $evaluator->should_show() );
	}

	public function test_cache_hit_skips_query() {
		$evaluator = $this->create_evaluator( true, true, false );
		$user_id   = $this->login_as_administrator();

		set_transient( 'gla_notif_coupons-not-synced_' . $user_id, 0, HOUR_IN_SECONDS );

		$evaluator->expects( $this->never() )->method( 'has_coupon' );

		$this->assertFalse( $evaluator->should_show() );
	}

	/**
	 * Create a test evaluator with stubbed dependencies and query results.
	 *
	 * @param bool $supported_market Whether the target market supports coupon channel visibility.
	 * @param bool $has_coupon       Whether the merchant has at least one coupon.
	 * @param bool $has_synced       Whether at least one coupon is already synced to Google.
	 *
	 * @return CouponsNotSyncedEvaluator|MockObject
	 */
	private function create_evaluator( bool $supported_market, bool $has_coupon, bool $has_synced ): CouponsNotSyncedEvaluator {
		$merchant_center = $this->createMock( MerchantCenterService::class );
		$target_audience = $this->createMock( TargetAudience::class );

		$target_audience->method( 'get_main_target_country' )->willReturn( 'US' );
		$merchant_center->method( 'is_promotion_supported_country' )->willReturn( $supported_market );

		$evaluator = $this->getMockBuilder( CouponsNotSyncedEvaluator::class )
			->setConstructorArgs( [ $merchant_center, $target_audience ] )
			->onlyMethods( [ 'has_coupon', 'has_synced_coupon' ] )
			->getMock();

		$evaluator->method( 'has_coupon' )->willReturn( $has_coupon );
		$evaluator->method( 'has_synced_coupon' )->willReturn( $has_synced );

		return $evaluator;
	}
}
