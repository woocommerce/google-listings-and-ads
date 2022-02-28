<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Product;

use Automattic\WooCommerce\GoogleListingsAndAds\Product\FilteredProductList;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductMetaHandler;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductSyncer;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\ContainerAwareUnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\ProductTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\ChannelVisibility;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\MCStatus;
use WC_Helper_Product;
use WC_Product;

/**
 * Class ProductRepositoryTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Product
 *
 * @property ProductRepository  $product_repository
 * @property ProductMetaHandler $product_meta
 * @property ProductHelper      $product_helper
 */
class ProductRepositoryTest extends ContainerAwareUnitTest {

	use ProductTrait;

	public function test_find_returns_all_supported_products() {
		// supported
		$simple_product   = WC_Helper_Product::create_simple_product();
		$variable_product = WC_Helper_Product::create_variation_product();

		// unsupported
		WC_Helper_Product::create_external_product();

		$results = $this->product_repository->find();

		$this->assertContainsOnlyInstancesOf( WC_Product::class, $results );

		$expected = array_merge(
			[
				$simple_product->get_id(),
				$variable_product->get_id(),
			],
			$variable_product->get_children()
		);

		$this->assertCount( count( $expected ), $results );

		// compare the IDs because the objects might not be identical
		$result_ids = array_map( [ __CLASS__, 'get_product_id' ], $results );
		$this->assertEquals(
			$expected,
			$result_ids
		);
	}

	public function test_find_always_returns_objects() {
		WC_Helper_Product::create_simple_product();
		$results = $this->product_repository->find( [ 'return' => 'ids' ] );
		$this->assertContainsOnlyInstancesOf( WC_Product::class, $results );
	}

	public function test_find_returns_limited_and_based_on_offset() {
		WC_Helper_Product::create_simple_product();
		WC_Helper_Product::create_variation_product();

		$results_unlimited = $this->product_repository->find();

		$results = $this->product_repository->find( [], 1, 1 );

		$this->assertCount( 1, $results );

		// compare the IDs because the objects might not be identical
		$result_ids = array_map( [ __CLASS__, 'get_product_id' ], $results );
		$this->assertEquals( [ $results_unlimited[1]->get_id() ], $result_ids );
	}

	public function test_find_ids_always_returns_integer_ids() {
		WC_Helper_Product::create_simple_product();
		$results = $this->product_repository->find_ids( [ 'return' => 'objects' ] );
		$this->assertContainsOnly( 'integer', $results );
	}

	public function test_find_passes_query_args() {
		$product_1 = WC_Helper_Product::create_simple_product();
		$product_1->set_status( 'draft' );
		$product_1->save();

		$product_2 = WC_Helper_Product::create_simple_product();
		$product_2->set_status( 'draft' );
		$product_2->save();
		$this->product_meta->update_errors( $product_2, [ 'Error' ] );

		$product_3 = WC_Helper_Product::create_simple_product();
		$product_3->set_status( 'publish' );
		$product_3->save();

		WC_Helper_Product::create_variation_product();

		$query_args = [
			'status'     => 'draft',
			'type'       => [ 'simple' ],
			'meta_query' => [
				'relation' => 'OR',
				[
					'key'     => ProductMetaHandler::KEY_ERRORS,
					'compare' => 'NOT EXISTS',
				],
				[
					'key'     => ProductMetaHandler::KEY_ERRORS,
					'compare' => '=',
					'value'   => '',
				],
			],
		];
		$this->assertEquals( [ $product_1->get_id() ], $this->product_repository->find_ids( $query_args ) );

		// compare the IDs because the objects might not be identical
		$result_ids = array_map( [ __CLASS__, 'get_product_id' ], $this->product_repository->find( $query_args ) );
		$this->assertEquals( [ $product_1->get_id() ], $result_ids );
	}

	public function test_find_by_ids() {
		$product_1 = WC_Helper_Product::create_simple_product();
		$product_2 = WC_Helper_Product::create_simple_product();
		WC_Helper_Product::create_simple_product();
		WC_Helper_Product::create_variation_product();

		$ids = [
			$product_1->get_id(),
			$product_2->get_id(),
		];

		$this->assertEqualSets(
			array_map( 'wc_get_product', $ids ),
			$this->product_repository->find_by_ids( $ids )
		);
	}

