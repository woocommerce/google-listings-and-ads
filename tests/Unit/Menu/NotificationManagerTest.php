<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\Assets\AssetsHandlerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Menu\NotificationManager;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\Admin\PageController;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class NotificationManagerTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Google
 */
class NotificationManagerTest extends UnitTest {

	/** @var MockObject|AssetsHandlerInterface $assets_handler */
	protected $assets_handler;

	/** @var NotificationManager $notification_manager */
	protected $notification_manager;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->assets_handler = $this->createMock( AssetsHandlerInterface::class );

		$this->notification_manager = new NotificationManager( $this->assets_handler );
	}

	public function test_notification_script_not_added_to_non_woocommerce_admin_pages() {
		global $wp_filter;

		// Create a backup and clear all hooks.
		$_wp_filter = $wp_filter;
		$wp_filter = [];

		// The script will be registered but not enqueued.
		$this->assets_handler->expects( $this->once() )->method( 'register' );
		$this->assets_handler->expects( $this->never() )->method( 'enqueue' );

		$this->notification_manager->register();

		do_action( 'admin_enqueue_scripts' );

		// Restore hooks.
		$wp_filter = $_wp_filter;
	}

	public function test_notification_script_is_added_to_analytics_admin_pages() {
		global $wp_filter;

		// Create a backup and clear all hooks.
		$_wp_filter = $wp_filter;
		$wp_filter = [];

		// Mock being on a Marketing admin page.
		$_GET['page'] = 'wc-admin';
		$_GET['path'] = '/analytics/overview';

		// The script will be registered but not enqueued.
		$this->assets_handler->expects( $this->once() )->method( 'register' );
		$this->assets_handler->expects( $this->once() )->method( 'enqueue' );

		$this->notification_manager->register();

		do_action( 'admin_enqueue_scripts' );

		// Restore hooks.
		$wp_filter = $_wp_filter;
	}
}
