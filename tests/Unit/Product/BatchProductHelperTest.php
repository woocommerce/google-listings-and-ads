<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Product;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\MerchantApiException;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Models\ProductInput;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services\MapiDataSourcesService;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\AttributeMappingRulesQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\AccountReconnect;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidClass;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\BatchInvalidProductEntry;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\BatchProductEntry;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\BatchProductIDRequestEntry;
use Automattic\WooCommerce\GoogleListingsAndAds\Integration\WPML;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MarketService;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\BatchProductHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\AttributeManager;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\Brand;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\Color;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductFactory;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductMetaHandler;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\WCProductAdapter;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\ContainerAwareUnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\ProductMetaTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\ProductTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\ChannelVisibility;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use WC_Helper_Product;
use WC_Product;
use WC_Product_Variable;
use WC_Product_Variation;

/**
 * Class BatchProductHelperTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Product
 */
class BatchProductHelperTest extends ContainerAwareUnitTest {

	use ProductMetaTrait;
	use ProductTrait;

	/** @var ProductMetaHandler $product_meta */
	protected $product_meta;

	/** @var ProductHelper $product_helper */
	protected $product_helper;

	/** @var MockObject|ValidatorInterface $validator */
	protected $validator;

	/** @var ProductFactory $product_factory */
	protected $product_factory;

	/** @var MockObject|MarketService $market_service */
	protected $market_service;

	/** @var MockObject|WPML $wpml */
	protected $wpml;

	/** @var BatchProductHelper $batch_product_helper */
	protected $batch_product_helper;

	/** @var WC $wc */
	protected $wc;

	/** @var AttributeMappingRulesQuery $rules_query */
	protected $rules_query;

	/** @var MockObject|MapiDataSourcesService $data_sources */
	protected $data_sources;

	/**
	 * Converted price per currency code returned by the WPML stub configured in
	 * set_up_market_service_stubs(); a null value marks the currency as
	 * unconvertible. Currencies not in the map convert to the product's own price.
	 *
	 * @var array<string, float|null>
	 */
	protected $wpml_converted_prices = [];

	public function test_filter_synced_products_all_synced() {
		$synced_product = WC_Helper_Product::create_simple_product();
		$this->product_helper->mark_as_synced( $synced_product, $this->generate_google_product_mock() );

		$products = [
			$synced_product,
			WC_Helper_Product::create_simple_product(),
		];

		$results = $this->batch_product_helper->filter_synced_products( $products );
		$this->assertCount( 1, $results );
		$this->assertEquals( [ $synced_product ], $results );
	}

	public function test_mark_as_synced() {
		$product     = WC_Helper_Product::create_simple_product();
		$batch_entry = new BatchProductEntry( $product->get_id(), $this->generate_google_product_mock() );
		$this->batch_product_helper->mark_as_synced( $batch_entry );
		$this->assertTrue( $this->product_helper->is_product_synced( $product ) );
	}

	public function test_mark_as_synced_error_if_no_google_product_provided() {
		$product     = WC_Helper_Product::create_simple_product();
		$batch_entry = new BatchProductEntry( $product->get_id(), null );
		$this->expectException( InvalidClass::class );
		$this->batch_product_helper->mark_as_synced( $batch_entry );
	}

	public function test_mark_as_unsynced() {
		$synced_product = WC_Helper_Product::create_simple_product();
		$this->product_helper->mark_as_synced( $synced_product, $this->generate_google_product_mock() );

		$batch_entry = new BatchProductEntry( $synced_product->get_id() );
		$this->batch_product_helper->mark_as_unsynced( $batch_entry );

		$product = $this->wc->get_product( $synced_product->get_id() );
		$this->assertFalse( $this->product_helper->is_product_synced( $product ) );
	}

	public function test_mark_batch_as_unsynced() {
		$products    = $this->create_simple_product_set( 2 );
		$product_ids = array_keys( $products );

		$this->batch_product_helper->mark_batch_as_unsynced( $product_ids );

		foreach ( $product_ids as $product_id ) {
			$product = $this->wc->get_product( $product_id );
			$this->assertFalse( $this->product_helper->is_product_synced( $product ) );
		}
	}

	public function test_mark_as_invalid() {
		$product = WC_Helper_Product::create_simple_product();
		$errors  = [
			'Error 1',
			'Error 2',
		];

		$batch_entry = new BatchInvalidProductEntry( $product->get_id(), 'online:en:US:gla_1', $errors );
		$this->batch_product_helper->mark_as_invalid( $batch_entry );
		$this->assertEqualSets( $errors, $this->product_meta->get_errors( $product ) );
	}

	public function test_generate_delete_request_entries() {
		$products = $this->create_and_return_supported_test_products();

		foreach ( $products as $product ) {
			$this->product_helper->mark_as_synced( $product, $this->generate_google_product_mock( 'online:en:US:gla_' . $product->get_id() ) );
		}

		$results = $this->batch_product_helper->generate_delete_request_entries( $products );

		$this->assertContainsOnlyInstancesOf( BatchProductIDRequestEntry::class, $results );

		foreach ( $results as $google_id => $request_entry ) {
			$this->assertEquals( $google_id, $request_entry->get_product_id() );

			// check that the assigned Google ID is correctly mapped to the WooCommerce product ID
			$this->assertEquals( 'online:en:US:gla_' . $request_entry->get_wc_product_id(), $request_entry->get_product_id() );
		}
	}

	public function test_generate_delete_request_entries_variable_product() {
		$variable   = WC_Helper_Product::create_variation_product();
		$variations = [];
		foreach ( $variable->get_children() as $variation_id ) {
			$variation = $this->wc->get_product( $variation_id );
			$this->product_helper->mark_as_synced( $variation, $this->generate_google_product_mock() );
			$variations[] = $variation;
		}

		$results = $this->batch_product_helper->generate_delete_request_entries( [ $variable ] );

		$this->assertCount( \count( $variations ), $results );
	}

	public function test_generate_delete_request_entries_including_invalid_product() {
		$products = [
			$this->generate_simple_product_mock(),
			new BatchProductEntry( 0, null ),
		];
		$this->expectException( InvalidClass::class );
		$this->batch_product_helper->generate_delete_request_entries( $products );
	}

	public function test_generate_delete_request_entries_skips_if_no_synced_google_id_exists() {
		$products = $this->create_and_return_supported_test_products();

		// mark all products as synced
		foreach ( $products as $product ) {
			$this->product_helper->mark_as_synced( $product, $this->generate_google_product_mock( 'online:en:US:gla_' . $product->get_id() ) );
		}

		// skip one product from the list and delete its google id
		$skipped_product = $products[0];
		$this->product_meta->delete_google_ids( $skipped_product );

		$results = $this->batch_product_helper->generate_delete_request_entries( $products );

		$this->assertArrayNotHasKey( 'online:en:US:gla_' . $skipped_product->get_id(), $results );
	}

	public function test_generate_mapi_delete_entries() {
		$products = $this->create_and_return_supported_test_products();

		foreach ( $products as $product ) {
			$this->product_helper->mark_as_synced(
				$product,
				$this->generate_google_product_mock( "en~US~gla_{$product->get_id()}", 'US' )
			);
		}

		$results = $this->batch_product_helper->generate_mapi_delete_entries( $products );

		$this->assertCount( count( $products ), $results );
		foreach ( $results as $entry ) {
			$this->assertInstanceOf( ProductInput::class, $entry['input'] );
			$this->assertSame( "en~US~gla_{$entry['wc_product_id']}", $entry['google_id'] );
			$this->assertSame( 'en', $entry['input']->get_content_language() );
			$this->assertSame( 'US', $entry['input']->get_feed_label() );
			$this->assertSame( "gla_{$entry['wc_product_id']}", $entry['input']->get_offer_id() );
		}
	}

	public function test_generate_mapi_update_entries() {
		$products = $this->create_and_return_supported_test_products();

		$this->market_service->expects( $this->any() )
			->method( 'get_primary_market' )
			->willReturn(
				[
					'country'    => null,
					'feed_label' => null,
					'language'   => 'en',
				]
			);

		$this->market_service->expects( $this->any() )
			->method( 'get_main_feed_label' )
			->willReturn( 'US' );

		$this->market_service->expects( $this->any() )
			->method( 'get_all_countries' )
			->willReturn( [ 'US' ] );

		$this->validator->expects( $this->any() )
			->method( 'validate' )
			->willReturn( [] );

		$this->rules_query->expects( $this->any() )
			->method( 'get_results' )
			->willReturn( [] );

		$results = $this->batch_product_helper->generate_mapi_update_entries( $products );

		// the number of results can be bigger because of variable products
		$this->assertGreaterThanOrEqual( \count( $products ), \count( $results ) );

		foreach ( $results as $entry ) {
			$this->assertInstanceOf( ProductInput::class, $entry['input'] );
		}

		// the products (including variations if a variable product) sent to the method should ALL be returned as results
		$results_product_ids = array_map(
			function ( array $entry ) {
				return $entry['product']->get_id();
			},
			$results
		);
		$param_product_ids   = [];
		foreach ( $products as $product ) {
			if ( $product instanceof WC_Product_Variable ) {
				foreach ( $product->get_children() as $child ) {
					$param_product_ids[] = $child->get_id();
				}
			} else {
				$param_product_ids[] = $product->get_id();
			}
		}
		$this->assertEqualSets( $param_product_ids, $results_product_ids );
	}

	public function test_generate_mapi_update_entries_primary_only_single_entry_per_product() {
		$products = $this->create_and_return_supported_test_products();

		$this->set_up_market_service_stubs(
			[ 'US' ],
			[
				'primary' => [
					'country'    => 'US',
					'feed_label' => 'US',
					'language'   => 'en',
				],
			]
		);

		$this->validator->expects( $this->any() )->method( 'validate' )->willReturn( [] );
		$this->rules_query->expects( $this->any() )->method( 'get_results' )->willReturn( [] );

		$results = $this->batch_product_helper->generate_mapi_update_entries( $products );

		$param_product_ids = $this->flatten_to_simple_product_ids( $products );

		$this->assertCount( count( $param_product_ids ), $results );

		foreach ( $results as $entry ) {
			$this->assertSame( 'US', $entry['input']->get_feed_label() );
			$this->assertSame( 'US', $entry['country'] );
		}
	}

