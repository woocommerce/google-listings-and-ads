<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Product;

use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\AttributeMappingRulesQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidClass;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\BatchInvalidProductEntry;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\BatchProductEntry;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\BatchProductIDRequestEntry;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\BatchProductRequestEntry;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MarketService;
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

	/** @var MockObject|MarketService $market_service */
	protected $market_service;

	/** @var BatchProductHelper $batch_product_helper */
	protected $batch_product_helper;

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

		$this->market_service->expects( $this->any() )
			->method( 'get_primary_market' )
			->willReturn(
				[
					'country'    => 'US',
					'feed_label' => 'US',
					'language'   => 'en',
				]
			);

		$this->market_service->expects( $this->any() )
			->method( 'get_all_countries' )
			->willReturn( [ 'US' ] );

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

	public function test_validate_and_generate_update_request_entries_primary_only_single_entry_per_product() {
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

		$results = $this->batch_product_helper->validate_and_generate_update_request_entries( $products );

		$param_product_ids = $this->flatten_to_simple_product_ids( $products );

		$this->assertCount( count( $param_product_ids ), $results );

		foreach ( $results as $entry ) {
			$this->assertSame( 'US', $entry->get_product()->getFeedLabel() );
			$this->assertSame( 'US', $entry->get_product()->getTargetCountry() );
		}
	}

	public function test_validate_and_generate_update_request_entries_primary_plus_secondary_two_entries_per_product() {
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

		$results = $this->batch_product_helper->validate_and_generate_update_request_entries( $products );

		$param_product_ids = $this->flatten_to_simple_product_ids( $products );

		$this->assertCount( count( $param_product_ids ) * 2, $results );

		$feed_labels_by_product = [];
		foreach ( $results as $entry ) {
			$wc_id                              = $entry->get_wc_product_id();
			$feed_labels_by_product[ $wc_id ][] = $entry->get_product()->getFeedLabel();
		}

		foreach ( $feed_labels_by_product as $labels ) {
			sort( $labels );
			$this->assertSame( [ 'DE', 'US' ], $labels );
		}
	}

	public function test_validate_and_generate_update_request_entries_two_secondaries_three_entries_per_product() {
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

		$results = $this->batch_product_helper->validate_and_generate_update_request_entries( $products );

		$param_product_ids = $this->flatten_to_simple_product_ids( $products );

		$this->assertCount( count( $param_product_ids ) * 3, $results );
	}

	public function test_validate_and_generate_update_request_entries_secondary_shipping_scoped_to_own_country() {
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

		$results = $this->batch_product_helper->validate_and_generate_update_request_entries( $products );

		$this->assertCount( 2, $results );

		$by_label = [];
		foreach ( $results as $entry ) {
			$by_label[ $entry->get_product()->getFeedLabel() ] = $entry->get_product()->getShipping();
		}

		$secondary_shipping_countries = array_map(
			static function ( $s ) {
				return $s->getCountry();
			},
			$by_label['DE']
		);
		$this->assertEqualSets( [ 'DE' ], $secondary_shipping_countries );

		$primary_shipping_countries = array_map(
			static function ( $s ) {
				return $s->getCountry();
			},
			$by_label['US']
		);
		// Primary's add_shipping_country walk is unchanged from GOOWOO-591: all countries
		// (including the secondary's) plus the product's own target country are present.
		foreach ( [ 'US', 'CA', 'DE' ] as $expected_country ) {
			$this->assertContains( $expected_country, $primary_shipping_countries );
		}
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

		$this->market_service->expects( $this->any() )
			->method( 'get_primary_market' )
			->willReturn(
				[
					'country'    => 'US',
					'feed_label' => 'US',
					'language'   => 'en',
				]
			);

		$this->market_service->expects( $this->any() )
			->method( 'get_all_countries' )
			->willReturn( [ 'US' ] );

		$results = $this->batch_product_helper->validate_and_generate_update_request_entries( $products );

		$results_product_ids = array_map(
			function ( BatchProductRequestEntry $request_entry ) {
				return $request_entry->get_wc_product_id();
			},
			$results
		);

		$this->assertNotContains( $invalid_product->get_id(), $results_product_ids );
	}

	public function test_validate_and_generate_update_request_entries_secondary_throw_discards_primary() {
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
			$this->market_service
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

		$results = $helper->validate_and_generate_update_request_entries( [ $product ] );

		// Nothing should land: the primary entry must not survive a secondary failure.
		$this->assertCount( 0, $results );
	}

	public function test_validate_and_generate_update_request_entries_skips_product_when_secondary_invalid() {
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
		$this->validator->expects( $this->any() )
			->method( 'validate' )
			->willReturnCallback(
				function ( WCProductAdapter $adapter ) {
					if ( 'DE' === $adapter->getFeedLabel() ) {
						$violations = new ConstraintViolationList();
						$violations->add( $this->createMock( ConstraintViolation::class ) );
						return $violations;
					}

					return [];
				}
			);

		$this->rules_query->expects( $this->any() )->method( 'get_results' )->willReturn( [] );

		$results = $this->batch_product_helper->validate_and_generate_update_request_entries( [ $product ] );

		$this->assertCount( 0, $results );

		// Re-fetch the product so the meta read does not hit the original
		// instance's stale cache — mark_as_invalid persisted against a fresh
		// instance via ProductHelper::get_wc_product().
		$refreshed = $this->wc->get_product( $product->get_id() );
		$this->assertNotEmpty( $this->product_meta->get_errors( $refreshed ) );
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

		$this->market_service->expects( $this->any() )
			->method( 'get_primary_market' )
			->willReturn(
				[
					'country'    => 'US',
					'feed_label' => 'US',
					'language'   => 'en',
				]
			);

		$this->market_service->expects( $this->any() )
			->method( 'get_all_countries' )
			->willReturn( [ 'US' ] );

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

		$this->market_service->expects( $this->once() )
			->method( 'get_all_feed_labels' )
			->willReturn( [ 'US' ] );

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

		$this->market_service->expects( $this->once() )
			->method( 'get_main_feed_label' )
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
	 * Configure the MarketService mock so the helper can build primary + secondary entries.
	 *
	 * @param string[] $all_countries Return value for get_all_countries().
	 * @param array[]  $markets       Return value for get_markets() keyed by market ID.
	 */
	private function set_up_market_service_stubs( array $all_countries, array $markets ): void {
		$this->market_service->method( 'get_primary_market' )->willReturn( $markets['primary'] );
		$this->market_service->method( 'get_all_countries' )->willReturn( $all_countries );
		$this->market_service->method( 'get_markets' )->willReturn( $markets );
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
		$this->validator            = $this->createMock( ValidatorInterface::class );
		$this->rules_query          = $this->createMock( AttributeMappingRulesQuery::class );
		$this->product_meta         = $this->container->get( ProductMetaHandler::class );
		$this->product_factory      = $this->container->get( ProductFactory::class );
		$this->wc                   = $this->container->get( WC::class );
		$this->product_helper       = new ProductHelper( $this->product_meta, $this->wc, $this->market_service );
		$this->batch_product_helper = new BatchProductHelper( $this->product_meta, $this->product_helper, $this->validator, $this->product_factory, $this->rules_query, $this->market_service );
	}
}
