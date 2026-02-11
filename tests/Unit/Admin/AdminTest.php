<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Admin;

use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Admin;
use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AdsService;
use Automattic\WooCommerce\GoogleListingsAndAds\Assets\AdminScriptWithBuiltDependenciesAsset;
use Automattic\WooCommerce\GoogleListingsAndAds\Assets\AssetsHandlerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\ViewFactory;
use ReflectionMethod;
use ReflectionProperty;
use WC_Helper_Order;

/**
 * Class AdminTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Admin
 */
class AdminTest extends UnitTest {

	/**
	 * Meta-boxes script handle.
	 */
	private const META_BOXES_HANDLE = 'gla-meta-boxes';

	/**
	 * @var Admin
	 */
	private $admin;

	/**
	 * @var AdminScriptWithBuiltDependenciesAsset|null
	 */
	private $meta_boxes_asset;

	/**
	 * @var \WP_Screen|null
	 */
	private $previous_screen;

	/**
	 * @var array<string, string>
	 */
	private $previous_get;

	public function setUp(): void {
		parent::setUp();

		$this->admin            = $this->create_admin();
		$this->meta_boxes_asset = $this->get_meta_boxes_asset();
	}

	/**
	 * Create an Admin instance with mocked dependencies.
	 * Admin is not available from the container in unit tests (AdminServiceProvider is conditional on is_admin()).
	 *
	 * @return Admin
	 */
	private function create_admin(): Admin {
		$assets_handler  = $this->createMock( AssetsHandlerInterface::class );
		$view_factory    = $this->createMock( ViewFactory::class );
		$merchant_center = $this->createMock( MerchantCenterService::class );
		$merchant_center->method( 'is_setup_complete' )->willReturn( false );
		$ads = $this->createMock( AdsService::class );
		$ads->method( 'is_setup_complete' )->willReturn( false );

		$admin = new Admin( $assets_handler, $view_factory, $merchant_center, $ads );

		$options = $this->createMock( OptionsInterface::class );
		$options->method( 'get_merchant_id' )->willReturn( 0 );
		$options->method( 'get_ads_id' )->willReturn( 0 );
		$admin->set_options_object( $options );

		return $admin;
	}

	public function tearDown(): void {
		if ( $this->previous_screen !== null ) {
			$GLOBALS['current_screen'] = $this->previous_screen; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		} else {
			unset( $GLOBALS['current_screen'] );
		}
		if ( isset( $this->previous_get ) ) {
			$_GET = $this->previous_get;
		}
		parent::tearDown();
	}

	/**
	 * Get the meta-boxes asset from Admin's get_assets() via reflection.
	 *
	 * @return AdminScriptWithBuiltDependenciesAsset|null
	 */
	protected function get_meta_boxes_asset(): ?AdminScriptWithBuiltDependenciesAsset {
		$method = new ReflectionMethod( Admin::class, 'get_assets' );
		$method->setAccessible( true );
		$assets = $method->invoke( $this->admin );

		foreach ( $assets as $asset ) {
			if ( $asset->get_handle() === self::META_BOXES_HANDLE ) {
				return $asset instanceof AdminScriptWithBuiltDependenciesAsset ? $asset : null;
			}
		}

		return null;
	}

	/**
	 * Set the current screen for the test.
	 *
	 * @param string $screen_id Screen id (e.g. woocommerce_page_wc-orders).
	 */
	protected function set_current_screen_id( string $screen_id ): void {
		if ( ! isset( $GLOBALS['current_screen'] ) ) {
			$this->previous_screen = null;
		} else {
			$this->previous_screen = $GLOBALS['current_screen'];
		}

		$screen     = $this->getMockBuilder( \WP_Screen::class )
			->disableOriginalConstructor()
			->onlyMethods( [ 'in_admin' ] )
			->getMock();
		$screen->id = $screen_id;
		$screen->method( 'in_admin' )->willReturn( true );
		$GLOBALS['current_screen'] = $screen; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	}

	/**
	 * Restore current screen to previous state.
	 */
	protected function restore_screen(): void {
		if ( $this->previous_screen !== null ) {
			$GLOBALS['current_screen'] = $this->previous_screen; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		} else {
			unset( $GLOBALS['current_screen'] );
		}
	}

	/**
	 * Get the glaData array from the meta-boxes asset. Invokes get_assets() so the result reflects the current request (screen and $_GET).
	 *
	 * @return array<string, mixed>|null
	 */
	protected function get_meta_boxes_gla_data(): ?array {
		$method = new ReflectionMethod( Admin::class, 'get_assets' );
		$method->setAccessible( true );
		$assets = $method->invoke( $this->admin );

		foreach ( $assets as $asset ) {
			if ( $asset->get_handle() === self::META_BOXES_HANDLE ) {
				$prop = new ReflectionProperty( $asset, 'inline_scripts' );
				$prop->setAccessible( true );
				$inline_scripts = $prop->getValue( $asset );

				return isset( $inline_scripts['glaData'] ) && is_array( $inline_scripts['glaData'] ) ? $inline_scripts['glaData'] : null;
			}
		}

		return null;
	}

	/**
	 * Test that the meta-boxes asset is registered in get_assets().
	 */
	public function test_meta_boxes_asset_is_registered(): void {
		$this->assertNotNull( $this->meta_boxes_asset, 'gla-meta-boxes asset should be returned by get_assets()' );
	}