	public function test_generate_mapi_update_entries_primary_null_country_uses_main_feed_label() {
		// Regression test for the original issue-3 TypeError: the real
		// MarketService contract has get_primary_market() returning null for
		// both country and feed_label (the primary market targets multiple
		// countries), and the helper must resolve the main feed label itself
		// instead of passing those nulls into ProductFactory::create().
		// Stubbed directly, without set_up_market_service_stubs(), so the
		// null return is explicit in the test.
		$products = $this->create_and_return_supported_test_products();

		$primary = [
			'country'    => null,
			'feed_label' => null,
			'language'   => [],
		];

		$this->market_service->method( 'get_primary_market' )->willReturn( $primary );
		$this->market_service->method( 'get_markets' )->willReturn( [ 'primary' => $primary ] );
		$this->market_service->method( 'get_all_countries' )->willReturn( [ 'US' ] );
		$this->market_service->method( 'get_main_feed_label' )->willReturn( 'US' );

		$this->validator->expects( $this->any() )->method( 'validate' )->willReturn( [] );
		$this->rules_query->expects( $this->any() )->method( 'get_results' )->willReturn( [] );

		$results = $this->batch_product_helper->generate_mapi_update_entries( $products );

		$this->assertCount( count( $this->flatten_to_simple_product_ids( $products ) ), $results );

		foreach ( $results as $entry ) {
			$this->assertSame( 'US', $entry['input']->get_feed_label() );
			$this->assertSame( 'US', $entry['country'] );
		}
	}

	public function test_generate_mapi_update_entries_rethrows_account_reconnect() {
		$products = $this->create_and_return_supported_test_products();

		$this->market_service->method( 'get_primary_market' )->willReturn( [ 'country' => null, 'feed_label' => null, 'language' => 'en' ] );
		$this->market_service->method( 'get_main_feed_label' )->willReturn( 'US' );
		$this->market_service->method( 'get_all_countries' )->willReturn( [ 'US' ] );
		$this->validator->method( 'validate' )->willReturn( [] );
		$this->rules_query->method( 'get_results' )->willReturn( [] );

		// An account-wide auth failure surfaces while resolving the data source.
		$this->data_sources->method( 'ensure_data_source_for' )->willThrowException( AccountReconnect::jetpack_disconnected() );

		$this->expectException( AccountReconnect::class );

		$this->batch_product_helper->generate_mapi_update_entries( $products );
	}

	public function test_generate_mapi_update_entries_skips_product_on_other_exception() {
		$products = $this->create_and_return_supported_test_products();

		$this->market_service->method( 'get_primary_market' )->willReturn( [ 'country' => null, 'feed_label' => null, 'language' => 'en' ] );
		$this->market_service->method( 'get_main_feed_label' )->willReturn( 'US' );
		$this->market_service->method( 'get_all_countries' )->willReturn( [ 'US' ] );
		$this->validator->method( 'validate' )->willReturn( [] );
		$this->rules_query->method( 'get_results' )->willReturn( [] );

		// A per-product failure (not account-wide) is still swallowed and the product skipped.
		$this->data_sources->method( 'ensure_data_source_for' )->willThrowException( new MerchantApiException( 400, [], __METHOD__ ) );

		$this->assertSame( [], $this->batch_product_helper->generate_mapi_update_entries( $products ) );
	}

	public function test_generate_mapi_update_entries_primary_plus_secondary_two_entries_per_product() {
		$products = $this->create_and_return_supported_test_products();

		$this->set_up_market_service_stubs(
			[ 'US', 'DE' ],
			[
				'primary' => [
					'country'    => 'US',
					'feed_label' => 'US',
					'language'   => 'en',
				],
				'de'      => [
					'country'    => 'DE',
					'feed_label' => 'DE',
					'language'   => 'de',
				],
			]
		);

		$this->validator->expects( $this->any() )->method( 'validate' )->willReturn( [] );
		$this->rules_query->expects( $this->any() )->method( 'get_results' )->willReturn( [] );

		$results = $this->batch_product_helper->generate_mapi_update_entries( $products );

		$param_product_ids = $this->flatten_to_simple_product_ids( $products );

		$this->assertCount( count( $param_product_ids ) * 2, $results );

		$feed_labels_by_product = [];
		foreach ( $results as $entry ) {
			$wc_id                              = $entry['product']->get_id();
			$feed_labels_by_product[ $wc_id ][] = $entry['input']->get_feed_label();
		}

		foreach ( $feed_labels_by_product as $labels ) {
			sort( $labels );
			// The secondary market has no currency configured, so its derived
			// label falls back to the store currency; the primary stays bare.
			$this->assertSame( [ 'DE-' . strtoupper( substr( get_locale(), 0, 2 ) ) . '-' . get_woocommerce_currency(), 'US' ], $labels );
		}
	}

	public function test_generate_mapi_update_entries_two_secondaries_three_entries_per_product() {
		$products = $this->create_and_return_supported_test_products();

		$this->set_up_market_service_stubs(
			[ 'US', 'DE', 'FR' ],
			[
				'primary' => [
					'country'    => 'US',
					'feed_label' => 'US',
					'language'   => 'en',
				],
				'de'      => [
					'country'    => 'DE',
					'feed_label' => 'DE',
					'language'   => 'de',
				],
				'fr'      => [
					'country'    => 'FR',
					'feed_label' => 'FR',
					'language'   => 'fr',
				],
			]
		);

		$this->validator->expects( $this->any() )->method( 'validate' )->willReturn( [] );
		$this->rules_query->expects( $this->any() )->method( 'get_results' )->willReturn( [] );

		$results = $this->batch_product_helper->generate_mapi_update_entries( $products );

		$param_product_ids = $this->flatten_to_simple_product_ids( $products );

		$this->assertCount( count( $param_product_ids ) * 3, $results );
	}

	public function test_generate_mapi_update_entries_secondary_shipping_scoped_to_own_country() {
		$products = [ WC_Helper_Product::create_simple_product() ];

		$this->set_up_market_service_stubs(
			[ 'US', 'CA', 'DE' ],
			[
				'primary' => [
					'country'    => 'US',
					'feed_label' => 'US',
					'language'   => 'en',
				],
				'de'      => [
					'country'    => 'DE',
					'feed_label' => 'DE',
					'language'   => 'de',
				],
			]
		);

		$this->validator->expects( $this->any() )->method( 'validate' )->willReturn( [] );
		$this->rules_query->expects( $this->any() )->method( 'get_results' )->willReturn( [] );

		$results = $this->batch_product_helper->generate_mapi_update_entries( $products );

		$this->assertCount( 2, $results );

		$by_label = [];
		foreach ( $results as $entry ) {
			$by_label[ $entry['input']->get_feed_label() ] = $entry['input']->get_attributes()['shipping'] ?? [];
		}

		$secondary_shipping_countries = array_column( $by_label[ 'DE-' . strtoupper( substr( get_locale(), 0, 2 ) ) . '-' . get_woocommerce_currency() ], 'country' );
		$this->assertEqualSets( [ 'DE' ], $secondary_shipping_countries );

		$primary_shipping_countries = array_column( $by_label['US'], 'country' );
		// Primary's shipping countries are unchanged from GOOWOO-591: all countries
		// (including the secondary's) plus the product's own target country are present.
		foreach ( [ 'US', 'CA', 'DE' ] as $expected_country ) {
			$this->assertContains( $expected_country, $primary_shipping_countries );
		}
	}

	public function test_generate_mapi_update_entries_wpml_match_emits_with_product_language() {
		$product = WC_Helper_Product::create_simple_product();

		$this->market_service->method( 'has_multilingual_support' )->willReturn( true );
		$this->wpml->method( 'get_post_language' )->willReturn( 'fr' );

		$this->set_up_market_service_stubs(
			[ 'US', 'FR' ],
			[
				'primary' => [
					'country'    => 'US',
					'feed_label' => 'US',
					'language'   => [ 'en' ],
				],
				'fr'      => [
					'country'    => 'FR',
					'feed_label' => 'FR-PROMO',
					'language'   => [ 'fr', 'en' ],
				],
			]
		);

		$this->validator->expects( $this->any() )->method( 'validate' )->willReturn( [] );
		$this->rules_query->expects( $this->any() )->method( 'get_results' )->willReturn( [] );

		$results = $this->batch_product_helper->generate_mapi_update_entries( [ $product ] );

		// Product language 'fr' is not in primary's ['en']; only the France secondary
		// entry is emitted, under the currency-derived feed label (falling back to
		// the store currency since the market has none configured).
		$this->assertCount( 1, $results );
		$entry = $results[0];
		$this->assertSame( 'FR', $entry['country'] );
		$this->assertSame( 'FR-FR-' . get_woocommerce_currency(), $entry['input']->get_feed_label() );
		$this->assertSame( 'fr', $entry['input']->get_content_language() );
	}

	public function test_generate_mapi_update_entries_primary_keeps_bare_feed_label_for_every_language() {
		$product = WC_Helper_Product::create_simple_product();

		$this->market_service->method( 'has_multilingual_support' )->willReturn( true );
		$this->wpml->method( 'get_post_language' )->willReturn( 'fr' );

		$this->set_up_market_service_stubs(
			[ 'US' ],
			[
				'primary' => [
					'country'    => 'US',
					'feed_label' => 'US',
					'language'   => [ 'en', 'fr' ],
				],
			]
		);

		$this->validator->expects( $this->any() )->method( 'validate' )->willReturn( [] );
		$this->rules_query->expects( $this->any() )->method( 'get_results' )->willReturn( [] );

		$results = $this->batch_product_helper->generate_mapi_update_entries( [ $product ] );

		// The primary label never varies by language: Google records the entry's
		// language separately (contentLanguage), and keeping the bare label
		// preserves the identity of existing production entries.
		$this->assertCount( 1, $results );
		$this->assertSame( 'US', $results[0]['input']->get_feed_label() );
		$this->assertSame( 'US', $results[0]['country'] );
		$this->assertSame( 'fr', $results[0]['input']->get_content_language() );
	}

