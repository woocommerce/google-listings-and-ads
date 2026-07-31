<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Admin;

use Automattic\WooCommerce\GoogleListingsAndAds\Admin\NotificationsSystem;
use Automattic\WooCommerce\GoogleListingsAndAds\Assets\Asset;
use Automattic\WooCommerce\GoogleListingsAndAds\Assets\AssetsHandler;
use Automattic\WooCommerce\GoogleListingsAndAds\Assets\AssetsHandlerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class NotificationsSystemTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Admin
 */
class NotificationsSystemTest extends UnitTest {

	/** @var MockObject|AssetsHandlerInterface $assets_handler */
	protected $assets_handler;

	/** @var MockObject|OptionsInterface $options */
	protected $options;

	/** @var NotificationsSystem $notifications_system */
	protected $notifications_system;

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

		$this->assets_handler = $this->createMock( AssetsHandlerInterface::class );
		$this->options        = $this->createMock( OptionsInterface::class );

		$this->notifications_system = new NotificationsSystem(
			$this->assets_handler,
			$this->options
		);

		$this->build_dir = dirname( __DIR__, 3 ) . '/js/build';
		wp_mkdir_p( $this->build_dir );
		$this->stub_build_artifacts(
			[
				'notifications-system.js',
				'notifications-system.css',
				'notifications-system.asset.php',
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

		$this->assets_handler->expects( $this->once() )
			->method( 'register_many' )
			->with(
				$this->callback(
					function ( array $assets ) {
						$this->assertCount( 2, $assets );
						$this->assert_asset_handles(
							$assets,
							[
								'google-listings-and-ads-notifications-system',
								'google-listings-and-ads-notifications-system-css',
							]
						);

						return true;
					}
				)
			);

		$this->assets_handler->expects( $this->once() )
			->method( 'enqueue_many' )
			->with(
				$this->callback(
					function ( array $assets ) {
						$this->assertCount( 2, $assets );

						return true;
					}
				)
			);

		$this->notifications_system->register();
		set_current_screen( 'dashboard' );
		do_action( 'admin_enqueue_scripts' );
	}

	public function test_enqueues_assets_on_any_wc_admin_page() {
		$_GET['page'] = 'wc-admin';
		$_GET['path'] = '/analytics/overview';

		$this->assets_handler->expects( $this->once() )->method( 'register_many' );
		$this->assets_handler->expects( $this->once() )->method( 'enqueue_many' );

		$this->notifications_system->register();
		set_current_screen( 'dashboard' );
		do_action( 'admin_enqueue_scripts' );
	}

	public function test_does_not_enqueue_assets_outside_wc_admin() {
		$_GET['page'] = 'wc-settings';

		$this->assets_handler->expects( $this->never() )->method( 'register_many' );
		$this->assets_handler->expects( $this->never() )->method( 'enqueue_many' );

		$this->notifications_system->register();
		set_current_screen( 'dashboard' );
		do_action( 'admin_enqueue_scripts' );
	}

	public function test_gla_data_fallback_does_not_overwrite_existing_gla_data() {
		$this->set_marketing_overview_page();

		update_option( 'date_format', 'F j, Y' );

		$this->options->method( 'get_merchant_id' )->willReturn( 123 );
		$this->options->method( 'get_ads_id' )->willReturn( 456 );

		// This test checks WordPress's actual registered script data below, so it
		// needs a real handler here instead of the mocked $this->assets_handler.
		$notifications_system = new NotificationsSystem( new AssetsHandler(), $this->options );

		$notifications_system->register();
		set_current_screen( 'dashboard' );
		do_action( 'admin_enqueue_scripts' );

		$inline_scripts = wp_scripts()->get_data( 'google-listings-and-ads-notifications-system', 'before' );
		$this->assertNotEmpty( $inline_scripts );

		$inline_script = implode( '', (array) $inline_scripts );

		// Must merge into (not overwrite) `window.glaData`, since the main
		// bundle's own `glaData` isn't guaranteed present on this page (e.g.
		// the core WooCommerce Marketing overview page).
		$this->assertStringContainsString( 'window.glaData = window.glaData ||', $inline_script );
		$this->assertStringContainsString( '"slug":"gla"', $inline_script );
		$this->assertStringContainsString( '"dateFormat":"F j, Y"', $inline_script );
		$this->assertStringContainsString( '"mcId":123', $inline_script );
		$this->assertStringContainsString( '"adsId":456', $inline_script );
	}

	/**
	 * Set the current request to the WooCommerce Marketing overview page.
	 */
	private function set_marketing_overview_page(): void {
		$_GET['page'] = 'wc-admin';
		$_GET['path'] = '/marketing';
	}

	/**
	 * Assert that assets contain the expected handles.
	 *
	 * @param Asset[]  $assets
	 * @param string[] $expected_handles
	 */
	private function assert_asset_handles( array $assets, array $expected_handles ): void {
		$handles = array_map(
			static function ( Asset $asset ) {
				return $asset->get_handle();
			},
			$assets
		);

		foreach ( $expected_handles as $expected_handle ) {
			$this->assertContains( $expected_handle, $handles );
		}
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
