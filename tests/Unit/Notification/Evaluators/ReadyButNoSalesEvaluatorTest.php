<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Notification\Evaluators;

use Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators\ReadyButNoSalesEvaluator;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationCacheKeys;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationPriorities;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationSnoozeDurations;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;
use WC_Shipping_Method;
use WC_Shipping_Zone;

defined( 'ABSPATH' ) || exit;

/**
 * Class ReadyButNoSalesEvaluatorTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Notification\Evaluators
 */
class ReadyButNoSalesEvaluatorTest extends UnitTest {

	/** @var MockObject|WC $wc */
	protected $wc;

	/** @var ReadyButNoSalesEvaluator $evaluator */
	protected $evaluator;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->wc        = $this->createMock( WC::class );
		$this->evaluator = new ReadyButNoSalesEvaluator( $this->wc );
	}

	public function test_get_id() {
		$this->assertEquals( 'ready-but-no-sales', $this->evaluator->get_id() );
	}

	public function test_get_priority() {
		$this->assertEquals( NotificationPriorities::READY_BUT_NO_SALES, $this->evaluator->get_priority() );
	}

	public function test_get_snooze_duration() {
		$this->assertEquals( NotificationSnoozeDurations::READY_BUT_NO_SALES, $this->evaluator->get_snooze_duration() );
	}

	public function test_should_show_when_store_is_ready_and_has_no_revenue_orders() {
		$evaluator = $this->create_evaluator_with_revenue_orders( false );

		$this->wc->method( 'has_enabled_payment_gateways' )->willReturn( true );

		$this->assertTrue( $evaluator->should_show() );
	}

	public function test_should_show_when_only_default_zone_has_shipping_methods() {
		$default_zone = $this->createMock( WC_Shipping_Zone::class );
		$default_zone->method( 'get_shipping_methods' )
			->with( true )
			->willReturn( [ $this->createMock( WC_Shipping_Method::class ) ] );

		$this->wc->method( 'has_enabled_payment_gateways' )->willReturn( true );
		$this->wc->method( 'get_shipping_zones' )->willReturn( [] );
		$this->wc->method( 'get_shipping_zone' )->with( 0 )->willReturn( $default_zone );

		$evaluator = $this->create_evaluator_with_revenue_orders( false, null );

		$this->assertTrue( $evaluator->should_show() );
	}

	public function test_should_not_show_when_payment_gateways_are_missing() {
		$evaluator = $this->create_evaluator_with_revenue_orders( false );

		$this->wc->method( 'has_enabled_payment_gateways' )->willReturn( false );
		$evaluator->expects( $this->never() )->method( 'store_has_any_enabled_shipping_method' );

		$this->assertFalse( $evaluator->should_show() );
	}

	public function test_should_not_show_when_shipping_methods_are_missing() {
		$evaluator = $this->create_evaluator_with_revenue_orders( false, false );

		$this->wc->method( 'has_enabled_payment_gateways' )->willReturn( true );

		$this->assertFalse( $evaluator->should_show() );
	}

	public function test_should_not_show_when_revenue_orders_exist() {
		$evaluator = $this->create_evaluator_with_revenue_orders( true );

		$this->wc->method( 'has_enabled_payment_gateways' )->willReturn( true );

		$this->assertFalse( $evaluator->should_show() );
	}

	public function test_cache_hit_skips_query() {
		$evaluator = $this->create_evaluator_with_revenue_orders( false );
		$this->login_as_administrator();

		set_transient( NotificationCacheKeys::for_site( 'ready-but-no-sales' ), 1, HOUR_IN_SECONDS );

		$this->wc->expects( $this->never() )->method( 'has_enabled_payment_gateways' );
		$evaluator->expects( $this->never() )->method( 'store_has_any_enabled_shipping_method' );
		$evaluator->expects( $this->never() )->method( 'has_minimum_revenue_orders' );

		$this->assertTrue( $evaluator->should_show() );
	}

	/**
	 * Create a test evaluator with a stubbed revenue orders check.
	 *
	 * @param bool      $has_revenue_orders
	 * @param bool|null $has_shipping_methods When null, the real shipping check runs.
	 *
	 * @return ReadyButNoSalesEvaluator|MockObject
	 */
	private function create_evaluator_with_revenue_orders( bool $has_revenue_orders, ?bool $has_shipping_methods = true ): ReadyButNoSalesEvaluator {
		$only_methods = [ 'has_minimum_revenue_orders' ];

		if ( null !== $has_shipping_methods ) {
			$only_methods[] = 'store_has_any_enabled_shipping_method';
		}

		$evaluator = $this->getMockBuilder( ReadyButNoSalesEvaluator::class )
			->setConstructorArgs( [ $this->wc ] )
			->onlyMethods( $only_methods )
			->getMock();

		$evaluator->method( 'has_minimum_revenue_orders' )->willReturn( $has_revenue_orders );

		if ( null !== $has_shipping_methods ) {
			$evaluator->method( 'store_has_any_enabled_shipping_method' )->willReturn( $has_shipping_methods );
		}

		return $evaluator;
	}
}