	public function test_generate_mapi_update_entries_secondary_market_exchange_rate_converts_price() {
		$product = WC_Helper_Product::create_simple_product(
			true,
			[
				'price'         => 100,
				'regular_price' => 100,
			]
		);

		$this->market_service->method( 'has_multilingual_support' )->willReturn( false );

		// No WPML conversion for EUR, so the market's fixed exchange rate is
		// the entry's only conversion source.
		$this->wpml_converted_prices['EUR'] = null;

		$this->set_up_market_service_stubs(
			[ 'US', 'DE' ],
			[
				'primary' => [
					'country'    => 'US',
					'feed_label' => 'US',
					'language'   => [ 'en' ],
				],
				'de'      => [
					'country'       => 'DE',
					'feed_label'    => 'DE',
					'language'      => [],
					'currency'      => [ 'EUR' ],
					'exchange_rate' => 0.92,
				],
			]
		);

		$this->validator->expects( $this->any() )->method( 'validate' )->willReturn( [] );
		$this->rules_query->expects( $this->any() )->method( 'get_results' )->willReturn( [] );

		$results = $this->batch_product_helper->generate_mapi_update_entries( [ $product ] );

		$this->assertCount( 2, $results );

		$secondary = array_values(
			array_filter(
				$results,
				static function ( array $entry ): bool {
					return 'DE' === $entry['country'];
				}
			)
		);

		// The market's stored exchange rate reaches the emission adapter: the
		// secondary entry's price is converted (100.00 at 0.92) and labelled
		// with the market currency, without any WPML conversion available.
		$this->assertCount( 1, $secondary );
		$attributes = $secondary[0]['input']->get_attributes();
		$this->assertSame( 'EUR', $attributes['price']['currencyCode'] );
		$this->assertSame( '92000000', $attributes['price']['amountMicros'] );
	}

	public function test_generate_mapi_update_entries_wpml_match_alternate_language_in_set() {
		$product = WC_Helper_Product::create_simple_product();

		$this->market_service->method( 'has_multilingual_support' )->willReturn( true );
		$this->wpml->method( 'get_post_language' )->willReturn( 'en' );

		$this->set_up_market_service_stubs(
			[ 'US', 'FR' ],
			[
				'primary' => [
					'country'    => 'US',
					'feed_label' => 'US',
					'language'   => [],
				],
				'fr'      => [
					'country'    => 'FR',
					'feed_label' => 'FR-PROMO',
					'language'   => [ 'fr', 'en' ],
				],
			]
		);

		$this->validator->expects( $this->any() )->method( 'validate' )->willReturn( [] );
		$this->rules_query->expects( $this->any() )->method( 'get_results' )->willReturn( [] );

		$results = $this->batch_product_helper->generate_mapi_update_entries( [ $product ] );

		$secondary_entries = array_filter(
			$results,
			static function ( array $entry ) {
				return 'FR-EN-' . get_woocommerce_currency() === $entry['input']->get_feed_label();
			}
		);

		$this->assertCount( 1, $secondary_entries );
		$entry = array_values( $secondary_entries )[0];
		$this->assertSame( 'en', $entry['input']->get_content_language() );
	}

	public function test_generate_mapi_update_entries_wpml_no_match_emits_no_entry_for_market() {
		$product = WC_Helper_Product::create_simple_product();

		$this->market_service->method( 'has_multilingual_support' )->willReturn( true );
		$this->wpml->method( 'get_post_language' )->willReturn( 'de' );

		$this->set_up_market_service_stubs(
			[ 'US', 'FR' ],
			[
				'primary' => [
					'country'    => 'US',
					'feed_label' => 'US',
					'language'   => [ 'en' ],
				],
				'fr'      => [
					'country'    => 'FR',
					'feed_label' => 'FR-PROMO',
					'language'   => [ 'fr', 'en' ],
				],
			]
		);

		$this->validator->expects( $this->any() )->method( 'validate' )->willReturn( [] );
		$this->rules_query->expects( $this->any() )->method( 'get_results' )->willReturn( [] );

		$results = $this->batch_product_helper->generate_mapi_update_entries( [ $product ] );

		// Product language 'de' is in neither market's list; nothing is emitted.
		$this->assertCount( 0, $results );
	}

	public function test_generate_mapi_update_entries_wpml_primary_locale_form_matches_short_code() {
		$product = WC_Helper_Product::create_simple_product();

		$this->market_service->method( 'has_multilingual_support' )->willReturn( true );
		$this->wpml->method( 'get_post_language' )->willReturn( 'en' );

		$this->set_up_market_service_stubs(
			[ 'US' ],
			[
				'primary' => [
					'country'    => 'US',
					'feed_label' => 'US',
					'language'   => [ 'en_US' ],
				],
			]
		);

		$this->validator->expects( $this->any() )->method( 'validate' )->willReturn( [] );
		$this->rules_query->expects( $this->any() )->method( 'get_results' )->willReturn( [] );

		$results = $this->batch_product_helper->generate_mapi_update_entries( [ $product ] );

		// 'en_US' in the market list is converted to 'en' before comparison, so the product matches the primary.
		$this->assertCount( 1, $results );
		$this->assertSame( 'US', $results[0]['input']->get_feed_label() );
		$this->assertSame( 'en', $results[0]['input']->get_content_language() );
	}

	public function test_generate_mapi_update_entries_wpml_inactive_empty_language_emits_for_every_market() {
		$product = WC_Helper_Product::create_simple_product();

		$this->market_service->method( 'has_multilingual_support' )->willReturn( false );

		$this->set_up_market_service_stubs(
			[ 'US', 'FR' ],
			[
				'primary' => [
					'country'    => 'US',
					'feed_label' => 'US',
					'language'   => [],
				],
				'fr'      => [
					'country'    => 'FR',
					'feed_label' => 'FR',
					'language'   => [],
				],
			]
		);

		$this->validator->expects( $this->any() )->method( 'validate' )->willReturn( [] );
		$this->rules_query->expects( $this->any() )->method( 'get_results' )->willReturn( [] );

		$results = $this->batch_product_helper->generate_mapi_update_entries( [ $product ] );

		$this->assertCount( 2, $results );

		// When WPML is inactive and no language override is passed, the adapter's
		// default contentLanguage from get_locale() is used. The empty string is
		// the signal: the override is not applied and the default stands.
		foreach ( $results as $entry ) {
			$this->assertNotSame( '', $entry['input']->get_content_language() );
		}
	}

	public function test_generate_mapi_update_entries_wpml_inactive_non_empty_language_ignored() {
		$product = WC_Helper_Product::create_simple_product();

		$this->market_service->method( 'has_multilingual_support' )->willReturn( false );

		$this->set_up_market_service_stubs(
			[ 'US', 'FR' ],
			[
				'primary' => [
					'country'    => 'US',
					'feed_label' => 'US',
					'language'   => [ 'en' ],
				],
				'fr'      => [
					'country'    => 'FR',
					'feed_label' => 'FR',
					'language'   => [ 'fr' ],
				],
			]
		);

		$this->validator->expects( $this->any() )->method( 'validate' )->willReturn( [] );
		$this->rules_query->expects( $this->any() )->method( 'get_results' )->willReturn( [] );

		$results = $this->batch_product_helper->generate_mapi_update_entries( [ $product ] );

		// With WPML inactive, the market's language[] is ignored: every market still emits an entry.
		$this->assertCount( 2, $results );
	}

	public function test_generate_mapi_update_entries_wpml_variable_product_matching_variations_only() {
		$variable    = WC_Helper_Product::create_variation_product();
		$variations  = array_map( 'wc_get_product', $variable->get_children() );
		$matching_id = $variations[0]->get_id();

		$this->market_service->method( 'has_multilingual_support' )->willReturn( true );
		$this->wpml->method( 'get_post_language' )
			->willReturnCallback(
				static function ( int $post_id ) use ( $matching_id ) {
					return $post_id === $matching_id ? 'fr' : 'de';
				}
			);

		$this->set_up_market_service_stubs(
			[ 'US', 'FR' ],
			[
				'primary' => [
					'country'    => 'US',
					'feed_label' => 'US',
					'language'   => [],
				],
				'fr'      => [
					'country'    => 'FR',
					'feed_label' => 'FR',
					'language'   => [ 'fr' ],
				],
			]
		);

		$this->validator->expects( $this->any() )->method( 'validate' )->willReturn( [] );
		$this->rules_query->expects( $this->any() )->method( 'get_results' )->willReturn( [] );

		$results = $this->batch_product_helper->generate_mapi_update_entries( [ $variable ] );

		// The fr-language variation syncs to the France market under the
		// language-currency derived feed label (the variation's own language,
		// and the store currency as the market has none configured).
		$fr_entries = array_filter(
			$results,
			static function ( array $entry ) {
				return 'FR-FR-' . get_woocommerce_currency() === $entry['input']->get_feed_label();
			}
		);

		$this->assertCount( 1, $fr_entries );
		$entry = array_values( $fr_entries )[0];
		$this->assertSame( $matching_id, $entry['product']->get_id() );
	}

	public function test_generate_mapi_update_entries_skips_invalid_product() {
		$products = $this->create_and_return_supported_test_products();

		// skip one product from the list
		$invalid_product = $products[0];

		$this->validator->expects( $this->any() )
			->method( 'validate' )
			->willReturnCallback(
				function ( WCProductAdapter $product ) use ( $invalid_product ) {
					if ( $product->get_wc_product()->get_id() === $invalid_product->get_id() ) {
						$violation_example = $this->createMock( ConstraintViolation::class );
						$violations        = new ConstraintViolationList();
						$violations->add( $violation_example );

						return $violations;
					}

					return [];
				}
			);

		$this->rules_query->expects( $this->any() )
			->method( 'get_results' )
			->willReturn( [] );

		$this->market_service->expects( $this->any() )
			->method( 'get_primary_market' )
			->willReturn(
				[
					'country'    => null,
					'feed_label' => null,
					'language'   => 'en',
				]
			);

		$this->market_service->expects( $this->any() )
			->method( 'get_main_feed_label' )
			->willReturn( 'US' );

		$this->market_service->expects( $this->any() )
			->method( 'get_all_countries' )
			->willReturn( [ 'US' ] );

		$results = $this->batch_product_helper->generate_mapi_update_entries( $products );

		$results_product_ids = array_map(
			function ( array $entry ) {
				return $entry['product']->get_id();
			},
			$results
		);

		$this->assertNotContains( $invalid_product->get_id(), $results_product_ids );
	}

