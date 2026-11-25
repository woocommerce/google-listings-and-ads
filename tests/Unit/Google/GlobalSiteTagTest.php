<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\TagManager\Connection as TagManagerConnection;
use Automattic\WooCommerce\GoogleListingsAndAds\Assets\AssetsHandlerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\GlobalSiteTag;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\GoogleGtagJs;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;
use ArrayObject;
use ReflectionMethod;
use ReflectionProperty;
use RuntimeException;
use WC_Helper_Order;
use WC_Helper_Product;
use WC_Order;
use WC_Product;
use WP_Scripts;

defined( 'ABSPATH' ) || exit;

/**
 * Class GlobalSiteTagTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Google
 */
class GlobalSiteTagTest extends UnitTest {

	/** @var MockObject|AssetsHandlerInterface $assets_handler */
	protected $assets_handler;

	/** @var MockObject|GoogleGtagJs $gtag_js */
	protected $gtag_js;

	/** @var MockObject|ProductHelper $product_helper */
	protected $product_helper;

	/** @var MockObject|WC $wc */
	protected $wc;

	/** @var MockObject|WP $wp */
	protected $wp;

	/** @var MockObject|TagManagerConnection $tag_manager_connection */
	protected $tag_manager_connection;

	/** @var GlobalSiteTag $tag */
	protected $tag;

	/** @var GlobalSiteTag $tag */
	protected $options;

	/** @var string[] $inline_scripts Inline scripts printed during a test. */
	protected $inline_scripts = [];

	protected const TEST_CONVERSION_ID    = 'test_id';
	protected const TEST_CONVERSION_LABEL = 'test_conversion_label';
	protected const DEVELOPER_ID_PARAM    = '"developer_id.dOGY3NW": true';

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->options                = $this->createMock( OptionsInterface::class );
		$this->assets_handler         = $this->createMock( AssetsHandlerInterface::class );
		$this->gtag_js                = $this->createMock( GoogleGtagJs::class );
		$this->product_helper         = $this->createMock( ProductHelper::class );
		$this->wc                     = $this->createMock( WC::class );
		$this->wp                     = $this->createMock( WP::class );
		$this->tag_manager_connection = $this->getMockBuilder( TagManagerConnection::class )
			->disableOriginalConstructor()
			->onlyMethods( [ 'get_connection_data', 'has_ads_conversion_conflict' ] )
			->getMock();

		// Connected by default so the existing gtag.js-focused tests below don't all need to
		// opt in — the dedicated tests further down cover the disconnected case explicitly.
		$this->tag_manager_connection->method( 'get_connection_data' )->willReturn(
			[ 'container_public_id' => 'GTM-TEST1234' ]
		);

