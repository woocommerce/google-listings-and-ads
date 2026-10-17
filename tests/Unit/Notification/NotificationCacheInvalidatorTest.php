<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Notification;

use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\Container\Container;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\InvalidatableNotificationEvaluatorInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationCacheInvalidator;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationCacheKeys;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationEvaluatorInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class NotificationCacheInvalidatorTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Notification
 */
class NotificationCacheInvalidatorTest extends UnitTest {

	/** @var MockObject|Container $container */
	protected $container;

	/** @var NotificationCacheInvalidator $invalidator */
	protected $invalidator;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->container   = $this->createMock( Container::class );
		$this->invalidator = new NotificationCacheInvalidator();
		$this->invalidator->set_container( $this->container );
	}

	/**
	 * Clears the real WordPress actions register() binds, so listeners from one test do not
	 * fire in the next.
	 */
	public function tearDown(): void {
		foreach ( [ 'woocommerce_gla_updated_campaign', 'woocommerce_gla_updated_coupon', 'hook_a', 'hook_b' ] as $hook ) {
			remove_all_actions( $hook );
		}

		parent::tearDown();
	}

	public function test_firing_a_declared_hook_clears_the_evaluators_site_transient() {
		$this->set_evaluators(
			[ $this->create_invalidatable_evaluator( 'paused-campaign', [ 'woocommerce_gla_updated_campaign' ] ) ]
		);
		$this->invalidator->register();

		set_transient( NotificationCacheKeys::for_site( 'paused-campaign' ), 1, HOUR_IN_SECONDS );

		do_action( 'woocommerce_gla_updated_campaign', 123, 'edited' );

		$this->assertFalse( get_transient( NotificationCacheKeys::for_site( 'paused-campaign' ) ) );
	}

	public function test_hook_only_clears_the_evaluator_that_declared_it() {
		$this->set_evaluators(
			[
				$this->create_invalidatable_evaluator( 'paused-campaign', [ 'woocommerce_gla_updated_campaign' ] ),
				$this->create_invalidatable_evaluator( 'coupons-not-synced', [ 'woocommerce_gla_updated_coupon' ] ),
			]
		);
		$this->invalidator->register();

		set_transient( NotificationCacheKeys::for_site( 'paused-campaign' ), 1, HOUR_IN_SECONDS );
		set_transient( NotificationCacheKeys::for_site( 'coupons-not-synced' ), 1, HOUR_IN_SECONDS );

		do_action( 'woocommerce_gla_updated_coupon' );

		// Only the coupon evaluator's cache is cleared; the campaign one is untouched.
		$this->assertFalse( get_transient( NotificationCacheKeys::for_site( 'coupons-not-synced' ) ) );
		$this->assertSame( 1, (int) get_transient( NotificationCacheKeys::for_site( 'paused-campaign' ) ) );
	}

	public function test_evaluator_without_the_interface_is_ignored() {
		$plain = $this->createMock( NotificationEvaluatorInterface::class );
		$plain->method( 'get_id' )->willReturn( 'paid-orders' );

		$this->set_evaluators( [ $plain ] );
		$this->invalidator->register();

		set_transient( NotificationCacheKeys::for_site( 'paid-orders' ), 1, HOUR_IN_SECONDS );

		do_action( 'woocommerce_gla_updated_campaign' );

		// A trend-only evaluator declares no hooks, so its cache survives (TTL-only).
		$this->assertSame( 1, (int) get_transient( NotificationCacheKeys::for_site( 'paid-orders' ) ) );
	}

	public function test_all_declared_hooks_invalidate_the_evaluator() {
		$this->set_evaluators(
			[ $this->create_invalidatable_evaluator( 'paused-campaign', [ 'hook_a', 'hook_b' ] ) ]
		);
		$this->invalidator->register();

		foreach ( [ 'hook_a', 'hook_b' ] as $hook ) {
			set_transient( NotificationCacheKeys::for_site( 'paused-campaign' ), 1, HOUR_IN_SECONDS );
			do_action( $hook );
			$this->assertFalse(
				get_transient( NotificationCacheKeys::for_site( 'paused-campaign' ) ),
				"Hook {$hook} did not invalidate the cache."
			);
		}
	}

	public function test_register_is_a_noop_when_no_evaluators_are_registered() {
		$this->container->method( 'has' )
			->with( NotificationEvaluatorInterface::class )
			->willReturn( false );

		$this->invalidator->register();

		// No fatal, and no hook is bound: an unrelated transient is left alone.
		set_transient( NotificationCacheKeys::for_site( 'noop-unrelated' ), 1, HOUR_IN_SECONDS );
		do_action( 'gla_noop_unbound_hook' );
		$this->assertSame( 1, (int) get_transient( NotificationCacheKeys::for_site( 'noop-unrelated' ) ) );
	}

	/**
	 * Point the mocked container at a set of evaluators.
	 *
	 * @param NotificationEvaluatorInterface[] $evaluators
	 */
	private function set_evaluators( array $evaluators ): void {
		$this->container->method( 'has' )
			->with( NotificationEvaluatorInterface::class )
			->willReturn( true );

		$this->container->method( 'get' )
			->with( NotificationEvaluatorInterface::class )
			->willReturn( $evaluators );
	}

	/**
	 * Create a mocked invalidatable evaluator.
	 *
	 * @param string   $id    The notification ID.
	 * @param string[] $hooks The invalidation hooks it declares.
	 *
	 * @return MockObject|InvalidatableNotificationEvaluatorInterface
	 */
	private function create_invalidatable_evaluator( string $id, array $hooks ): MockObject {
		$evaluator = $this->createMock( InvalidatableNotificationEvaluatorInterface::class );
		$evaluator->method( 'get_id' )->willReturn( $id );
		$evaluator->method( 'get_invalidation_hooks' )->willReturn( $hooks );

		return $evaluator;
	}
}