	public function test_generate_mapi_update_entries_secondary_throw_discards_primary() {
		$product = WC_Helper_Product::create_simple_product();

		// Use the real factory to build a valid primary adapter, then route the
		// secondary call through a mock that throws so the catch in the production
		// code fires after the primary entry has been staged.
		$real_factory    = $this->container->get( ProductFactory::class );
		$primary_adapter = $real_factory->create( $product, 'US', [], 'US', 'en' );

		$factory_mock = $this->createMock( ProductFactory::class );
		$factory_mock->method( 'create' )
			->willReturnCallback(
				function ( WC_Product $p, string $country ) use ( $primary_adapter ) {
					if ( 'US' === $country ) {
						return $primary_adapter;
					}
					throw new InvalidValue( 'simulated secondary factory failure' );
				}
			);

		$helper = new BatchProductHelper(
			$this->product_meta,
			$this->product_helper,
			$this->validator,
			$factory_mock,
			$this->rules_query,
			$this->market_service,
			$this->wpml,
			$this->container->get( AttributeManager::class ),
			$this->data_sources
		);

		$this->set_up_market_service_stubs(
			[ 'US', 'DE' ],
			[
				'primary' => [
					'country'    => 'US',
					'feed_label' => 'US',
					'language'   => 'en',
				],
				'de'      => [
					'country'    => 'DE',
					'feed_label' => 'DE',
					'language'   => 'de',
				],
			]
		);

		$this->validator->expects( $this->any() )->method( 'validate' )->willReturn( [] );
		$this->rules_query->expects( $this->any() )->method( 'get_results' )->willReturn( [] );

		$results = $helper->generate_mapi_update_entries( [ $product ] );

		// Nothing should land: the primary entry must not survive a secondary failure.
		$this->assertCount( 0, $results );
	}

	public function test_generate_mapi_update_entries_skips_only_the_invalid_secondary() {
		$product = WC_Helper_Product::create_simple_product();

		$this->set_up_market_service_stubs(
			[ 'US', 'DE' ],
			[
				'primary' => [
					'country'    => 'US',
					'feed_label' => 'US',
					'language'   => 'en',
				],
				'de'      => [
					'country'    => 'DE',
					'feed_label' => 'DE',
					'language'   => 'de',
				],
			]
		);

		// Primary adapter passes validation; the secondary (DE) adapter fails.
		// The secondary's derived feed label carries the store currency since
		// the market has no currency configured.
		$this->validator->expects( $this->any() )
			->method( 'validate' )
			->willReturnCallback(
				function ( WCProductAdapter $adapter ) {
					if ( 'DE-' . strtoupper( substr( get_locale(), 0, 2 ) ) . '-' . get_woocommerce_currency() === $adapter->getFeedLabel() ) {
						$violations = new ConstraintViolationList();
						$violations->add( $this->createMock( ConstraintViolation::class ) );
						return $violations;
					}

					return [];
				}
			);

		$this->rules_query->expects( $this->any() )->method( 'get_results' )->willReturn( [] );

		$results = $this->batch_product_helper->generate_mapi_update_entries( [ $product ] );

		// The invalid DE secondary is skipped, but the valid primary entry still survives.
		$this->assertCount( 1, $results );
		$this->assertSame( 'US', $results[0]['country'] );

		// Re-fetch the product so the meta read does not hit the original
		// instance's stale cache: mark_as_invalid persisted against a fresh
		// instance via ProductHelper::get_wc_product().
		$refreshed = $this->wc->get_product( $product->get_id() );
		$this->assertNotEmpty( $this->product_meta->get_errors( $refreshed ) );
	}

	public function test_generate_mapi_update_entries_skips_not_sync_ready() {
		$products = $this->create_and_return_supported_test_products();

		// skip one product from the list
		$skipped_product = $products[0];
		if ( $skipped_product instanceof WC_Product_Variation ) {
			$this->product_meta->update_visibility( wc_get_product( $skipped_product->get_parent_id() ), ChannelVisibility::DONT_SYNC_AND_SHOW );
		} else {
			$this->product_meta->update_visibility( $skipped_product, ChannelVisibility::DONT_SYNC_AND_SHOW );
		}

		$this->market_service->expects( $this->any() )
			->method( 'get_primary_market' )
			->willReturn(
				[
					'country'    => null,
					'feed_label' => null,
					'language'   => 'en',
				]
			);

		$this->market_service->expects( $this->any() )
			->method( 'get_main_feed_label' )
			->willReturn( 'US' );

		$this->market_service->expects( $this->any() )
			->method( 'get_all_countries' )
			->willReturn( [ 'US' ] );

		$this->validator->expects( $this->any() )
			->method( 'validate' )
			->willReturn( [] );

		$this->rules_query->expects( $this->any() )
			->method( 'get_results' )
			->willReturn( [] );

		$results = $this->batch_product_helper->generate_mapi_update_entries( $products );

		$results_product_ids = array_map(
			function ( array $entry ) {
				return $entry['product']->get_id();
			},
			$results
		);

		$this->assertNotContains( $skipped_product->get_id(), $results_product_ids );
	}

	public function test_generate_mapi_delete_entries_variable_product() {
		$variable   = WC_Helper_Product::create_variation_product();
		$variations = [];
		foreach ( $variable->get_children() as $variation_id ) {
			$variation = $this->wc->get_product( $variation_id );
			$this->product_helper->mark_as_synced(
				$variation,
				$this->generate_google_product_mock( "en~US~gla_{$variation->get_id()}", 'US' )
			);
			$variations[] = $variation;
		}

		$results = $this->batch_product_helper->generate_mapi_delete_entries( [ $variable ] );

		$this->assertCount( count( $variations ), $results );
	}

	public function test_generate_mapi_delete_entries_skips_products_without_google_id() {
		$products = $this->create_and_return_supported_test_products();

		foreach ( $products as $product ) {
			$this->product_helper->mark_as_synced(
				$product,
				$this->generate_google_product_mock( "en~US~gla_{$product->get_id()}", 'US' )
			);
		}

		$skipped_product = $products[0];
		$this->product_meta->delete_google_ids( $skipped_product );

		$results = $this->batch_product_helper->generate_mapi_delete_entries( $products );

		$this->assertNotContains( $skipped_product->get_id(), array_column( $results, 'wc_product_id' ) );
	}

	public function test_generate_mapi_delete_entries_skips_malformed_id() {
		$products = $this->create_and_return_supported_test_products();
		$product  = $products[0];

		$this->product_helper->mark_as_synced(
			$product,
			$this->generate_google_product_mock( 'malformed-id', 'US' )
		);

		$results = $this->batch_product_helper->generate_mapi_delete_entries( [ $product ] );

		$this->assertEmpty( $results );
	}

	public function test_generate_mapi_delete_entries_deletes_legacy_colon_id() {
		// A product synced before the MAPI cutover stores a legacy Content
		// API id (online:lang:country:offerId). It must still produce a delete entry instead of
		// being skipped, otherwise it lingers in Merchant Center after the product is deleted.
		$products = $this->create_and_return_supported_test_products();
		$product  = $products[0];

		$this->product_helper->mark_as_synced(
			$product,
			$this->generate_google_product_mock( "online:en:US:gla_{$product->get_id()}", 'US' )
		);

		$results = $this->batch_product_helper->generate_mapi_delete_entries( [ $product ] );

		$this->assertCount( 1, $results );
		$this->assertSame( "online:en:US:gla_{$product->get_id()}", $results[0]['google_id'] );
		$this->assertSame( 'en', $results[0]['input']->get_content_language() );
		$this->assertSame( 'US', $results[0]['input']->get_feed_label() );
		$this->assertSame( "gla_{$product->get_id()}", $results[0]['input']->get_offer_id() );
	}

	public function test_parse_deletable_identity_accepts_mapi_and_legacy_ids() {
		$this->assertSame(
			[ 'en', 'US', 'gla_29' ],
			$this->batch_product_helper->parse_deletable_identity( 'en~US~gla_29' )
		);
		$this->assertSame(
			[ 'en', 'US', 'gla_29' ],
			$this->batch_product_helper->parse_deletable_identity( 'online:en:US:gla_29' )
		);
		$this->assertNull( $this->batch_product_helper->parse_deletable_identity( 'malformed-id' ) );

		// A four-part colon string that is not an `online` Content API id is not a legacy id.
		$this->assertNull( $this->batch_product_helper->parse_deletable_identity( 'local:en:US:gla_29' ) );
		$this->assertNull( $this->batch_product_helper->parse_deletable_identity( 'foo:bar:baz:qux' ) );
	}

	public function test_parse_mapi_identity_rejects_legacy_colon_id() {
		// parse_mapi_identity stays tilde-only: the status read path relies on it returning null for
		// legacy ids, which the Merchant API rejects as invalid resource names.
		$this->assertNull( $this->batch_product_helper->parse_mapi_identity( 'online:en:US:gla_29' ) );
		$this->assertSame(
			[ 'en', 'US', 'gla_29' ],
			$this->batch_product_helper->parse_mapi_identity( 'en~US~gla_29' )
		);
	}

	public function test_generate_stale_products_delete_entries() {
		$products         = $this->create_and_return_supported_test_products();
		$stale_product    = $products[0];
		$stale_product_id = $stale_product->get_id();

		$this->market_service->expects( $this->once() )
			->method( 'get_all_feed_labels' )
			->willReturn( [ 'US' ] );

		$stale_google_ids = [
			'AU' => "en~AU~gla_{$stale_product_id}",
			'DK' => "en~DK~gla_{$stale_product_id}",
			'US' => "en~US~gla_{$stale_product_id}",
		];
		$this->product_meta->update_google_ids( $stale_product, $stale_google_ids );

		$results = $this->batch_product_helper->generate_stale_products_delete_entries( $products );

		$this->assertCount( 2, $results );

		foreach ( $results as $entry ) {
			$this->assertInstanceOf( ProductInput::class, $entry['input'] );
			$this->assertSame( $stale_product_id, $entry['wc_product_id'] );
		}

		$google_ids = array_column( $results, 'google_id' );
		$this->assertContains( $stale_google_ids['AU'], $google_ids );
		$this->assertContains( $stale_google_ids['DK'], $google_ids );
		$this->assertNotContains( $stale_google_ids['US'], $google_ids );
	}

