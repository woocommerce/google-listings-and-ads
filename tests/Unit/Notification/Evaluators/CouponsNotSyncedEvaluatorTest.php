<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Notification\Evaluators;

use Automattic\WooCommerce\GoogleListingsAndAds\Coupon\CouponHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators\CouponsNotSyncedEvaluator;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationPriorities;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationSnoozeDurations;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\SyncStatus;
use PHPUnit\Framework\MockObject\MockObject;
use WC_Coupon;

defined( 'ABSPATH' ) || exit;

/**
 * Class CouponsNotSyncedEvaluatorTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Notification\Evaluators
 */
class CouponsNotSyncedEvaluatorTest extends UnitTest {

	/** @var MockObject|CouponHelper $coupon_helper */
	protected $coupon_helper;

	/** @var CouponsNotSyncedEvaluator $evaluator */
	protected $evaluator;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->coupon_helper = $this->createMock( CouponHelper::class );
		$this->evaluator     = new CouponsNotSyncedEvaluator( $this->coupon_helper );
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

	public function test_should_show_when_supported_coupon_is_not_synced() {
		$supported_coupon   = $this->create_coupon( 1, false, [] );
		$unsupported_coupon = $this->create_coupon( 2, true, [ 'test@example.com' ] );

		$evaluator = $this->create_evaluator_with_coupons( [ $unsupported_coupon, $supported_coupon ] );

		$this->coupon_helper->method( 'get_sync_status' )
			->willReturnCallback(
				function ( WC_Coupon $coupon ) use ( $supported_coupon ) {
					if ( $coupon->get_id() === $supported_coupon->get_id() ) {
						return SyncStatus::NOT_SYNCED;
					}

					return SyncStatus::SYNCED;
				}
			);

		$this->assertTrue( $evaluator->should_show() );
	}

	public function test_should_not_show_when_supported_coupons_are_synced() {
		$supported_coupon = $this->create_coupon( 1, false, [] );
		$evaluator        = $this->create_evaluator_with_coupons( [ $supported_coupon ] );

		$this->coupon_helper->method( 'get_sync_status' )->willReturn( SyncStatus::SYNCED );

		$this->assertFalse( $evaluator->should_show() );
	}

	public function test_should_not_show_when_only_unsupported_coupons_are_not_synced() {
		$unsupported_coupon = $this->create_coupon( 1, true, [ 'test@example.com' ] );
		$evaluator          = $this->create_evaluator_with_coupons( [ $unsupported_coupon ] );

		$this->coupon_helper->expects( $this->never() )->method( 'get_sync_status' );

		$this->assertFalse( $evaluator->should_show() );
	}

	public function test_cache_hit_skips_query() {
		$evaluator = $this->create_evaluator_with_coupons( [ $this->create_coupon( 1, false, [] ) ] );
		$user_id   = $this->login_as_administrator();

		set_transient( 'gla_notif_coupons-not-synced_' . $user_id, 0, HOUR_IN_SECONDS );

		$this->coupon_helper->expects( $this->never() )->method( 'get_sync_status' );

		$this->assertFalse( $evaluator->should_show() );
		$this->assertFalse( $evaluator->query_called );
	}

	/**
	 * Create a test evaluator with stubbed coupons.
	 *
	 * @param WC_Coupon[] $coupons
	 *
	 * @return CouponsNotSyncedEvaluator&object{query_called:bool}
	 */
	private function create_evaluator_with_coupons( array $coupons ): CouponsNotSyncedEvaluator {
		return new class( $this->coupon_helper, $coupons ) extends CouponsNotSyncedEvaluator {
			/** @var bool */
			public $query_called = false;

			/** @var WC_Coupon[] */
			private $coupons;

			/**
			 * @param CouponHelper $coupon_helper
			 * @param WC_Coupon[]  $coupons
			 */
			public function __construct( CouponHelper $coupon_helper, array $coupons ) {
				parent::__construct( $coupon_helper );
				$this->coupons = $coupons;
			}

			protected function get_coupons(): array {
				$this->query_called = true;

				return $this->coupons;
			}
		};
	}

	/**
	 * Create a coupon for testing.
	 *
	 * @param int      $id
	 * @param bool     $virtual
	 * @param string[] $email_restrictions
	 *
	 * @return WC_Coupon
	 */
	private function create_coupon( int $id, bool $virtual, array $email_restrictions ): WC_Coupon {
		$coupon = $this->createMock( WC_Coupon::class );
		$coupon->method( 'get_id' )->willReturn( $id );
		$coupon->method( 'get_virtual' )->willReturn( $virtual );
		$coupon->method( 'get_email_restrictions' )->willReturn( $email_restrictions );
		$coupon->method( 'get_exclude_sale_items' )->willReturn( false );

		return $coupon;
	}
}
