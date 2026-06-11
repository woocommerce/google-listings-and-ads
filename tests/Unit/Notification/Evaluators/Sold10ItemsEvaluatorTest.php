<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Notification\Evaluators;

use Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators\Sold10ItemsEvaluator;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationPriorities;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationSnoozeDurations;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;

defined( 'ABSPATH' ) || exit;

/**
 * Class Sold10ItemsEvaluatorTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Notification\Evaluators
 */
class Sold10ItemsEvaluatorTest extends UnitTest {

	/** @var Sold10ItemsEvaluator $evaluator */
	protected $evaluator;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->evaluator = new Sold10ItemsEvaluator();
	}

	public function test_get_id() {
		$this->assertEquals( 'sold-10-items', $this->evaluator->get_id() );
	}

	public function test_get_priority() {
		$this->assertEquals( NotificationPriorities::SOLD_10_ITEMS, $this->evaluator->get_priority() );
	}

	public function test_get_snooze_duration() {
		$this->assertEquals( NotificationSnoozeDurations::SOLD_10_ITEMS, $this->evaluator->get_snooze_duration() );
	}

	public function test_should_show_when_ten_or_more_completed_orders() {
		$evaluator = $this->create_evaluator_with_order_count( 10 );

		$this->assertTrue( $evaluator->should_show() );
	}

	public function test_should_not_show_when_fewer_than_ten_completed_orders() {
		$evaluator = $this->create_evaluator_with_order_count( 9 );

		$this->assertFalse( $evaluator->should_show() );
	}

	public function test_cache_hit_skips_query() {
		$evaluator = $this->create_evaluator_with_order_count( 0 );
		$user_id   = $this->login_as_administrator();

		set_transient( 'gla_notif_sold-10-items_' . $user_id, 1, HOUR_IN_SECONDS );

		$this->assertTrue( $evaluator->should_show() );
		$this->assertFalse( $evaluator->query_called );
	}

	/**
	 * Create a test evaluator with a stubbed completed order count.
	 *
	 * @param int $order_count
	 *
	 * @return Sold10ItemsEvaluator&object{query_called:bool}
	 */
	private function create_evaluator_with_order_count( int $order_count ): Sold10ItemsEvaluator {
		return new class( $order_count ) extends Sold10ItemsEvaluator {
			/** @var bool */
			public $query_called = false;

			/** @var int */
			private $order_count;

			/**
			 * @param int $order_count
			 */
			public function __construct( int $order_count ) {
				$this->order_count = $order_count;
			}

			protected function get_completed_order_count(): int {
				$this->query_called = true;

				return $this->order_count;
			}
		};
	}
}
