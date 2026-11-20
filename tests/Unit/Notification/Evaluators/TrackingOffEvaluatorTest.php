<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Notification\Evaluators;

use Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators\TrackingOffEvaluator;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationPriorities;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationSnoozeDurations;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;

defined( 'ABSPATH' ) || exit;

/**
 * Class TrackingOffEvaluatorTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Notification\Evaluators
 */
class TrackingOffEvaluatorTest extends UnitTest {

	/** @var TrackingOffEvaluator $evaluator */
	protected $evaluator;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->evaluator = new TrackingOffEvaluator();
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
		update_option( 'woocommerce_allow_tracking', 'no' );

		$this->assertTrue( $this->evaluator->should_show() );
	}

	public function test_should_not_show_when_tracking_enabled() {
		update_option( 'woocommerce_allow_tracking', 'yes' );

		$this->assertFalse( $this->evaluator->should_show() );
	}

	public function test_should_show_when_tracking_disabled_by_filter() {
		// Even with the opt-in on, a tracking filter can turn tracking off. The signal
		// should track that (matching isWCTracksEnabled), not just the raw option.
		update_option( 'woocommerce_allow_tracking', 'yes' );
		add_filter( 'woocommerce_apply_tracking', '__return_false' );

		$this->assertTrue( $this->evaluator->should_show() );

		remove_filter( 'woocommerce_apply_tracking', '__return_false' );
	}
}
