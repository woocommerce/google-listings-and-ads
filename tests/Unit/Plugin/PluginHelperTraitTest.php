<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers;

use PHPUnit\Framework\TestCase;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;


/**
 * Class PluginHelperTraitTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers
 */
class PluginHelperTraitTest extends TestCase {


	/** @var PluginHelper $trait */
	protected $trait;

	/** @var PluginHelper $wpml_trait */
	protected $wpml_trait;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		// phpcs:ignore Universal.Classes.RequireAnonClassParentheses.Missing
		$this->trait = new class {
			use PluginHelper {
				convert_to_standard_decimal as public;
				get_site_url as public;
			}
		};

		// phpcs:ignore Universal.Classes.RequireAnonClassParentheses.Missing
		$this->wpml_trait = new class {
			use PluginHelper {
				get_site_url as public;
			}

			protected function is_wpml_active(): bool {
				return true;
			}
		};
	}


	public function test_comma_decimals_gets_converted_to_dot_decimals() {
		$this->assertEquals( '10.5', $this->trait->convert_to_standard_decimal( '10,5' ) );
	}

	public function test_dot_decimals_remain_unchanged() {
		$this->assertEquals( '10.5', $this->trait->convert_to_standard_decimal( '10.5' ) );
	}

	public function test_invalid_numbers() {
		$this->assertEquals( 'no valid. number', $this->trait->convert_to_standard_decimal( 'no valid, number' ) );
	}

	public function test_with_thousands_separator() {
		$this->assertEquals( '1234.5', $this->trait->convert_to_standard_decimal( '1' . wc_get_price_thousand_separator() . '234,5' ) );
	}

	public function test_with_no_decimals() {
		$this->assertEquals( '12345', $this->trait->convert_to_standard_decimal( '12345' ) );
	}

	public function test_with_different_wc_decimal_separator() {
		add_filter(
			'wc_get_price_decimal_separator',
			[ $this, 'callback_filter_decimal_separator' ]
		);

		$this->assertEquals( '10.5', $this->trait->convert_to_standard_decimal( '10-5' ) );
	}

	public function callback_filter_decimal_separator() {
		return '-';
	}

	public function test_get_site_url_returns_home_url_without_wpml() {
		$this->assertSame( get_home_url(), $this->trait->get_site_url() );
	}

	public function test_get_site_url_returns_default_language_url_on_another_language_domain() {
		$this->mock_wpml_domain_per_language();

		$this->assertSame( 'https://example.nl', $this->wpml_trait->get_site_url() );
	}

	public function test_get_site_url_ignores_wpml_filters_when_wpml_is_not_active() {
		$this->mock_wpml_domain_per_language();

		$this->assertSame( 'https://example.de', $this->trait->get_site_url() );
	}

	public function test_get_site_url_keeps_home_url_when_wpml_returns_an_invalid_url() {
		add_filter( 'wpml_default_language', [ $this, 'callback_filter_default_language' ] );
		add_filter( 'wpml_permalink', '__return_null' );

		$this->assertSame( get_home_url(), $this->wpml_trait->get_site_url() );
	}

	public function test_get_site_url_filter_overrides_default_language_url() {
		$this->mock_wpml_domain_per_language();
		add_filter( 'woocommerce_gla_site_url', [ $this, 'callback_filter_site_url' ] );

		$this->assertSame( 'https://pinned.test', $this->wpml_trait->get_site_url() );
	}

	/**
	 * Mocks a request to the German domain of a store whose default language is Dutch.
	 */
	protected function mock_wpml_domain_per_language() {
		add_filter( 'home_url', [ $this, 'callback_filter_home_url' ] );
		add_filter( 'wpml_default_language', [ $this, 'callback_filter_default_language' ] );
		add_filter( 'wpml_permalink', [ $this, 'callback_filter_permalink' ], 10, 2 );
	}

	public function callback_filter_home_url() {
		return 'https://example.de';
	}

	public function callback_filter_default_language() {
		return 'nl';
	}

	public function callback_filter_permalink( $url, $language ) {
		return 'nl' === $language ? 'https://example.nl/' : $url;
	}

	public function callback_filter_site_url() {
		return 'https://pinned.test';
	}

	public function tearDown(): void {
		parent::tearDown();
		remove_filter( 'wc_get_price_decimal_separator', [ $this, 'callback_filter_decimal_separator' ] );
		remove_filter( 'home_url', [ $this, 'callback_filter_home_url' ] );
		remove_filter( 'wpml_default_language', [ $this, 'callback_filter_default_language' ] );
		remove_filter( 'wpml_permalink', [ $this, 'callback_filter_permalink' ] );
		remove_filter( 'wpml_permalink', '__return_null' );
		remove_filter( 'woocommerce_gla_site_url', [ $this, 'callback_filter_site_url' ] );
	}
}
