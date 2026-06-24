<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Notification\Evaluators;

use Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators\TrackingOffEvaluator;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationPriorities;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationSnoozeDurations;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class TrackingOffEvaluatorTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Notification\Evaluators
 */
class TrackingOffEvaluatorTest extends UnitTest {

	/** @var MockObject|WP $wp */
	protected $wp;

	/** @var TrackingOffEvaluator $evaluator */
	protected $evaluator;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->wp        = $this->createMock( WP::class );
		$this->evaluator = new TrackingOffEvaluator();
		$this->evaluator->set_wp_proxy_object( $this->wp );
	}

	public function test_get_id() {
		$this->assertEquals( 'tracking-off', $this->evaluator->get_id() );
	}

	public function test_get_priority() {
		$this->assertEquals( NotificationPriorities::TRACKING_OFF, $this->evaluator->get_priority() );
	}

	public function test_get_snooze_duration() {
		$this->assertEquals( NotificationSnoozeDurations::TRACKING_OFF, $this->evaluator->get_snooze_duration() );
	}

	public function test_should_show_when_tracking_disabled() {
		$this->wp->method( 'get_option' )
			->with( 'woocommerce_allow_tracking', 'no' )
			->willReturn( 'no' );

		$this->assertTrue( $this->evaluator->should_show() );
	}

	public function test_should_not_show_when_tracking_enabled() {
		$this->wp->method( 'get_option' )
			->with( 'woocommerce_allow_tracking', 'no' )
			->willReturn( 'yes' );

		$this->assertFalse( $this->evaluator->should_show() );
	}
}
