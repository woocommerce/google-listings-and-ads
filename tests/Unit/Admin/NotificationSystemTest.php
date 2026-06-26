<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Admin;

use Automattic\WooCommerce\GoogleListingsAndAds\Admin\NotificationSystem;
use Automattic\WooCommerce\GoogleListingsAndAds\Assets\AdminScriptWithBuiltDependenciesAsset;
use Automattic\WooCommerce\GoogleListingsAndAds\Assets\Asset;
use Automattic\WooCommerce\GoogleListingsAndAds\Assets\AssetsHandlerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionClass;

defined( 'ABSPATH' ) || exit;

/**
 * Class NotificationSystemTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Admin
 */
class NotificationSystemTest extends UnitTest {

	/** @var MockObject|AssetsHandlerInterface $assets_handler */
	protected $assets_handler;

	/** @var MockObject|MerchantCenterService $merchant_center */
	protected $merchant_center;

	/** @var MockObject|OptionsInterface $options */
	protected $options;

	/** @var NotificationSystem $notification_system */
	protected $notification_system;

	/** @var string $build_dir */
	protected $build_dir;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->login_as_administrator();

		$this->assets_handler  = $this->createMock( AssetsHandlerInterface::class );
		$this->merchant_center = $this->createMock( MerchantCenterService::class );
		$this->options         = $this->createMock( OptionsInterface::class );

		$this->notification_system = new NotificationSystem(
			$this->assets_handler,
			$this->merchant_center,
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

		$this->merchant_center->method( 'is_setup_complete' )->willReturn( true );
		$this->options->method( 'get_merchant_id' )->willReturn( 123 );
		$this->options->method( 'get_ads_id' )->willReturn( 456 );

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

		$this->notification_system->register();
		set_current_screen( 'dashboard' );
		do_action( 'admin_enqueue_scripts' );
	}

	public function test_does_not_enqueue_assets_on_other_admin_pages() {
		$_GET['page'] = 'wc-admin';
		$_GET['path'] = '/analytics/overview';

		$this->assets_handler->expects( $this->never() )->method( 'register_many' );
		$this->assets_handler->expects( $this->never() )->method( 'enqueue_many' );

		$this->notification_system->register();
		set_current_screen( 'dashboard' );
		do_action( 'admin_enqueue_scripts' );
	}

	public function test_adds_gla_data_inline_script_to_notifications_bundle() {
		$this->set_marketing_overview_page();

		update_option( 'date_format', 'F j, Y' );

		$this->merchant_center->method( 'is_setup_complete' )->willReturn( true );
		$this->options->method( 'get_merchant_id' )->willReturn( 123 );
		$this->options->method( 'get_ads_id' )->willReturn( 456 );

		$this->assets_handler->expects( $this->once() )
			->method( 'register_many' )
			->with(
				$this->callback(
					function ( array $assets ) {
						$script   = $this->get_script_asset( $assets );
						$gla_data = $this->get_inline_script_data( $script, 'glaData' );

						$this->assertSame( 'F j, Y', $gla_data['dateFormat'] );
						$this->assertTrue( $gla_data['mcSetupComplete'] );
						$this->assertSame( 123, $gla_data['initialWpData']['mcId'] );
						$this->assertSame( 456, $gla_data['initialWpData']['adsId'] );
						$this->assertNotEmpty( $gla_data['initialWpData']['version'] );

						return true;
					}
				)
			);

		$this->assets_handler->expects( $this->once() )->method( 'enqueue_many' );

		$this->notification_system->register();
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
	 * Get the script asset from a list of registered assets.
	 *
	 * @param Asset[] $assets
	 *
	 * @return AdminScriptWithBuiltDependenciesAsset
	 */
	private function get_script_asset( array $assets ): AdminScriptWithBuiltDependenciesAsset {
		foreach ( $assets as $asset ) {
			if ( $asset instanceof AdminScriptWithBuiltDependenciesAsset ) {
				return $asset;
			}
		}

		$this->fail( 'Expected AdminScriptWithBuiltDependenciesAsset in registered assets.' );
	}

	/**
	 * Get inline script data from a script asset.
	 *
	 * @param AdminScriptWithBuiltDependenciesAsset $script
	 * @param string                                $variable_name
	 *
	 * @return array
	 */
	private function get_inline_script_data( AdminScriptWithBuiltDependenciesAsset $script, string $variable_name ): array {
		$reflection = new ReflectionClass( $script );
		$property   = $reflection->getProperty( 'inline_scripts' );
		$property->setAccessible( true );
		$inline_scripts = $property->getValue( $script );

		$this->assertArrayHasKey( $variable_name, $inline_scripts );

		return $inline_scripts[ $variable_name ];
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