	/**
	 * Test that the meta-boxes script is enqueued only on WC Edit Order screen (woocommerce_page_wc-orders, action=edit).
	 */
	public function test_meta_boxes_asset_enqueued_on_wc_order_edit_screen(): void {
		$this->assertNotNull( $this->meta_boxes_asset );

		$this->previous_get = $_GET; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$_GET['action']     = 'edit';

		$this->set_current_screen_id( 'woocommerce_page_wc-orders' );

		$this->assertTrue(
			$this->meta_boxes_asset->can_enqueue(),
			'Meta-boxes asset should be enqueued on woocommerce_page_wc-orders with action=edit'
		);

		$this->restore_screen();
	}

	/**
	 * Test that the meta-boxes script is enqueued on admin_page_wc-orders with action=edit (users without WC menu).
	 */
	public function test_meta_boxes_asset_enqueued_on_admin_page_wc_orders_edit(): void {
		$this->assertNotNull( $this->meta_boxes_asset );

		$this->previous_get = $_GET; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$_GET['action']     = 'edit';

		$this->set_current_screen_id( 'admin_page_wc-orders' );

		$this->assertTrue(
			$this->meta_boxes_asset->can_enqueue(),
			'Meta-boxes asset should be enqueued on admin_page_wc-orders with action=edit'
		);

		$this->restore_screen();
	}

	/**
	 * Test that the meta-boxes script is not enqueued on orders list (no action=edit).
	 */
	public function test_meta_boxes_asset_not_enqueued_on_orders_list(): void {
		$this->assertNotNull( $this->meta_boxes_asset );

		$this->previous_get = $_GET; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		unset( $_GET['action'] );

		$this->set_current_screen_id( 'woocommerce_page_wc-orders' );

		$this->assertFalse(
			$this->meta_boxes_asset->can_enqueue(),
			'Meta-boxes asset should not be enqueued on orders list (no action=edit)'
		);

		$this->restore_screen();
	}

	/**
	 * Test that the meta-boxes script is not enqueued on product edit screen.
	 */
	public function test_meta_boxes_asset_not_enqueued_on_product_screen(): void {
		$this->assertNotNull( $this->meta_boxes_asset );

		$this->previous_get = $_GET; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$_GET['action']     = 'edit';

		$this->set_current_screen_id( 'product' );

		$this->assertFalse(
			$this->meta_boxes_asset->can_enqueue(),
			'Meta-boxes asset should not be enqueued on product screen'
		);

		$this->restore_screen();
	}

	/**
	 * Test that the meta-boxes script is not enqueued when current screen is null.
	 */
	public function test_meta_boxes_asset_not_enqueued_when_no_screen(): void {
		$this->assertNotNull( $this->meta_boxes_asset );

		$this->previous_get = $_GET; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$_GET['action']     = 'edit';

		if ( isset( $GLOBALS['current_screen'] ) ) {
			$this->previous_screen = $GLOBALS['current_screen'];
		} else {
			$this->previous_screen = null;
		}
		unset( $GLOBALS['current_screen'] );

		$this->assertFalse(
			$this->meta_boxes_asset->can_enqueue(),
			'Meta-boxes asset should not be enqueued when there is no current screen'
		);

		$this->restore_screen();
	}

	/**
	 * Test that glaData for the meta-boxes asset includes orderAttributionSource when on order edit screen.
	 */
	public function test_order_attribution_source_present_in_gladata_on_order_edit_screen(): void {
		$this->previous_get = $_GET; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$_GET['action']     = 'edit';
		$_GET['id']         = '999';
		$this->set_current_screen_id( 'woocommerce_page_wc-orders' );

		$gla_data = $this->get_meta_boxes_gla_data();
		$this->assertNotNull( $gla_data, 'glaData should be set on meta-boxes asset' );
		$this->assertArrayHasKey( 'orderAttributionSource', $gla_data, 'glaData should include orderAttributionSource' );

		$this->restore_screen();
		$_GET = $this->previous_get;
	}

	/**
	 * Test that orderAttributionSource reflects the value from order meta when order has utm_source set.
	 */
	public function test_order_attribution_source_value_from_order_meta(): void {
		$order = WC_Helper_Order::create_order();
		$order->update_meta_data( '_wc_order_attribution_utm_source', 'google' );
		$order->save();

		$this->previous_get = $_GET; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$_GET['action']     = 'edit';
		$_GET['id']         = (string) $order->get_id();
		$this->set_current_screen_id( 'woocommerce_page_wc-orders' );

		$gla_data = $this->get_meta_boxes_gla_data();
		$this->assertNotNull( $gla_data );
		$this->assertSame( 'google', $gla_data['orderAttributionSource'] ?? null );

		$this->restore_screen();
		$_GET = $this->previous_get;
	}

	/**
	 * Test that orderAttributionSource is null when no order ID in request or order has no attribution.
	 */
	public function test_order_attribution_source_null_when_no_order_or_no_attribution(): void {
		$this->previous_get = $_GET; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$_GET['action']     = 'edit';
		unset( $_GET['id'], $_GET['post'] );
		$this->set_current_screen_id( 'woocommerce_page_wc-orders' );

		$gla_data = $this->get_meta_boxes_gla_data();
		$this->assertNotNull( $gla_data );
		$this->assertArrayHasKey( 'orderAttributionSource', $gla_data );
		$this->assertNull( $gla_data['orderAttributionSource'] );

		$this->restore_screen();
		$_GET = $this->previous_get;
	}
}
