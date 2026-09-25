<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\Assets\AssetsHandlerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\GlobalSiteTag;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\GoogleGtagJs;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;
use WC_Helper_Order;

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

	/** @var GlobalSiteTag $tag */
	protected $tag;

	/** @var GlobalSiteTag $tag */
	protected $options;

	protected const TEST_CONVERSION_ID    = 'test_id';
	protected const TEST_CONVERSION_LABEL = 'test_conversion_label';

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->options        = $this->createMock( OptionsInterface::class );
		$this->assets_handler = $this->createMock( AssetsHandlerInterface::class );
		$this->gtag_js        = $this->createMock( GoogleGtagJs::class );
		$this->product_helper = $this->createMock( ProductHelper::class );
		$this->wc             = $this->createMock( WC::class );
		$this->wp             = $this->createMock( WP::class );

		$this->tag = new GlobalSiteTag( $this->assets_handler, $this->gtag_js, $this->product_helper, $this->wc, $this->wp );
		$this->tag->set_options_object( $this->options );
	}

	public function test_is_needed_returns_true_by_default() {
		$this->assertTrue( GlobalSiteTag::is_needed() );
	}

	public function test_is_needed_returns_false_when_disabled_by_filter() {
		add_filter( 'woocommerce_gla_disable_gtag_tracking', '__return_true' );

		$this->assertFalse( GlobalSiteTag::is_needed() );
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
		$this->wp->expects( $this->once() )
			->method( 'wp_script_is' )
			->with( 'woocommerce-google-analytics-integration', 'enqueued' )
			->willReturn( true );
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
		$this->wp->expects( $this->once() )
			->method( 'wp_script_is' )
			->with( 'woocommerce-google-analytics-integration', 'enqueued' )
			->willReturn( true );
		$this->wp->expects( $this->once() )
			->method( 'wp_add_inline_script' )
			->willReturn( false );

		ob_start();
		$this->tag->activate_global_site_tag( self::TEST_CONVERSION_ID );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Global site tag (gtag.js)', $output );
		$this->assertStringContainsString( 'gtag("config", "test_id"', $output );
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

	public function test_purchase_event() {
		add_filter( 'woocommerce_is_order_received_page', '__return_true' );

		$order = WC_Helper_Order::create_order();

		$invoked_count = $this->exactly( 1 );
		$this->wp->expects( $invoked_count )
			->method( 'wp_print_inline_script_tag' )
			->willReturnCallback(
				function ( string $script ) use ( $invoked_count ) {
					if ( 1 === $invoked_count->getInvocationCount() ) {
						$this->assertStringStartsWith( 'gtag("event", "purchase"', $script );
					}
				}
			);

		$this->tag->maybe_display_purchase_event_snippet( self::TEST_CONVERSION_ID, self::TEST_CONVERSION_LABEL, $order->get_id() );

		// Reload order and confirm tracked meta is set.
		$order = wc_get_order( $order->get_id() );
		$this->assertSame( 1, (int) $order->get_meta( '_gla_tracked', true ) );
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
		$this->wp->expects( $this->once() )
			->method( 'wp_script_is' )
			->with( 'woocommerce-google-analytics-integration', 'enqueued' )
			->willReturn( true );
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
		$this->wp->expects( $this->once() )
			->method( 'wp_script_is' )
			->with( 'woocommerce-google-analytics-integration', 'enqueued' )
			->willReturn( true );
		$this->wp->expects( $this->once() )
			->method( 'wp_add_inline_script' )
			->with( 'woocommerce-google-analytics-integration', $inline_script )
			->willReturn( true );
		$this->wp->expects( $this->never() )->method( 'wp_print_inline_script_tag' );

		$this->tag->add_inline_event_script( $inline_script );
	}

	public function test_purchase_event_is_not_marked_as_tracked_when_output_fails() {
		add_filter( 'woocommerce_is_order_received_page', '__return_true' );

		$order = WC_Helper_Order::create_order();

		$this->gtag_js->method( 'is_adding_framework' )->willReturn( false );
		$this->wp->expects( $this->once() )
			->method( 'wp_print_inline_script_tag' )
			->willThrowException( new \RuntimeException( 'Unable to print the purchase event.' ) );

		try {
			$this->tag->maybe_display_purchase_event_snippet( self::TEST_CONVERSION_ID, self::TEST_CONVERSION_LABEL, $order->get_id() );
			$this->fail( 'Expected event output to fail.' );
		} catch ( \RuntimeException $exception ) {
			$this->assertSame( 'Unable to print the purchase event.', $exception->getMessage() );
		}

		$order = wc_get_order( $order->get_id() );
		$this->assertEmpty( $order->get_meta( '_gla_tracked', true ) );
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
}
