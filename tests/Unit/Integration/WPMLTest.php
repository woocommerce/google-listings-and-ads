<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Integration;

use Automattic\WooCommerce\GoogleListingsAndAds\Integration\WPML;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\WCMLSettingsStub;
use PHPUnit\Framework\MockObject\MockObject;
use WC_DateTime;
use WC_Helper_Product;
use WC_Product;

defined( 'ABSPATH' ) || exit;

/**
 * Class WPMLTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Integration
 */
class WPMLTest extends UnitTest {

	public function tearDown(): void {
		remove_all_filters( 'wpml_active_languages' );
		remove_all_filters( 'wpml_default_language' );
		remove_all_filters( 'wpml_post_language_details' );
		remove_all_filters( 'wcml_raw_price_amount' );
		remove_all_filters( 'wpml_current_language' );
		remove_all_actions( 'wpml_switch_language' );

		parent::tearDown();
	}

	public function test_run_in_all_languages_invokes_callback_and_returns_value_when_not_active(): void {
		$integration = $this->create_integration( false );

		$called = false;
		$result = $integration->run_in_all_languages(
			function () use ( &$called ) {
				$called = true;
				return 'the-result';
			}
		);

		$this->assertTrue( $called );
		$this->assertSame( 'the-result', $result );
	}

	public function test_run_in_all_languages_does_not_switch_language_when_not_active(): void {
		$integration = $this->create_integration( false );

		$switched = [];
		add_action(
			'wpml_switch_language',
			function ( $code ) use ( &$switched ) {
				$switched[] = $code;
			}
		);

		$integration->run_in_all_languages(
			function () {
				return null;
			}
		);

		$this->assertSame( [], $switched );
	}

	public function test_run_in_all_languages_switches_to_all_and_restores_when_active(): void {
		$integration = $this->create_integration( true );

		// Pretend the site is currently in French.
		add_filter(
			'wpml_current_language',
			function () {
				return 'fr';
			}
		);

		$switched          = [];
		$language_in_scope = null;
		add_action(
			'wpml_switch_language',
			function ( $code ) use ( &$switched ) {
				$switched[] = $code;
			}
		);

		$result = $integration->run_in_all_languages(
			function () use ( &$switched, &$language_in_scope ) {
				// The switch to 'all' must have happened before the callback runs.
				$language_in_scope = end( $switched );
				return 'done';
			}
		);

		$this->assertSame( 'done', $result );
		// Switched to 'all' before the callback, then restored to the captured language.
		$this->assertSame( [ 'all', 'fr' ], $switched );
		$this->assertSame( 'all', $language_in_scope );
	}

	public function test_run_in_all_languages_restores_language_even_when_callback_throws(): void {
		$integration = $this->create_integration( true );

		add_filter(
			'wpml_current_language',
			function () {
				return 'en';
			}
		);

		$switched = [];
		add_action(
			'wpml_switch_language',
			function ( $code ) use ( &$switched ) {
				$switched[] = $code;
			}
		);

		try {
			$integration->run_in_all_languages(
				function () {
					throw new \RuntimeException( 'boom' );
				}
			);
			$this->fail( 'Expected exception was not thrown.' );
		} catch ( \RuntimeException $e ) {
			$this->assertSame( 'boom', $e->getMessage() );
		}

		$this->assertSame( [ 'all', 'en' ], $switched );
	}

	public function test_get_languages_returns_empty_when_not_active(): void {
		$integration = $this->create_integration( false );

		$this->assertSame( [], $integration->get_languages() );
	}

	public function test_get_default_currencies_empty_when_not_active(): void {
		$this->assertSame( [], $this->create_integration( false )->get_default_currencies() );
	}

	public function test_get_default_currencies_empty_when_multi_currency_off(): void {
		$this->assertSame( [], $this->create_integration( true, [], false )->get_default_currencies() );
	}

