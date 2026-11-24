<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Notification;

use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\JobRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\RefreshNotificationEvaluators;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\Container\Container;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\CachedNotificationEvaluatorInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\CachedNotificationEvaluatorTrait;
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

	/** @var MockObject|RefreshNotificationEvaluators $refresh_job */
	protected $refresh_job;

	/** @var NotificationCacheInvalidator $invalidator */
	protected $invalidator;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->container   = $this->createMock( Container::class );
		$this->refresh_job = $this->createMock( RefreshNotificationEvaluators::class );

		$job_repository = $this->createMock( JobRepository::class );
		$job_repository->method( 'get' )
			->with( RefreshNotificationEvaluators::class )
			->willReturn( $this->refresh_job );

		$this->invalidator = new NotificationCacheInvalidator( $job_repository );
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

	public function test_firing_a_declared_hook_marks_the_result_stale_and_keeps_it_visible() {
		$evaluator = $this->create_cached_evaluator( 'paused-campaign', [ 'woocommerce_gla_updated_campaign' ] );
		$this->set_evaluators( [ $evaluator ] );
		$this->invalidator->register();

		$evaluator->refresh();
		$this->assertFalse( $evaluator->needs_refresh() );

		$this->refresh_job->expects( $this->once() )->method( 'schedule' );

		do_action( 'woocommerce_gla_updated_campaign', 123, 'edited' );

		$this->assertTrue( $evaluator->needs_refresh() );
		$this->assertTrue( $evaluator->should_show() );
	}

	public function test_hook_only_invalidates_the_evaluator_that_declared_it() {
		$campaign = $this->create_cached_evaluator( 'paused-campaign', [ 'woocommerce_gla_updated_campaign' ] );
		$coupon   = $this->create_cached_evaluator( 'coupons-not-synced', [ 'woocommerce_gla_updated_coupon' ] );
		$this->set_evaluators( [ $campaign, $coupon ] );
		$this->invalidator->register();

		$campaign->refresh();
		$coupon->refresh();

		do_action( 'woocommerce_gla_updated_coupon' );

		$this->assertTrue( $coupon->needs_refresh() );
		$this->assertFalse( $campaign->needs_refresh() );
	}

	public function test_all_declared_hooks_invalidate_the_evaluator() {
		$evaluator = $this->create_cached_evaluator( 'paused-campaign', [ 'hook_a', 'hook_b' ] );
		$this->set_evaluators( [ $evaluator ] );
		$this->invalidator->register();

		foreach ( [ 'hook_a', 'hook_b' ] as $hook ) {
			$this->store_fresh_result( 'paused-campaign' );
			do_action( $hook );
			$this->assertTrue( $evaluator->needs_refresh(), "Hook {$hook} did not invalidate the cache." );
		}
	}

	public function test_refresh_is_scheduled_once_per_request() {
		$this->set_evaluators(
			[
				$this->create_cached_evaluator( 'paused-campaign', [ 'woocommerce_gla_updated_campaign' ] ),
				$this->create_cached_evaluator( 'coupons-not-synced', [ 'woocommerce_gla_updated_coupon' ] ),
			]
		);
		$this->invalidator->register();

		$this->refresh_job->expects( $this->once() )->method( 'schedule' );

		do_action( 'woocommerce_gla_updated_campaign' );
		do_action( 'woocommerce_gla_updated_coupon' );
		do_action( 'woocommerce_gla_updated_campaign' );
	}

	public function test_nothing_stored_stays_empty() {
		$this->set_evaluators( [ $this->create_cached_evaluator( 'paused-campaign', [ 'woocommerce_gla_updated_campaign' ] ) ] );
		$this->invalidator->register();

		do_action( 'woocommerce_gla_updated_campaign' );

		$this->assertFalse( get_transient( NotificationCacheKeys::for_site( 'paused-campaign' ) ) );
	}

	public function test_legacy_plain_value_is_kept_until_refreshed() {
		$this->set_evaluators( [ $this->create_cached_evaluator( 'paused-campaign', [ 'woocommerce_gla_updated_campaign' ] ) ] );
		$this->invalidator->register();

		set_transient( NotificationCacheKeys::for_site( 'paused-campaign' ), 1, HOUR_IN_SECONDS );

		do_action( 'woocommerce_gla_updated_campaign' );

		$this->assertSame( 1, (int) get_transient( NotificationCacheKeys::for_site( 'paused-campaign' ) ) );
	}

	public function test_invalidatable_evaluator_without_the_cache_has_its_transient_deleted() {
		$evaluator = $this->createMock( InvalidatableNotificationEvaluatorInterface::class );
		$evaluator->method( 'get_id' )->willReturn( 'external-notification' );
		$evaluator->method( 'get_invalidation_hooks' )->willReturn( [ 'woocommerce_gla_updated_campaign' ] );
		$this->set_evaluators( [ $evaluator ] );
		$this->invalidator->register();

		set_transient( NotificationCacheKeys::for_site( 'external-notification' ), 1, HOUR_IN_SECONDS );

		$this->refresh_job->expects( $this->never() )->method( 'schedule' );

		do_action( 'woocommerce_gla_updated_campaign' );

		$this->assertFalse( get_transient( NotificationCacheKeys::for_site( 'external-notification' ) ) );
	}

	public function test_evaluator_without_the_interface_is_ignored() {
		$plain = $this->createMock( NotificationEvaluatorInterface::class );
		$plain->method( 'get_id' )->willReturn( 'paid-orders' );

		$this->set_evaluators( [ $plain ] );
		$this->invalidator->register();

		set_transient( NotificationCacheKeys::for_site( 'paid-orders' ), 1, HOUR_IN_SECONDS );

		do_action( 'woocommerce_gla_updated_campaign' );

		// A trend-only evaluator declares no hooks, so its cache survives until it goes stale.
		$this->assertSame( 1, (int) get_transient( NotificationCacheKeys::for_site( 'paid-orders' ) ) );
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
	 * Store a fresh, positive result in the timestamped format.
	 *
	 * @param string $id The notification ID.
	 */
	private function store_fresh_result( string $id ): void {
		set_transient(
			NotificationCacheKeys::for_site( $id ),
			[
				'value'          => 1,
				'computed_at'    => time() - 60,
				'invalidated_at' => 0,
			],
			DAY_IN_SECONDS
		);
	}

	/**
	 * Create an invalidatable evaluator backed by the real cache trait, whose condition is always met.
	 *
	 * @param string   $id    The notification ID.
	 * @param string[] $hooks The invalidation hooks it declares.
	 *
	 * @return CachedNotificationEvaluatorInterface|InvalidatableNotificationEvaluatorInterface
	 */
	private function create_cached_evaluator( string $id, array $hooks ) {
		// phpcs:ignore Universal.Classes.RequireAnonClassParentheses.Missing
		$evaluator = new class implements CachedNotificationEvaluatorInterface, InvalidatableNotificationEvaluatorInterface {
			use CachedNotificationEvaluatorTrait;

			/** @var string */
			public $id = '';

			/** @var string[] */
			public $hooks = [];

			public function get_id(): string {
				return $this->id;
			}

			public function get_priority(): int {
				return 10;
			}

			public function get_snooze_duration(): ?int {
				return null;
			}

			public function get_invalidation_hooks(): array {
				return $this->hooks;
			}

			protected function evaluate_condition(): bool {
				return true;
			}
		};

		$evaluator->id    = $id;
		$evaluator->hooks = $hooks;

		return $evaluator;
	}
}
