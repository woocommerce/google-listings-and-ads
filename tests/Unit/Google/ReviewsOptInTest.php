<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\Google\EstimatedDeliveryTimeResolver;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\ReviewsOptIn;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;
use WC_Helper_Order;

defined( 'ABSPATH' ) || exit;

/**
 * Class ReviewsOptInTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Google
 */
class ReviewsOptInTest extends UnitTest {

	/** @var MockObject|OptionsInterface $options */
	protected $options;

	/** @var MockObject|EstimatedDeliveryTimeResolver $delivery_time_resolver */
	protected $delivery_time_resolver;

	/** @var MockObject|WP $wp */
	protected $wp;

	/** @var ReviewsOptIn $opt_in */
	protected $opt_in;

	protected const TEST_MERCHANT_ID = 12345;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->options                = $this->createMock( OptionsInterface::class );
		$this->delivery_time_resolver = $this->createMock( EstimatedDeliveryTimeResolver::class );
		$this->wp                     = $this->createMock( WP::class );

		$this->opt_in = new ReviewsOptIn( $this->delivery_time_resolver, $this->wp );
		$this->opt_in->set_options_object( $this->options );

		unset( $_GET['key'] );
	}

	/**
	 * Runs after each test is executed.
	 */
	public function tearDown(): void {
		unset( $_GET['key'] );
		parent::tearDown();
	}

	/**
	 * Create a fully-qualified order ready to be prompted, and wire up the mocks so the happy
	 * path succeeds, unless a test overrides one of them afterwards.
	 *
	 * @return \WC_Order
	 */
	protected function create_eligible_order() {
		add_filter( 'woocommerce_is_order_received_page', '__return_true' );

		$order = WC_Helper_Order::create_order();
		$order->set_shipping_country( 'US' );
		$order->set_billing_email( 'shopper@example.com' );
		$order->save();

		$_GET['key'] = $order->get_order_key();

		$this->wp->method( 'get_query_vars' )->with( 'order-received' )->willReturn( $order->get_id() );

		$this->options->method( 'get' )
			->with( OptionsInterface::MERCHANT_CENTER, [] )
			->willReturn( [ 'collect_reviews_after_purchase' => true ] );

		$this->options->method( 'get_merchant_id' )->willReturn( self::TEST_MERCHANT_ID );
		$this->wp->method( 'has_consent' )->willReturn( true );

		$this->delivery_time_resolver->method( 'get_max_transit_days_for_country' )
			->with( 'US' )
			->willReturn( 5 );

		return $order;
	}

	public function test_injects_snippet_when_enabled_and_delivery_time_available() {
		$order = $this->create_eligible_order();

		$this->expectOutputRegex( '/surveyoptin\.render/' );

		$this->opt_in->maybe_display_opt_in_snippet();

		$order = wc_get_order( $order->get_id() );
		$this->assertSame( 1, (int) $order->get_meta( '_gla_gcr_opt_in_prompted', true ) );
	}

	public function test_snippet_contains_expected_parameters_and_omits_opt_in_style() {
		$order = $this->create_eligible_order();

		ob_start();
		$this->opt_in->maybe_display_opt_in_snippet();
		$output = ob_get_clean();

		$this->assertStringContainsString( '"merchant_id":' . self::TEST_MERCHANT_ID, $output );
		$this->assertStringContainsString( '"order_id":"' . $order->get_id() . '"', $output );
		$this->assertStringContainsString( '"email":"shopper@example.com"', $output );
		$this->assertStringContainsString( '"delivery_country":"US"', $output );
		$this->assertStringContainsString( '"estimated_delivery_date":"', $output );
		$this->assertStringNotContainsString( 'opt_in_style', $output );
		$this->assertStringNotContainsString( '<script src=', $output ); // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript -- assertion string, not emitted markup.
	}

	public function test_no_injection_when_setting_disabled() {
		$this->create_eligible_order();

		$this->options = $this->createMock( OptionsInterface::class );
		$this->opt_in->set_options_object( $this->options );
		$this->options->method( 'get' )
			->with( OptionsInterface::MERCHANT_CENTER, [] )
			->willReturn( [ 'collect_reviews_after_purchase' => false ] );

		$this->expectOutputString( '' );

		$this->opt_in->maybe_display_opt_in_snippet();
	}

	public function test_no_injection_when_consent_not_granted() {
		$this->create_eligible_order();
		$this->wp = $this->createMock( WP::class );
		$this->wp->method( 'has_consent' )->willReturn( false );
		$this->opt_in = new ReviewsOptIn( $this->delivery_time_resolver, $this->wp );
		$this->opt_in->set_options_object( $this->options );

		$this->expectOutputString( '' );

		$this->opt_in->maybe_display_opt_in_snippet();
	}

	public function test_no_injection_when_not_order_received_page() {
		$this->create_eligible_order();
		add_filter( 'woocommerce_is_order_received_page', '__return_false' );

		$this->expectOutputString( '' );

		$this->opt_in->maybe_display_opt_in_snippet();
	}

	public function test_no_injection_when_order_key_invalid() {
		$this->create_eligible_order();
		$_GET['key'] = 'not-the-right-key';

		$this->expectOutputString( '' );

		$this->opt_in->maybe_display_opt_in_snippet();
	}

	public function test_no_injection_when_already_prompted() {
		$order = $this->create_eligible_order();
		$order->update_meta_data( '_gla_gcr_opt_in_prompted', 1 );
		$order->save_meta_data();

		$this->expectOutputString( '' );

		$this->opt_in->maybe_display_opt_in_snippet();
	}

	public function test_no_injection_when_merchant_center_not_connected() {
		$this->create_eligible_order();

		$this->options = $this->createMock( OptionsInterface::class );
		$this->opt_in->set_options_object( $this->options );
		$this->options->method( 'get' )
			->with( OptionsInterface::MERCHANT_CENTER, [] )
			->willReturn( [ 'collect_reviews_after_purchase' => true ] );
		$this->options->method( 'get_merchant_id' )->willReturn( 0 );

		$this->expectOutputString( '' );

		$this->opt_in->maybe_display_opt_in_snippet();
	}

	public function test_no_injection_when_destination_country_has_no_shipping_time_entry() {
		$this->create_eligible_order();

		$this->delivery_time_resolver = $this->createMock( EstimatedDeliveryTimeResolver::class );
		$this->opt_in                 = new ReviewsOptIn( $this->delivery_time_resolver, $this->wp );
		$this->opt_in->set_options_object( $this->options );
		$this->delivery_time_resolver->method( 'get_max_transit_days_for_country' )->willReturn( null );

		$this->expectOutputString( '' );

		$this->opt_in->maybe_display_opt_in_snippet();
	}
}
