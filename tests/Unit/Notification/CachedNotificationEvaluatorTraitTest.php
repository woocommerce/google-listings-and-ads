<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Notification;

use Automattic\WooCommerce\GoogleListingsAndAds\Notification\CachedNotificationEvaluatorInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\CachedNotificationEvaluatorTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationCacheKeys;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;

defined( 'ABSPATH' ) || exit;

/**
 * Class CachedNotificationEvaluatorTraitTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Notification
 */
class CachedNotificationEvaluatorTraitTest extends UnitTest {

	public const ID = 'cached-test';

	/** @var object Evaluator using the trait, with a configurable condition and an evaluation counter. */
	protected $evaluator;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		// phpcs:ignore Universal.Classes.RequireAnonClassParentheses.Missing
		$this->evaluator = new class {
			use CachedNotificationEvaluatorTrait;

			/** @var bool */
			public $condition = true;

			/** @var int */
			public $evaluations = 0;

			/** @var callable|null */
			public $during_evaluation;

			public function get_id(): string {
				return CachedNotificationEvaluatorTraitTest::ID;
			}

			public function get_priority(): int {
				return 10;
			}

			public function get_snooze_duration(): ?int {
				return null;
			}

			protected function evaluate_condition(): bool {
				++$this->evaluations;

				if ( $this->during_evaluation ) {
					( $this->during_evaluation )();
				}

				return $this->condition;
			}
		};
	}

	public function test_nothing_stored_shows_nothing_and_needs_refresh() {
		$this->assertFalse( $this->evaluator->should_show() );
		$this->assertTrue( $this->evaluator->needs_refresh() );
		$this->assertSame( 0, $this->evaluator->evaluations );
	}

	public function test_refresh_stores_a_fresh_result() {
		$this->assertTrue( $this->evaluator->refresh() );

		$this->assertTrue( $this->evaluator->should_show() );
		$this->assertFalse( $this->evaluator->needs_refresh() );
		$this->assertSame( 1, $this->evaluator->evaluations );
	}

	public function test_should_show_never_evaluates_the_condition() {
		$this->evaluator->refresh();
		$this->evaluator->should_show();
		$this->evaluator->should_show();

		$this->assertSame( 1, $this->evaluator->evaluations );
	}

	public function test_refresh_stores_a_negative_result() {
		$this->evaluator->condition = false;

		$this->assertFalse( $this->evaluator->refresh() );

		$this->assertFalse( $this->evaluator->should_show() );
		$this->assertFalse( $this->evaluator->needs_refresh() );
	}

	public function test_legacy_plain_value_is_shown_and_needs_refresh() {
		set_transient( $this->get_key(), 1, HOUR_IN_SECONDS );

		$this->assertTrue( $this->evaluator->should_show() );
		$this->assertTrue( $this->evaluator->needs_refresh() );
	}

	public function test_result_older_than_stale_after_needs_refresh_but_is_still_shown() {
		$this->store( 1, time() - CachedNotificationEvaluatorInterface::STALE_AFTER - 1, 0 );

		$this->assertTrue( $this->evaluator->should_show() );
		$this->assertTrue( $this->evaluator->needs_refresh() );
	}

	public function test_invalidated_result_needs_refresh() {
		$computed_at = time() - 60;
		$this->store( 1, $computed_at, $computed_at + 1 );

		$this->assertTrue( $this->evaluator->needs_refresh() );
	}

	public function test_invalidation_before_the_refresh_started_is_cleared_by_it() {
		$this->store( 1, time() - 120, time() - 60 );

		$this->evaluator->refresh();

		$this->assertFalse( $this->evaluator->needs_refresh() );
		$this->assertSame( 0, get_transient( $this->get_key() )['invalidated_at'] );
	}

	public function test_invalidation_during_refresh_keeps_the_result_stale() {
		$invalidated_at = time() + 1;

		$this->evaluator->during_evaluation = function () use ( $invalidated_at ) {
			$this->store( 1, time() - 60, $invalidated_at );
		};

		$this->evaluator->refresh();

		$this->assertTrue( $this->evaluator->needs_refresh() );
		$this->assertSame( $invalidated_at, get_transient( $this->get_key() )['invalidated_at'] );
	}

	public function test_invalidate_right_after_a_refresh_marks_the_result_stale() {
		$this->evaluator->refresh();
		$this->evaluator->invalidate();

		$this->assertTrue( $this->evaluator->needs_refresh() );
		$this->assertTrue( $this->evaluator->should_show() );
		$this->assertSame( 1, $this->evaluator->evaluations );
	}

	public function test_invalidate_with_nothing_stored_stores_nothing() {
		$this->evaluator->invalidate();

		$this->assertFalse( get_transient( $this->get_key() ) );
	}

	public function test_invalidate_keeps_a_legacy_plain_value() {
		set_transient( $this->get_key(), 1, HOUR_IN_SECONDS );

		$this->evaluator->invalidate();

		$this->assertSame( 1, (int) get_transient( $this->get_key() ) );
	}

	/**
	 * Store a result in the timestamped format.
	 *
	 * @param int $value          The stored result.
	 * @param int $computed_at    When the result was computed.
	 * @param int $invalidated_at When the result was last invalidated.
	 */
	private function store( int $value, int $computed_at, int $invalidated_at ): void {
		set_transient(
			$this->get_key(),
			[
				'value'          => $value,
				'computed_at'    => $computed_at,
				'invalidated_at' => $invalidated_at,
			],
			DAY_IN_SECONDS
		);
	}

	/**
	 * @return string
	 */
	private function get_key(): string {
		return NotificationCacheKeys::for_site( self::ID );
	}
}