	public function test_generate_stale_products_delete_entries_handles_legacy_colon_id() {
		// Regression (GOOWOO-802): the stale-products cleanup path must convert a legacy Content API
		// id, not skip it, or the out-of-audience country's entry lingers in Merchant Center.
		$products         = $this->create_and_return_supported_test_products();
		$stale_product    = $products[0];
		$stale_product_id = $stale_product->get_id();

		$this->market_service->expects( $this->once() )
			->method( 'get_all_feed_labels' )
			->willReturn( [ 'US' ] );

		// AU is no longer in the target audience and stored under the legacy colon format.
		$this->product_meta->update_google_ids(
			$stale_product,
			[
				'AU' => "online:en:AU:gla_{$stale_product_id}",
				'US' => "online:en:US:gla_{$stale_product_id}",
			]
		);

		$results = $this->batch_product_helper->generate_stale_products_delete_entries( $products );

		$this->assertCount( 1, $results );
		$this->assertSame( "online:en:AU:gla_{$stale_product_id}", $results[0]['google_id'] );
		$this->assertSame( 'en', $results[0]['input']->get_content_language() );
		$this->assertSame( 'AU', $results[0]['input']->get_feed_label() );
		$this->assertSame( "gla_{$stale_product_id}", $results[0]['input']->get_offer_id() );
	}

	public function test_generate_stale_countries_delete_entries() {
		$products         = $this->create_and_return_supported_test_products();
		$stale_product    = $products[0];
		$stale_product_id = $stale_product->get_id();

		$this->market_service->expects( $this->once() )
			->method( 'get_all_feed_labels' )
			->willReturn( [ 'US' ] );

		$stale_google_ids = [
			'AU' => "en~AU~gla_{$stale_product_id}",
			'DK' => "en~DK~gla_{$stale_product_id}",
			'US' => "en~US~gla_{$stale_product_id}",
		];
		$this->product_meta->update_google_ids( $stale_product, $stale_google_ids );

		$results = $this->batch_product_helper->generate_stale_countries_delete_entries( $products );

		$this->assertCount( 2, $results );

		foreach ( $results as $entry ) {
			$this->assertInstanceOf( ProductInput::class, $entry['input'] );
			$this->assertSame( $stale_product_id, $entry['wc_product_id'] );
		}

		$google_ids = array_column( $results, 'google_id' );
		$this->assertContains( $stale_google_ids['AU'], $google_ids );
		$this->assertContains( $stale_google_ids['DK'], $google_ids );
		$this->assertNotContains( $stale_google_ids['US'], $google_ids );
	}

	public function test_generate_stale_countries_delete_entries_handles_legacy_colon_id() {
		// Regression (GOOWOO-802): the stale-country cleanup path must convert a legacy Content API
		// id, not skip it, or the entry for the stale country lingers in Merchant Center.
		$products         = $this->create_and_return_supported_test_products();
		$stale_product    = $products[0];
		$stale_product_id = $stale_product->get_id();

		$this->market_service->expects( $this->once() )
			->method( 'get_all_feed_labels' )
			->willReturn( [ 'US' ] );

		// AU is stale (not the main country) and stored under the legacy colon format.
		$this->product_meta->update_google_ids(
			$stale_product,
			[
				'AU' => "online:en:AU:gla_{$stale_product_id}",
				'US' => "online:en:US:gla_{$stale_product_id}",
			]
		);

		$results = $this->batch_product_helper->generate_stale_countries_delete_entries( $products );

		$this->assertCount( 1, $results );
		$this->assertSame( "online:en:AU:gla_{$stale_product_id}", $results[0]['google_id'] );
		$this->assertSame( 'en', $results[0]['input']->get_content_language() );
		$this->assertSame( 'AU', $results[0]['input']->get_feed_label() );
		$this->assertSame( "gla_{$stale_product_id}", $results[0]['input']->get_offer_id() );
	}

	public function test_generate_mapi_update_entries_merges_parent_and_variation_attributes() {
		$this->set_up_market_service_stubs(
			[ 'US' ],
			[
				'primary' => [
					'country'    => 'US',
					'feed_label' => 'US',
					'language'   => 'en',
				],
			]
		);

		$this->validator->expects( $this->any() )->method( 'validate' )->willReturn( [] );
		$this->rules_query->expects( $this->any() )->method( 'get_results' )->willReturn( [] );

		$attribute_manager = $this->container->get( AttributeManager::class );

		$variable  = WC_Helper_Product::create_variation_product();
		$variation = $this->wc->get_product( $variable->get_children()[0] );

		// Parent-level attribute (inherited by the variation) plus a variation-level attribute.
		$attribute_manager->update( $variable, new Brand( 'ParentBrand' ) );
		$attribute_manager->update( $variation, new Color( 'VariationColor' ) );

		$entries = $this->batch_product_helper->generate_mapi_update_entries( [ $variable ] );

		$entry = null;
		foreach ( $entries as $candidate ) {
			if ( $candidate['product']->get_id() === $variation->get_id() ) {
				$entry = $candidate;
				break;
			}
		}

		$this->assertNotNull( $entry, 'No entry generated for the variation.' );

		$attrs = $entry['input']->get_attributes();
		$this->assertSame( 'ParentBrand', $attrs['brand'] );
		$this->assertSame( 'VariationColor', $attrs['color'] );
	}

	public function test_stale_entry_generators_keep_currency_derived_keys_of_configured_markets() {
		$products         = $this->create_and_return_supported_test_products();
		$stale_product    = $products[0];
		$stale_product_id = $stale_product->get_id();

		$this->market_service->expects( $this->any() )
			->method( 'get_all_feed_labels' )
			->willReturn( [ 'US', 'BE', 'BE-EUR' ] );

		$google_ids = [
			'US'     => "en~US~gla_{$stale_product_id}",
			'BE-EUR' => "fr~BE-EUR~gla_{$stale_product_id}",
			'DK'     => "en~DK~gla_{$stale_product_id}",
		];
		$this->product_meta->update_google_ids( $stale_product, $google_ids );

		foreach ( [ 'generate_stale_products_delete_entries', 'generate_stale_countries_delete_entries' ] as $method ) {
			$results = $this->batch_product_helper->{$method}( $products );

			$this->assertCount( 1, $results, "{$method} flagged the wrong entries as stale" );
			$this->assertContains( $google_ids['DK'], array_column( $results, 'google_id' ) );
		}
	}

	public function test_secondary_market_currency_passed_to_factory() {
		$product         = WC_Helper_Product::create_simple_product();
		$real_factory    = $this->container->get( ProductFactory::class );
		$primary_adapter = $real_factory->create( $product, 'US', [], 'US', 'en' );

		$captured_secondary_args = null;

		$factory_mock = $this->createMock( ProductFactory::class );
		$factory_mock->method( 'create' )
			->willReturnCallback(
				function ( WC_Product $p, string $country, array $rules, string $feed_label, string $language, ?string $currency_override = null ) use ( $primary_adapter, $real_factory, $product, &$captured_secondary_args ) {
					if ( 'US' === $country ) {
						return $primary_adapter;
					}
					$captured_secondary_args = [
						'country'           => $country,
						'feed_label'        => $feed_label,
						'language'          => $language,
						'currency_override' => $currency_override,
					];
					return $real_factory->create( $product, $country, $rules, $feed_label, $language, $currency_override );
				}
			);

		$helper = new BatchProductHelper(
			$this->product_meta,
			$this->product_helper,
			$this->validator,
			$factory_mock,
			$this->rules_query,
			$this->market_service,
			$this->wpml,
			$this->container->get( AttributeManager::class ),
			$this->data_sources
		);

		$this->set_up_market_service_stubs(
			[ 'US', 'DE' ],
			[
				'primary' => [
					'country'    => 'US',
					'feed_label' => 'US',
					'language'   => 'en',
				],
				'de'      => [
					'country'    => 'DE',
					'feed_label' => 'DE',
					'language'   => [ 'de' ],
					'currency'   => [ 'EUR' ],
				],
			]
		);

		$this->validator->expects( $this->any() )->method( 'validate' )->willReturn( [] );
		$this->rules_query->expects( $this->any() )->method( 'get_results' )->willReturn( [] );

		$helper->generate_mapi_update_entries( [ $product ] );

		$this->assertSame( 'EUR', $captured_secondary_args['currency_override'] );
	}

	public function test_secondary_market_empty_currency_array_passes_empty_string() {
		$product         = WC_Helper_Product::create_simple_product();
		$real_factory    = $this->container->get( ProductFactory::class );
		$primary_adapter = $real_factory->create( $product, 'US', [], 'US', 'en' );

		$captured_currency_override = null;

		$factory_mock = $this->createMock( ProductFactory::class );
		$factory_mock->method( 'create' )
			->willReturnCallback(
				function ( WC_Product $p, string $country, array $rules, string $feed_label, string $language, ?string $currency_override = null ) use ( $primary_adapter, $real_factory, $product, &$captured_currency_override ) {
					if ( 'US' === $country ) {
						return $primary_adapter;
					}
					$captured_currency_override = $currency_override;
					return $real_factory->create( $product, $country, $rules, $feed_label, $language, $currency_override );
				}
			);

		$helper = new BatchProductHelper(
			$this->product_meta,
			$this->product_helper,
			$this->validator,
			$factory_mock,
			$this->rules_query,
			$this->market_service,
			$this->wpml,
			$this->container->get( AttributeManager::class ),
			$this->data_sources
		);

		$this->set_up_market_service_stubs(
			[ 'US', 'DE' ],
			[
				'primary' => [
					'country'    => 'US',
					'feed_label' => 'US',
					'language'   => 'en',
				],
				'de'      => [
					'country'    => 'DE',
					'feed_label' => 'DE',
					'language'   => [ 'de' ],
					'currency'   => [],
				],
			]
		);

		$this->validator->expects( $this->any() )->method( 'validate' )->willReturn( [] );
		$this->rules_query->expects( $this->any() )->method( 'get_results' )->willReturn( [] );

		$helper->generate_mapi_update_entries( [ $product ] );

		$this->assertSame( '', $captured_currency_override );
	}