		$this->tag = new GlobalSiteTag( $this->assets_handler, $this->gtag_js, $this->product_helper, $this->wc, $this->wp, $this->tag_manager_connection );
		$this->tag->set_options_object( $this->options );
	}

	/**
	 * Runs after each test is executed.
	 */
	public function tearDown(): void {
		if ( WC()->cart ) {
			WC()->cart->empty_cart();
		}
		unset( $GLOBALS['post'] );
		parent::tearDown();
	}

	public function test_is_needed_returns_true_by_default() {
		$this->assertTrue( GlobalSiteTag::is_needed() );
	}

	public function test_is_needed_stays_true_when_the_native_ads_tag_is_disabled_by_filter() {
		// is_needed() no longer applies this filter -- see should_fire_native_ads_tag() below.
		add_filter( 'woocommerce_gla_disable_gtag_tracking', '__return_true' );

		$this->assertTrue( GlobalSiteTag::is_needed() );
	}

	public function test_should_fire_native_ads_tag_true_when_configured_and_no_conflict() {
		$this->assertTrue(
			GlobalSiteTag::should_fire_native_ads_tag( self::TEST_CONVERSION_ID, self::TEST_CONVERSION_LABEL, false )
		);
	}

	public function test_should_fire_native_ads_tag_false_when_conflict() {
		$this->assertFalse(
			GlobalSiteTag::should_fire_native_ads_tag( self::TEST_CONVERSION_ID, self::TEST_CONVERSION_LABEL, true )
		);
	}

	public function test_should_fire_native_ads_tag_false_when_not_configured() {
		$this->assertFalse( GlobalSiteTag::should_fire_native_ads_tag( null, null, false ) );
		$this->assertFalse( GlobalSiteTag::should_fire_native_ads_tag( self::TEST_CONVERSION_ID, null, false ) );
		$this->assertFalse( GlobalSiteTag::should_fire_native_ads_tag( null, self::TEST_CONVERSION_LABEL, false ) );
	}

	public function test_should_fire_native_ads_tag_false_when_disabled_by_filter() {
		add_filter( 'woocommerce_gla_disable_gtag_tracking', '__return_true' );

		$this->assertFalse(
			GlobalSiteTag::should_fire_native_ads_tag( self::TEST_CONVERSION_ID, self::TEST_CONVERSION_LABEL, false )
		);
	}

	public function test_global_site_tag_is_printed_when_wcga_handle_is_not_enqueued() {
		$this->gtag_js->ga4w_v2 = true;
		$this->gtag_js->expects( $this->once() )
			->method( 'is_adding_framework' )
			->willReturn( true );
		$this->wp->expects( $this->once() )
			->method( 'wp_script_is' )
			->with( 'woocommerce-google-analytics-integration', 'enqueued' )
			->willReturn( false );
		$this->wp->expects( $this->never() )->method( 'wp_add_inline_script' );

		ob_start();
		$this->tag->activate_global_site_tag( self::TEST_CONVERSION_ID );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Global site tag (gtag.js)', $output );
		$this->assertStringContainsString( 'gtag("config", "test_id"', $output );
	}

	public function test_global_site_tag_is_attached_to_wcga_when_handle_is_enqueued() {
		$this->gtag_js->ga4w_v2 = true;
		$this->gtag_js->expects( $this->once() )
			->method( 'is_adding_framework' )
			->willReturn( true );
		$this->wp->expects( $this->exactly( 2 ) )
			->method( 'wp_script_is' )
			->willReturnMap(
				[
					[ 'woocommerce-google-analytics-integration', 'enqueued', true ],
					[ 'woocommerce-google-analytics-integration', 'done', false ],
				]
			);
		$this->wp->expects( $this->once() )
			->method( 'wp_add_inline_script' )
			->with(
				'woocommerce-google-analytics-integration',
				$this->stringStartsWith( 'gtag("config", "test_id"' )
			)
			->willReturn( true );

		ob_start();
		$this->tag->activate_global_site_tag( self::TEST_CONVERSION_ID );
		$output = ob_get_clean();

		$this->assertSame( '', $output );
	}

	public function test_global_site_tag_is_printed_when_wcga_attachment_fails() {
		$this->gtag_js->ga4w_v2 = true;
		$this->gtag_js->expects( $this->once() )
			->method( 'is_adding_framework' )
			->willReturn( true );
		$this->wp->expects( $this->exactly( 2 ) )
			->method( 'wp_script_is' )
			->willReturnMap(
				[
					[ 'woocommerce-google-analytics-integration', 'enqueued', true ],
					[ 'woocommerce-google-analytics-integration', 'done', false ],
				]
			);
		$this->wp->expects( $this->once() )
			->method( 'wp_add_inline_script' )
			->willReturn( false );

		ob_start();
		$this->tag->activate_global_site_tag( self::TEST_CONVERSION_ID );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Global site tag (gtag.js)', $output );
		$this->assertStringContainsString( 'gtag("config", "test_id"', $output );
	}

	public function test_global_site_tag_config_is_printed_when_wcga_handle_is_done() {
		$this->gtag_js->ga4w_v2 = true;
		$this->gtag_js->expects( $this->once() )
			->method( 'is_adding_framework' )
			->willReturn( true );
		$this->wp->expects( $this->exactly( 2 ) )
			->method( 'wp_script_is' )
			->willReturnMap(
				[
					[ 'woocommerce-google-analytics-integration', 'enqueued', true ],
					[ 'woocommerce-google-analytics-integration', 'done', true ],
				]
			);
		$this->wp->expects( $this->never() )->method( 'wp_add_inline_script' );
		$this->wp->expects( $this->once() )
			->method( 'wp_print_inline_script_tag' )
			->with( $this->stringStartsWith( 'gtag("config", "test_id"' ) );

		ob_start();
		$this->tag->activate_global_site_tag( self::TEST_CONVERSION_ID );
		$output = ob_get_clean();

		$this->assertSame( '', $output );
	}

	public function test_legacy_wcga_config_does_not_print_a_second_global_site_tag() {
		$this->gtag_js->ga4w_v2 = false;
		$this->gtag_js->expects( $this->once() )
			->method( 'is_adding_framework' )
			->willReturn( true );
		$this->wp->expects( $this->never() )->method( 'wp_script_is' );
		$this->wp->expects( $this->never() )->method( 'wp_add_inline_script' );
		$this->wp->expects( $this->never() )->method( 'wp_print_inline_script_tag' );

		ob_start();
		$this->tag->activate_global_site_tag( self::TEST_CONVERSION_ID );
		$output = ob_get_clean();

		$filtered_snippet = apply_filters( 'woocommerce_gtag_snippet', "<script>\n</script>" );

		$this->assertSame( '', $output );
		$this->assertStringContainsString( "gtag('config', 'test_id'", $filtered_snippet );
	}

	/**
	 * The WP Consent API bridge is the only GLA script that lifts the consent default
	 * printed with GLA's own tag, so GLA enqueues it whenever it prints that tag,
	 * whether or not Woo Analytics is active.
	 *
	 * @dataProvider wp_consent_api_bridge_provider
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 *
	 * @param bool $wcga_active          Whether Woo Analytics is active (its gtag class is defined).
	 * @param bool $wcga_adds_framework  Whether Woo Analytics adds its framework for this visitor.
	 * @param bool $wcga_handle_enqueued Whether the Woo Analytics script handle is enqueued.
	 * @param bool $run_head             Whether the template calls wp_head.
	 * @param bool $expect_bridge        Whether GLA prints its own tag and enqueues the WP Consent API bridge.
	 * @param bool $override_display     Whether a subclass replaces display_global_site_tag() without calling the parent.
	 */
	public function test_wp_consent_api_bridge_follows_printed_global_site_tag( bool $wcga_active, bool $wcga_adds_framework, bool $wcga_handle_enqueued, bool $run_head, bool $expect_bridge, bool $override_display = false ) {
		require_once dirname( __DIR__, 2 ) . '/data/wp-consent-api-functions.php';
		if ( $wcga_active && ! class_exists( '\\WC_Google_Gtag_JS', false ) ) {
			class_alias( self::class, 'WC_Google_Gtag_JS' );
		}

		if ( $override_display ) {
			$this->tag = $this->getMockBuilder( GlobalSiteTag::class )
				->setConstructorArgs( [ $this->assets_handler, $this->gtag_js, $this->product_helper, $this->wc, $this->wp, $this->tag_manager_connection ] )
				->onlyMethods( [ 'display_global_site_tag' ] )
				->getMock();
			$this->tag->method( 'display_global_site_tag' )->willReturnCallback(
				function () {
					echo '<!-- Global site tag (gtag.js) - printed by a subclass -->';
				}
			);
			$this->tag->set_options_object( $this->options );
		}

		$this->gtag_js->ga4w_v2 = true;
		$this->gtag_js->method( 'is_adding_framework' )->willReturn( $wcga_adds_framework );
		$this->wp->method( 'wp_script_is' )->willReturnMap(
			[
				[ 'woocommerce-google-analytics-integration', 'enqueued', $wcga_handle_enqueued ],
				[ 'woocommerce-google-analytics-integration', 'done', false ],
			]
		);
		$this->wp->method( 'wp_add_inline_script' )->willReturn( true );
		$this->options->method( 'get' )->willReturn(
			[
				'conversion_id'    => self::TEST_CONVERSION_ID,
				'conversion_label' => self::TEST_CONVERSION_LABEL,
			]
		);

		$enqueued = [];
		$this->assets_handler->method( 'enqueue' )->willReturnCallback(
			function ( $asset ) use ( &$enqueued ) {
				$enqueued[] = $asset->get_handle();
			}
		);

		// Keep only this service's head and footer callbacks.
		remove_all_actions( 'wp_head' );
		remove_all_actions( 'wp_footer' );

		// Asset registration requires the built scripts, which PHP-only CI does not build.
		$stubbed_scripts = $this->stub_missing_build_scripts( [ 'gtag-events', 'wp-consent-api' ] );
		try {
			$this->tag->register();

			// A queued event makes the footer print GLA's tag when the template skips wp_head.
			$this->tag->add_inline_event_script( 'gtag("event", "page_view");' );

			ob_start();
			if ( $run_head ) {
				do_action( 'wp_head' );
			}
			do_action( 'wp_footer' );
			$output = ob_get_clean();
		} finally {
			array_map( 'unlink', $stubbed_scripts );
		}

		$this->assertSame( $expect_bridge, str_contains( $output, 'Global site tag (gtag.js)' ) );
		$this->assertContains( 'gla-gtag-events', $enqueued );
		$this->assertSame( $expect_bridge, in_array( 'gla-wp-consent-api', $enqueued, true ) );
	}

	public function wp_consent_api_bridge_provider(): array {
		return [
			'Woo Analytics not installed'                  => [ false, false, false, true, true ],
			'Woo Analytics active, not adding a framework' => [ true, false, false, true, true ],
			'Woo Analytics active, handle not enqueued'    => [ true, true, false, true, true ],
			// Without wp_head, WordPress never fires wp_enqueue_scripts, so the bridge is enqueued but never printed.
			'Woo Analytics active, no wp_head'             => [ true, false, false, false, true ],
			'Woo Analytics carries the Ads configuration'  => [ true, true, true, true, false ],
			'Subclass replaces display_global_site_tag()'  => [ true, false, false, true, true, true ],
		];
	}

	/**
	 * Without GLA's own Ads tag, a connected Tag Manager container still gets the consent default,
	 * so the WP Consent API bridge must be enqueued to lift it once the shopper chooses.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_wp_consent_api_bridge_is_enqueued_for_a_tag_manager_only_connection() {
		require_once dirname( __DIR__, 2 ) . '/data/wp-consent-api-functions.php';

		$this->gtag_js->method( 'is_adding_framework' )->willReturn( false );
		$this->options->method( 'get' )->willReturn( false );

		[ $output, $enqueued ] = $this->render_registered_page();

		$this->assertStringContainsString( 'function gtag() { dataLayer.push(arguments); }', $output );
		$this->assertStringContainsString( "gtag( 'consent', 'default'", $output );
		$this->assertStringNotContainsString( 'Global site tag (gtag.js)', $output );
		$this->assertContains( 'gla-wp-consent-api', $enqueued );
	}

	/**
	 * Woo Analytics 2.0+ is set up, but its script isn't on the page, so GLA prints the shim and
	 * consent default itself, ahead of its Ads tag, and enqueues the WP Consent API bridge.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_shim_and_consent_default_print_before_the_ads_tag_when_woo_analytics_script_is_missing() {
		require_once dirname( __DIR__, 2 ) . '/data/wp-consent-api-functions.php';
		class_alias( self::class, 'WC_Google_Gtag_JS' );

		$this->gtag_js->ga4w_v2 = true;
		$this->gtag_js->method( 'is_adding_framework' )->willReturn( true );
		$this->wp->method( 'wp_script_is' )->willReturn( false );
		$this->options->method( 'get' )->willReturn(
			[
				'conversion_id'    => self::TEST_CONVERSION_ID,
				'conversion_label' => self::TEST_CONVERSION_LABEL,
			]
		);

		[ $output, $enqueued ] = $this->render_registered_page();

		$shim_position       = strpos( $output, 'function gtag() { dataLayer.push(arguments); }' );
		$consent_position    = strpos( $output, "gtag( 'consent', 'default'" );
		$ads_config_position = strpos( $output, 'gtag("config", "' . self::TEST_CONVERSION_ID . '"' );
		$this->assertNotFalse( $shim_position );
		$this->assertNotFalse( $consent_position );
		$this->assertNotFalse( $ads_config_position );
		$this->assertLessThan( $ads_config_position, $shim_position );
		$this->assertLessThan( $ads_config_position, $consent_position );
		$this->assertStringContainsString( 'Global site tag (gtag.js)', $output );
		$this->assertContains( 'gla-wp-consent-api', $enqueued );
	}

	/**
	 * Woo Analytics 2.0+ prints the Google tag, so GLA prints no shim or consent default of its
	 * own and doesn't enqueue the WP Consent API bridge.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_no_shim_consent_default_or_bridge_when_woo_analytics_prints_the_google_tag() {
		require_once dirname( __DIR__, 2 ) . '/data/wp-consent-api-functions.php';
		class_alias( self::class, 'WC_Google_Gtag_JS' );

		$this->gtag_js->ga4w_v2 = true;
		$this->gtag_js->method( 'is_adding_framework' )->willReturn( true );
		$this->wp->method( 'wp_script_is' )->willReturnMap(
			[
				[ 'woocommerce-google-analytics-integration', 'enqueued', true ],
				[ 'woocommerce-google-analytics-integration', 'done', false ],
			]
		);
		$this->wp->method( 'wp_add_inline_script' )->willReturn( true );
		$this->options->method( 'get' )->willReturn(
			[
				'conversion_id'    => self::TEST_CONVERSION_ID,
				'conversion_label' => self::TEST_CONVERSION_LABEL,
			]
		);

		[ $output, $enqueued ] = $this->render_registered_page();

		$this->assertStringNotContainsString( 'function gtag() { dataLayer.push(arguments); }', $output );
		$this->assertStringNotContainsString( "gtag( 'consent', 'default'", $output );
		$this->assertNotContains( 'gla-wp-consent-api', $enqueued );
	}

	public function test_purchase_event_not_order_received_page() {
		add_filter( 'woocommerce_is_order_received_page', '__return_false' );
		$this->wp->expects( $this->never() )->method( 'wp_print_inline_script_tag' );

		$this->tag->maybe_display_purchase_event_snippet( self::TEST_CONVERSION_ID, self::TEST_CONVERSION_LABEL, 0 );
	}

	public function test_purchase_event_no_order() {
		add_filter( 'woocommerce_is_order_received_page', '__return_true' );
		$this->wp->expects( $this->never() )->method( 'wp_print_inline_script_tag' );

		$this->tag->maybe_display_purchase_event_snippet( self::TEST_CONVERSION_ID, self::TEST_CONVERSION_LABEL, 0 );
	}

	public function test_purchase_event_already_tracked() {
		add_filter( 'woocommerce_is_order_received_page', '__return_true' );

		$order = WC_Helper_Order::create_order();
		$order->update_meta_data( '_gla_tracked', 1 );
		$order->save_meta_data();

		$this->wp->expects( $this->never() )->method( 'wp_print_inline_script_tag' );

		$this->tag->maybe_display_purchase_event_snippet( self::TEST_CONVERSION_ID, self::TEST_CONVERSION_LABEL, $order->get_id() );
	}

	/**
	 * Known, accepted limitation: `register()` calls this with empty id/label when the native Ads
	 * tag is suppressed (e.g. a GTM conflict), and the order is still marked tracked. If the
	 * conflict later clears, a revisit to this same order's thank-you page can no longer fire its
	 * Ads conversion. Narrow in practice (requires revisiting a specific historical order's URL
	 * after the merchant fixes their container) — documented here rather than fixed, since the
	 * fix would need the data layer push and the Ads-tag firing to be tracked independently, and
	 * that's judged out of scope for now.
	 */
	public function test_purchase_event_marks_order_tracked_even_when_ads_conversion_is_suppressed() {
		add_filter( 'woocommerce_is_order_received_page', '__return_true' );

		$order = WC_Helper_Order::create_order();

		$this->tag->maybe_display_purchase_event_snippet( '', '', $order->get_id() );

		$order = wc_get_order( $order->get_id() );
		$this->assertSame( 1, (int) $order->get_meta( '_gla_tracked', true ) );
	}

	public function test_purchase_event() {
		add_filter( 'woocommerce_is_order_received_page', '__return_true' );

		$order = WC_Helper_Order::create_order();

		$invoked_count = $this->exactly( 2 );
		$this->wp->expects( $invoked_count )
			->method( 'wp_print_inline_script_tag' )
			->willReturnCallback(
				function ( string $script ) use ( $invoked_count ) {
					if ( 1 === $invoked_count->getInvocationCount() ) {
						$this->assertStringStartsWith( 'gtag("event", "purchase"', $script );
						$this->assertStringContainsString( self::DEVELOPER_ID_PARAM, $script );
					} elseif ( 2 === $invoked_count->getInvocationCount() ) {
						// The parallel GA4-schema push for the merchant's own GTM tags.
						$this->assertStringContainsString( 'dataLayer.push({', $script );
						$this->assertStringContainsString( 'event: "purchase"', $script );
						$this->assertStringContainsString( 'item_id: "gla_', $script );
					}
				}
			);

		$this->tag->maybe_display_purchase_event_snippet( self::TEST_CONVERSION_ID, self::TEST_CONVERSION_LABEL, $order->get_id() );

		// Reload order and confirm tracked meta is set.
		$order = wc_get_order( $order->get_id() );
		$this->assertSame( 1, (int) $order->get_meta( '_gla_tracked', true ) );
	}

	public function test_purchase_event_does_not_push_to_data_layer_when_tag_manager_not_connected() {
		add_filter( 'woocommerce_is_order_received_page', '__return_true' );

		// A second `method()->willReturn()` on the same mock method doesn't override the one
		// already set in setUp() — PHPUnit keeps whichever was configured first — so this needs
		// its own mock, stubbed disconnected from the start, rather than re-stubbing the shared one.
		$disconnected_tag_manager = $this->getMockBuilder( TagManagerConnection::class )
			->disableOriginalConstructor()
			->onlyMethods( [ 'get_connection_data', 'has_ads_conversion_conflict' ] )
			->getMock();
		$disconnected_tag_manager->method( 'get_connection_data' )->willReturn( [] );
		$tag = new GlobalSiteTag( $this->assets_handler, $this->gtag_js, $this->product_helper, $this->wc, $this->wp, $disconnected_tag_manager );
		$tag->set_options_object( $this->options );

		$order = WC_Helper_Order::create_order();

		// Only the gtag.js snippet should print — no parallel dataLayer push.
		$invoked_count = $this->exactly( 1 );
		$this->wp->expects( $invoked_count )
			->method( 'wp_print_inline_script_tag' )
			->willReturnCallback(
				function ( string $script ) {
					$this->assertStringStartsWith( 'gtag("event", "purchase"', $script );
				}
			);

		$tag->maybe_display_purchase_event_snippet( self::TEST_CONVERSION_ID, self::TEST_CONVERSION_LABEL, $order->get_id() );
	}

	public function test_view_item_event_snippet() {
		$product = WC_Helper_Product::create_simple_product();
		$this->go_to( get_permalink( $product->get_id() ) );

		$this->options->method( 'get' )->with( OptionsInterface::ADS_CONVERSION_ACTION )->willReturn(
			[
				'conversion_id'    => self::TEST_CONVERSION_ID,
				'conversion_label' => self::TEST_CONVERSION_LABEL,
			]
		);

		$this->product_helper->expects( $this->exactly( 3 ) )
			->method( 'get_categories' )
			->willReturn( [ 'Test Category' ] );

		$invoked_count = $this->exactly( 2 );
		$this->wp->expects( $invoked_count )
			->method( 'wp_print_inline_script_tag' )
			->willReturnCallback(
				function ( string $script ) use ( $invoked_count, $product ) {
					if ( 1 === $invoked_count->getInvocationCount() ) {
						$this->assertStringStartsWith( 'gtag("event", "view_item"', $script );
					} elseif ( 2 === $invoked_count->getInvocationCount() ) {
						// The parallel GA4-schema push for the merchant's own GTM tags.
						$this->assertStringContainsString( 'dataLayer.push({', $script );
						$this->assertStringContainsString( 'event: "view_item"', $script );
						$this->assertStringContainsString( 'item_id: "gla_' . $product->get_id() . '"', $script );
						$this->assertStringContainsString( 'item_category: "Test Category"', $script );
						$this->assertStringContainsString( 'currency: "' . get_woocommerce_currency() . '"', $script );
					}
				}
			);

		$method = new ReflectionMethod( $this->tag, 'display_view_item_event_snippet' );
		$method->setAccessible( true );
		$method->invoke( $this->tag, true );
	}

	public function test_view_item_event_snippet_does_not_push_to_data_layer_when_tag_manager_not_connected() {
		$product = WC_Helper_Product::create_simple_product();
		$this->go_to( get_permalink( $product->get_id() ) );

		// A second `method()->willReturn()` on the same mock method doesn't override the one
		// already set in setUp() — PHPUnit keeps whichever was configured first — so this needs
		// its own mock, stubbed disconnected from the start, rather than re-stubbing the shared one.
		$disconnected_tag_manager = $this->getMockBuilder( TagManagerConnection::class )
			->disableOriginalConstructor()
			->onlyMethods( [ 'get_connection_data', 'has_ads_conversion_conflict' ] )
			->getMock();
		$disconnected_tag_manager->method( 'get_connection_data' )->willReturn( [] );
		$tag = new GlobalSiteTag( $this->assets_handler, $this->gtag_js, $this->product_helper, $this->wc, $this->wp, $disconnected_tag_manager );
		$tag->set_options_object( $this->options );

		$this->options->method( 'get' )->with( OptionsInterface::ADS_CONVERSION_ACTION )->willReturn(
			[
				'conversion_id'    => self::TEST_CONVERSION_ID,
				'conversion_label' => self::TEST_CONVERSION_LABEL,
			]
		);
		$this->product_helper->method( 'get_categories' )->willReturn( [ 'Test Category' ] );

		// Only the gtag.js snippet should print — no parallel dataLayer push.
		$invoked_count = $this->exactly( 1 );
		$this->wp->expects( $invoked_count )
			->method( 'wp_print_inline_script_tag' )
			->willReturnCallback(
				function ( string $script ) {
					$this->assertStringStartsWith( 'gtag("event", "view_item"', $script );
				}
			);

		$method = new ReflectionMethod( $tag, 'display_view_item_event_snippet' );
		$method->setAccessible( true );
		$method->invoke( $tag, true );
	}

	public function test_view_item_event_snippet_not_a_product_page() {
		$this->go_to( home_url( '/' ) );

		$this->wp->expects( $this->never() )->method( 'wp_print_inline_script_tag' );

		$method = new ReflectionMethod( $this->tag, 'display_view_item_event_snippet' );
		$method->setAccessible( true );
		$method->invoke( $this->tag, true );
	}

	public function test_add_product_data_includes_category() {
		$product = WC_Helper_Product::create_simple_product();

		$this->product_helper->expects( $this->once() )
			->method( 'get_categories' )
			->willReturn( [ 'Test Category' ] );

		$method = new ReflectionMethod( $this->tag, 'add_product_data' );
		$method->setAccessible( true );
		$method->invoke( $this->tag, $product );

		$products_property = new ReflectionProperty( $this->tag, 'products' );
		$products_property->setAccessible( true );

		$this->assertEquals(
			[
				'name'     => $product->get_name(),
				'price'    => wc_get_price_to_display( $product ),
				'category' => 'Test Category',
			],
			$products_property->getValue( $this->tag )[ $product->get_id() ]
		);
	}

	public function test_add_product_data_joins_multiple_categories() {
		$product = WC_Helper_Product::create_simple_product();

		$this->product_helper->expects( $this->once() )
			->method( 'get_categories' )
			->willReturn( [ 'Category A', 'Category B' ] );

		$method = new ReflectionMethod( $this->tag, 'add_product_data' );
		$method->setAccessible( true );
		$method->invoke( $this->tag, $product );

		$products_property = new ReflectionProperty( $this->tag, 'products' );
		$products_property->setAccessible( true );

		$this->assertEquals(
			'Category A & Category B',
			$products_property->getValue( $this->tag )[ $product->get_id() ]['category']
		);
	}

	public function test_available_variation_data_includes_the_parent_category() {
		$parent    = WC_Helper_Product::create_variation_product();
		$variation = wc_get_product( $parent->get_children()[0] );

		$this->product_helper->expects( $this->once() )
			->method( 'get_categories' )
			->with( $parent )
			->willReturn( [ 'Test Category' ] );

		$method = new ReflectionMethod( $this->tag, 'product_data_hooks' );
		$method->setAccessible( true );
		$method->invoke( $this->tag );

		$data = apply_filters( 'woocommerce_available_variation', [], $parent, $variation );

		$this->assertSame( 'Test Category', $data['category'] );
	}

	public function test_view_item_event_snippet_reports_names_and_categories_as_plain_text() {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_name( 'Grete Jalk France &amp; Son Sofa' );
		$product->save();
		$this->go_to( get_permalink( $product->get_id() ) );

		$this->options->method( 'get' )->with( OptionsInterface::ADS_CONVERSION_ACTION )->willReturn(
			[
				'conversion_id'    => self::TEST_CONVERSION_ID,
				'conversion_label' => self::TEST_CONVERSION_LABEL,
			]
		);
		$this->product_helper->method( 'get_categories' )->willReturn( [ 'Clocks &amp; Collectibles', 'Unusual &amp; Unique' ] );

		$scripts = $this->capture_inline_scripts_in_array();

		$method = new ReflectionMethod( $this->tag, 'display_view_item_event_snippet' );
		$method->setAccessible( true );
		$method->invoke( $this->tag, true );

		[ $ads_script, $data_layer_script ] = $scripts->getArrayCopy();

		$this->assertStringContainsString( 'name: "Grete Jalk France & Son Sofa"', $ads_script );
		$this->assertStringContainsString( 'category: "Clocks & Collectibles & Unusual & Unique"', $ads_script );
		$this->assertStringContainsString( 'item_name: "Grete Jalk France & Son Sofa"', $data_layer_script );
		$this->assertStringContainsString( 'item_category: "Clocks & Collectibles & Unusual & Unique"', $data_layer_script );
		$this->assertStringNotContainsString( '&amp;', $ads_script . $data_layer_script );
	}

	public function test_view_item_event_snippet_escapes_names_and_categories_for_the_inline_script() {
		$product = WC_Helper_Product::create_simple_product();
		$this->go_to( get_permalink( $product->get_id() ) );

		$this->product_helper->method( 'get_categories' )->willReturn( [ 'Say "hi" </script><script>alert(1)' ] );

		$scripts = $this->capture_inline_scripts_in_array();

		$method = new ReflectionMethod( $this->tag, 'display_view_item_event_snippet' );
		$method->setAccessible( true );
		$method->invoke( $this->tag, false );

		$data_layer_script = $scripts->getArrayCopy()[0];

		$this->assertStringContainsString( 'item_category: "Say \\"hi\\" \\u003C\\/script\\u003E\\u003Cscript\\u003Ealert(1)"', $data_layer_script );
		$this->assertStringNotContainsString( '<', $data_layer_script );
	}

	public function test_view_item_event_snippet_uses_the_decision_register_made_even_if_the_filter_changes_afterward() {
		$product = WC_Helper_Product::create_simple_product();
		$this->go_to( get_permalink( $product->get_id() ) );

		// willReturnCallback, not ->with( ADS_CONVERSION_ACTION ) -- firing wp_head for real below
		// also runs activate_global_site_tag(), which reads other option keys the test doesn't
		// care about (e.g. ADS_ENHANCED_CONVERSIONS_ENABLED); a bare key constraint would fail
		// on those instead of just returning null for them.
		$this->options->method( 'get' )->willReturnCallback(
			function ( $key ) {
				return OptionsInterface::ADS_CONVERSION_ACTION === $key
					? [
						'conversion_id'    => self::TEST_CONVERSION_ID,
						'conversion_label' => self::TEST_CONVERSION_LABEL,
					]
					: null;
			}
		);
		$this->product_helper->method( 'get_categories' )->willReturn( [ 'Test Category' ] );

		// register_assets() constructs real, built JS assets that aren't available in this test
		// environment -- see the other register_assets()-mocking tests above for the same reason.
		$tag = $this->getMockBuilder( GlobalSiteTag::class )
			->setConstructorArgs( [ $this->assets_handler, $this->gtag_js, $this->product_helper, $this->wc, $this->wp, $this->tag_manager_connection ] )
			->onlyMethods( [ 'register_assets' ] )
			->getMock();
		$tag->set_options_object( $this->options );

		$tag->register();
		// A real page load fires wp_head -- and with it, register()'s own defer_events flush --
		// well before any body hook or a theme's own later filter change; simulate that here.
		do_action( 'wp_head' );

		// Simulate the filter being added later in the same request, e.g. from a theme hook that
		// runs after plugins_loaded -- register() already made its decision and must not re-read
		// this when the hooks it registered actually fire.
		add_filter( 'woocommerce_gla_disable_gtag_tracking', '__return_true' );

		$printed_ads_event = false;
		$this->wp->method( 'wp_print_inline_script_tag' )->willReturnCallback(
			function ( string $script ) use ( &$printed_ads_event ) {
				if ( str_starts_with( $script, 'gtag("event", "view_item"' ) ) {
					$printed_ads_event = true;
				}
			}
		);

		do_action( 'woocommerce_after_single_product' );

		remove_filter( 'woocommerce_gla_disable_gtag_tracking', '__return_true' );

		$this->assertTrue(
			$printed_ads_event,
			'The Ads view_item event should still fire using register()\'s own decision, not a filter value that changed afterward.'
		);
	}

	public function test_register_assets_receives_the_same_native_ads_tag_decision_register_computed() {
		$this->options->method( 'get' )->with( OptionsInterface::ADS_CONVERSION_ACTION )->willReturn(
			[
				'conversion_id'    => self::TEST_CONVERSION_ID,
				'conversion_label' => self::TEST_CONVERSION_LABEL,
			]
		);

		// tag_manager_connection is connected by default (see setUp()) with no conflict, so the
		// native Ads tag is enabled -- register_assets() must receive that same true, not derive
		// its own fresh answer later from inside its wp_footer closure.
		$tag = $this->getMockBuilder( GlobalSiteTag::class )
			->setConstructorArgs( [ $this->assets_handler, $this->gtag_js, $this->product_helper, $this->wc, $this->wp, $this->tag_manager_connection ] )
			->onlyMethods( [ 'register_assets' ] )
			->getMock();
		$tag->set_options_object( $this->options );

		$tag->expects( $this->once() )->method( 'register_assets' )->with( true );

		$tag->register();
	}

	public function test_purchase_event_reports_names_and_categories_as_plain_text() {
		add_filter( 'woocommerce_is_order_received_page', '__return_true' );

		$product = WC_Helper_Product::create_simple_product();
		$product->set_name( 'Grete Jalk France &amp; Son Sofa' );
		$product->save();
		$order = $this->create_order_with( $product );

		$this->product_helper->method( 'get_categories' )->willReturn( [ 'Clocks &amp; Collectibles', 'Unusual &amp; Unique' ] );

		$scripts = $this->capture_inline_scripts_in_array();

		$this->tag->maybe_display_purchase_event_snippet( self::TEST_CONVERSION_ID, self::TEST_CONVERSION_LABEL, $order->get_id() );

		[ $ads_script, $data_layer_script ] = $scripts->getArrayCopy();

		$this->assertStringContainsString( 'name: "Grete Jalk France & Son Sofa"', $ads_script );
		$this->assertStringContainsString( 'item_name: "Grete Jalk France & Son Sofa"', $data_layer_script );
		$this->assertStringContainsString( 'item_category: "Clocks & Collectibles & Unusual & Unique"', $data_layer_script );
		$this->assertStringNotContainsString( '&amp;', $ads_script . $data_layer_script );
	}

	public function test_add_product_data_reports_names_and_categories_as_plain_text() {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_name( 'Grete Jalk France &amp; Son Sofa' );

		$this->product_helper->method( 'get_categories' )->willReturn( [ 'Clocks &amp; Collectibles', 'Unusual &amp; Unique' ] );

		$method = new ReflectionMethod( $this->tag, 'add_product_data' );
		$method->setAccessible( true );
		$method->invoke( $this->tag, $product );

		$products_property = new ReflectionProperty( $this->tag, 'products' );
		$products_property->setAccessible( true );
		$product_data = $products_property->getValue( $this->tag )[ $product->get_id() ];

		$this->assertSame( 'Grete Jalk France & Son Sofa', $product_data['name'] );
		$this->assertSame( 'Clocks & Collectibles & Unusual & Unique', $product_data['category'] );
	}

	public function test_available_variation_data_reports_names_and_categories_as_plain_text() {
		$parent    = WC_Helper_Product::create_variation_product();
		$variation = wc_get_product( $parent->get_children()[0] );
		$variation->set_name( 'Sofa &amp; Chair' );

		$this->product_helper->method( 'get_categories' )->willReturn( [ 'Clocks &amp; Collectibles' ] );

		$method = new ReflectionMethod( $this->tag, 'product_data_hooks' );
		$method->setAccessible( true );
		$method->invoke( $this->tag );

		$data = apply_filters( 'woocommerce_available_variation', [], $parent, $variation );

		$this->assertSame( 'Sofa & Chair', $data['display_name'] );
		$this->assertSame( 'Clocks & Collectibles', $data['category'] );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_purchase_event_is_printed_when_wcga_class_exists_but_framework_is_unavailable() {
		if ( ! class_exists( '\\WC_Google_Gtag_JS', false ) ) {
			class_alias( self::class, 'WC_Google_Gtag_JS' );
		}

		add_filter( 'woocommerce_is_order_received_page', '__return_true' );

		// This test is about the WCGA-framework fallback specifically -- disconnect Tag Manager
		// (connected by default in setUp()) so its own parallel dataLayer push, covered by its
		// own dedicated tests, doesn't also call add_inline_event_script() a second time here.
		$this->tag_manager_connection = $this->getMockBuilder( TagManagerConnection::class )
			->disableOriginalConstructor()
			->onlyMethods( [ 'get_connection_data', 'has_ads_conversion_conflict' ] )
			->getMock();
		$this->tag_manager_connection->method( 'get_connection_data' )->willReturn( [] );
		$this->tag = new GlobalSiteTag( $this->assets_handler, $this->gtag_js, $this->product_helper, $this->wc, $this->wp, $this->tag_manager_connection );
		$this->tag->set_options_object( $this->options );

		$order = WC_Helper_Order::create_order();

		$this->gtag_js->expects( $this->once() )
			->method( 'is_adding_framework' )
			->willReturn( false );
		$this->wp->expects( $this->never() )->method( 'wp_script_is' );
		$this->wp->expects( $this->never() )->method( 'wp_add_inline_script' );
		$this->wp->expects( $this->once() )
			->method( 'wp_print_inline_script_tag' )
			->with(
				$this->callback(
					function ( string $script ) use ( $order ) {
						$this->assertStringStartsWith( 'gtag("event", "purchase"', $script );

						$untracked_order = wc_get_order( $order->get_id() );
						$this->assertEmpty( $untracked_order->get_meta( '_gla_tracked', true ) );

						return true;
					}
				)
			);

		$this->tag->maybe_display_purchase_event_snippet( self::TEST_CONVERSION_ID, self::TEST_CONVERSION_LABEL, $order->get_id() );

		$order = wc_get_order( $order->get_id() );
		$this->assertSame( 1, (int) $order->get_meta( '_gla_tracked', true ) );
	}

	public function test_inline_event_script_is_printed_when_wcga_handle_is_not_enqueued() {
		$inline_script = 'gtag("event", "purchase");';

		$this->gtag_js->expects( $this->once() )
			->method( 'is_adding_framework' )
			->willReturn( true );
		$this->wp->expects( $this->once() )
			->method( 'wp_script_is' )
			->with( 'woocommerce-google-analytics-integration', 'enqueued' )
			->willReturn( false );
		$this->wp->expects( $this->never() )->method( 'wp_add_inline_script' );
		$this->wp->expects( $this->once() )
			->method( 'wp_print_inline_script_tag' )
			->with( $inline_script );

		$this->tag->add_inline_event_script( $inline_script );
	}

	public function test_inline_event_script_falls_back_when_wcga_attachment_fails() {
		$inline_script = 'gtag("event", "purchase");';

		$this->gtag_js->expects( $this->once() )
			->method( 'is_adding_framework' )
			->willReturn( true );
		$this->wp->expects( $this->exactly( 2 ) )
			->method( 'wp_script_is' )
			->willReturnMap(
				[
					[ 'woocommerce-google-analytics-integration', 'enqueued', true ],
					[ 'woocommerce-google-analytics-integration', 'done', false ],
				]
			);
		$this->wp->expects( $this->once() )
			->method( 'wp_add_inline_script' )
			->with( 'woocommerce-google-analytics-integration', $inline_script )
			->willReturn( false );
		$this->wp->expects( $this->once() )
			->method( 'wp_print_inline_script_tag' )
			->with( $inline_script );

		$this->tag->add_inline_event_script( $inline_script );
	}

	public function test_inline_event_script_attaches_to_wcga_when_framework_is_available() {
		$inline_script = 'gtag("event", "purchase");';

		$this->gtag_js->expects( $this->once() )
			->method( 'is_adding_framework' )
			->willReturn( true );
		$this->wp->expects( $this->exactly( 2 ) )
			->method( 'wp_script_is' )
			->willReturnMap(
				[
					[ 'woocommerce-google-analytics-integration', 'enqueued', true ],
					[ 'woocommerce-google-analytics-integration', 'done', false ],
				]
			);
		$this->wp->expects( $this->once() )
			->method( 'wp_add_inline_script' )
			->with( 'woocommerce-google-analytics-integration', $inline_script )
			->willReturn( true );
		$this->wp->expects( $this->never() )->method( 'wp_print_inline_script_tag' );

		$this->tag->add_inline_event_script( $inline_script );
	}

	public function test_inline_event_script_is_printed_when_wcga_handle_is_done() {
		$inline_script = 'gtag("event", "purchase");';

		$this->gtag_js->expects( $this->once() )
			->method( 'is_adding_framework' )
			->willReturn( true );
		$this->wp->expects( $this->exactly( 2 ) )
			->method( 'wp_script_is' )
			->willReturnMap(
				[
					[ 'woocommerce-google-analytics-integration', 'enqueued', true ],
					[ 'woocommerce-google-analytics-integration', 'done', true ],
				]
			);
		$this->wp->expects( $this->never() )->method( 'wp_add_inline_script' );
		$this->wp->expects( $this->once() )
			->method( 'wp_print_inline_script_tag' )
			->with( $inline_script );

		$this->tag->add_inline_event_script( $inline_script );
	}

	/**
	 * @dataProvider tracking_lifecycle_provider
	 *
	 * @param bool $wcga_available    Whether Woo Analytics supplies its framework.
	 * @param bool $render_before_head Whether content renders before wp_head.
	 * @param bool $run_head          Whether the template calls wp_head.
	 * @param bool $wcga_in_head      Whether Woo Analytics prints its handle in the head.
	 */
	public function test_block_theme_purchase_event_follows_ads_configuration( bool $wcga_available, bool $render_before_head, bool $run_head, bool $wcga_in_head ) {
		global $wp_scripts;

		$handle = 'woocommerce-google-analytics-integration';
		$order  = WC_Helper_Order::create_order();

		wp_set_current_user( 0 );
		if ( ! $wcga_available ) {
			$this->login_as_administrator();
		}
		add_filter( 'woocommerce_is_order_received_page', '__return_true' );
		$this->gtag_js->ga4w_v2 = true;
		$this->gtag_js->method( 'is_adding_framework' )->willReturn( $wcga_available );
		$this->options->method( 'get' )->willReturnMap(
			[
				[
					OptionsInterface::ADS_CONVERSION_ACTION,
					null,
					[
						'conversion_id'    => self::TEST_CONVERSION_ID,
						'conversion_label' => self::TEST_CONVERSION_LABEL,
					],
				],
				[ OptionsInterface::ADS_ENHANCED_CONVERSIONS_ENABLED, null, true ],
			]
		);
		$this->wc->method( 'get_customer_details' )->willReturn( [ 'email' => 'customer@example.com' ] );

		// Use WordPress's real script registry and output, rather than fixed status mocks.
		// Skip unrelated asset registration: PHP-only CI does not build JavaScript assets.
		$this->tag = $this->getMockBuilder( GlobalSiteTag::class )
			->setConstructorArgs( [ $this->assets_handler, $this->gtag_js, $this->product_helper, $this->wc, new WP(), $this->tag_manager_connection ] )
			->onlyMethods( [ 'register_assets' ] )
			->getMock();
		$this->tag->expects( $this->once() )->method( 'register_assets' );
		$this->tag->set_options_object( $this->options );

		// Isolate the relevant head hooks; WordPress's test case restores hooks afterward.
		remove_all_actions( 'wp_head' );
		remove_all_actions( 'wp_enqueue_scripts' );
		remove_all_actions( 'wp_footer' );
		add_action( 'wp_head', 'wp_enqueue_scripts', 1 );
		add_action( 'wp_head', 'wp_print_head_scripts', 9 );
		$original_wp_scripts = $wp_scripts;
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Isolate script state for this lifecycle test.
		$wp_scripts = new WP_Scripts();
		wp_register_script( $handle, '/wcga-test.js', [], '1.0', ! $wcga_in_head );
		add_action(
			'wp_enqueue_scripts',
			function () use ( $handle, $wcga_available ) {
				if ( $wcga_available ) {
					wp_enqueue_script( $handle );
				}
			},
			5
		);
		add_action(
			'wp_footer',
			function () {
				wp_scripts()->do_footer_items();
			},
			20
		);
		$this->tag->register();

		// Model the order-confirmation block's buffered woocommerce_before_thankyou hook.
		register_block_type(
			'gla-test/order-confirmation',
			[
				'render_callback' => function () use ( $order ) {
					ob_start();
					do_action( 'woocommerce_before_thankyou', $order->get_id() );
					$this->tag->add_inline_event_script( 'gtag("event", "view_item", {send_to: "GLA"});' );
					return ob_get_clean();
				},
			]
		);

		try {
			$this->assertFalse( wp_script_is( $handle, 'enqueued' ) );

			$body = '';
			if ( $render_before_head ) {
				// template-canvas.php renders blocks before wp_head, but emits their HTML afterward.
				$body = do_blocks( '<!-- wp:gla-test/order-confirmation /--><!-- wp:gla-test/order-confirmation /-->' );
				$this->assertSame( '', $body );
				$this->assertEmpty( wc_get_order( $order->get_id() )->get_meta( '_gla_tracked', true ) );
			}

			ob_start();
			if ( $run_head ) {
				wp_head();
			}
			$head = ob_get_clean();
			$this->assertSame( $wcga_available && $run_head, wp_script_is( $handle, 'enqueued' ) );

			if ( ! $render_before_head ) {
				$body = do_blocks( '<!-- wp:gla-test/order-confirmation /-->' );
			}

			ob_start();
			wp_footer();
			$footer = ob_get_clean();
			$html   = $head . $body . $footer;

			$config    = 'gtag("config", "test_id"';
			$user_data = 'gtag("set", "user_data"';
			$purchase  = 'gtag("event", "purchase"';
			$this->assertSame( 1, substr_count( $html, $config ) );
			$this->assertSame( 1, substr_count( $html, $user_data ) );
			$this->assertSame( 1, substr_count( $html, $purchase ) );
			$this->assertSame( $render_before_head ? 2 : 1, substr_count( $html, 'gtag("event", "view_item"' ) );
			$this->assertSame( 1, (int) wc_get_order( $order->get_id() )->get_meta( '_gla_tracked', true ) );
			$this->assertSame( $wcga_available && $run_head ? 0 : 1, substr_count( $html, 'Global site tag (gtag.js)' ) );
			$this->assertLessThan(
				strpos( $html, $purchase ),
				strpos( $html, $config ),
				'Ads configuration must appear before the block-theme purchase event.'
			);
			$this->assertLessThan(
				strpos( $html, $purchase ),
				strpos( $html, $user_data ),
				'Enhanced-conversion data must appear before the block-theme purchase event.'
			);
			$this->assertLessThan( strpos( $html, 'gtag("event", "view_item"' ), strpos( $html, $config ) );

			// Events arriving after footer scripts have printed must still be emitted.
			ob_start();
			$this->tag->add_inline_event_script( 'gtag("event", "late_event");' );
			$late_output = ob_get_clean();
			$this->assertStringContainsString( 'gtag("event", "late_event");', $late_output );
		} finally {
			unregister_block_type( 'gla-test/order-confirmation' );
			// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restore the registry used by other tests.
			$wp_scripts = $original_wp_scripts;
		}
	}

	public function tracking_lifecycle_provider(): array {
		return [
			'block theme guest'               => [ true, true, true, false ],
			'block theme administrator'       => [ false, true, true, false ],
			'classic theme guest'             => [ true, false, true, false ],
			'block theme without wp_head'     => [ true, true, false, false ],
			'block theme with printed handle' => [ true, true, true, true ],
		];
	}

	public function test_queued_purchase_is_not_marked_as_tracked_when_output_fails() {
		// Keep real tracking hooks without requiring built JavaScript assets.
		$this->tag = $this->getMockBuilder( GlobalSiteTag::class )
			->setConstructorArgs( [ $this->assets_handler, $this->gtag_js, $this->product_helper, $this->wc, $this->wp, $this->tag_manager_connection ] )
			->onlyMethods( [ 'register_assets' ] )
			->getMock();
		$this->tag->expects( $this->once() )->method( 'register_assets' );
		$this->tag->set_options_object( $this->options );

		add_filter( 'woocommerce_is_order_received_page', '__return_true' );
		remove_all_actions( 'wp_head' );
		$this->options->method( 'get' )->willReturnMap(
			[
				[
					OptionsInterface::ADS_CONVERSION_ACTION,
					null,
					[
						'conversion_id'    => self::TEST_CONVERSION_ID,
						'conversion_label' => self::TEST_CONVERSION_LABEL,
					],
				],
			]
		);
		$this->tag->register();
		$order = WC_Helper_Order::create_order();
		do_action( 'woocommerce_before_thankyou', $order->get_id() );
		$this->assertEmpty( wc_get_order( $order->get_id() )->get_meta( '_gla_tracked', true ) );
		$this->wp->expects( $this->once() )->method( 'wp_print_inline_script_tag' )
			->willThrowException( new RuntimeException( 'Unable to print queued purchase.' ) );

		ob_start();
		try {
			wp_head();
			$this->fail( 'Expected queued event output to fail.' );
		} catch ( RuntimeException $exception ) {
			$this->assertSame( 'Unable to print queued purchase.', $exception->getMessage() );
		} finally {
			ob_end_clean();
		}
		$this->assertEmpty( wc_get_order( $order->get_id() )->get_meta( '_gla_tracked', true ) );
	}

	public function test_purchase_event_is_not_marked_as_tracked_when_output_fails() {
		add_filter( 'woocommerce_is_order_received_page', '__return_true' );

		$order = WC_Helper_Order::create_order();

		$this->gtag_js->method( 'is_adding_framework' )->willReturn( false );
		$this->wp->expects( $this->once() )
			->method( 'wp_print_inline_script_tag' )
			->willThrowException( new RuntimeException( 'Unable to print the purchase event.' ) );

		try {
			$this->tag->maybe_display_purchase_event_snippet( self::TEST_CONVERSION_ID, self::TEST_CONVERSION_LABEL, $order->get_id() );
			$this->fail( 'Expected event output to fail.' );
		} catch ( RuntimeException $exception ) {
			$this->assertSame( 'Unable to print the purchase event.', $exception->getMessage() );
		}

		$order = wc_get_order( $order->get_id() );
		$this->assertEmpty( $order->get_meta( '_gla_tracked', true ) );
	}

	public function test_view_item_event() {
		$product = WC_Helper_Product::create_simple_product();

		// Set the global post directly rather than with go_to(), which unsets globals like $pagenow for later tests.
		$GLOBALS['post'] = get_post( $product->get_id() ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		// This test is about the Ads view_item event specifically -- disconnect Tag Manager
		// (connected by default in setUp()) so its own parallel dataLayer push, covered by its
		// own dedicated tests, doesn't also land in $this->inline_scripts here. A fresh mock,
		// not a re-stub of setUp()'s shared one -- see register_with_conversion_action()'s own
		// disconnected-mock tests for why re-stubbing doesn't work.
		$this->tag_manager_connection = $this->getMockBuilder( TagManagerConnection::class )
			->disableOriginalConstructor()
			->onlyMethods( [ 'get_connection_data', 'has_ads_conversion_conflict' ] )
			->getMock();
		$this->tag_manager_connection->method( 'get_connection_data' )->willReturn( [] );

		$this->capture_inline_scripts();
		$this->register_with_conversion_action();
		$this->run_wp_head();

		do_action( 'woocommerce_after_single_product' );

		$this->assertCount( 1, $this->inline_scripts );
		$this->assertStringStartsWith( 'gtag("event", "view_item"', $this->inline_scripts[0] );
		$this->assertStringContainsString( self::DEVELOPER_ID_PARAM, $this->inline_scripts[0] );
	}

	public function test_page_view_event() {
		$this->capture_inline_scripts();
		$this->register_with_conversion_action();
		$this->run_wp_head();

		do_action( 'wp_body_open' );

		$this->assertCount( 1, $this->inline_scripts );
		$this->assertStringStartsWith( 'gtag("event", "page_view"', $this->inline_scripts[0] );
		$this->assertStringContainsString( self::DEVELOPER_ID_PARAM, $this->inline_scripts[0] );
	}

	public function test_cart_page_view_event() {
		add_filter( 'woocommerce_is_cart', '__return_true' );
		wc_load_cart();
		WC()->cart->add_to_cart( WC_Helper_Product::create_simple_product()->get_id() );

		$this->capture_inline_scripts();
		$this->register_with_conversion_action();
		$this->run_wp_head();

		do_action( 'wp_body_open' );

		$this->assertCount( 1, $this->inline_scripts );
		$this->assertStringStartsWith( 'gtag("event", "page_view"', $this->inline_scripts[0] );
		$this->assertStringContainsString( 'ecomm_pagetype: "cart"', $this->inline_scripts[0] );
		$this->assertStringContainsString( self::DEVELOPER_ID_PARAM, $this->inline_scripts[0] );
	}

	public function test_enhanced_conversion_data_is_null_when_no_customer_data() {
		// Setup empty customer data.
		$this->wc->expects( $this->once() )
			->method( 'get_customer_details' )
			->willReturn( [] );

		$this->options->expects( $this->once() )->method( 'get' )->willReturn( true );

		// Get the enhanced conversion tag.
		$gtag = $this->tag->get_enhanced_conversion_tag();

		// Tag should be empty with no customer data.
		$this->assertEmpty( $gtag );
	}

	public function test_enhanced_conversion_data_is_set_with_customer_email() {
		// Setup test customer with email address only.
		$email      = 'test@mail.test';
		$email_hash = hash( 'sha256', strtolower( trim( $email ) ) );

		$this->wc->expects( $this->once() )
			->method( 'get_customer_details' )
			->willReturn( [ 'email' => $email ] );

		$this->options->expects( $this->once() )->method( 'get' )->willReturn( true );

		// Get the enhanvced conversion tag.
		$gtag = $this->tag->get_enhanced_conversion_tag();

		// Confirm the hashed email and key is present.
		$this->assertStringContainsString( 'sha256_email_address', $gtag );
		$this->assertStringContainsString( $email_hash, $gtag );
	}

	public function test_enhanced_conversion_data_is_set_with_customer_phone() {
		// Test GB phone number with hashed e614 format.
		$phone      = '01629 582299';
		$phone_hash = hash( 'sha256', strtolower( trim( '+441629582299' ) ) );

		$customer_mock = [
			'email'   => 'test@mail.test',
			'phone'   => $phone,
			'country' => 'GB',
		];

		$this->wc->expects( $this->once() )
			->method( 'get_customer_details' )
			->willReturn( $customer_mock );

		$this->options->expects( $this->once() )->method( 'get' )->willReturn( true );

		// Get the enhanvced conversion tag.
		$gtag = $this->tag->get_enhanced_conversion_tag();

		// Confirm the hashed phone and key is present.
		$this->assertStringContainsString( 'sha256_phone_number', $gtag );
		$this->assertStringContainsString( $phone_hash, $gtag );
	}

	public function test_enhanced_conversion_data_is_empty_when_only_customer_phone_available() {
		// Test GB phone number with hashed e614 format.
		$phone      = '01629 582299';
		$phone_hash = hash( 'sha256', strtolower( trim( '+441629582299' ) ) );

		$customer_mock = [
			'phone'   => $phone,
			'country' => 'GB',
		];

		$this->wc->expects( $this->once() )
			->method( 'get_customer_details' )
			->willReturn( $customer_mock );

		$this->options->expects( $this->once() )->method( 'get' )->willReturn( true );

		// Get the enhanvced conversion tag.
		$gtag = $this->tag->get_enhanced_conversion_tag();

		// Confirm the hashed phone and key is present.
		$this->assertEmpty( $gtag );
	}

	public function test_enhanced_conversion_data_is_set_with_customer_address() {
		// Test GB address with hashed names.
		$first      = 'Test';
		$last       = 'Customer';
		$first_hash = hash( 'sha256', strtolower( trim( $first ) ) );
		$last_hash  = hash( 'sha256', strtolower( trim( $last ) ) );
		$postcode   = 'DE4 3GX';

		$customer_mock = [
			'email'      => 'test@mail.test',
			'first_name' => $first,
			'last_name'  => $last,
			'postcode'   => $postcode,
			'country'    => 'GB',
		];

		$this->wc->expects( $this->once() )
			->method( 'get_customer_details' )
			->willReturn( $customer_mock );

		$this->options->expects( $this->once() )->method( 'get' )->willReturn( true );

		// Get the enhanvced conversion tag.
		$gtag = $this->tag->get_enhanced_conversion_tag();

		// Confirm the hashed values and keys are present.
		$this->assertStringContainsString( 'sha256_first_name', $gtag );
		$this->assertStringContainsString( 'sha256_last_name', $gtag );
		$this->assertStringContainsString( 'postal_code', $gtag );
		$this->assertStringContainsString( 'country', $gtag );
		$this->assertStringContainsString( $first_hash, $gtag );
		$this->assertStringContainsString( $last_hash, $gtag );
		$this->assertStringContainsString( $postcode, $gtag );
	}

	public function test_register_wires_up_view_item_hook_for_a_tag_manager_only_connection() {
		// Regression test: register() itself gated every hook it wires up — including the
		// ones is_tag_manager_connected() protects — behind having an Ads conversion action
		// configured, so a merchant with only Tag Manager connected got nothing wired up at
		// all. The inner methods' own guards never got a chance to matter. Exercises
		// register() itself, not the inner methods directly, since calling them directly is
		// exactly the blind spot that let the original bug through untested.
		$this->options->method( 'get' )->with( OptionsInterface::ADS_CONVERSION_ACTION )->willReturn( false );

		$connected_tag_manager = $this->getMockBuilder( TagManagerConnection::class )
			->disableOriginalConstructor()
			->onlyMethods( [ 'get_connection_data', 'has_ads_conversion_conflict' ] )
			->getMock();
		$connected_tag_manager->method( 'get_connection_data' )->willReturn(
			[ 'container_public_id' => 'GTM-TEST1234' ]
		);

		// register_assets() constructs a ScriptWithBuiltDependenciesAsset that reads the
		// built js/build/gtag-events.asset.php file from disk — a real dependency the PHP
		// unit test job's environment never builds. $this->assets_handler is already mocked
		// so the actual registration is a no-op regardless; skip the method itself so this
		// test can exercise register()'s real hook-wiring logic without needing a JS build.
		$tag = $this->getMockBuilder( GlobalSiteTag::class )
			->setConstructorArgs( [ $this->assets_handler, $this->gtag_js, $this->product_helper, $this->wc, $this->wp, $connected_tag_manager ] )
			->onlyMethods( [ 'register_assets' ] )
			->getMock();
		$tag->set_options_object( $this->options );

		$tag->register();
		// A real page load fires wp_head -- and with it, register()'s own defer_events flush --
		// well before any body hook; simulate that here or the event stays queued forever.
		do_action( 'wp_head' );

		$product = WC_Helper_Product::create_simple_product();
		$this->go_to( get_permalink( $product->get_id() ) );
		$this->product_helper->method( 'get_categories' )->willReturn( [ 'Test Category' ] );

		$this->wp->expects( $this->once() )
			->method( 'wp_print_inline_script_tag' )
			->willReturnCallback(
				function ( string $script ) {
					// Only the dataLayer push should fire — no conversion action to build the
					// Ads gtag.js snippet from.
					$this->assertStringContainsString( 'dataLayer.push({', $script );
				}
			);

		do_action( 'woocommerce_after_single_product' );
	}

	public function test_register_wires_up_purchase_hook_for_a_tag_manager_only_connection() {
		// Same regression as test_register_wires_up_view_item_hook_for_a_tag_manager_only_connection
		// above, for the purchase path: register() itself must wire up woocommerce_before_thankyou
		// for a Tag-Manager-only connection, not just the view_item hook. Exercises register()
		// itself, not maybe_display_purchase_event_snippet() directly, for the same reason.
		$this->options->method( 'get' )->with( OptionsInterface::ADS_CONVERSION_ACTION )->willReturn( false );

		$connected_tag_manager = $this->getMockBuilder( TagManagerConnection::class )
			->disableOriginalConstructor()
			->onlyMethods( [ 'get_connection_data', 'has_ads_conversion_conflict' ] )
			->getMock();
		$connected_tag_manager->method( 'get_connection_data' )->willReturn(
			[ 'container_public_id' => 'GTM-TEST1234' ]
		);

		// See the view_item regression test above for why register_assets() is skipped here.
		$tag = $this->getMockBuilder( GlobalSiteTag::class )
			->setConstructorArgs( [ $this->assets_handler, $this->gtag_js, $this->product_helper, $this->wc, $this->wp, $connected_tag_manager ] )
			->onlyMethods( [ 'register_assets' ] )
			->getMock();
		$tag->set_options_object( $this->options );

		$tag->register();
		// A real page load fires wp_head -- and with it, register()'s own defer_events flush --
		// well before any body hook; simulate that here or the event stays queued forever.
		do_action( 'wp_head' );

		add_filter( 'woocommerce_is_order_received_page', '__return_true' );
		$order = WC_Helper_Order::create_order();

		$this->wp->expects( $this->once() )
			->method( 'wp_print_inline_script_tag' )
			->willReturnCallback(
				function ( string $script ) {
					// Only the dataLayer push should fire — no conversion action to build the
					// Ads gtag.js snippet from.
					$this->assertStringContainsString( 'dataLayer.push({', $script );
				}
			);

		do_action( 'woocommerce_before_thankyou', $order->get_id() );
	}

	public function test_register_publishes_consent_defaults_for_a_tag_manager_only_connection() {
		// Regression guard: the shim used to only publish alongside the Ads-gated snippet.
		$this->options->method( 'get' )->with( OptionsInterface::ADS_CONVERSION_ACTION )->willReturn( false );

		$connected_tag_manager = $this->getMockBuilder( TagManagerConnection::class )
			->disableOriginalConstructor()
			->onlyMethods( [ 'get_connection_data', 'has_ads_conversion_conflict' ] )
			->getMock();
		$connected_tag_manager->method( 'get_connection_data' )->willReturn(
			[ 'container_public_id' => 'GTM-TEST1234' ]
		);

		$tag = $this->getMockBuilder( GlobalSiteTag::class )
			->setConstructorArgs( [ $this->assets_handler, $this->gtag_js, $this->product_helper, $this->wc, $this->wp, $connected_tag_manager ] )
			->onlyMethods( [ 'register_assets' ] )
			->getMock();
		$tag->set_options_object( $this->options );

		$tag->register();

		ob_start();
		do_action( 'wp_head' );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'window.dataLayer = window.dataLayer || []', $output );
		$this->assertStringContainsString( 'function gtag() { dataLayer.push(arguments); }', $output );
		$this->assertStringContainsString( "gtag( 'consent', 'default'", $output );

		// No Ads conversion action configured — the Ads-specific gtag.js loader must not appear.
		$this->assertStringNotContainsString( 'googletagmanager.com/gtag/js', $output );
	}

	public function test_register_skips_consent_defaults_when_another_plugin_already_provides_gtag_js() {
		// Regression guard: a second, competing gtag()/dataLayer shim would conflict with the one
		// Google Analytics for WooCommerce already injects, rather than complement it.
		$this->options->method( 'get' )->with( OptionsInterface::ADS_CONVERSION_ACTION )->willReturn( false );

		// Proves the early return was actually reached, not just that the plugin's markers
		// happen to be absent for some other reason (e.g. the wp_head hook never firing).
		$this->gtag_js->expects( $this->once() )->method( 'is_adding_framework' )->willReturn( true );

		$connected_tag_manager = $this->getMockBuilder( TagManagerConnection::class )
			->disableOriginalConstructor()
			->onlyMethods( [ 'get_connection_data', 'has_ads_conversion_conflict' ] )
			->getMock();
		$connected_tag_manager->method( 'get_connection_data' )->willReturn(
			[ 'container_public_id' => 'GTM-TEST1234' ]
		);

		$tag = $this->getMockBuilder( GlobalSiteTag::class )
			->setConstructorArgs( [ $this->assets_handler, $this->gtag_js, $this->product_helper, $this->wc, $this->wp, $connected_tag_manager ] )
			->onlyMethods( [ 'register_assets' ] )
			->getMock();
		$tag->set_options_object( $this->options );

		$tag->register();

		ob_start();
		do_action( 'wp_head' );
		$output = ob_get_clean();

		$this->assertStringNotContainsString( 'function gtag() { dataLayer.push(arguments); }', $output );
	}

	public function test_register_publishes_consent_defaults_exactly_once_when_ads_is_also_configured() {
		// display_global_site_tag() also calls get_enhanced_conversion_tag(), which reads a
		// second option key — a plain ->with( ADS_CONVERSION_ACTION ) stub would fail on that
		// second call, so this maps both keys explicitly instead.
		$this->options->method( 'get' )->willReturnCallback(
			function ( string $key ) {
				if ( OptionsInterface::ADS_CONVERSION_ACTION === $key ) {
					return [
						'conversion_id'    => self::TEST_CONVERSION_ID,
						'conversion_label' => self::TEST_CONVERSION_LABEL,
					];
				}

				return false;
			}
		);

		// tag_manager_connection is connected by default (see setUp()) — both the unconditional
		// consent-defaults hook and the Ads-gated gtag.js hook fire on wp_head here.
		$tag = $this->getMockBuilder( GlobalSiteTag::class )
			->setConstructorArgs( [ $this->assets_handler, $this->gtag_js, $this->product_helper, $this->wc, $this->wp, $this->tag_manager_connection ] )
			->onlyMethods( [ 'register_assets' ] )
			->getMock();
		$tag->set_options_object( $this->options );

		$tag->register();

		ob_start();
		do_action( 'wp_head' );
		$output = ob_get_clean();

		$this->assertSame( 1, substr_count( $output, 'window.dataLayer = window.dataLayer || []' ) );
		$this->assertSame( 1, substr_count( $output, "gtag( 'consent', 'default'" ) );
		$this->assertStringContainsString( 'googletagmanager.com/gtag/js', $output );

		// Google's Consent Mode requires the default consent signal to be set before gtag.js
		// loads — confirm the shim really does still run first now that it's a separate hook.
		$this->assertLessThan(
			strpos( $output, 'googletagmanager.com/gtag/js' ),
			strpos( $output, "gtag( 'consent', 'default'" )
		);
	}

	public function test_register_suppresses_ads_tag_when_tag_manager_container_has_a_conflicting_ads_tag() {
		$this->options->method( 'get' )->with( OptionsInterface::ADS_CONVERSION_ACTION )->willReturn(
			[
				'conversion_id'    => self::TEST_CONVERSION_ID,
				'conversion_label' => self::TEST_CONVERSION_LABEL,
			]
		);
		$this->tag_manager_connection->method( 'has_ads_conversion_conflict' )->willReturn( true );

		// tag_manager_connection is connected by default (see setUp()), so the consent-defaults
		// shim still publishes — only the Ads-specific gtag.js script is suppressed.
		$tag = $this->getMockBuilder( GlobalSiteTag::class )
			->setConstructorArgs( [ $this->assets_handler, $this->gtag_js, $this->product_helper, $this->wc, $this->wp, $this->tag_manager_connection ] )
			->onlyMethods( [ 'register_assets' ] )
			->getMock();
		$tag->set_options_object( $this->options );

		$tag->register();

		ob_start();
		do_action( 'wp_head' );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'window.dataLayer = window.dataLayer || []', $output );
		$this->assertStringNotContainsString( 'googletagmanager.com/gtag/js', $output );
	}

	public function test_register_still_runs_for_a_connected_container_when_the_native_ads_tag_is_disabled_by_filter() {
		// The filter used to also block GlobalSiteTag from registering when a Tag Manager
		// container was connected. It should only suppress the native Ads tag now.
		add_filter( 'woocommerce_gla_disable_gtag_tracking', '__return_true' );

		$this->options->method( 'get' )->with( OptionsInterface::ADS_CONVERSION_ACTION )->willReturn(
			[
				'conversion_id'    => self::TEST_CONVERSION_ID,
				'conversion_label' => self::TEST_CONVERSION_LABEL,
			]
		);

		// tag_manager_connection is connected by default (see setUp()).
		$tag = $this->getMockBuilder( GlobalSiteTag::class )
			->setConstructorArgs( [ $this->assets_handler, $this->gtag_js, $this->product_helper, $this->wc, $this->wp, $this->tag_manager_connection ] )
			->onlyMethods( [ 'register_assets' ] )
			->getMock();
		$tag->set_options_object( $this->options );

		// register_assets() enqueues gla-wp-consent-api; asserting it's called proves the whole
		// service still registers, not just the Ads tag.
		$tag->expects( $this->once() )->method( 'register_assets' );

		$tag->register();

		ob_start();
		do_action( 'wp_head' );
		$output = ob_get_clean();

		remove_filter( 'woocommerce_gla_disable_gtag_tracking', '__return_true' );

		$this->assertStringContainsString( 'window.dataLayer = window.dataLayer || []', $output );
		$this->assertStringContainsString( "gtag( 'consent', 'default'", $output );

		// The native Ads tag itself is still suppressed -- only its own gtag.js loader is gone.
		$this->assertStringNotContainsString( 'googletagmanager.com/gtag/js', $output );
	}

	public function test_register_does_nothing_when_neither_tag_manager_nor_ads_is_active() {
		// Regression guard: register() must still no-op when neither Ads nor Tag Manager is
		// configured — the existing early return must still hold.
		$this->options->method( 'get' )->with( OptionsInterface::ADS_CONVERSION_ACTION )->willReturn( false );

		$disconnected_tag_manager = $this->getMockBuilder( TagManagerConnection::class )
			->disableOriginalConstructor()
			->onlyMethods( [ 'get_connection_data', 'has_ads_conversion_conflict' ] )
			->getMock();
		$disconnected_tag_manager->method( 'get_connection_data' )->willReturn( [] );

		$tag = new GlobalSiteTag( $this->assets_handler, $this->gtag_js, $this->product_helper, $this->wc, $this->wp, $disconnected_tag_manager );
		$tag->set_options_object( $this->options );

		$this->assets_handler->expects( $this->never() )->method( 'register' );
		$this->wp->expects( $this->never() )->method( 'wp_print_inline_script_tag' );

		$tag->register();

		ob_start();
		do_action( 'wp_head' );
		do_action( 'wp_body_open' );
		$output = ob_get_clean();

		// wp_head/wp_body_open always carry other WordPress/WooCommerce core output regardless of
		// this plugin — assert none of *our* data-layer machinery appears, not that the buffer is
		// empty outright.
		$this->assertStringNotContainsString( 'dataLayer', $output );
		$this->assertStringNotContainsString( 'gtag', $output );
		$this->assertStringNotContainsString( 'googletagmanager.com', $output );
	}

	/**
	 * Register the service with a conversion action, which adds its event hooks.
	 *
	 * Asset registration is skipped, as it reads the built js/build/ files which
	 * aren't guaranteed to exist in a unit test run.
	 */
	protected function register_with_conversion_action(): void {
		$this->tag = $this->getMockBuilder( GlobalSiteTag::class )
			->setConstructorArgs( [ $this->assets_handler, $this->gtag_js, $this->product_helper, $this->wc, $this->wp, $this->tag_manager_connection ] )
			->onlyMethods( [ 'register_assets' ] )
			->getMock();
		$this->tag->set_options_object( $this->options );

		// Keep only this service's wp_head callback, so run_wp_head() stays isolated.
		remove_all_actions( 'wp_head' );

		$this->options->method( 'get' )->willReturn(
			[
				'conversion_id'    => self::TEST_CONVERSION_ID,
				'conversion_label' => self::TEST_CONVERSION_LABEL,
			]
		);

		$this->tag->register();
	}

	/**
	 * Register the service, then run the head and footer actions.
	 *
	 * @return array{0: string, 1: string[]} The printed output and the handles of the enqueued assets.
	 */
	protected function render_registered_page(): array {
		$enqueued = [];
		$this->assets_handler->method( 'enqueue' )->willReturnCallback(
			function ( $asset ) use ( &$enqueued ) {
				$enqueued[] = $asset->get_handle();
			}
		);

		// Keep only this service's head and footer callbacks.
		remove_all_actions( 'wp_head' );
		remove_all_actions( 'wp_footer' );

		// Asset registration requires the built scripts, which PHP-only CI does not build.
		$stubbed_scripts = $this->stub_missing_build_scripts( [ 'gtag-events', 'wp-consent-api' ] );
		try {
			$this->tag->register();
			$this->tag->add_inline_event_script( 'gtag("event", "page_view");' );

			ob_start();
			do_action( 'wp_head' );
			do_action( 'wp_footer' );
			$output = ob_get_clean();
		} finally {
			array_map( 'unlink', $stubbed_scripts );
		}

		return [ $output, $enqueued ];
	}

	/**
	 * Create empty built scripts that are missing, leaving existing builds untouched.
	 *
	 * @param string[] $names Script names under js/build, without extension.
	 *
	 * @return string[] Paths created, for the caller to remove.
	 */
	protected function stub_missing_build_scripts( array $names ): array {
		$build_dir = dirname( __DIR__, 3 ) . '/js/build';
		wp_mkdir_p( $build_dir );

		$created = [];
		foreach ( $names as $name ) {
			$path = "{$build_dir}/{$name}.js";
			if ( ! is_readable( $path ) && ! is_readable( "{$build_dir}/{$name}.min.js" ) ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
				file_put_contents( $path, '' );
				$created[] = $path;
			}
		}

		return $created;
	}

	/**
	 * Run wp_head, which outputs the site tag and stops deferring events.
	 */
	protected function run_wp_head(): void {
		ob_start();
		do_action( 'wp_head' );
		ob_end_clean();
	}

	/**
	 * Collect every inline script printed into $inline_scripts.
	 */
	protected function capture_inline_scripts(): void {
		$this->wp->method( 'wp_print_inline_script_tag' )->willReturnCallback(
			function ( string $script ) {
				$this->inline_scripts[] = $script;
			}
		);
	}

	/**
	 * Collect every inline event script the tag prints, in order.
	 *
	 * @return ArrayObject The printed scripts, filled in as they're printed.
	 */
	private function capture_inline_scripts_in_array(): ArrayObject {
		$scripts = new ArrayObject();
		$this->wp->method( 'wp_print_inline_script_tag' )->willReturnCallback(
			function ( string $script ) use ( $scripts ) {
				$scripts->append( $script );
			}
		);

		return $scripts;
	}

	/**
	 * Create an order containing one of the given product.
	 *
	 * @param WC_Product $product
	 *
	 * @return WC_Order
	 */
	private function create_order_with( WC_Product $product ): WC_Order {
		$order = wc_create_order();
		$order->add_product( $product, 1 );
		$order->set_billing_email( 'customer@example.com' );
		$order->calculate_totals();
		$order->save();

		return $order;
	}
}
