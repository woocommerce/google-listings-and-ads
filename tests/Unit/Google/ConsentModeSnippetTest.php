<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\Google\ConsentModeSnippet;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;

defined( 'ABSPATH' ) || exit;

/**
 * Class ConsentModeSnippetTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Google
 */
class ConsentModeSnippetTest extends UnitTest {

	public function test_it_denies_consent_by_default() {
		$this->assertStringContainsString( "gtag( 'consent', 'default'", ConsentModeSnippet::get_config() );
		$this->assertStringContainsString( "ad_storage: 'denied'", ConsentModeSnippet::get_config() );
	}

	public function test_it_uses_the_snippet_a_filter_returns() {
		add_filter( 'woocommerce_gla_gtag_consent', '__return_empty_string' );
		$config = ConsentModeSnippet::get_config();
		remove_filter( 'woocommerce_gla_gtag_consent', '__return_empty_string' );

		$this->assertSame( '', $config );
	}

	/**
	 * @dataProvider invalid_filter_values_provider
	 *
	 * @param mixed $value What a callback on the filter returns.
	 */
	public function test_it_falls_back_to_the_default_when_a_filter_returns_something_that_is_not_a_string( $value ) {
		$callback = static function () use ( $value ) {
			return $value;
		};
		add_filter( 'woocommerce_gla_gtag_consent', $callback );
		$config = ConsentModeSnippet::get_config();
		remove_filter( 'woocommerce_gla_gtag_consent', $callback );

		$this->assertStringContainsString( "gtag( 'consent', 'default'", $config );
	}

	/**
	 * @return array
	 */
	public function invalid_filter_values_provider(): array {
		return [
			'null'  => [ null ],
			'false' => [ false ],
			'array' => [ [ 'denied' ] ],
		];
	}
}
