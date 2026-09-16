<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Notification;

use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationEvaluatorInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationService;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationSnoozeDurations;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\SiteScopedNotificationEvaluatorInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\Options;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\Container\Container;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class NotificationServiceTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Notification
 */
class NotificationServiceTest extends UnitTest {

	/** @var MockObject|Container $container */
	protected $container;

	/** @var NotificationService $service */
	protected $service;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->login_as_administrator();

		$this->container = $this->createMock( Container::class );

		$options = new Options();
		$options->set_wp_proxy_object( new WP() );

		$this->service = new NotificationService( new WP() );
		$this->service->set_container( $this->container );
		$this->service->set_options_object( $options );
	}

	public function test_returns_ids_in_priority_order() {
		$this->set_evaluators(
			[
				$this->create_mocked_evaluator( 'notification-c', 30, true ),
				$this->create_mocked_evaluator( 'notification-a', 10, true ),
				$this->create_mocked_evaluator( 'notification-b', 20, true ),
			]
		);

		$ids = wp_list_pluck( $this->service->get_notifications(), 'id' );

		$this->assertEquals( [ 'notification-a', 'notification-b', 'notification-c' ], $ids );
	}

	public function test_excludes_notifications_that_should_not_show() {
		$this->set_evaluators(
			[
				$this->create_mocked_evaluator( 'notification-a', 10, true ),
				$this->create_mocked_evaluator( 'notification-b', 20, false ),
			]
		);

		$ids = wp_list_pluck( $this->service->get_notifications(), 'id' );

		$this->assertEquals( [ 'notification-a' ], $ids );
	}

	public function test_triggered_at_is_set_on_first_trigger_and_unchanged_afterwards() {
		$this->set_evaluators(
			[
				$this->create_mocked_evaluator( 'notification-a', 10, true ),
			]
		);

		$first  = $this->service->get_notifications();
		$second = $this->service->get_notifications();

		$this->assertNotEmpty( $first[0]['triggered_at'] );
		$this->assertIsInt( $first[0]['triggered_at'] );
		$this->assertEquals( $first[0]['triggered_at'], $second[0]['triggered_at'] );
	}

	public function test_dismissed_notification_absent_from_all_future_results() {
		$this->set_evaluators(
			[
				$this->create_mocked_evaluator( 'notification-a', 10, true ),
				$this->create_mocked_evaluator( 'notification-b', 20, true ),
			]
		);

		$this->service->dismiss( 'notification-a' );

		$first_ids  = wp_list_pluck( $this->service->get_notifications(), 'id' );
		$second_ids = wp_list_pluck( $this->service->get_notifications(), 'id' );

		$this->assertEquals( [ 'notification-b' ], $first_ids );
		$this->assertEquals( [ 'notification-b' ], $second_ids );
	}

	public function test_dismissing_one_id_does_not_affect_another() {
		$this->set_evaluators(
			[
				$this->create_mocked_evaluator( 'notification-a', 10, true ),
				$this->create_mocked_evaluator( 'notification-b', 20, true ),
			]
		);

		// Record a trigger for both notifications first.
		$initial      = $this->service->get_notifications();
		$triggered_at = wp_list_pluck( $initial, 'triggered_at', 'id' );

		$this->service->dismiss( 'notification-a' );

		$remaining = $this->service->get_notifications();

		$this->assertCount( 1, $remaining );
		$this->assertEquals( 'notification-b', $remaining[0]['id'] );
		$this->assertEquals( $triggered_at['notification-b'], $remaining[0]['triggered_at'] );
	}

	public function test_non_manage_woocommerce_user_returns_empty_array() {
		wp_set_current_user( 0 );

		$this->assertEquals( [], $this->service->get_notifications() );
	}

	public function test_returns_empty_array_when_no_evaluators_are_registered() {
		$this->container->method( 'has' )
			->with( NotificationEvaluatorInterface::class )
			->willReturn( false );

		$this->assertEquals( [], $this->service->get_notifications() );
	}

	public function test_has_returns_true_for_registered_evaluator_id() {
		$this->set_evaluators(
			[
				$this->create_mocked_evaluator( 'notification-a', 10, true ),
			]
		);

		$this->assertTrue( $this->service->has( 'notification-a' ) );
	}

	public function test_has_returns_false_for_unknown_id() {
		$this->set_evaluators(
			[
				$this->create_mocked_evaluator( 'notification-a', 10, true ),
			]
		);

		$this->assertFalse( $this->service->has( 'unknown-id' ) );
	}

	public function test_dismiss_unknown_id_does_not_persist_state() {
		$this->set_evaluators(
			[
				$this->create_mocked_evaluator( 'notification-a', 10, true ),
			]
		);

		$this->service->dismiss( 'unknown-id' );

		$state = get_user_meta( get_current_user_id(), 'gla_notifications_state', true );

		$this->assertFalse( isset( $state['unknown-id'] ) );
	}

	public function test_login_scoped_dismissal_stays_hidden_until_next_login() {
		$this->set_evaluators(
			[
				$this->create_mocked_evaluator( 'notification-a', 10, true, NotificationSnoozeDurations::UNTIL_NEXT_LOGIN ),
			]
		);

		$this->service->get_notifications();
		$this->service->dismiss( 'notification-a' );

		$this->assertEquals( [], $this->service->get_notifications() );
	}

	public function test_login_scoped_dismissal_reappears_after_login() {
		$this->set_evaluators(
			[
				$this->create_mocked_evaluator( 'notification-a', 10, true, NotificationSnoozeDurations::UNTIL_NEXT_LOGIN ),
			]
		);

		$this->service->get_notifications();
		$this->service->dismiss( 'notification-a' );

		$user = get_user_by( 'id', get_current_user_id() );
		$this->service->clear_login_scoped_dismissals( $user->user_login, $user );

		$ids = wp_list_pluck( $this->service->get_notifications(), 'id' );

		$this->assertEquals( [ 'notification-a' ], $ids );
	}

	public function test_login_scoped_dismissal_clear_tolerates_missing_user_arg() {
		$this->set_evaluators(
			[
				$this->create_mocked_evaluator( 'notification-a', 10, true, NotificationSnoozeDurations::UNTIL_NEXT_LOGIN ),
			]
		);

		$this->service->get_notifications();
		$this->service->dismiss( 'notification-a' );

		// Some plugins/login flows fire `wp_login` with only the username.
		$this->service->clear_login_scoped_dismissals( 'some_user_login' );

		$this->assertEquals( [], $this->service->get_notifications() );
	}

	public function test_login_scoped_clear_does_not_affect_permanent_dismissals() {
		$this->set_evaluators(
			[
				$this->create_mocked_evaluator( 'notification-a', 10, true ),
				$this->create_mocked_evaluator( 'notification-b', 20, true, NotificationSnoozeDurations::UNTIL_NEXT_LOGIN ),
			]
		);

		$this->service->get_notifications();
		$this->service->dismiss( 'notification-a' );
		$this->service->dismiss( 'notification-b' );

		$user = get_user_by( 'id', get_current_user_id() );
		$this->service->clear_login_scoped_dismissals( $user->user_login, $user );

		$ids = wp_list_pluck( $this->service->get_notifications(), 'id' );

		$this->assertEquals( [ 'notification-b' ], $ids );
	}

	public function test_time_based_snooze_hides_until_duration_expires() {
		$this->set_evaluators(
			[
				$this->create_mocked_evaluator( 'notification-a', 10, true, HOUR_IN_SECONDS ),
			]
		);

		$this->service->get_notifications();
		$this->service->dismiss( 'notification-a' );

		$this->assertEquals( [], $this->service->get_notifications() );

		$state                                    = get_user_meta( get_current_user_id(), 'gla_notifications_state', true );
		$state['notification-a']['snoozed_until'] = time() - 1;

		update_user_meta( get_current_user_id(), 'gla_notifications_state', $state );

		$ids = wp_list_pluck( $this->service->get_notifications(), 'id' );

		$this->assertEquals( [ 'notification-a' ], $ids );
	}

	public function test_site_scoped_notification_persists_state_in_site_option() {
		$this->set_evaluators(
			[
				$this->create_mocked_evaluator( 'paid-orders', 10, true, null, true ),
			]
		);

		$this->service->get_notifications();

		$site_state = get_option( 'gla_' . OptionsInterface::NOTIFICATIONS_SITE_STATE, [] );
		$user_state = get_user_meta( get_current_user_id(), 'gla_notifications_state', true );

		$this->assertNotEmpty( $site_state['paid-orders']['triggered_at'] );
		$this->assertFalse( isset( $user_state['paid-orders'] ) );
	}

	public function test_site_scoped_dismiss_is_shared_across_users() {
		$this->set_evaluators(
			[
				$this->create_mocked_evaluator( 'paid-orders', 10, true, null, true ),
			]
		);

		$this->service->get_notifications();
		$this->service->dismiss( 'paid-orders' );

		$second_admin_id = $this->factory->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $second_admin_id );

		$this->assertEquals( [], $this->service->get_notifications() );
	}

	public function test_triggered_at_is_set_once_for_site_scoped_notifications() {
		$this->set_evaluators(
			[
				$this->create_mocked_evaluator( 'paid-orders', 10, true, null, true ),
			]
		);

		$first  = $this->service->get_notifications();
		$second = $this->service->get_notifications();

		$this->assertNotEmpty( $first[0]['triggered_at'] );
		$this->assertEquals( $first[0]['triggered_at'], $second[0]['triggered_at'] );
	}

	/**
	 * Have the mocked container return the given evaluators.
	 *
	 * @param NotificationEvaluatorInterface[] $evaluators
	 *
	 * @return void
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
	 * Create a mocked notification evaluator.
	 *
	 * @param string   $id          The notification ID.
	 * @param int      $priority    The notification priority.
	 * @param bool     $should_show Whether the condition is met.
	 * @param int|null $snooze      The snooze duration in seconds.
	 * @param bool     $site_scoped Whether state is stored site-wide.
	 *
	 * @return MockObject|NotificationEvaluatorInterface
	 */
	private function create_mocked_evaluator( string $id, int $priority, bool $should_show, ?int $snooze = null, bool $site_scoped = false ): MockObject {
		$interface = $site_scoped ? SiteScopedNotificationEvaluatorInterface::class : NotificationEvaluatorInterface::class;
		$evaluator = $this->createMock( $interface );
		$evaluator->method( 'get_id' )->willReturn( $id );
		$evaluator->method( 'get_priority' )->willReturn( $priority );
		$evaluator->method( 'should_show' )->willReturn( $should_show );
		$evaluator->method( 'get_snooze_duration' )->willReturn( $snooze );

		return $evaluator;
	}
}
