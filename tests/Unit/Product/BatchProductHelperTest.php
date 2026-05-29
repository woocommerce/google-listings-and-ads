<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Product;

use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\AttributeMappingRulesQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidClass;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\BatchInvalidProductEntry;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\BatchProductEntry;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\BatchProductIDRequestEntry;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\BatchProductRequestEntry;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MarketService;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\TargetAudience;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\BatchProductHelper;
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

	/** @var MockObject|TargetAudience $target_audience */
	protected $target_audience;

	/** @var BatchProductHelper $batch_product_helper */
	protected $batch_product_helper;

	/** @var MockObject|MarketService $market_service */
	protected $market_service;

	/** @var AttributeMappingRulesQuery $rules_query */
	protected $rules_query;

	/** @var WC $wc */
	protected $wc;

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

	public function test_validate_and_generate_update_request_entries() {
		$products = $this->create_and_return_supported_test_products();

		$this->target_audience->expects( $this->any() )
			->method( 'get_main_target_country' )
			->willReturn( 'US' );
		$this->validator->expects( $this->any() )
			->method( 'validate' )
			->willReturn( [] );

		$this->rules_query->expects( $this->any() )
			->method( 'get_results' )
			->willReturn( [] );

		$results = $this->batch_product_helper->validate_and_generate_update_request_entries( $products );

		// the number of results can be bigger because of variable products
		$this->assertGreaterThanOrEqual( \count( $products ), \count( $results ) );

		$this->assertContainsOnlyInstancesOf( BatchProductRequestEntry::class, $results );

		// the products (including variations if a variable product) sent to the method should ALL be returned as results
		$results_product_ids = array_map(
			function ( BatchProductRequestEntry $request_entry ) {
				return $request_entry->get_wc_product_id();
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

	public function test_validate_and_generate_update_request_entries_skips_invalid_product() {
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

		$this->target_audience->expects( $this->any() )
			->method( 'get_main_target_country' )
			->willReturn( 'US' );

		$results = $this->batch_product_helper->validate_and_generate_update_request_entries( $products );

		$results_product_ids = array_map(
			function ( BatchProductRequestEntry $request_entry ) {
				return $request_entry->get_wc_product_id();
			},
			$results
		);

		$this->assertNotContains( $invalid_product->get_id(), $results_product_ids );
	}

	public function test_validate_and_generate_update_request_entries_skips_not_sync_ready() {
		$products = $this->create_and_return_supported_test_products();

		// skip one product from the list
		$skipped_product = $products[0];
		if ( $skipped_product instanceof WC_Product_Variation ) {
			$this->product_meta->update_visibility( wc_get_product( $skipped_product->get_parent_id() ), ChannelVisibility::DONT_SYNC_AND_SHOW );
		} else {
			$this->product_meta->update_visibility( $skipped_product, ChannelVisibility::DONT_SYNC_AND_SHOW );
		}

		$this->target_audience->expects( $this->any() )
			->method( 'get_main_target_country' )
			->willReturn( 'US' );
		$this->validator->expects( $this->any() )
			->method( 'validate' )
			->willReturn( [] );
		$this->rules_query->expects( $this->any() )
			->method( 'get_results' )
			->willReturn( [] );

		$results = $this->batch_product_helper->validate_and_generate_update_request_entries( $products );

		$results_product_ids = array_map(
			function ( BatchProductRequestEntry $request_entry ) {
				return $request_entry->get_wc_product_id();
			},
			$results
		);

		$this->assertNotContains( $skipped_product->get_id(), $results_product_ids );
	}

	public function test_validate_and_generate_update_request_entries_including_invalid_product() {
		$products = [
			$this->generate_simple_product_mock(),
			new BatchProductEntry( 0, null ),
		];
		$this->expectException( InvalidClass::class );
		$this->batch_product_helper->validate_and_generate_update_request_entries( $products );
	}

	public function test_generate_stale_products_request_entries() {
		$products         = $this->create_and_return_supported_test_products();
		$stale_product    = $products[0];
		$stale_product_id = $stale_product->get_id();

		$this->target_audience->expects( $this->once() )
			->method( 'get_target_countries' )
			->willReturn( [ 'US' ] );
		$this->target_audience->expects( $this->any() )
			->method( 'get_main_target_country' )
			->willReturn( 'US' );

		$stale_google_ids = [
			'AU' => "online:en:AU:gla_{$stale_product_id}",
			'DK' => "online:en:DK:gla_{$stale_product_id}",
			'US' => "online:en:US:gla_{$stale_product_id}",
		];
		$this->product_meta->update_google_ids( $stale_product, $stale_google_ids );

		$results = $this->batch_product_helper->generate_stale_products_request_entries( $products );

		$this->assertCount( 2, $results );
		$this->assertContainsOnlyInstancesOf( BatchProductIDRequestEntry::class, $results );
		$this->assertArrayHasKey( $stale_google_ids['AU'], $results );
		$this->assertArrayHasKey( $stale_google_ids['DK'], $results );

		foreach ( $results as $request_entry ) {
			$this->assertEquals( $stale_product_id, $request_entry->get_wc_product_id() );
		}
	}

	public function test_generate_stale_countries_request_entries() {
		$products         = $this->create_and_return_supported_test_products();
		$stale_product    = $products[0];
		$stale_product_id = $stale_product->get_id();

		$this->target_audience->expects( $this->once() )
			->method( 'get_main_target_country' )
			->willReturn( 'US' );

		$stale_google_ids = [
			'AU' => "online:en:AU:gla_{$stale_product_id}",
			'DK' => "online:en:DK:gla_{$stale_product_id}",
			'US' => "online:en:US:gla_{$stale_product_id}",
		];
		$this->product_meta->update_google_ids( $stale_product, $stale_google_ids );

		$results = $this->batch_product_helper->generate_stale_countries_request_entries( $products );

		$this->assertCount( 2, $results );
		$this->assertContainsOnlyInstancesOf( BatchProductIDRequestEntry::class, $results );
		$this->assertArrayHasKey( $stale_google_ids['AU'], $results );
		$this->assertArrayHasKey( $stale_google_ids['DK'], $results );

		foreach ( $results as $request_entry ) {
			$this->assertEquals( $stale_product_id, $request_entry->get_wc_product_id() );
		}
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
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->target_audience = $this->createMock( TargetAudience::class );
		$this->validator       = $this->createMock( ValidatorInterface::class );
		$this->rules_query     = $this->createMock( AttributeMappingRulesQuery::class );
		$this->market_service  = $this->createMock( MarketService::class );
		// Default: non-multilingual mode.
		$this->market_service->expects( $this->any() )
							 ->method( 'has_multilingual_support' )
							 ->willReturn( false );
		$this->product_meta         = $this->container->get( ProductMetaHandler::class );
		$this->product_factory      = $this->container->get( ProductFactory::class );
		$this->wc                   = $this->container->get( WC::class );
		$this->product_helper       = new ProductHelper( $this->product_meta, $this->wc, $this->target_audience, $this->market_service );
		$this->batch_product_helper = new BatchProductHelper( $this->product_meta, $this->product_helper, $this->validator, $this->product_factory, $this->target_audience, $this->rules_query, $this->market_service );
	}

	public function test_generate_stale_products_request_entries_multilingual_mode() {
		$products         = $this->create_and_return_supported_test_products();
		$stale_product    = $products[0];
		$stale_product_id = $stale_product->get_id();

		$market_service = $this->createMock( MarketService::class );
		$market_service->expects( $this->any() )
					   ->method( 'has_multilingual_support' )
					   ->willReturn( true );
		$market_service->expects( $this->any() )
					   ->method( 'get_markets' )
					   ->willReturn(
						   [
							   'primary' => [
								   'language' => [ 'en' ],
								   'currency' => [ 'USD' ],
							   ],
						   ]
					   );

		$product_helper       = new ProductHelper( $this->product_meta, $this->wc, $this->target_audience, $market_service );
		$batch_product_helper = new BatchProductHelper( $this->product_meta, $product_helper, $this->validator, $this->product_factory, $this->target_audience, $this->rules_query, $market_service );

		// Simulate existing google_ids keyed by feedLabel — "fr-EUR" is stale, "en-USD" is active.
		$google_ids = [
			'en-USD' => "online:en:US:gla_{$stale_product_id}",
			'fr-EUR' => "online:fr:FR:gla_{$stale_product_id}",
		];
		$this->product_meta->update_google_ids( $stale_product, $google_ids );

		$results = $batch_product_helper->generate_stale_products_request_entries( $products );

		$this->assertCount( 1, $results );
		$this->assertArrayHasKey( $google_ids['fr-EUR'], $results );
	}

	public function test_generate_stale_products_request_entries_multilingual_mode_pre_existing_country_keys_are_stale() {
		$products         = $this->create_and_return_supported_test_products();
		$stale_product    = $products[0];
		$stale_product_id = $stale_product->get_id();

		$market_service = $this->createMock( MarketService::class );
		$market_service->expects( $this->any() )
					   ->method( 'has_multilingual_support' )
					   ->willReturn( true );
		$market_service->expects( $this->any() )
					   ->method( 'get_markets' )
					   ->willReturn(
						   [
							   'primary' => [
								   'language' => [ 'en' ],
								   'currency' => [ 'USD' ],
							   ],
						   ]
					   );

		$product_helper       = new ProductHelper( $this->product_meta, $this->wc, $this->target_audience, $market_service );
		$batch_product_helper = new BatchProductHelper( $this->product_meta, $product_helper, $this->validator, $this->product_factory, $this->target_audience, $this->rules_query, $market_service );

		// Pre-migration google_ids keyed by country code — all are stale in multilingual mode.
		$google_ids = [
			'US' => "online:en:US:gla_{$stale_product_id}",
			'AU' => "online:en:AU:gla_{$stale_product_id}",
		];
		$this->product_meta->update_google_ids( $stale_product, $google_ids );

		$results = $batch_product_helper->generate_stale_products_request_entries( $products );

		$this->assertCount( 2, $results );
	}

	public function test_validate_and_generate_update_request_entries_multilingual_uses_per_market_country() {
		$product = WC_Helper_Product::create_simple_product();

		$market_service = $this->createMock( MarketService::class );
		$market_service->method( 'has_multilingual_support' )->willReturn( true );
		$market_service->method( 'get_markets' )->willReturn(
			[
				'primary' => [
					'id'        => 'primary',
					'countries' => [ 'US' ],
					'language'  => [ 'en' ],
					'currency'  => [ 'USD' ],
				],
				'FR'      => [
					// No 'id' key — secondary markets never have 'id' => 'primary'.
					// get_markets() also adds 'countries' => ['FR'] to secondary markets,
					// so this mirrors real data to confirm we don't accidentally use the primary branch.
					'country'   => 'FR',
					'countries' => [ 'FR' ],
					'language'  => [ 'fr' ],
					'currency'  => [ 'EUR' ],
				],
			]
		);

		$this->target_audience->method( 'get_main_target_country' )->willReturn( 'US' );
		$this->target_audience->method( 'get_target_countries' )->willReturn( [ 'US' ] );

		// Capture args passed to create_for_market for each pair.
		$factory       = $this->createMock( ProductFactory::class );
		$adapter       = $this->createMock( WCProductAdapter::class );
		$captured_args = [];
		$factory->method( 'create_for_market' )
			->willReturnCallback(
				function ( $wc_product, string $target_country, array $rules, string $feed_label ) use ( $adapter, &$captured_args ) {
					$captured_args[] = [ 'target_country' => $target_country, 'feed_label' => $feed_label ];
					return $adapter;
				}
			);

		$market_service->method( 'get_product_in_language' )->willReturn( null );
		$this->validator->method( 'validate' )->willReturn( [] );
		$this->rules_query->method( 'get_results' )->willReturn( [] );

		$product_helper       = new ProductHelper( $this->product_meta, $this->wc, $this->target_audience, $market_service );
		$batch_product_helper = new BatchProductHelper( $this->product_meta, $product_helper, $this->validator, $factory, $this->target_audience, $this->rules_query, $market_service );

		$batch_product_helper->validate_and_generate_update_request_entries( [ $product ] );

		$this->assertCount( 2, $captured_args );

		$by_feed_label = array_column( $captured_args, 'target_country', 'feed_label' );
		// Primary market (en + USD) must use main_target_country.
		$this->assertSame( 'US', $by_feed_label['en-USD'] );
		// Secondary market (fr + EUR) must use the market's own country, not main_target_country.
		$this->assertSame( 'FR', $by_feed_label['fr-EUR'] );
	}

	public function test_validate_and_generate_update_request_entries_multilingual_uses_translated_product(): void {
		$original_product   = WC_Helper_Product::create_simple_product();
		$translated_product = WC_Helper_Product::create_simple_product();

		$market_service = $this->createMock( MarketService::class );
		$market_service->method( 'has_multilingual_support' )->willReturn( true );
		$market_service->method( 'get_markets' )->willReturn(
			[
				'primary' => [
					'id'       => 'primary',
					'language' => [ 'en' ],
					'currency' => [ 'USD' ],
				],
				'FR'      => [
					'country'  => 'FR',
					'language' => [ 'fr' ],
					'currency' => [ 'EUR' ],
				],
			]
		);

		$this->target_audience->method( 'get_main_target_country' )->willReturn( 'US' );
		$this->target_audience->method( 'get_target_countries' )->willReturn( [ 'US' ] );

		// Return translated product only for 'fr'; return null for 'en' (no translation needed).
		$market_service->method( 'get_product_in_language' )
			->willReturnCallback(
				function ( $product, string $language ) use ( $translated_product ) {
					return 'fr' === $language ? $translated_product : null;
				}
			);

		$factory          = $this->createMock( ProductFactory::class );
		$adapter          = $this->createMock( WCProductAdapter::class );
		$captured_products = [];
		$factory->method( 'create_for_market' )
			->willReturnCallback(
				function ( $wc_product, string $target_country, array $rules, string $feed_label ) use ( $adapter, &$captured_products ) {
					$captured_products[ $feed_label ] = $wc_product;
					return $adapter;
				}
			);

		$this->validator->method( 'validate' )->willReturn( [] );
		$this->rules_query->method( 'get_results' )->willReturn( [] );

		$product_helper       = new ProductHelper( $this->product_meta, $this->wc, $this->target_audience, $market_service );
		$batch_product_helper = new BatchProductHelper( $this->product_meta, $product_helper, $this->validator, $factory, $this->target_audience, $this->rules_query, $market_service );

		$batch_product_helper->validate_and_generate_update_request_entries( [ $original_product ] );

		// en-USD pair has no translation — must use the original product.
		$this->assertSame( $original_product->get_id(), $captured_products['en-USD']->get_id() );
		// fr-EUR pair has a translation — must use the translated product, not the original.
		$this->assertSame( $translated_product->get_id(), $captured_products['fr-EUR']->get_id() );
	}

	public function test_validate_and_generate_update_request_entries_multilingual_aggregates_shared_feed_label_shipping_countries(): void {
		$product = WC_Helper_Product::create_simple_product();

		$market_service = $this->createMock( MarketService::class );
		$market_service->method( 'has_multilingual_support' )->willReturn( true );
		$market_service->method( 'get_markets' )->willReturn(
			[
				'primary' => [
					'id'       => 'primary',
					'language' => [ 'en' ],
					'currency' => [ 'USD' ],
				],
				'CM'      => [
					'country'  => 'CM',
					'language' => [ 'fr' ],
					'currency' => [ 'EUR' ],
				],
				'UG'      => [
					'country'  => 'UG',
					'language' => [ 'fr' ],
					'currency' => [ 'EUR' ],
				],
			]
		);

		$this->target_audience->method( 'get_main_target_country' )->willReturn( 'US' );
		$this->target_audience->method( 'get_target_countries' )->willReturn( [ 'US' ] );
		$market_service->method( 'get_product_in_language' )->willReturn( null );

		$captured_shipping = [];
		$factory           = $this->createMock( ProductFactory::class );
		$factory->method( 'create_for_market' )
			->willReturnCallback(
				function ( $wc_product, string $target_country, array $rules, string $feed_label ) use ( &$captured_shipping ) {
					$adapter = $this->createMock( WCProductAdapter::class );
					$adapter->method( 'add_shipping_country' )
						->willReturnCallback(
							function ( string $country ) use ( $feed_label, &$captured_shipping ) {
								$captured_shipping[ $feed_label ][] = $country;
							}
						);
					return $adapter;
				}
			);

		$this->validator->method( 'validate' )->willReturn( [] );
		$this->rules_query->method( 'get_results' )->willReturn( [] );

		$product_helper       = new ProductHelper( $this->product_meta, $this->wc, $this->target_audience, $market_service );
		$batch_product_helper = new BatchProductHelper( $this->product_meta, $product_helper, $this->validator, $factory, $this->target_audience, $this->rules_query, $market_service );

		$batch_product_helper->validate_and_generate_update_request_entries( [ $product ] );

		// Two distinct feed-label pairs: en-USD (primary) and fr-EUR (CM + UG merged).
		$this->assertCount( 2, $captured_shipping );

		// Primary market ships to its single target country.
		$this->assertSame( [ 'US' ], $captured_shipping['en-USD'] );

		// CM and UG both map to fr-EUR — both countries must be present.
		$this->assertEqualSets( [ 'CM', 'UG' ], $captured_shipping['fr-EUR'] );
	}

	public function test_generate_stale_countries_request_entries_returns_empty_in_multilingual_mode() {
		$products = $this->create_and_return_supported_test_products();

		$market_service = $this->createMock( MarketService::class );
		$market_service->expects( $this->any() )
					   ->method( 'has_multilingual_support' )
					   ->willReturn( true );

		$product_helper       = new ProductHelper( $this->product_meta, $this->wc, $this->target_audience, $market_service );
		$batch_product_helper = new BatchProductHelper( $this->product_meta, $product_helper, $this->validator, $this->product_factory, $this->target_audience, $this->rules_query, $market_service );

		$results = $batch_product_helper->generate_stale_countries_request_entries( $products );

		$this->assertEmpty( $results );
	}
}