	public function test_get_default_currencies_empty_when_wcml_global_missing(): void {
		global $woocommerce_wpml;
		$previous         = $woocommerce_wpml;
		$woocommerce_wpml = null;

		$this->assertSame( [], $this->create_integration( true, [], true )->get_default_currencies() );

		$woocommerce_wpml = $previous;
	}

	public function test_get_default_currencies_empty_when_wcml_setting_malformed(): void {
		global $woocommerce_wpml;
		$previous         = $woocommerce_wpml;
		$woocommerce_wpml = new WCMLSettingsStub( 'not-an-array' );

		$this->assertSame( [], $this->create_integration( true, [], true )->get_default_currencies() );

		$woocommerce_wpml = $previous;
	}

	public function test_get_default_currencies_reads_wcml_settings_and_skips_store_currency_pairings(): void {
		global $woocommerce_wpml;
		$previous         = $woocommerce_wpml;
		$woocommerce_wpml = new WCMLSettingsStub(
			[
				'de' => 'EUR',
				// WCML stores 0 for a language that keeps the store currency.
				'en' => 0,
				'fr' => 'EUR',
			]
		);

		$integration = $this->create_integration( true, [], true );

		$this->assertSame(
			[
				'de' => 'EUR',
				'fr' => 'EUR',
			],
			$integration->get_default_currencies()
		);
		$this->assertSame( 'EUR', $integration->get_default_currency_for_language( 'de' ) );
		$this->assertSame( '', $integration->get_default_currency_for_language( 'en' ) );
		$this->assertSame( '', $integration->get_default_currency_for_language( 'nl' ) );

		$woocommerce_wpml = $previous;
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

	public function test_get_post_language_returns_empty_when_not_active(): void {
		$integration = $this->create_integration( false );

		$this->assertSame( '', $integration->get_post_language( 42 ) );
	}

	public function test_get_post_language_returns_code_from_filter(): void {
		$integration = $this->create_integration( true );

		add_filter(
			'wpml_post_language_details',
			function () {
				return [ 'language_code' => 'fr' ];
			}
		);

		$this->assertSame( 'fr', $integration->get_post_language( 42 ) );
	}

	public function test_get_post_language_returns_empty_when_filter_returns_non_array(): void {
		$integration = $this->create_integration( true );

		add_filter(
			'wpml_post_language_details',
			function () {
				return null;
			}
		);

		$this->assertSame( '', $integration->get_post_language( 42 ) );
	}

	public function test_get_post_language_returns_empty_when_filter_missing_language_code(): void {
		$integration = $this->create_integration( true );

		add_filter(
			'wpml_post_language_details',
			function () {
				return [ 'other' => 'value' ];
			}
		);

		$this->assertSame( '', $integration->get_post_language( 42 ) );
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

	public function test_get_product_price_in_currency_returns_null_when_not_active(): void {
		$integration = $this->create_integration( false );

		$product = $this->createMock( WC_Product::class );
		$product->expects( $this->never() )->method( 'get_regular_price' );

		$this->assertNull( $integration->get_product_price_in_currency( $product, 'EUR' ) );
	}

	public function test_get_product_price_in_currency_returns_null_when_wcml_multi_currency_off(): void {
		$integration = $this->create_integration( true, [], false );

		$product = $this->createMock( WC_Product::class );
		$product->expects( $this->never() )->method( 'get_regular_price' );

		$this->assertNull( $integration->get_product_price_in_currency( $product, 'EUR' ) );
	}

	public function test_get_product_price_in_currency_returns_null_when_regular_price_empty(): void {
		$integration = $this->create_integration( true, [], true );

		$product = $this->createMock( WC_Product::class );
		$product->method( 'get_regular_price' )->willReturn( '' );

		$this->assertNull( $integration->get_product_price_in_currency( $product, 'EUR' ) );
	}

	public function test_get_product_price_in_currency_returns_zero_for_free_product(): void {
		$integration = $this->create_integration( true, [], true );

		$product = $this->createMock( WC_Product::class );
		$product->method( 'get_regular_price' )->willReturn( '0' );

		add_filter(
			'wcml_raw_price_amount',
			function ( $price ) {
				return (float) $price;
			}
		);

		$this->assertSame( 0.0, $integration->get_product_price_in_currency( $product, 'EUR' ) );
	}

	public function test_get_product_price_in_currency_returns_converted_price(): void {
		$integration = $this->create_integration( true, [], true );

		$product = $this->createMock( WC_Product::class );
		$product->method( 'get_regular_price' )->willReturn( '10' );

		add_filter(
			'wcml_raw_price_amount',
			function ( $price ) {
				return (float) $price * 0.8;
			}
		);

		$this->assertSame( 8.0, $integration->get_product_price_in_currency( $product, 'EUR' ) );
	}

	public function test_get_product_sale_price_in_currency_returns_null_when_not_active(): void {
		$integration = $this->create_integration( false );

		$product = $this->createMock( WC_Product::class );
		$product->expects( $this->never() )->method( 'get_sale_price' );

		$this->assertNull( $integration->get_product_sale_price_in_currency( $product, 'EUR' ) );
	}

	public function test_get_product_sale_price_in_currency_returns_null_when_wcml_multi_currency_off(): void {
		$integration = $this->create_integration( true, [], false );

		$product = $this->createMock( WC_Product::class );
		$product->expects( $this->never() )->method( 'get_sale_price' );

		$this->assertNull( $integration->get_product_sale_price_in_currency( $product, 'EUR' ) );
	}

	public function test_get_product_sale_price_in_currency_returns_null_when_sale_price_empty(): void {
		$integration = $this->create_integration( true, [], true );

		$product = $this->createMock( WC_Product::class );
		$product->method( 'get_sale_price' )->willReturn( '' );

		$this->assertNull( $integration->get_product_sale_price_in_currency( $product, 'EUR' ) );
	}

	public function test_get_product_sale_price_in_currency_returns_null_when_sale_empty_but_regular_set(): void {
		$integration = $this->create_integration( true, [], true );

		$product = $this->createMock( WC_Product::class );
		$product->method( 'get_regular_price' )->willReturn( '10' );
		$product->method( 'get_sale_price' )->willReturn( '' );

		$this->assertNull( $integration->get_product_sale_price_in_currency( $product, 'EUR' ) );
	}

	public function test_get_product_sale_price_in_currency_returns_zero_for_free_sale(): void {
		$integration = $this->create_integration( true, [], true );

		$product = $this->createMock( WC_Product::class );
		$product->method( 'get_sale_price' )->willReturn( '0' );

		add_filter(
			'wcml_raw_price_amount',
			function ( $price ) {
				return (float) $price;
			}
		);

		$this->assertSame( 0.0, $integration->get_product_sale_price_in_currency( $product, 'EUR' ) );
	}

	public function test_get_product_sale_price_in_currency_returns_converted_sale(): void {
		$integration = $this->create_integration( true, [], true );

		$product = $this->createMock( WC_Product::class );
		$product->method( 'get_sale_price' )->willReturn( '8' );

		add_filter(
			'wcml_raw_price_amount',
			function ( $price ) {
				return (float) $price * 0.8;
			}
		);

		$this->assertSame( 6.4, $integration->get_product_sale_price_in_currency( $product, 'EUR' ) );
	}

	public function test_get_product_price_in_currency_returns_null_when_manual_regular_empty(): void {
		$integration = $this->create_integration(
			true,
			[],
			true,
			[
				'_regular_price' => '',
				'_price'         => '',
			]
		);

		$product = $this->createMock( WC_Product::class );

		$this->assertNull( $integration->get_product_price_in_currency( $product, 'EUR' ) );
	}

	public function test_get_product_price_in_currency_returns_null_when_manual_regular_missing(): void {
		$integration = $this->create_integration(
			true,
			[],
			true,
			[
				'_price' => '90',
			]
		);

		$product = $this->createMock( WC_Product::class );

		$this->assertNull( $integration->get_product_price_in_currency( $product, 'EUR' ) );
	}

	public function test_get_product_price_in_currency_returns_manual_regular_price(): void {
		$integration = $this->create_integration(
			true,
			[],
			true,
			[
				'_regular_price' => '90',
				'_sale_price'    => '70',
				'_price'         => '70',
			]
		);

		$product = $this->createMock( WC_Product::class );
		// Base regular price should NOT be consulted when manual prices are set.
		$product->expects( $this->never() )->method( 'get_regular_price' );

		add_filter(
			'wcml_raw_price_amount',
			function ( $price ) {
				// Filter should NOT run when manual prices are present.
				return (float) $price * 0.5;
			}
		);

		$this->assertSame( 90.0, $integration->get_product_price_in_currency( $product, 'EUR' ) );
	}

	public function test_get_product_sale_price_in_currency_returns_manual_sale_price(): void {
		$integration = $this->create_integration(
			true,
			[],
			true,
			[
				'_regular_price' => '90',
				'_sale_price'    => '70',
				'_price'         => '70',
			]
		);

		$product = $this->createMock( WC_Product::class );
		$product->expects( $this->never() )->method( 'get_sale_price' );

		add_filter(
			'wcml_raw_price_amount',
			function ( $price ) {
				return (float) $price * 0.5;
			}
		);

		$this->assertSame( 70.0, $integration->get_product_sale_price_in_currency( $product, 'EUR' ) );
	}

	public function test_get_product_sale_price_in_currency_returns_null_when_manual_sale_empty(): void {
		// Merchant set a manual EUR regular price but left the EUR sale price empty:
		// EUR market should carry no sale.
		$integration = $this->create_integration(
			true,
			[],
			true,
			[
				'_regular_price' => '90',
				'_sale_price'    => '',
				'_price'         => '90',
			]
		);

		$product = $this->createMock( WC_Product::class );

		$this->assertNull( $integration->get_product_sale_price_in_currency( $product, 'EUR' ) );
	}

	public function test_get_product_sale_price_in_currency_auto_mode_returns_null_when_base_sale_end_in_past(): void {
		// Auto mode (no manual prices). Base sale set with an end date already in the past.
		$integration = $this->create_integration( true, [], true );

		$product = $this->createMock( WC_Product::class );
		$product->method( 'get_sale_price' )->willReturn( '8' );
		$product->method( 'get_date_on_sale_to' )->willReturn( new WC_DateTime( '2020-01-01' ) );

		$this->assertNull( $integration->get_product_sale_price_in_currency( $product, 'EUR' ) );
	}

	public function test_get_product_sale_price_in_currency_auto_mode_returns_value_when_base_sale_end_in_future(): void {
		// Auto mode with a future end date: filter runs and converted value is returned.
		$integration = $this->create_integration( true, [], true );

		$product = $this->createMock( WC_Product::class );
		$product->method( 'get_sale_price' )->willReturn( '8' );
		$product->method( 'get_date_on_sale_to' )->willReturn( new WC_DateTime( '2099-01-01' ) );

		add_filter(
			'wcml_raw_price_amount',
			function ( $price ) {
				return (float) $price * 0.8;
			}
		);

		$this->assertSame( 6.4, $integration->get_product_sale_price_in_currency( $product, 'EUR' ) );
	}

	public function test_get_product_sale_dates_in_currency_returns_null_when_not_active(): void {
		$integration = $this->create_integration( false );

		$product = WC_Helper_Product::create_simple_product();

		$this->assertNull( $integration->get_product_sale_dates_in_currency( $product, 'EUR' ) );
	}

	public function test_get_product_sale_dates_in_currency_returns_null_when_wcml_off(): void {
		$integration = $this->create_integration( true, [], false );

		$product = WC_Helper_Product::create_simple_product();

		$this->assertNull( $integration->get_product_sale_dates_in_currency( $product, 'EUR' ) );
	}

	public function test_get_product_sale_dates_in_currency_returns_null_when_no_per_currency_dates(): void {
		$integration = $this->create_integration( true, [], true );

		$product = WC_Helper_Product::create_simple_product();

		$this->assertNull( $integration->get_product_sale_dates_in_currency( $product, 'EUR' ) );
	}

	public function test_get_product_sale_dates_in_currency_returns_formatted_range_when_both_dates_set(): void {
		$integration = $this->create_integration( true, [], true );

		$product = WC_Helper_Product::create_simple_product();
		// 2026-06-01T00:00:00Z and 2027-06-01T00:00:00Z as Unix timestamps.
		update_post_meta( $product->get_id(), '_sale_price_dates_from_EUR', 1748736000 );
		update_post_meta( $product->get_id(), '_sale_price_dates_to_EUR', 1780272000 );

		$expected = sprintf( '%s/%s', gmdate( 'Y-m-d\TH:i:sP', 1748736000 ), gmdate( 'Y-m-d\TH:i:sP', 1780272000 ) );

		$this->assertSame( $expected, $integration->get_product_sale_dates_in_currency( $product, 'EUR' ) );
	}

	public function test_get_product_sale_dates_in_currency_returns_formatted_range_when_only_from_set(): void {
		$integration = $this->create_integration( true, [], true );

		$product = WC_Helper_Product::create_simple_product();
		update_post_meta( $product->get_id(), '_sale_price_dates_from_EUR', 1748736000 );

		$expected = sprintf( '%s/', gmdate( 'Y-m-d\TH:i:sP', 1748736000 ) );

		$this->assertSame( $expected, $integration->get_product_sale_dates_in_currency( $product, 'EUR' ) );
	}

	public function test_get_product_sale_dates_in_currency_returns_formatted_range_when_only_to_set(): void {
		$integration = $this->create_integration( true, [], true );

		$product = WC_Helper_Product::create_simple_product();
		update_post_meta( $product->get_id(), '_sale_price_dates_to_EUR', 1780272000 );

		$expected = sprintf( '/%s', gmdate( 'Y-m-d\TH:i:sP', 1780272000 ) );

		$this->assertSame( $expected, $integration->get_product_sale_dates_in_currency( $product, 'EUR' ) );
	}

	public function test_get_product_sale_price_in_currency_returns_null_when_manual_sale_missing(): void {
		// Manual mode with no _sale_price key at all: also no sale.
		$integration = $this->create_integration(
			true,
			[],
			true,
			[
				'_regular_price' => '90',
				'_price'         => '90',
			]
		);

		$product = $this->createMock( WC_Product::class );

		$this->assertNull( $integration->get_product_sale_price_in_currency( $product, 'EUR' ) );
	}

	/**
	 * @param bool                        $is_active
	 * @param string[]                    $currency_codes
	 * @param bool|null                   $wcml_multi_currency_on When non-null, stubs is_wcml_multi_currency_on() to this value.
	 * @param array<string, string>|false $custom_prices          Return value for get_wcml_custom_prices() (default false = auto mode).
	 *
	 * @return WPML&MockObject
	 */
	private function create_integration( bool $is_active, array $currency_codes = [], ?bool $wcml_multi_currency_on = null, $custom_prices = false ): WPML {
		$methods = [ 'is_active', 'get_wcml_custom_prices' ];

		if ( ! empty( $currency_codes ) ) {
			$methods[] = 'get_active_currency_codes';
		}

		if ( null !== $wcml_multi_currency_on ) {
			$methods[] = 'is_wcml_multi_currency_on';
		}

		$integration = $this->createPartialMock( WPML::class, $methods );
		$integration->method( 'is_active' )->willReturn( $is_active );
		$integration->method( 'get_wcml_custom_prices' )->willReturn( $custom_prices );

		if ( ! empty( $currency_codes ) ) {
			$integration->method( 'get_active_currency_codes' )->willReturn( $currency_codes );
		}

		if ( null !== $wcml_multi_currency_on ) {
			$integration->method( 'is_wcml_multi_currency_on' )->willReturn( $wcml_multi_currency_on );
		}

		return $integration;
	}
}