	public function test_two_secondary_markets_each_receive_their_own_currency() {
		$product         = WC_Helper_Product::create_simple_product();
		$real_factory    = $this->container->get( ProductFactory::class );
		$primary_adapter = $real_factory->create( $product, 'US', [], 'US', 'en' );

		$captured_currencies_by_country = [];

		$factory_mock = $this->createMock( ProductFactory::class );
		$factory_mock->method( 'create' )
			->willReturnCallback(
				function ( WC_Product $p, string $country, array $rules, string $feed_label, string $language, ?string $currency_override = null ) use ( $primary_adapter, $real_factory, $product, &$captured_currencies_by_country ) {
					if ( 'US' === $country ) {
						return $primary_adapter;
					}
					$captured_currencies_by_country[ $country ] = $currency_override;
					return $real_factory->create( $product, $country, $rules, $feed_label, $language, $currency_override );
				}
			);

		$helper = new BatchProductHelper(
			$this->product_meta,
			$this->product_helper,
			$this->validator,
			$factory_mock,
			$this->rules_query,
			$this->market_service,
			$this->wpml,
			$this->container->get( AttributeManager::class ),
			$this->data_sources
		);

		$this->set_up_market_service_stubs(
			[ 'US', 'DE', 'AU' ],
			[
				'primary' => [
					'country'    => 'US',
					'feed_label' => 'US',
					'language'   => 'en',
				],
				'de'      => [
					'country'    => 'DE',
					'feed_label' => 'DE',
					'language'   => [ 'de' ],
					'currency'   => [ 'EUR' ],
				],
				'au'      => [
					'country'    => 'AU',
					'feed_label' => 'AU',
					'language'   => [ 'en' ],
					'currency'   => [ 'AUD' ],
				],
			]
		);

		$this->validator->expects( $this->any() )->method( 'validate' )->willReturn( [] );
		$this->rules_query->expects( $this->any() )->method( 'get_results' )->willReturn( [] );

		$helper->generate_mapi_update_entries( [ $product ] );

		$this->assertSame( 'EUR', $captured_currencies_by_country['DE'] );
		$this->assertSame( 'AUD', $captured_currencies_by_country['AU'] );
	}

	public function test_secondary_market_emits_a_feed_per_enabled_currency() {
		$product         = WC_Helper_Product::create_simple_product();
		$real_factory    = $this->container->get( ProductFactory::class );
		$primary_adapter = $real_factory->create( $product, 'US', [], 'US', 'en' );

		$captured_currencies_by_country = [];

		$factory_mock = $this->createMock( ProductFactory::class );
		$factory_mock->method( 'create' )
			->willReturnCallback(
				function ( WC_Product $p, string $country, array $rules, string $feed_label, string $language, ?string $currency_override = null ) use ( $primary_adapter, $real_factory, $product, &$captured_currencies_by_country ) {
					if ( 'US' === $country ) {
						return $primary_adapter;
					}
					$captured_currencies_by_country[ $country ][] = $currency_override;
					return $real_factory->create( $product, $country, $rules, $feed_label, $language, $currency_override );
				}
			);

		$helper = new BatchProductHelper(
			$this->product_meta,
			$this->product_helper,
			$this->validator,
			$factory_mock,
			$this->rules_query,
			$this->market_service,
			$this->wpml,
			$this->container->get( AttributeManager::class ),
			$this->data_sources
		);

		$this->set_up_market_service_stubs(
			[ 'US', 'DE' ],
			[
				'primary' => [
					'country'    => 'US',
					'feed_label' => 'US',
					'language'   => 'en',
				],
				'de'      => [
					'country'    => 'DE',
					'feed_label' => 'DE',
					'language'   => [ 'de' ],
					'currency'   => [ 'EUR', 'USD' ],
				],
			]
		);

		$this->validator->expects( $this->any() )->method( 'validate' )->willReturn( [] );
		$this->rules_query->expects( $this->any() )->method( 'get_results' )->willReturn( [] );

		$helper->generate_mapi_update_entries( [ $product ] );

		// One secondary feed per configured currency for the market's language. The
		// store-currency entry carries no override, so it prices exactly as a
		// single-currency market's entries always have.
		$this->assertSame( [ 'EUR', '' ], $captured_currencies_by_country['DE'] );
	}

	public function test_secondary_market_skips_only_the_currency_that_fails_validation() {
		$product         = WC_Helper_Product::create_simple_product();
		$real_factory    = $this->container->get( ProductFactory::class );
		$primary_adapter = $real_factory->create( $product, 'US', [], 'US', 'en' );

		$factory_mock = $this->createMock( ProductFactory::class );
		$factory_mock->method( 'create' )
			->willReturnCallback(
				function ( WC_Product $p, string $country, array $rules, string $feed_label, string $language, ?string $currency_override = null ) use ( $primary_adapter, $real_factory, $product ) {
					return 'US' === $country
						? $primary_adapter
						: $real_factory->create( $product, $country, $rules, $feed_label, $language, $currency_override );
				}
			);

		$helper = new BatchProductHelper(
			$this->product_meta,
			$this->product_helper,
			$this->validator,
			$factory_mock,
			$this->rules_query,
			$this->market_service,
			$this->wpml,
			$this->container->get( AttributeManager::class ),
			$this->data_sources
		);

		$this->set_up_market_service_stubs(
			[ 'US', 'DE' ],
			[
				'primary' => [
					'country'    => 'US',
					'feed_label' => 'US',
					'language'   => 'en',
				],
				'de'      => [
					'country'    => 'DE',
					'feed_label' => 'DE',
					'language'   => [ 'de' ],
					'currency'   => [ 'EUR', 'USD' ],
				],
			]
		);

		// The USD pair fails validation (e.g. the product has no USD price); EUR passes.
		// A derived secondary feed label ends with its market currency.
		$this->validator->expects( $this->any() )
			->method( 'validate' )
			->willReturnCallback(
				function ( WCProductAdapter $adapter ) {
					if ( '-USD' === substr( $adapter->getFeedLabel(), -4 ) ) {
						$violations = new ConstraintViolationList();
						$violations->add( $this->createMock( ConstraintViolation::class ) );
						return $violations;
					}

					return [];
				}
			);
		$this->rules_query->expects( $this->any() )->method( 'get_results' )->willReturn( [] );

		$labels = array_map(
			static function ( array $entry ): string {
				return $entry['input']->get_feed_label();
			},
			$helper->generate_mapi_update_entries( [ $product ] )
		);

		$secondary_labels = array_values(
			array_filter(
				$labels,
				static function ( string $label ): bool {
					return 'DE-' === substr( $label, 0, 3 );
				}
			)
		);

		// Only the valid EUR pair survives; the invalid USD pair is skipped without dropping the
		// product (its primary entry is still present).
		$this->assertContains( 'US', $labels );
		$this->assertCount( 1, $secondary_labels );
		$this->assertStringEndsWith( '-EUR', $secondary_labels[0] );
	}

	public function test_secondary_market_skipped_when_no_currency_enabled_for_language() {
		$product = WC_Helper_Product::create_simple_product();

		$this->set_up_market_service_stubs(
			[ 'US', 'DE' ],
			[
				'primary' => [
					'country'    => 'US',
					'feed_label' => 'US',
					'language'   => 'en',
				],
				'de'      => [
					'country'    => 'DE',
					'feed_label' => 'DE',
					'language'   => [ 'de' ],
					'currency'   => [ 'EUR' ],
				],
			],
			// WCML enables no currency for the market's language, so no secondary feed can be built.
			static function (): array {
				return [];
			}
		);

		$this->validator->expects( $this->any() )->method( 'validate' )->willReturn( [] );
		$this->rules_query->expects( $this->any() )->method( 'get_results' )->willReturn( [] );

		$results = $this->batch_product_helper->generate_mapi_update_entries( [ $product ] );

		// The product still syncs to its primary market; the secondary market emits nothing rather
		// than a store-currency price mislabelled for the disabled currency.
		$this->assertCount( 1, $results );
		$this->assertSame( 'US', $results[0]['input']->get_feed_label() );
	}

	public function test_primary_entry_uses_store_currency_regardless_of_market_currency_array() {
		$product = WC_Helper_Product::create_simple_product();

		$this->set_up_market_service_stubs(
			[ 'US' ],
			[
				'primary' => [
					'country'    => 'US',
					'feed_label' => 'US',
					'language'   => 'en',
					'currency'   => [ 'EUR' ],
				],
			]
		);

		$this->validator->expects( $this->any() )->method( 'validate' )->willReturn( [] );
		$this->rules_query->expects( $this->any() )->method( 'get_results' )->willReturn( [] );

		$results = $this->batch_product_helper->generate_mapi_update_entries( [ $product ] );

		// The bare-label entry prices in the store currency even though the configured
		// array does not list it; the configured currency adds its own derived entry.
		$this->assertCount( 2, $results );
		$this->assertSame( 'US', $results[0]['input']->get_feed_label() );
		$this->assertSame( get_woocommerce_currency(), $results[0]['input']->get_attributes()['price']['currencyCode'] );
		$this->assertSame( 'EUR', $results[1]['input']->get_attributes()['price']['currencyCode'] );
	}

	public function test_primary_bare_label_entry_keeps_store_currency_and_extra_currency_adds_derived_entry() {
		$product = WC_Helper_Product::create_simple_product();

		$this->set_up_market_service_stubs(
			[ 'US' ],
			[
				'primary' => [
					'country'    => 'US',
					'feed_label' => 'US',
					'language'   => 'en',
					'currency'   => [ get_woocommerce_currency(), 'EUR' ],
				],
			]
		);

		$this->validator->expects( $this->any() )->method( 'validate' )->willReturn( [] );
		$this->rules_query->expects( $this->any() )->method( 'get_results' )->willReturn( [] );

		$results = $this->batch_product_helper->generate_mapi_update_entries( [ $product ] );

		$this->assertCount( 2, $results );

		// The bare-label entry always prices in the store currency.
		$this->assertSame( 'US', $results[0]['input']->get_feed_label() );
		$this->assertSame( get_woocommerce_currency(), $results[0]['input']->get_attributes()['price']['currencyCode'] );

		// The additional currency gets its own derived-label entry with
		// converted prices; both entries stay attached to the primary country.
		$this->assertSame( 'US-EN-EUR', $results[1]['input']->get_feed_label() );
		$this->assertSame( 'EUR', $results[1]['input']->get_attributes()['price']['currencyCode'] );
		$this->assertSame( 'US', $results[1]['country'] );
	}