	public function test_find_synced_products() {
		$product_1 = WC_Helper_Product::create_simple_product();
		$this->product_helper->mark_as_synced( $product_1, $this->generate_google_product_mock() );

		WC_Helper_Product::create_simple_product();

		// compare the IDs because the objects might not be identical
		$result_ids = array_map( [ __CLASS__, 'get_product_id' ], $this->product_repository->find_synced_products() );
		$this->assertEquals(
			[ $product_1->get_id() ],
			$result_ids
		);

		$this->assertEquals(
			[ $product_1->get_id() ],
			$this->product_repository->find_synced_product_ids()
		);
	}

	public function test_find_sync_ready_products() {
		// create some products that are not sync ready

		// manually set to dont-sync-and-show
		$product_1 = WC_Helper_Product::create_simple_product();
		$this->product_meta->update_visibility( $product_1, ChannelVisibility::DONT_SYNC_AND_SHOW );

		// product status is set to private and therefore not supported
		$product_2 = WC_Helper_Product::create_simple_product();
		$this->product_meta->update_visibility( $product_2, ChannelVisibility::SYNC_AND_SHOW );
		$product_2->set_status( 'private' );
		$product_2->save();

		// product type is not supported
		$product_3 = WC_Helper_Product::create_external_product();
		$this->product_meta->update_visibility( $product_3, ChannelVisibility::SYNC_AND_SHOW );

		// syncing this product has failed several times recently
		$product_4 = WC_Helper_Product::create_simple_product();
		$this->product_meta->update_visibility( $product_4, ChannelVisibility::SYNC_AND_SHOW );
		$this->product_meta->update_failed_sync_attempts( $product_4, ProductSyncer::FAILURE_THRESHOLD + 1 );
		$this->product_meta->update_sync_failed_at( $product_4, strtotime( '+1 year' ) );

		// a simple product that is sync ready
		$simple_product = WC_Helper_Product::create_simple_product();
		$this->product_meta->update_visibility( $simple_product, ChannelVisibility::SYNC_AND_SHOW );

		// a variable product that is sync ready along with all its variations
		$variable_product = WC_Helper_Product::create_variation_product();
		$this->product_meta->update_visibility( wc_get_product( $variable_product ), ChannelVisibility::SYNC_AND_SHOW );
		foreach ( $variable_product->get_children() as $variation_id ) {
			$this->product_meta->update_visibility( wc_get_product( $variation_id ), ChannelVisibility::SYNC_AND_SHOW );
		}

		$results = $this->product_repository->find_sync_ready_products();
		$this->assertInstanceOf( FilteredProductList::class, $results );

		// compare the IDs because the objects might not be identical
		$this->assertEquals(
			array_merge( [ $simple_product->get_id() ], $variable_product->get_children() ),
			$results->get_product_ids()
		);
		$this->assertEquals(
			array_merge( [ $simple_product->get_id() ], $variable_product->get_children() ),
			$this->product_repository->find_sync_ready_product_ids()->get()
		);
	}

	public function test_find_sync_ready_products_unfiltered_count() {
		// a variable product that is not sync ready (only the parent is marked as do not sync and show)
		$no_sync_product = WC_Helper_Product::create_variation_product();
		$this->product_meta->update_visibility( wc_get_product( $no_sync_product ), ChannelVisibility::DONT_SYNC_AND_SHOW );

		// a simple product that is sync ready
		$simple_product = WC_Helper_Product::create_simple_product();
		$this->product_meta->update_visibility( $simple_product, ChannelVisibility::SYNC_AND_SHOW );

		// a variable product that is sync ready along with all its variations
		$variable_product = WC_Helper_Product::create_variation_product();
		$this->product_meta->update_visibility( wc_get_product( $variable_product ), ChannelVisibility::SYNC_AND_SHOW );

		$results = $this->product_repository->find_sync_ready_products();

		// compare the IDs because the objects might not be identical
		$this->assertEquals(
			array_merge( [ $simple_product->get_id() ], $variable_product->get_children() ),
			$results->get_product_ids()
		);

		// unsynced variations should be included in the unfiltered count
		$this->assertEquals(
			$results->get_unfiltered_count(),
			count( $results ) + count( $no_sync_product->get_children() )
		);
	}

