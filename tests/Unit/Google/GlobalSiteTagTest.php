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
use WC_Helper_Product;

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

		$this->options        = $this->createMock( OptionsInterface::class );
		$this->assets_handler = $this->createMock( AssetsHandlerInterface::class );
		$this->gtag_js        = $this->createMock( GoogleGtagJs::class );
		$this->product_helper = $this->createMock( ProductHelper::class );
		$this->wc             = $this->createMock( WC::class );
		$this->wp             = $this->createMock( WP::class );

		$this->tag = new GlobalSiteTag( $this->assets_handler, $this->gtag_js, $this->product_helper, $this->wc, $this->wp );
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

	public function test_is_needed_returns_false_when_disabled_by_filter() {
		add_filter( 'woocommerce_gla_disable_gtag_tracking', '__return_true' );

		$this->assertFalse( GlobalSiteTag::is_needed() );
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
						$this->assertStringContainsString( self::DEVELOPER_ID_PARAM, $script );
					}
				}
			);

		$this->tag->maybe_display_purchase_event_snippet( self::TEST_CONVERSION_ID, self::TEST_CONVERSION_LABEL, $order->get_id() );

		// Reload order and confirm tracked meta is set.
		$order = wc_get_order( $order->get_id() );
		$this->assertSame( 1, (int) $order->get_meta( '_gla_tracked', true ) );
	}

	public function test_view_item_event() {
		$product = WC_Helper_Product::create_simple_product();

		// Set the global post directly rather than with go_to(), which unsets globals like $pagenow for later tests.
		$GLOBALS['post'] = get_post( $product->get_id() ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		$this->capture_inline_scripts();
		$this->register_with_conversion_action();

		do_action( 'woocommerce_after_single_product' );

		$this->assertCount( 1, $this->inline_scripts );
		$this->assertStringStartsWith( 'gtag("event", "view_item"', $this->inline_scripts[0] );
		$this->assertStringContainsString( self::DEVELOPER_ID_PARAM, $this->inline_scripts[0] );
	}

	public function test_page_view_event() {
		$this->capture_inline_scripts();
		$this->register_with_conversion_action();

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

	/**
	 * Register the service with a conversion action, which adds its event hooks.
	 *
	 * Asset registration is skipped, as it reads the built js/build/ files which
	 * aren't guaranteed to exist in a unit test run.
	 */
	protected function register_with_conversion_action(): void {
		$this->tag = $this->getMockBuilder( GlobalSiteTag::class )
			->setConstructorArgs( [ $this->assets_handler, $this->gtag_js, $this->product_helper, $this->wc, $this->wp ] )
			->onlyMethods( [ 'register_assets' ] )
			->getMock();
		$this->tag->set_options_object( $this->options );

		$this->options->method( 'get' )->willReturn(
			[
				'conversion_id'    => self::TEST_CONVERSION_ID,
				'conversion_label' => self::TEST_CONVERSION_LABEL,
			]
		);

		$this->tag->register();
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
}