	public function test_invalid_primary_currency_skips_only_that_currency_feed() {
		// A validation failure isolated to one additional primary currency must not discard the
		// entries already staged for the product (the bare primary feed and any earlier valid
		// currency), nor stop its secondary markets being generated.
		$product = WC_Helper_Product::create_simple_product();

		$this->set_up_market_service_stubs(
			[ 'US', 'DE' ],
			[
				'primary' => [
					'country'    => 'US',
					'feed_label' => 'US',
					'language'   => 'en',
					'currency'   => [ get_woocommerce_currency(), 'EUR', 'GBP' ],
				],
				'de'      => [
					'country'    => 'DE',
					'feed_label' => 'DE',
					'language'   => [ 'de' ],
					'currency'   => [ 'EUR' ],
				],
			]
		);

		// Only the primary market's EUR copy fails.
		$this->validator->expects( $this->any() )
			->method( 'validate' )
			->willReturnCallback(
				function ( WCProductAdapter $adapter ) {
					if ( 'US-EN-EUR' === $adapter->getFeedLabel() ) {
						$violations = new ConstraintViolationList();
						$violations->add( $this->createMock( ConstraintViolation::class ) );
						return $violations;
					}

					return [];
				}
			);
		$this->rules_query->expects( $this->any() )->method( 'get_results' )->willReturn( [] );

		$messages = [];
		$capture  = static function ( $message ) use ( &$messages ) {
			$messages[] = $message;
		};
		add_action( 'woocommerce_gla_debug_message', $capture );

		$labels = array_map(
			static function ( array $entry ): string {
				return $entry['input']->get_feed_label();
			},
			$this->batch_product_helper->generate_mapi_update_entries( [ $product ] )
		);

		remove_action( 'woocommerce_gla_debug_message', $capture );

		$this->assertNotContains( 'US-EN-EUR', $labels );
		$this->assertContains( 'US', $labels );
		$this->assertContains( 'US-EN-GBP', $labels );
		$this->assertNotEmpty(
			array_filter(
				$labels,
				static function ( string $label ): bool {
					return 'DE-' === substr( $label, 0, 3 );
				}
			)
		);
		$this->assertNotEmpty(
			array_filter(
				$messages,
				static function ( $message ): bool {
					return false !== strpos( (string) $message, 'EUR primary market feed' );
				}
			)
		);
	}

	public function test_secondary_market_with_two_currencies_emits_one_entry_per_currency() {
		$product        = WC_Helper_Product::create_simple_product();
		$store_currency = get_woocommerce_currency();

		$this->set_up_market_service_stubs(
			[ 'US', 'AE' ],
			[
				'primary' => [
					'country'    => 'US',
					'feed_label' => 'US',
					'language'   => 'en',
				],
				'ae'      => [
					'country'    => 'AE',
					'feed_label' => 'AE',
					'language'   => [ 'en' ],
					'currency'   => [ $store_currency, 'AED' ],
				],
			]
		);

		$this->validator->expects( $this->any() )->method( 'validate' )->willReturn( [] );
		$this->rules_query->expects( $this->any() )->method( 'get_results' )->willReturn( [] );

		$results = $this->batch_product_helper->generate_mapi_update_entries( [ $product ] );

		$this->assertCount( 3, $results );

		$labels = array_map(
			static function ( array $entry ): string {
				return $entry['input']->get_feed_label();
			},
			$results
		);

		$this->assertContains( 'AE-EN-' . $store_currency, $labels );
		$this->assertContains( 'AE-EN-AED', $labels );

		foreach ( $results as $entry ) {
			if ( 'AE-EN-AED' === $entry['input']->get_feed_label() ) {
				$this->assertSame( 'AED', $entry['input']->get_attributes()['price']['currencyCode'] );
			}
			if ( 'AE-EN-' . $store_currency === $entry['input']->get_feed_label() ) {
				$this->assertSame( $store_currency, $entry['input']->get_attributes()['price']['currencyCode'] );
			}
		}
	}

	public function test_unconvertible_currency_skips_only_that_currency_entry() {
		$product        = WC_Helper_Product::create_simple_product();
		$store_currency = get_woocommerce_currency();

		// AED has no converted price, so its entry is skipped while the
		// store-currency entry and the primary entry still sync.
		$this->wpml_converted_prices['AED'] = null;

		$this->set_up_market_service_stubs(
			[ 'US', 'AE' ],
			[
				'primary' => [
					'country'    => 'US',
					'feed_label' => 'US',
					'language'   => 'en',
				],
				'ae'      => [
					'country'    => 'AE',
					'feed_label' => 'AE',
					'language'   => [ 'en' ],
					'currency'   => [ $store_currency, 'AED' ],
				],
			]
		);

		$this->validator->expects( $this->any() )->method( 'validate' )->willReturn( [] );
		$this->rules_query->expects( $this->any() )->method( 'get_results' )->willReturn( [] );

		$results = $this->batch_product_helper->generate_mapi_update_entries( [ $product ] );

		$this->assertCount( 2, $results );

		$labels = array_map(
			static function ( array $entry ): string {
				return $entry['input']->get_feed_label();
			},
			$results
		);

		$this->assertContains( 'US', $labels );
		$this->assertContains( 'AE-EN-' . $store_currency, $labels );
		$this->assertNotContains( 'AE-EN-AED', $labels );
	}

	public function test_stale_entry_generators_keep_every_configured_currency_label() {
		$products         = $this->create_and_return_supported_test_products();
		$stale_product    = $products[0];
		$stale_product_id = $stale_product->get_id();

		$this->market_service->expects( $this->any() )
			->method( 'get_all_feed_labels' )
			->willReturn( [ 'US', 'AE-EN-USD', 'AE-EN-AED' ] );

		$google_ids = [
			'AE-EN-USD' => "en~AE-EN-USD~gla_{$stale_product_id}",
			'AE-EN-AED' => "en~AE-EN-AED~gla_{$stale_product_id}",
			'DK'        => "en~DK~gla_{$stale_product_id}",
		];
		$this->product_meta->update_google_ids( $stale_product, $google_ids );

		$results = $this->batch_product_helper->generate_stale_products_delete_entries( $products );

		$this->assertCount( 1, $results );
		$this->assertContains( $google_ids['DK'], array_column( $results, 'google_id' ) );
	}

	public function test_generate_skips_unchanged_recently_synced_product() {
		$this->set_up_market_service_stubs(
			[ 'US' ],
			[
				'primary' => [
					'country'    => 'US',
					'feed_label' => 'US',
					'language'   => 'en',
				],
			]
		);

		$this->validator->expects( $this->any() )->method( 'validate' )->willReturn( [] );
		$this->rules_query->expects( $this->any() )->method( 'get_results' )->willReturn( [] );

		$product = WC_Helper_Product::create_simple_product();

		// First pass builds the entry and its payload hash.
		$entries = $this->batch_product_helper->generate_mapi_update_entries( [ $product ] );
		$this->assertCount( 1, $entries );
		$hash      = $entries[0]['hash'];
		$entry_key = $entries[0]['input']->get_content_language() . '|' . $entries[0]['input']->get_feed_label();

		// Simulate a successful sync of that payload.
		$this->product_meta->update_sync_hash( $product, [ $entry_key => $hash ] );
		$this->product_meta->update_synced_at( $product, time() );

		// Unchanged and recently synced: skipped.
		$this->assertEmpty( $this->batch_product_helper->generate_mapi_update_entries( [ $product ] ) );

		// Stale sync (older than the expiry window): not skipped, so it gets refreshed.
		$this->product_meta->update_synced_at( $product, time() - ( 26 * DAY_IN_SECONDS ) );
		$this->assertCount( 1, $this->batch_product_helper->generate_mapi_update_entries( [ $product ] ) );
	}

	public function test_force_resync_filter_includes_unchanged_product() {
		$this->set_up_market_service_stubs(
			[ 'US' ],
			[
				'primary' => [
					'country'    => 'US',
					'feed_label' => 'US',
					'language'   => 'en',
				],
			]
		);

		$this->validator->expects( $this->any() )->method( 'validate' )->willReturn( [] );
		$this->rules_query->expects( $this->any() )->method( 'get_results' )->willReturn( [] );

		$product   = WC_Helper_Product::create_simple_product();
		$entries   = $this->batch_product_helper->generate_mapi_update_entries( [ $product ] );
		$entry_key = $entries[0]['input']->get_content_language() . '|' . $entries[0]['input']->get_feed_label();
		$this->product_meta->update_sync_hash( $product, [ $entry_key => $entries[0]['hash'] ] );
		$this->product_meta->update_synced_at( $product, time() );

		// Would be skipped without the filter.
		$this->assertEmpty( $this->batch_product_helper->generate_mapi_update_entries( [ $product ] ) );

		add_filter( 'woocommerce_gla_force_product_resync', '__return_true' );
		$forced = $this->batch_product_helper->generate_mapi_update_entries( [ $product ] );
		remove_filter( 'woocommerce_gla_force_product_resync', '__return_true' );

		$this->assertCount( 1, $forced );
	}

	public function test_freshness_filter_is_clamped_to_the_expiry_window() {
		$this->set_up_market_service_stubs(
			[ 'US' ],
			[
				'primary' => [
					'country'    => 'US',
					'feed_label' => 'US',
					'language'   => 'en',
				],
			]
		);

		$this->validator->expects( $this->any() )->method( 'validate' )->willReturn( [] );
		$this->rules_query->expects( $this->any() )->method( 'get_results' )->willReturn( [] );

		$product   = WC_Helper_Product::create_simple_product();
		$entries   = $this->batch_product_helper->generate_mapi_update_entries( [ $product ] );
		$entry_key = $entries[0]['input']->get_content_language() . '|' . $entries[0]['input']->get_feed_label();
		$this->product_meta->update_sync_hash( $product, [ $entry_key => $entries[0]['hash'] ] );
		// Synced 30 days ago: past the 25-day resubmission window.
		$this->product_meta->update_synced_at( $product, time() - ( 30 * DAY_IN_SECONDS ) );

		// A freshness filter above the expiry window must not let the product be skipped,
		// or ResubmitExpiringProducts would no-op and the product could expire out of MC.
		add_filter(
			'woocommerce_gla_sync_hash_freshness',
			function () {
				return 60 * DAY_IN_SECONDS;
			}
		);
		$entries2 = $this->batch_product_helper->generate_mapi_update_entries( [ $product ] );
		remove_all_filters( 'woocommerce_gla_sync_hash_freshness' );

		$this->assertCount( 1, $entries2 );
	}

