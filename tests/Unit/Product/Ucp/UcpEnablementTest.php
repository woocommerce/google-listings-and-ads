<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Product\Ucp;

use Automattic\WooCommerce\GoogleListingsAndAds\Product\Ucp\UcpEnablement;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;

/**
 * Test subclass that overrides woocommerce-ai version detection so the plugin-detection
 * fallback can be exercised without defining a process-global `WCAI_VERSION` constant.
 */
class StubUcpEnablement extends UcpEnablement {

	/** @var string|null */
	public static $stub_version = null;

	protected static function detected_wcai_version(): ?string {
		return static::$stub_version;
	}
}

/**
 * Class UcpEnablementTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Product\Ucp
 */
class UcpEnablementTest extends UnitTest {

	public function setUp(): void {
		parent::setUp();

		remove_all_filters( 'woocommerce_agentic_commerce_enabled' );
		StubUcpEnablement::$stub_version = null;
		delete_option( UcpEnablement::FEATURE_OPTION );
	}

	public function tearDown(): void {
		remove_all_filters( 'woocommerce_agentic_commerce_enabled' );
		StubUcpEnablement::$stub_version = null;
		delete_option( UcpEnablement::FEATURE_OPTION );

		parent::tearDown();
	}

	public function test_enabled_when_contract_filter_returns_true() {
		add_filter( 'woocommerce_agentic_commerce_enabled', '__return_true' );

		$this->assertTrue( UcpEnablement::is_enabled() );
	}

	public function test_disabled_when_contract_filter_returns_false() {
		add_filter( 'woocommerce_agentic_commerce_enabled', '__return_false' );

		$this->assertFalse( UcpEnablement::is_enabled() );
	}

	public function test_contract_filter_takes_precedence_over_plugin_detection() {
		// Filter says no even though the plugin is present, recent, and the option is on.
		StubUcpEnablement::$stub_version = UcpEnablement::MIN_WCAI_VERSION;
		update_option( UcpEnablement::FEATURE_OPTION, 'yes' );
		add_filter( 'woocommerce_agentic_commerce_enabled', '__return_false' );

		$this->assertFalse( StubUcpEnablement::is_enabled() );
	}

	public function test_disabled_when_plugin_absent() {
		StubUcpEnablement::$stub_version = null;
		update_option( UcpEnablement::FEATURE_OPTION, 'yes' );

		$this->assertFalse( StubUcpEnablement::is_enabled() );
	}

	public function test_disabled_when_plugin_too_old() {
		StubUcpEnablement::$stub_version = '0.0.1';
		update_option( UcpEnablement::FEATURE_OPTION, 'yes' );

		$this->assertFalse( StubUcpEnablement::is_enabled() );
	}

	public function test_disabled_when_plugin_present_but_option_off() {
		StubUcpEnablement::$stub_version = UcpEnablement::MIN_WCAI_VERSION;
		update_option( UcpEnablement::FEATURE_OPTION, 'no' );

		$this->assertFalse( StubUcpEnablement::is_enabled() );
	}

	public function test_enabled_when_plugin_present_and_option_on() {
		StubUcpEnablement::$stub_version = UcpEnablement::MIN_WCAI_VERSION;
		update_option( UcpEnablement::FEATURE_OPTION, 'yes' );

		$this->assertTrue( StubUcpEnablement::is_enabled() );
	}
}