	public function test_find_expiring_product_ids() {
		$product_1 = WC_Helper_Product::create_simple_product();
		$this->product_helper->mark_as_synced( $product_1, $this->generate_google_product_mock() );

		$product_2 = WC_Helper_Product::create_simple_product();
		$this->product_helper->mark_as_synced( $product_2, $this->generate_google_product_mock() );
		$this->product_meta->update_synced_at( $product_2, strtotime( '-30 days' ) );

		$product_3 = WC_Helper_Product::create_simple_product();
		$this->product_helper->mark_as_invalid( $product_3, [ 'Error 1' ] );

		$this->assertEquals(
			[ $product_2->get_id() ],
			$this->product_repository->find_expiring_product_ids()
		);
	}

	public function test_find_mc_not_synced_product_ids() {
		$product_1 = WC_Helper_Product::create_simple_product();
		$this->product_helper->mark_as_synced( $product_1, $this->generate_google_product_mock() );

		$product_2 = WC_Helper_Product::create_simple_product();
		$this->product_meta->update_mc_status( $product_2, MCStatus::NOT_SYNCED );

		WC_Helper_Product::create_simple_product();

		$variable_product = WC_Helper_Product::create_variation_product();
		$this->product_meta->update_mc_status( $variable_product, MCStatus::NOT_SYNCED );
		foreach ( $variable_product->get_children() as $variation_id ) {
			$this->product_meta->update_mc_status( wc_get_product( $variation_id ), MCStatus::NOT_SYNCED );
		}

		$this->assertEqualSets(
			[ $product_2->get_id(), $variable_product->get_id() ],
			$this->product_repository->find_mc_not_synced_product_ids()
		);
	}

	public function test_find_all_synced_google_ids() {
		$synced_google_ids = [];

		$product_1 = WC_Helper_Product::create_simple_product();
		$this->product_helper->mark_as_synced( $product_1, $this->generate_google_product_mock( 'online:en:US:gla_' . $product_1->get_id() ) );
		$synced_google_ids[] = 'online:en:US:gla_' . $product_1->get_id();

		// a synced product with an invalid google id set
		$product_2 = WC_Helper_Product::create_simple_product();
		$this->product_helper->mark_as_synced( $product_2, $this->generate_google_product_mock( 'online:en:US:gla_' . $product_2->get_id() ) );
		$product_2->update_meta_data( '_wc_gla_google_ids', 1 );
		$product_2->save();

		WC_Helper_Product::create_simple_product();

		$variable_product = WC_Helper_Product::create_variation_product();
		foreach ( $variable_product->get_children() as $variation_id ) {
			$this->product_helper->mark_as_synced( wc_get_product( $variation_id ), $this->generate_google_product_mock( 'online:en:US:gla_' . $variation_id ) );
			$synced_google_ids[] = 'online:en:US:gla_' . $variation_id;
		}

		$this->assertEqualSets(
			$synced_google_ids,
			$this->product_repository->find_all_synced_google_ids()
		);
	}
	public function test_find_delete_product_ids() {
		$product_1 = WC_Helper_Product::create_simple_product();
		$this->product_helper->mark_as_synced( $product_1, $this->generate_google_product_mock() );

		// A synced product with 5 failed delete attempts.
		$product_2 = WC_Helper_Product::create_simple_product();
		$this->product_helper->mark_as_synced( $product_2, $this->generate_google_product_mock() );
		$this->product_meta->update_failed_delete_attempts( $product_2, 5 );

		$ids = [ $product_1->get_id(), $product_2->get_id() ];

		$this->assertEquals(
			[ $product_1->get_id() ],
			$this->product_repository->find_delete_product_ids( $ids )
		);
	}

	/**
	 * Runs before each test is executed.
	 */
	public function setUp() {
		parent::setUp();
		$this->product_meta       = $this->container->get( ProductMetaHandler::class );
		$this->product_helper     = $this->container->get( ProductHelper::class );
		$this->product_repository = $this->container->get( ProductRepository::class );
	}
}
