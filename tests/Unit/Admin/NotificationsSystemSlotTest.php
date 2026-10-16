<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Admin;

use Automattic\WooCommerce\GoogleListingsAndAds\Admin\NotificationsSystemSlot;
use Automattic\WooCommerce\GoogleListingsAndAds\Assets\Asset;
use Automattic\WooCommerce\GoogleListingsAndAds\Assets\AssetsHandlerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class NotificationsSystemSlotTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Admin
 */
class NotificationsSystemSlotTest extends UnitTest {

	/** @var MockObject|AssetsHandlerInterface $assets_handler */
	protected $assets_handler;

	/** @var NotificationsSystemSlot $notifications_system_slot */
	protected $notifications_system_slot;

	/** @var string $build_dir */
	protected $build_dir;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		// Detach pre-existing admin_enqueue_scripts callbacks so firing the action runs only
		// what register() adds. WooCommerce core's WCAdminAssets callbacks print output when
		// the WooCommerce checkout has no built admin assets, which breaks strict CI output.
		remove_all_actions( 'admin_enqueue_scripts' );

		$this->login_as_administrator();

		$this->assets_handler            = $this->createMock( AssetsHandlerInterface::class );
		$this->notifications_system_slot = new NotificationsSystemSlot( $this->assets_handler );

		$this->build_dir = dirname( __DIR__, 3 ) . '/js/build';
		wp_mkdir_p( $this->build_dir );
		$this->stub_build_artifacts(
			[
				'woo-marketing-notifications-slot.js',
				'woo-marketing-notifications-slot.css',
				'woo-marketing-notifications-slot.asset.php',
			]
		);
	}

	/**
	 * Runs after each test is executed.
	 */
	public function tearDown(): void {
		unset( $_GET['page'], $_GET['path'] );

		parent::tearDown();
	}

	public function test_enqueues_assets_on_marketing_overview_page() {
		$this->set_marketing_overview_page();

		$this->assets_handler->expects( $this->exactly( 2 ) )
			->method( 'register' )
			->with(
				$this->callback(
					function ( Asset $asset ) {
						$this->assertContains(
							$asset->get_handle(),
							[
								'woocommerce-marketing-notifications-system-slot',
								'woocommerce-marketing-notifications-system-slot-css',
							]
						);

						return true;
					}
				)
			);

		$this->assets_handler->expects( $this->exactly( 2 ) )
			->method( 'enqueue' )
			->with(
				$this->callback(
					function ( Asset $asset ) {
						$this->assertContains(
							$asset->get_handle(),
							[
								'woocommerce-marketing-notifications-system-slot',
								'woocommerce-marketing-notifications-system-slot-css',
							]
						);

						return true;
					}
				)
			);

		$this->notifications_system_slot->register();
		set_current_screen( 'dashboard' );
		do_action( 'admin_enqueue_scripts' );
	}

	public function test_enqueues_assets_on_any_wc_admin_page() {
		$_GET['page'] = 'wc-admin';
		$_GET['path'] = '/analytics/overview';

		$this->assets_handler->expects( $this->exactly( 2 ) )->method( 'register' );
		$this->assets_handler->expects( $this->exactly( 2 ) )->method( 'enqueue' );

		$this->notifications_system_slot->register();
		set_current_screen( 'dashboard' );
		do_action( 'admin_enqueue_scripts' );
	}

	public function test_does_not_enqueue_assets_outside_wc_admin() {
		$_GET['page'] = 'wc-settings';

		$this->assets_handler->expects( $this->never() )->method( 'register' );
		$this->assets_handler->expects( $this->never() )->method( 'enqueue' );

		$this->notifications_system_slot->register();
		set_current_screen( 'dashboard' );
		do_action( 'admin_enqueue_scripts' );
	}

	/**
	 * Set the current request to the WooCommerce Marketing overview page.
	 */
	private function set_marketing_overview_page(): void {
		$_GET['page'] = 'wc-admin';
		$_GET['path'] = '/marketing';
	}

	/**
	 * Create stub build artifacts required by asset registration.
	 *
	 * @param string[] $files
	 */
	private function stub_build_artifacts( array $files ): void {
		foreach ( $files as $file ) {
			$path = "{$this->build_dir}/{$file}";

			if ( str_ends_with( $file, '.asset.php' ) ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
				file_put_contents( $path, "<?php return array( 'dependencies' => array(), 'version' => 'test' );" );
				continue;
			}

			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			file_put_contents( $path, '' );
		}
	}
}
