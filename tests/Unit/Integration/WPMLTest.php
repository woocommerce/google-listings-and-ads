<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Integration;

use Automattic\WooCommerce\GoogleListingsAndAds\Integration\WPML;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class WPMLTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Integration
 */
class WPMLTest extends UnitTest {

	public function tearDown(): void {
		remove_all_filters( 'wpml_active_languages' );

		parent::tearDown();
	}

	public function test_get_languages_returns_empty_when_not_active(): void {
		$integration = $this->create_integration( false );

		$this->assertSame( [], $integration->get_languages() );
	}

	public function test_get_default_language_code_returns_empty_when_not_active(): void {
		$integration = $this->create_integration( false );

		$this->assertSame( '', $integration->get_default_language_code() );
	}

	public function test_get_default_language_code_returns_wpml_default(): void {
		$integration = $this->create_integration( true );

		add_filter(
			'wpml_default_language',
			function () {
				return 'fr';
			}
		);

		$this->assertSame( 'fr', $integration->get_default_language_code() );
	}

	public function test_get_languages_returns_formatted_languages(): void {
		$integration = $this->create_integration( true );

		add_filter(
			'wpml_active_languages',
			function () {
				return [
					'en' => [
						'code'            => 'en',
						'translated_name' => 'English',
						'native_name'     => 'English',
						'display_name'    => 'English',
					],
					'de' => [
						'code'            => 'de',
						'translated_name' => 'German',
						'native_name'     => 'Deutsch',
						'display_name'    => 'German',
					],
				];
			}
		);

		$this->assertSame(
			[
				[
					'code'  => 'en',
					'label' => 'English',
				],
				[
					'code'  => 'de',
					'label' => 'German',
				],
			],
			$integration->get_languages()
		);
	}

	public function test_get_languages_uses_native_name_when_translated_name_missing(): void {
		$integration = $this->create_integration( true );

		add_filter(
			'wpml_active_languages',
			function () {
				return [
					'de' => [
						'code'         => 'de',
						'native_name'  => 'Deutsch',
						'display_name' => 'German',
					],
				];
			}
		);

		$this->assertSame(
			[
				[
					'code'  => 'de',
					'label' => 'Deutsch',
				],
			],
			$integration->get_languages()
		);
	}

	public function test_get_languages_uses_display_name_when_translated_and_native_missing(): void {
		$integration = $this->create_integration( true );

		add_filter(
			'wpml_active_languages',
			function () {
				return [
					'fr' => [
						'code'         => 'fr',
						'display_name' => 'French',
					],
				];
			}
		);

		$this->assertSame(
			[
				[
					'code'  => 'fr',
					'label' => 'French',
				],
			],
			$integration->get_languages()
		);
	}

	public function test_get_languages_returns_empty_when_filter_returns_non_array(): void {
		$integration = $this->create_integration( true );

		add_filter(
			'wpml_active_languages',
			function () {
				return null;
			}
		);

		$this->assertSame( [], $integration->get_languages() );
	}

	public function test_get_currencies_returns_empty_when_not_active(): void {
		$integration = $this->create_integration( false );

		$this->assertSame( [], $integration->get_currencies() );
	}

	public function test_get_currencies_returns_formatted_currencies(): void {
		$integration = $this->create_integration( true, [ 'USD', 'EUR' ] );

		$this->assertEquals(
			[
				[
					'code'   => 'USD',
					'symbol' => '$',
				],
				[
					'code'   => 'EUR',
					'symbol' => '€',
				],
			],
			$integration->get_currencies()
		);
	}

	public function test_get_currencies_returns_store_currency_when_no_wcml_codes(): void {
		$integration = $this->create_integration( true, [ get_woocommerce_currency() ] );

		$currency = get_woocommerce_currency();
		$symbol   = html_entity_decode( get_woocommerce_currency_symbol( $currency ), ENT_QUOTES, 'UTF-8' );

		$this->assertSame(
			[
				[
					'code'   => $currency,
					'symbol' => $symbol,
				],
			],
			$integration->get_currencies()
		);
	}

	public function test_get_currencies_skips_codes_without_symbol(): void {
		$integration = $this->create_integration( true, [ 'USD', 'INVALID' ] );

		$this->assertSame(
			[
				[
					'code'   => 'USD',
					'symbol' => '$',
				],
			],
			$integration->get_currencies()
		);
	}

	/**
	 * @param bool     $is_active
	 * @param string[] $currency_codes
	 *
	 * @return WPML&MockObject
	 */
	private function create_integration( bool $is_active, array $currency_codes = [] ): WPML {
		$methods = [ 'is_active' ];

		if ( ! empty( $currency_codes ) ) {
			$methods[] = 'get_active_currency_codes';
		}

		$integration = $this->createPartialMock( WPML::class, $methods );
		$integration->method( 'is_active' )->willReturn( $is_active );

		if ( ! empty( $currency_codes ) ) {
			$integration->method( 'get_active_currency_codes' )->willReturn( $currency_codes );
		}

		return $integration;
	}
}
