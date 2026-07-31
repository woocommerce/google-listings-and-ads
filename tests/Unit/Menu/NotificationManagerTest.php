<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Menu;

use Automattic\WooCommerce\GoogleListingsAndAds\Assets\AssetsHandlerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Menu\Dashboard;
use Automattic\WooCommerce\GoogleListingsAndAds\Menu\NotificationManager;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationService;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class NotificationManagerTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Menu
 */
class NotificationManagerTest extends UnitTest {

	/** @var MockObject|AssetsHandlerInterface $assets_handler */
	protected $assets_handler;

	/** @var MockObject|NotificationService $notification_service */
	protected $notification_service;

	/** @var NotificationManager $notification_manager */
	protected $notification_manager;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->login_as_administrator();

		$this->assets_handler       = $this->createMock( AssetsHandlerInterface::class );
		$this->notification_service = $this->createMock( NotificationService::class );

		$this->notification_manager = new NotificationManager( $this->assets_handler, $this->notification_service );
	}

	/**
	 * Runs after each test is executed.
	 */
	public function tearDown(): void {
		remove_filter( 'google_for_woocommerce_admin_menu_notification_count', [ $this->notification_manager, 'notifications_count' ] );
		remove_action( 'admin_menu', [ $this->notification_manager, 'display_aggregated_notification_pill' ], 20 );

		parent::tearDown();
	}

	public function test_notifications_count_increments_by_the_number_of_active_notifications() {
		$this->notification_service->method( 'get_notifications' )
			->willReturn(
				[
					[
						'id'           => 'notification-a',
						'triggered_at' => 1,
					],
					[
						'id'           => 'notification-b',
						'triggered_at' => 2,
					],
				]
			);

		$this->assertEquals( 5, $this->notification_manager->notifications_count( 3 ) );
	}

	public function test_notifications_count_unchanged_for_non_manage_woocommerce_user() {
		wp_set_current_user( 0 );

		// Use the real service so the capability gate in get_notifications() is exercised.
		$notification_manager = new NotificationManager( $this->assets_handler, new NotificationService( new WP() ) );

		$this->assertEquals( 3, $notification_manager->notifications_count( 3 ) );
	}

	public function test_badge_not_rendered_when_total_count_is_zero() {
		$this->notification_service->method( 'get_notifications' )->willReturn( [] );

		add_filter( 'google_for_woocommerce_admin_menu_notification_count', [ $this->notification_manager, 'notifications_count' ] );

		global $menu, $submenu;
		$menu    = [ // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			[ 'Marketing', 'manage_woocommerce', Dashboard::MARKETING_MENU_SLUG ],
		];
		$submenu = [ // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			Dashboard::MARKETING_MENU_SLUG => [
				[ 'Google for WooCommerce', 'manage_woocommerce', Dashboard::PATH ],
			],
		];

		$this->notification_manager->display_aggregated_notification_pill();

		$this->assertEquals( 'Marketing', $menu[0][0] );
		$this->assertEquals( 'Google for WooCommerce', $submenu[ Dashboard::MARKETING_MENU_SLUG ][0][0] );
	}

	public function test_badge_moved_to_overview_when_on_marketing_child_page() {
		$this->notification_service->method( 'get_notifications' )
			->willReturn(
				[
					[
						'id'           => 'notification-a',
						'triggered_at' => 1,
					],
				]
			);

		// Force the "user is viewing one of the Marketing child pages" branch without
		// depending on the real PageController singleton's registered page state, and
		// skip real asset registration (it reads the built js/build/ file, which
		// isn't guaranteed to exist in a unit test run).
		$this->notification_manager = $this->getMockBuilder( NotificationManager::class )
			->setConstructorArgs( [ $this->assets_handler, $this->notification_service ] )
			->onlyMethods( [ 'is_marketing_page', 'register_assets' ] )
			->getMock();

		$this->notification_manager->method( 'is_marketing_page' )->willReturn( true );

		add_filter( 'google_for_woocommerce_admin_menu_notification_count', [ $this->notification_manager, 'notifications_count' ] );

		global $menu, $submenu;
		$menu    = [ // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			[ 'Marketing', 'manage_woocommerce', Dashboard::MARKETING_MENU_SLUG ],
		];
		$submenu = [ // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			Dashboard::MARKETING_MENU_SLUG => [
				[ 'Overview', 'manage_woocommerce', 'admin.php?page=wc-admin&path=' . Dashboard::MARKETING_OVERVIEW_PATH ],
				[ 'Google for WooCommerce', 'manage_woocommerce', Dashboard::PATH ],
			],
		];

		$this->notification_manager->display_aggregated_notification_pill();

		$this->assertStringContainsString( 'update-plugins', $submenu[ Dashboard::MARKETING_MENU_SLUG ][0][0] );
		$this->assertEquals( 'Google for WooCommerce', $submenu[ Dashboard::MARKETING_MENU_SLUG ][1][0] );
	}

	public function test_register_only_wires_notifications_count_into_aggregation_filter() {
		$this->notification_service->method( 'get_notifications' )
			->willReturn(
				[
					[
						'id'           => 'notification-a',
						'triggered_at' => 1,
					],
				]
			);

		$this->notification_manager->register();

		$this->assertEquals( 1, apply_filters( 'google_for_woocommerce_admin_menu_notification_count', 0 ) );
	}
}
