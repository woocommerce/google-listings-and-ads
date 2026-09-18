<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Notification\Evaluators;

use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\TargetAudience;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators\CouponsNotSyncedEvaluator;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationCacheKeys;
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

	public function test_get_invalidation_hooks_covers_create_update_and_sync() {
		// Creating/updating a coupon can make the notification appear; syncing one can make it
		// disappear. All three transitions must bust the cache, or a stale result sticks until TTL.
		$this->assertSame(
			[
				'woocommerce_new_coupon',
				'woocommerce_update_coupon',
				'woocommerce_gla_updated_coupon',
			],
			$this->evaluator->get_invalidation_hooks()
		);
	}

	public function test_should_show_when_supported_coupon_exists_and_none_synced() {
		$supported_coupon   = $this->create_coupon( 1, false, [] );
		$unsupported_coupon = $this->create_coupon( 2, true, [ 'test@example.com' ] );

		$evaluator = $this->create_evaluator(
			true,
			false,
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
			true,
			false,
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

	public function test_should_not_show_when_only_unsupported_coupons_exist() {
		$virtual_coupon          = $this->create_coupon( 1, true, [] );
		$email_restricted_coupon = $this->create_coupon( 2, false, [ 'test@example.com' ] );
		$sale_excluded_coupon    = $this->create_coupon( 3, false, [], true );

		$evaluator = $this->create_evaluator(
			true,
			false,
			[
				1 => [ 1, 2, 3 ],
				2 => [],
			],
			[
				1 => $virtual_coupon,
				2 => $email_restricted_coupon,
				3 => $sale_excluded_coupon,
			]
		);

		$this->assertFalse( $evaluator->should_show() );
	}

	public function test_should_not_show_when_target_market_is_not_supported() {
		$evaluator = $this->create_evaluator( false, false, [ 1 => [ 1 ] ], [] );

		$evaluator->expects( $this->never() )->method( 'has_synced_coupon' );
		$evaluator->expects( $this->never() )->method( 'get_coupon_post_ids' );

		$this->assertFalse( $evaluator->should_show() );
	}

	public function test_should_not_show_when_a_coupon_is_already_synced() {
		$evaluator = $this->create_evaluator( true, true, [ 1 => [ 1 ] ], [] );

		$evaluator->expects( $this->never() )->method( 'get_coupon_post_ids' );

		$this->assertFalse( $evaluator->should_show() );
	}

	public function test_should_not_show_when_merchant_has_no_coupons() {
		$evaluator = $this->create_evaluator( true, false, [], [] );

		$this->assertFalse( $evaluator->should_show() );
	}

	public function test_scan_is_bounded_to_the_computed_page_count() {
		// A supported coupon lives on page 2, but only 1 page is reported. The scan must
		// stay bounded by the page count and never page past it, so the coupon on the
		// unreported page is not reached (guards against the previous unbounded loop).
		$supported_coupon = $this->create_coupon( 2, false, [] );

		$evaluator = $this->create_evaluator(
			true,
			false,
			[
				1 => [ 1 ],
				2 => [ 2 ],
			],
			[
				1 => $this->create_coupon( 1, true, [] ),
				2 => $supported_coupon,
			],
			1
		);

		$this->assertFalse( $evaluator->should_show() );
	}

	public function test_scan_spans_all_reported_pages_to_find_a_supported_coupon() {
		// Two full pages exist and the page count reports both. The only supported coupon
		// lives on page 2, so the scan must page past page 1 to reach it.
		$evaluator = $this->create_evaluator(
			true,
			false,
			[
				1 => [ 1 ],
				2 => [ 2 ],
			],
			[
				1 => $this->create_coupon( 1, true, [] ),
				2 => $this->create_coupon( 2, false, [] ),
			],
			2
		);

		$this->assertTrue( $evaluator->should_show() );
	}

	public function test_cache_hit_skips_query() {
		$evaluator = $this->create_evaluator( true, false, [ 1 => [ 1 ] ], [] );
		$this->login_as_administrator();

		set_transient( NotificationCacheKeys::for_site( 'coupons-not-synced' ), 0, HOUR_IN_SECONDS );

		$evaluator->expects( $this->never() )->method( 'get_coupon_post_ids' );

		$this->assertFalse( $evaluator->should_show() );
	}

	/**
	 * Create a test evaluator with stubbed dependencies and query results.
	 *
	 * @param bool                  $supported_market Whether the target market supports coupon channel visibility.
	 * @param bool                  $has_synced       Whether at least one coupon is already synced to Google.
	 * @param array<int, int[]>     $post_ids_by_page Coupon post IDs returned per page.
	 * @param array<int, WC_Coupon> $coupons_by_id    Coupon objects keyed by post ID.
	 * @param int|null              $total_pages      Page count to report; defaults to the number of pages provided.
	 *
	 * @return CouponsNotSyncedEvaluator|MockObject
	 */
	private function create_evaluator( bool $supported_market, bool $has_synced, array $post_ids_by_page, array $coupons_by_id, ?int $total_pages = null ): CouponsNotSyncedEvaluator {
		$merchant_center = $this->createMock( MerchantCenterService::class );
		$target_audience = $this->createMock( TargetAudience::class );

		$target_audience->method( 'get_main_target_country' )->willReturn( 'US' );
		$merchant_center->method( 'is_promotion_supported_country' )->willReturn( $supported_market );

		$evaluator = $this->getMockBuilder( CouponsNotSyncedEvaluator::class )
			->setConstructorArgs( [ $merchant_center, $target_audience ] )
			->onlyMethods( [ 'has_synced_coupon', 'get_coupon_page_count', 'get_coupon_post_ids', 'create_coupon' ] )
			->getMock();

		$evaluator->method( 'has_synced_coupon' )->willReturn( $has_synced );

		$evaluator->method( 'get_coupon_page_count' )->willReturn( $total_pages ?? count( $post_ids_by_page ) );

		$evaluator->method( 'get_coupon_post_ids' )
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
	 * Create a mocked coupon for testing coupon support.
	 *
	 * @param int      $id
	 * @param bool     $virtual
	 * @param string[] $email_restrictions
	 * @param bool     $exclude_sale_items
	 *
	 * @return WC_Coupon
	 */
	private function create_coupon( int $id, bool $virtual, array $email_restrictions, bool $exclude_sale_items = false ): WC_Coupon {
		$coupon = $this->createMock( WC_Coupon::class );
		$coupon->method( 'get_id' )->willReturn( $id );
		$coupon->method( 'get_virtual' )->willReturn( $virtual );
		$coupon->method( 'get_email_restrictions' )->willReturn( $email_restrictions );
		$coupon->method( 'get_exclude_sale_items' )->willReturn( $exclude_sale_items );

		return $coupon;
	}
}
