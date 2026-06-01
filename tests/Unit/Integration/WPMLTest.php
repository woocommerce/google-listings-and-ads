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

	public function test_get_product_price_in_currency_returns_null_when_not_active(): void {
		$integration = $this->create_integration( false );
		$product     = $this->createMock( \WC_Product::class );

		$this->assertNull( $integration->get_product_price_in_currency( $product, 'EUR' ) );
	}

	public function test_get_product_price_in_currency_returns_null_when_wcml_multi_currency_off(): void {
		$integration = $this->create_integration( true );
		$product     = $this->createMock( \WC_Product::class );

		// wcml_is_multi_currency_on not defined (function doesn't exist in test env) → null.
		$this->assertNull( $integration->get_product_price_in_currency( $product, 'EUR' ) );
	}

	public function test_get_product_price_in_currency_returns_null_when_no_price(): void {
		$integration = $this->create_integration( true );
		$product     = $this->createMock( \WC_Product::class );
		$product->method( 'get_price' )->willReturn( '' );

		add_filter( 'wcml_is_multi_currency_on', '__return_true' );

		$result = $integration->get_product_price_in_currency( $product, 'EUR' );

		remove_all_filters( 'wcml_is_multi_currency_on' );

		$this->assertNull( $result );
	}

	public function test_get_product_price_in_currency_returns_converted_price(): void {
		$integration = $this->create_integration( true );
		$product     = $this->createMock( \WC_Product::class );
		$product->method( 'get_price' )->willReturn( '100' );

		add_filter( 'wcml_is_multi_currency_on', '__return_true' );
		add_filter(
			'wcml_raw_price_amount',
			function ( $price, $currency ) {
				return 'EUR' === $currency ? 90.0 : $price;
			},
			10,
			2
		);

		$result = $integration->get_product_price_in_currency( $product, 'EUR' );

		remove_all_filters( 'wcml_is_multi_currency_on' );
		remove_all_filters( 'wcml_raw_price_amount' );

		$this->assertEquals( 90.0, $result );
	}

	public function test_get_product_price_in_currency_returns_zero_for_free_product(): void {
		$integration = $this->create_integration( true );
		$product     = $this->createMock( \WC_Product::class );
		$product->method( 'get_price' )->willReturn( '0' );

		add_filter( 'wcml_is_multi_currency_on', '__return_true' );
		add_filter( 'wcml_raw_price_amount', '__return_zero' );

		$result = $integration->get_product_price_in_currency( $product, 'EUR' );

		remove_all_filters( 'wcml_is_multi_currency_on' );
		remove_all_filters( 'wcml_raw_price_amount' );

		$this->assertEquals( 0.0, $result );
	}

	public function test_get_product_sale_price_in_currency_returns_null_when_not_active(): void {
		$integration = $this->create_integration( false );
		$product     = $this->createMock( \WC_Product::class );

		$this->assertNull( $integration->get_product_sale_price_in_currency( $product, 'EUR' ) );
	}

	public function test_get_product_sale_price_in_currency_returns_null_when_wcml_multi_currency_off(): void {
		$integration = $this->create_integration( true );
		$product     = $this->createMock( \WC_Product::class );

		// wcml_is_multi_currency_on not defined (function doesn't exist in test env) → null.
		$this->assertNull( $integration->get_product_sale_price_in_currency( $product, 'EUR' ) );
	}

	public function test_get_product_sale_price_in_currency_returns_null_when_no_sale_price(): void {
		$integration = $this->create_integration( true );
		$product     = $this->createMock( \WC_Product::class );
		$product->method( 'get_sale_price' )->willReturn( '' );

		add_filter( 'wcml_is_multi_currency_on', '__return_true' );

		$result = $integration->get_product_sale_price_in_currency( $product, 'EUR' );

		remove_all_filters( 'wcml_is_multi_currency_on' );

		$this->assertNull( $result );
	}

	public function test_get_product_sale_price_in_currency_returns_converted_sale_price(): void {
		$integration = $this->create_integration( true );
		$product     = $this->createMock( \WC_Product::class );
		$product->method( 'get_sale_price' )->willReturn( '80' );

		add_filter( 'wcml_is_multi_currency_on', '__return_true' );
		add_filter(
			'wcml_raw_price_amount',
			function ( $price, $currency ) {
				return 'EUR' === $currency ? 72.0 : $price;
			},
			10,
			2
		);

		$result = $integration->get_product_sale_price_in_currency( $product, 'EUR' );

		remove_all_filters( 'wcml_is_multi_currency_on' );
		remove_all_filters( 'wcml_raw_price_amount' );

		$this->assertEquals( 72.0, $result );
	}

	public function test_get_product_sale_price_in_currency_returns_zero_for_free_sale_product(): void {
		$integration = $this->create_integration( true );
		$product     = $this->createMock( \WC_Product::class );
		$product->method( 'get_sale_price' )->willReturn( '0' );

		add_filter( 'wcml_is_multi_currency_on', '__return_true' );
		add_filter( 'wcml_raw_price_amount', '__return_zero' );

		$result = $integration->get_product_sale_price_in_currency( $product, 'EUR' );

		remove_all_filters( 'wcml_is_multi_currency_on' );
		remove_all_filters( 'wcml_raw_price_amount' );

		$this->assertEquals( 0.0, $result );
	}

	public function test_get_product_in_language_returns_null_when_not_active(): void {
		$integration = $this->create_integration( false );
		$product     = $this->createMock( \WC_Product::class );

		$this->assertNull( $integration->get_product_in_language( $product, 'fr' ) );
	}

	public function test_get_product_in_language_returns_null_when_no_translation_exists(): void {
		$integration = $this->create_integration( true );
		$product     = $this->createMock( \WC_Product::class );
		$product->method( 'get_id' )->willReturn( 10 );

		// Filter returns 0 when no translation exists.
		add_filter( 'wpml_object_id', '__return_zero' );

		$result = $integration->get_product_in_language( $product, 'fr' );

		remove_all_filters( 'wpml_object_id' );

		$this->assertNull( $result );
	}

	public function test_get_product_in_language_returns_null_when_same_id_returned(): void {
		$integration = $this->create_integration( true );
		$product     = $this->createMock( \WC_Product::class );
		$product->method( 'get_id' )->willReturn( 10 );

		// Filter returns the same ID — no distinct translation exists.
		add_filter(
			'wpml_object_id',
			function () {
				return 10;
			}
		);

		$result = $integration->get_product_in_language( $product, 'en' );

		remove_all_filters( 'wpml_object_id' );

		$this->assertNull( $result );
	}

	public function test_get_product_in_language_returns_translated_product(): void {
		$integration = $this->create_integration( true );

		$original   = \WC_Helper_Product::create_simple_product();
		$translated = \WC_Helper_Product::create_simple_product();

		add_filter(
			'wpml_object_id',
			function () use ( $translated ) {
				return $translated->get_id();
			}
		);

		$result = $integration->get_product_in_language( $original, 'fr' );

		remove_all_filters( 'wpml_object_id' );

		$this->assertInstanceOf( \WC_Product::class, $result );
		$this->assertSame( $translated->get_id(), $result->get_id() );
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

	public function test_get_product_price_in_currency_passes_product_id_to_filter(): void {
		$integration = $this->create_integration( true );
		$product     = $this->createMock( \WC_Product::class );
		$product->method( 'get_price' )->willReturn( '100' );
		$product->method( 'get_id' )->willReturn( 42 );

		add_filter( 'wcml_is_multi_currency_on', '__return_true' );

		$received_product_id = null;
		add_filter(
			'wcml_raw_price_amount',
			function ( $price, $currency, $product_id ) use ( &$received_product_id ) {
				$received_product_id = $product_id;
				return 90.0;
			},
			10,
			3
		);

		$integration->get_product_price_in_currency( $product, 'EUR' );

		remove_all_filters( 'wcml_is_multi_currency_on' );
		remove_all_filters( 'wcml_raw_price_amount' );

		$this->assertEquals( 42, $received_product_id );
	}

	public function test_get_product_sale_price_in_currency_passes_product_id_to_filter(): void {
		$integration = $this->create_integration( true );
		$product     = $this->createMock( \WC_Product::class );
		$product->method( 'get_sale_price' )->willReturn( '80' );
		$product->method( 'get_id' )->willReturn( 99 );

		add_filter( 'wcml_is_multi_currency_on', '__return_true' );

		$received_product_id = null;
		add_filter(
			'wcml_raw_price_amount',
			function ( $price, $currency, $product_id ) use ( &$received_product_id ) {
				$received_product_id = $product_id;
				return 72.0;
			},
			10,
			3
		);

		$integration->get_product_sale_price_in_currency( $product, 'EUR' );

		remove_all_filters( 'wcml_is_multi_currency_on' );
		remove_all_filters( 'wcml_raw_price_amount' );

		$this->assertEquals( 99, $received_product_id );
	}
}
