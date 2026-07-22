<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Integration;

use Automattic\WooCommerce\GoogleListingsAndAds\Integration\WPML;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionMethod;
use stdClass;
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
		remove_all_filters( 'woocommerce_gla_language_currencies' );
		remove_all_filters( 'wpml_current_language' );
		remove_all_actions( 'wpml_switch_language' );

		unset( $GLOBALS['woocommerce_wpml'] );

		parent::tearDown();
	}

	public function test_can_convert_currency_requires_wpml_and_multi_currency(): void {
		$this->assertFalse( $this->create_integration( false )->can_convert_currency() );
		$this->assertFalse( $this->create_integration( true, [], false )->can_convert_currency() );
		$this->assertTrue( $this->create_integration( true, [], true )->can_convert_currency() );
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
					'code'      => 'USD',
					'symbol'    => '$',
					'languages' => [],
				],
				[
					'code'      => 'EUR',
					'symbol'    => '€',
					'languages' => [],
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
					'code'      => $currency,
					'symbol'    => $symbol,
					'languages' => [],
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
					'code'      => 'USD',
					'symbol'    => '$',
					'languages' => [],
				],
			],
			$integration->get_currencies()
		);
	}

	public function test_get_currencies_languages_reflect_wcml_per_language_config(): void {
		$integration = $this->create_integration(
			true,
			[ 'USD', 'AED' ],
			true,
			false,
			[
				'currency_options' => [
					'USD' => [
						'languages' => [
							'en' => 1,
							'fr' => 1,
						],
					],
					'AED' => [
						'languages' => [
							'en' => 0,
							'fr' => 1,
						],
					],
				],
			]
		);

		$this->add_active_languages_filter();

		$this->assertEquals(
			[
				[
					'code'      => 'USD',
					'symbol'    => '$',
					'languages' => [ 'en', 'fr' ],
				],
				[
					'code'      => 'AED',
					'symbol'    => html_entity_decode( get_woocommerce_currency_symbol( 'AED' ), ENT_QUOTES, 'UTF-8' ),
					'languages' => [ 'fr' ],
				],
			],
			$integration->get_currencies()
		);
	}

	public function test_get_currencies_languages_default_to_enabled_when_flags_missing(): void {
		// EUR has no stored entry at all; USD only has a flag for a language that is
		// no longer active. Both cases must resolve to all active languages, matching
		// WCML's own default of enabling currencies for newly added languages.
		$integration = $this->create_integration(
			true,
			[ 'USD', 'EUR' ],
			true,
			false,
			[
				'currency_options' => [
					'USD' => [
						'languages' => [ 'de' => 0 ],
					],
				],
			]
		);

		$this->add_active_languages_filter();

		$this->assertEquals(
			[
				[
					'code'      => 'USD',
					'symbol'    => '$',
					'languages' => [ 'en', 'fr' ],
				],
				[
					'code'      => 'EUR',
					'symbol'    => '€',
					'languages' => [ 'en', 'fr' ],
				],
			],
			$integration->get_currencies()
		);
	}

	public function test_get_currencies_languages_include_all_active_languages_when_multi_currency_off(): void {
		$integration = $this->create_integration( true, [ 'USD' ], false );

		$this->add_active_languages_filter();

		$this->assertEquals(
			[
				[
					'code'      => 'USD',
					'symbol'    => '$',
					'languages' => [ 'en', 'fr' ],
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

	public function test_convert_amount_returns_null_when_not_active(): void {
		$integration = $this->create_integration( false );

		$this->assertNull( $integration->convert_amount( 10.0, 'EUR' ) );
	}

	public function test_convert_amount_returns_null_when_wcml_multi_currency_off(): void {
		$integration = $this->create_integration( true, [], false );

		$this->assertNull( $integration->convert_amount( 10.0, 'EUR' ) );
	}

	public function test_convert_amount_returns_converted_amount(): void {
		$integration = $this->create_integration( true, [], true );

		add_filter(
			'wcml_raw_price_amount',
			function ( $price ) {
				return (float) $price * 0.8;
			}
		);

		$this->assertSame( 8.0, $integration->convert_amount( 10.0, 'EUR' ) );
	}

	public function test_convert_amount_returns_null_for_inactive_currency(): void {
		$integration = $this->create_integration( true, [ get_woocommerce_currency(), 'EUR' ], true );

		// WCML's conversion filter returns 0 for a currency it does not have
		// active, so an inactive currency must read as unconvertible, never
		// as a zero amount.
		$this->assertNull( $integration->convert_amount( 10.0, 'AED' ) );
	}

	public function test_get_product_price_in_currency_returns_null_for_inactive_currency(): void {
		$integration = $this->create_integration( true, [ get_woocommerce_currency(), 'EUR' ], true );

		$product = $this->createMock( WC_Product::class );
		$product->method( 'get_regular_price' )->willReturn( '10' );

		$this->assertNull( $integration->get_product_price_in_currency( $product, 'AED' ) );
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
	 * Registers an active-languages filter returning English and French.
	 */
	private function add_active_languages_filter(): void {
		add_filter(
			'wpml_active_languages',
			function () {
				return [
					'en' => [
						'code'            => 'en',
						'translated_name' => 'English',
					],
					'fr' => [
						'code'            => 'fr',
						'translated_name' => 'French',
					],
				];
			}
		);
	}

	/**
	 * @param bool                        $is_active
	 * @param string[]                    $currency_codes
	 * @param bool|null                   $wcml_multi_currency_on When non-null, stubs is_wcml_multi_currency_on() to this value.
	 * @param array<string, string>|false $custom_prices          Return value for get_wcml_custom_prices() (default false = auto mode).
	 * @param array|null                  $wcml_settings          When non-null, stubs get_wcml_settings() to this WCML settings map.
	 *
	 * @return WPML&MockObject
	 */
	private function create_integration( bool $is_active, array $currency_codes = [], ?bool $wcml_multi_currency_on = null, $custom_prices = false, ?array $wcml_settings = null ): WPML {
		$methods = [ 'is_active', 'get_wcml_custom_prices' ];

		// Conversion only happens into WCML-active currencies, so
		// multi-currency tests get a permissive default set covering the
		// store currency and EUR unless the test supplies its own codes.
		if ( empty( $currency_codes ) && true === $wcml_multi_currency_on ) {
			$currency_codes = [ get_woocommerce_currency(), 'EUR' ];
		}

		if ( ! empty( $currency_codes ) ) {
			$methods[] = 'get_active_currency_codes';
		}

		if ( null !== $wcml_multi_currency_on ) {
			$methods[] = 'is_wcml_multi_currency_on';
		}

		if ( null !== $wcml_settings ) {
			$methods[] = 'get_wcml_settings';
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

		if ( null !== $wcml_settings ) {
			$integration->method( 'get_wcml_settings' )->willReturn( $wcml_settings );
		}

		return $integration;
	}

	public function test_filter_currencies_for_language_keeps_all_when_no_options(): void {
		$method = new ReflectionMethod( WPML::class, 'filter_currencies_for_language' );
		$method->setAccessible( true );

		$this->assertSame( [ 'USD', 'EUR' ], $method->invoke( new WPML(), [ 'USD', 'EUR' ], [], 'fr' ) );
	}

	public function test_filter_currencies_for_language_drops_currency_disabled_for_language(): void {
		$method = new ReflectionMethod( WPML::class, 'filter_currencies_for_language' );
		$method->setAccessible( true );

		$options = [
			'USD' => [
				'languages' => [
					'en' => 1,
					'fr' => 1,
				],
			],
			'GBP' => [
				'languages' => [
					'en' => 1,
					'fr' => 0,
				],
			],
		];

		$this->assertSame( [ 'USD' ], array_values( $method->invoke( new WPML(), [ 'USD', 'GBP' ], $options, 'fr' ) ) );
		$this->assertSame( [ 'USD', 'GBP' ], array_values( $method->invoke( new WPML(), [ 'USD', 'GBP' ], $options, 'en' ) ) );
	}

	public function test_filter_currencies_for_language_keeps_currency_without_language_map(): void {
		$method = new ReflectionMethod( WPML::class, 'filter_currencies_for_language' );
		$method->setAccessible( true );

		// A currency whose options omit a languages map (or omit the language) is enabled everywhere.
		$this->assertSame( [ 'USD' ], array_values( $method->invoke( new WPML(), [ 'USD' ], [ 'USD' => [ 'rate' => 1 ] ], 'fr' ) ) );
	}

	public function test_get_currencies_enabled_for_language_can_be_overridden_by_filter(): void {
		add_filter(
			'woocommerce_gla_language_currencies',
			static function () {
				return [ 'EUR' ];
			}
		);

		$this->assertSame( [ 'EUR' ], ( new WPML() )->get_currencies_enabled_for_language( [ 'USD', 'EUR' ], 'fr' ) );

		remove_all_filters( 'woocommerce_gla_language_currencies' );
	}

	/**
	 * Drives the full public method through the real global read (get_wcml_currency_options()
	 * reading $woocommerce_wpml->get_setting) against WCML's verified shape (1 enabled, 0 disabled,
	 * absent = enabled).
	 */
	public function test_get_currencies_enabled_for_language_reads_wcml_global_via_get_setting(): void {
		$currency_options = [
			'USD' => [
				'languages' => [
					'en' => 1,
					'de' => 0,
				],
			],
			'EUR' => [
				'languages' => [
					'en' => 0,
					'de' => 1,
				],
			],
			'GBP' => [ 'languages' => [ 'en' => 1 ] ],
			'JPY' => [ 'rate' => 1 ],
		];

		$stub = $this->getMockBuilder( stdClass::class )->addMethods( [ 'get_setting' ] )->getMock();
		$stub->method( 'get_setting' )->willReturn( $currency_options );
		$GLOBALS['woocommerce_wpml'] = $stub;

		$integration = $this->create_integration( true, [], true );
		$codes       = [ 'USD', 'EUR', 'GBP', 'JPY' ];

		// de: USD disabled (0), EUR enabled (1), GBP language absent (enabled), JPY no map (enabled).
		$this->assertSame( [ 'EUR', 'GBP', 'JPY' ], $integration->get_currencies_enabled_for_language( $codes, 'de' ) );
		// en: USD enabled (1), EUR disabled (0), GBP enabled (1), JPY no map (enabled).
		$this->assertSame( [ 'USD', 'GBP', 'JPY' ], $integration->get_currencies_enabled_for_language( $codes, 'en' ) );
	}

	/**
	 * Older WCML exposes a public `settings` property instead of get_setting(); exercise that
	 * fallback read path.
	 */
	public function test_get_currencies_enabled_for_language_reads_wcml_global_via_settings_property(): void {
		$currency_options = [
			'USD' => [
				'languages' => [
					'en' => 1,
					'de' => 0,
				],
			],
			'EUR' => [
				'languages' => [
					'en' => 0,
					'de' => 1,
				],
			],
		];

		$stub                        = new stdClass();
		$stub->settings              = [ 'currency_options' => $currency_options ];
		$GLOBALS['woocommerce_wpml'] = $stub;

		$integration = $this->create_integration( true, [], true );

		$this->assertSame( [ 'EUR' ], $integration->get_currencies_enabled_for_language( [ 'USD', 'EUR' ], 'de' ) );
	}

	/**
	 * With WCML multi-currency off, a stale global is ignored and every currency is kept.
	 */
	public function test_get_currencies_enabled_for_language_keeps_all_when_multicurrency_off(): void {
		$stub = $this->getMockBuilder( stdClass::class )->addMethods( [ 'get_setting' ] )->getMock();
		$stub->method( 'get_setting' )->willReturn( [ 'USD' => [ 'languages' => [ 'de' => 0 ] ] ] );
		$GLOBALS['woocommerce_wpml'] = $stub;

		$integration = $this->create_integration( true, [], false );

		$this->assertSame( [ 'USD', 'EUR' ], $integration->get_currencies_enabled_for_language( [ 'USD', 'EUR' ], 'de' ) );
	}
}