	public function test_skip_is_scoped_to_the_entry_own_key() {
		$this->set_up_market_service_stubs(
			[ 'US', 'DE' ],
			[
				'primary' => [
					'country'    => 'US',
					'feed_label' => 'US',
					'language'   => 'en',
				],
				'de'      => [
					'country'    => 'DE',
					'feed_label' => 'DE',
					'language'   => [ 'de' ],
				],
			]
		);

		$this->validator->expects( $this->any() )->method( 'validate' )->willReturn( [] );
		$this->rules_query->expects( $this->any() )->method( 'get_results' )->willReturn( [] );

		$product = WC_Helper_Product::create_simple_product();
		$entries = $this->batch_product_helper->generate_mapi_update_entries( [ $product ] );
		$this->assertCount( 2, $entries );

		$first_key    = $entries[0]['input']->get_content_language() . '|' . $entries[0]['input']->get_feed_label();
		$second_label = $entries[1]['input']->get_feed_label();

		// Only the first entry was successfully synced.
		$this->product_meta->update_sync_hash( $product, [ $first_key => $entries[0]['hash'] ] );
		$this->product_meta->update_synced_at( $product, time() );

		// The first entry is skipped by its own key; the second entry is not
		// skipped by the first entry's hash and is generated again.
		$remaining = $this->batch_product_helper->generate_mapi_update_entries( [ $product ] );
		$this->assertCount( 1, $remaining );
		$this->assertSame( $second_label, $remaining[0]['input']->get_feed_label() );
	}

	public function test_legacy_string_sync_hash_never_matches() {
		$this->set_up_market_service_stubs(
			[ 'US' ],
			[
				'primary' => [
					'country'    => 'US',
					'feed_label' => 'US',
					'language'   => 'en',
				],
			]
		);

		$this->validator->expects( $this->any() )->method( 'validate' )->willReturn( [] );
		$this->rules_query->expects( $this->any() )->method( 'get_results' )->willReturn( [] );

		$product = WC_Helper_Product::create_simple_product();
		$entries = $this->batch_product_helper->generate_mapi_update_entries( [ $product ] );
		$this->assertCount( 1, $entries );

		// Store the matching hash in the legacy single-string format, written
		// directly because the meta handler now casts values to the keyed array.
		$product->update_meta_data( '_wc_gla_sync_hash', $entries[0]['hash'] );
		$product->save_meta_data();
		$this->product_meta->update_synced_at( $product, time() );

		// A legacy value never matches, so the entry is resubmitted and the
		// meta migrates to the keyed format on the next successful sync.
		$this->assertCount( 1, $this->batch_product_helper->generate_mapi_update_entries( [ $product ] ) );
	}

	public function test_data_source_name_change_invalidates_the_stored_hash() {
		$this->set_up_market_service_stubs(
			[ 'US' ],
			[
				'primary' => [
					'country'    => 'US',
					'feed_label' => 'US',
					'language'   => 'en',
				],
			]
		);

		$this->validator->expects( $this->any() )->method( 'validate' )->willReturn( [] );
		$this->rules_query->expects( $this->any() )->method( 'get_results' )->willReturn( [] );

		$build_helper = function ( string $data_source_name ): BatchProductHelper {
			$data_sources = $this->createMock( MapiDataSourcesService::class );
			$data_sources->method( 'ensure_data_source_for' )->willReturn( $data_source_name );

			return new BatchProductHelper(
				$this->product_meta,
				$this->product_helper,
				$this->validator,
				$this->product_factory,
				$this->rules_query,
				$this->market_service,
				$this->wpml,
				$this->container->get( AttributeManager::class ),
				$data_sources
			);
		};

		$product       = WC_Helper_Product::create_simple_product();
		$helper_first  = $build_helper( 'accounts/1/dataSources/111' );
		$entries_first = $helper_first->generate_mapi_update_entries( [ $product ] );
		$this->assertCount( 1, $entries_first );

		$entry_key = $entries_first[0]['input']->get_content_language() . '|' . $entries_first[0]['input']->get_feed_label();
		$this->product_meta->update_sync_hash( $product, [ $entry_key => $entries_first[0]['hash'] ] );
		$this->product_meta->update_synced_at( $product, time() );

		// Same data source: the unchanged payload is skipped.
		$this->assertEmpty( $helper_first->generate_mapi_update_entries( [ $product ] ) );

		// The data source was recreated (or the account changed): the resource
		// name differs, the hash no longer matches, and the entry resubmits.
		$helper_second  = $build_helper( 'accounts/1/dataSources/222' );
		$entries_second = $helper_second->generate_mapi_update_entries( [ $product ] );
		$this->assertCount( 1, $entries_second );
		$this->assertNotSame( $entries_first[0]['hash'], $entries_second[0]['hash'] );
	}

	public function test_data_source_resolution_failure_skips_the_product() {
		$this->set_up_market_service_stubs(
			[ 'US' ],
			[
				'primary' => [
					'country'    => 'US',
					'feed_label' => 'US',
					'language'   => 'en',
				],
			]
		);

		$this->validator->expects( $this->any() )->method( 'validate' )->willReturn( [] );
		$this->rules_query->expects( $this->any() )->method( 'get_results' )->willReturn( [] );

		$this->data_sources->method( 'ensure_data_source_for' )
			->willThrowException( new MerchantApiException( 500, [], __METHOD__ ) );

		$product = WC_Helper_Product::create_simple_product();

		// The resolution failure is caught per product inside
		// generate_mapi_update_entries(); the product is skipped and the
		// exception does not fail the whole batch.
		$this->assertSame( [], $this->batch_product_helper->generate_mapi_update_entries( [ $product ] ) );
	}

	/**
	 * @return WC_Product[]
	 */
	public function create_and_return_supported_test_products(): array {
		$variable        = WC_Helper_Product::create_variation_product();
		$test_products   = array_map( 'wc_get_product', $variable->get_children() );
		$test_products[] = WC_Helper_Product::create_simple_product();

		return $test_products;
	}

	/**
	 * Configure the MarketService mock so the helper can build primary + secondary entries.
	 *
	 * The primary entry's feed_label is used as the get_main_feed_label() stub, then the
	 * primary's country/feed_label keys are nulled to match the real MarketService contract:
	 * get_primary_market() always returns null for both, and the primary feed label is only
	 * exposed via get_main_feed_label().
	 *
	 * @param string[]      $all_countries           Return value for get_all_countries().
	 * @param array[]       $markets                 Return value for get_participating_markets() keyed by market ID.
	 * @param callable|null $currencies_for_language Overrides the get_market_currencies_for_language() stub; defaults to every configured currency enabled.
	 */
	private function set_up_market_service_stubs( array $all_countries, array $markets, ?callable $currencies_for_language = null ): void {
		$main_feed_label = $markets['primary']['feed_label'];

		$markets['primary']['country']    = null;
		$markets['primary']['feed_label'] = null;

		$this->market_service->method( 'get_primary_market' )->willReturn( $markets['primary'] );
		$this->market_service->method( 'get_all_countries' )->willReturn( $all_countries );
		$this->market_service->method( 'get_participating_markets' )->willReturn( $markets );
		$this->market_service->method( 'get_main_feed_label' )->willReturn( $main_feed_label );

		// Mirrors MarketService::get_participating_currencies() with every
		// configured currency treated as convertible: the market's configured
		// currencies without duplicates, or the store currency when none are
		// configured.
		$this->market_service->method( 'get_participating_currencies' )->willReturnCallback(
			static function ( array $market ): array {
				$configured = is_array( $market['currency'] ?? null )
					? $market['currency']
					: [ $market['currency'] ?? '' ];

				$currencies = array_values( array_unique( array_filter( array_map( 'strval', $configured ) ) ) );

				return empty( $currencies ) ? [ get_woocommerce_currency() ] : $currencies;
			}
		);

		// Non-store-currency entries are skipped when no converted price is
		// available, so the WPML stub returns the product's own price for any
		// requested currency; a test can mark a currency unconvertible via
		// $this->wpml_converted_prices before calling this helper.
		$this->wpml->method( 'get_product_price_in_currency' )->willReturnCallback(
			function ( WC_Product $product, string $currency ): ?float {
				if ( array_key_exists( $currency, $this->wpml_converted_prices ) ) {
					return $this->wpml_converted_prices[ $currency ];
				}

				$price = $product->get_regular_price();

				return '' === $price ? null : (float) $price;
			}
		);

		// Mirrors MarketService::get_market_feed_label(): the stored label plus
		// the uppercase two-letter language code plus the uppercase currency,
		// falling back to the site language and the store currency when empty.
		$this->market_service->method( 'get_market_feed_label' )->willReturnCallback(
			static function ( string $base_feed_label, string $language, string $currency ): string {
				if ( '' === $language ) {
					$language = substr( get_locale(), 0, 2 );
				}
				if ( '' === $currency ) {
					$currency = get_woocommerce_currency();
				}

				return strtoupper( $base_feed_label . '-' . substr( $language, 0, 2 ) . '-' . $currency );
			}
		);

		// Mirrors get_market_currencies_for_language() with WPML inactive: all configured currencies
		// enabled, or the store currency when none are configured.
		$this->market_service->method( 'get_market_currencies_for_language' )->willReturnCallback(
			$currencies_for_language ?? static function ( array $market ): array {
				$currencies = is_array( $market['currency'] ?? null )
					? array_values( array_filter( array_map( 'strval', $market['currency'] ) ) )
					: [];

				return empty( $currencies ) ? [ '' ] : $currencies;
			}
		);
	}

	/**
	 * Resolves variable products to their variation IDs; passes simple products through.
	 *
	 * @param WC_Product[] $products
	 * @return int[]
	 */
	private function flatten_to_simple_product_ids( array $products ): array {
		$ids = [];
		foreach ( $products as $product ) {
			if ( $product instanceof WC_Product_Variable ) {
				foreach ( $product->get_children() as $child_id ) {
					$ids[] = $child_id;
				}
			} else {
				$ids[] = $product->get_id();
			}
		}
		return $ids;
	}

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->market_service       = $this->createMock( MarketService::class );
		$this->wpml                 = $this->createMock( WPML::class );
		$this->validator            = $this->createMock( ValidatorInterface::class );
		$this->rules_query          = $this->createMock( AttributeMappingRulesQuery::class );
		$this->data_sources         = $this->createMock( MapiDataSourcesService::class );
		$this->product_meta         = $this->container->get( ProductMetaHandler::class );
		$this->wc                   = $this->container->get( WC::class );
		$this->product_factory      = $this->container->get( ProductFactory::class );
		$this->product_helper       = new ProductHelper( $this->product_meta, $this->wc, $this->market_service );
		$this->batch_product_helper = new BatchProductHelper(
			$this->product_meta,
			$this->product_helper,
			$this->validator,
			$this->product_factory,
			$this->rules_query,
			$this->market_service,
			$this->wpml,
			$this->container->get( AttributeManager::class ),
			$this->data_sources
		);
	}
}
