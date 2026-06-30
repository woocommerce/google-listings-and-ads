<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Notification\Evaluators;

use Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators\CouponsNotSyncedEvaluator;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationPriorities;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationSnoozeDurations;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;
use WC_Coupon;

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

		$this->evaluator = new CouponsNotSyncedEvaluator();
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

		$evaluator = $this->create_evaluator(
			[
				1 => [ 2 ],
				2 => [ 1 ],
			],
			[
				2 => $unsupported_coupon,
				1 => $supported_coupon,
			]
		);

		$this->assertTrue( $evaluator->should_show() );
	}

	public function test_should_show_when_supported_coupon_is_on_same_page_as_unsupported_coupons() {
		$unsupported_coupon = $this->create_coupon( 1, true, [ 'test@example.com' ] );
		$supported_coupon   = $this->create_coupon( 2, false, [] );

		$evaluator = $this->create_evaluator(
			[
				1 => [ 1, 2 ],
			],
			[
				1 => $unsupported_coupon,
				2 => $supported_coupon,
			]
		);

		$this->assertTrue( $evaluator->should_show() );
	}

	public function test_should_not_show_when_supported_coupons_are_synced() {
		$evaluator = $this->create_evaluator( [], [] );

		$this->assertFalse( $evaluator->should_show() );
	}

	public function test_should_not_show_when_only_unsupported_coupons_are_not_synced() {
		$unsupported_coupon = $this->create_coupon( 1, true, [ 'test@example.com' ] );

		$evaluator = $this->create_evaluator(
			[
				1 => [ 1 ],
				2 => [],
			],
			[
				1 => $unsupported_coupon,
			]
		);

		$this->assertFalse( $evaluator->should_show() );
	}

	public function test_cache_hit_skips_query() {
		$evaluator = $this->create_evaluator( [ 1 => [ 1 ] ], [] );
		$user_id   = $this->login_as_administrator();

		set_transient( 'gla_notif_coupons-not-synced_' . $user_id, 0, HOUR_IN_SECONDS );

		$evaluator->expects( $this->never() )->method( 'get_not_synced_coupon_post_ids' );

		$this->assertFalse( $evaluator->should_show() );
	}

	/**
	 * Create a test evaluator with stubbed query pages and coupons.
	 *
	 * @param array<int, int[]>     $post_ids_by_page Post IDs returned per page.
	 * @param array<int, WC_Coupon> $coupons_by_id    Coupon objects keyed by post ID.
	 *
	 * @return CouponsNotSyncedEvaluator|MockObject
	 */
	private function create_evaluator( array $post_ids_by_page, array $coupons_by_id ): CouponsNotSyncedEvaluator {
		$evaluator = $this->getMockBuilder( CouponsNotSyncedEvaluator::class )
			->onlyMethods( [ 'get_not_synced_coupon_post_ids', 'create_coupon' ] )
			->getMock();

		$evaluator->method( 'get_not_synced_coupon_post_ids' )
			->willReturnCallback(
				static function ( int $page ) use ( $post_ids_by_page ) {
					return $post_ids_by_page[ $page ] ?? [];
				}
			);

		if ( ! empty( $coupons_by_id ) ) {
			$evaluator->method( 'create_coupon' )
				->willReturnCallback(
					static function ( int $post_id ) use ( $coupons_by_id ) {
						return $coupons_by_id[ $post_id ];
					}
				);
		}

		return $evaluator;
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
