<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Product;

use Automattic\WooCommerce\GoogleListingsAndAds\Integration\WPML;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\FilteredProductList;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductFilter;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductMetaHandler;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductSyncer;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\ContainerAwareUnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\ProductTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\ChannelVisibility;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\SyncStatus;
use WC_Helper_Product;
use WC_Product;

/**
 * Class ProductRepositoryTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Product
 */
class ProductRepositoryTest extends ContainerAwareUnitTest {

	use ProductTrait;

	/** @var ProductRepository $product_repository */
	protected $product_repository;

	/** @var ProductMetaHandler $product_meta */
	protected $product_meta;

	/** @var ProductHelper $product_helper */
	protected $product_helper;

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

	public function test_get_sync_ready_products_meta_query() {
		$this->assertEquals(
			[
				'relation' => 'OR',
				[
					'key'     => 'visibility',
					'compare' => 'NOT EXISTS',
				],
				[
					'key'     => 'visibility',
					'compare' => '!=',
					'value'   => 'dont-sync-and-show',
				],
			],
			$this->product_repository->get_sync_ready_products_meta_query()
		);
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

		$this->assertEquals( [], $this->product_repository->find_by_ids( [] ) );
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

	public function test_find_synced_product_ids_after_id() {
		$product_1 = WC_Helper_Product::create_simple_product();
		$this->product_helper->mark_as_synced( $product_1, $this->generate_google_product_mock() );

		$product_2 = WC_Helper_Product::create_simple_product();
		$this->product_helper->mark_as_synced( $product_2, $this->generate_google_product_mock() );

		WC_Helper_Product::create_simple_product();

		$this->assertEquals(
			[ $product_1->get_id(), $product_2->get_id() ],
			$this->product_repository->find_synced_product_ids_after_id()
		);
	}

	public function test_find_synced_product_ids_after_id_respects_cursor() {
		$product_1 = WC_Helper_Product::create_simple_product();
		$this->product_helper->mark_as_synced( $product_1, $this->generate_google_product_mock() );

		$product_2 = WC_Helper_Product::create_simple_product();
		$this->product_helper->mark_as_synced( $product_2, $this->generate_google_product_mock() );

		// Both products are synced; passing the first ID as cursor should only return the second.
		$ids = $this->product_repository->find_synced_product_ids_after_id( $product_1->get_id() );

		$this->assertEquals( [ $product_2->get_id() ], $ids );
	}

	public function test_find_synced_product_ids_after_id_survives_a_shrinking_result_set() {
		$product_1 = WC_Helper_Product::create_simple_product();
		$this->product_helper->mark_as_synced( $product_1, $this->generate_google_product_mock() );

		$product_2 = WC_Helper_Product::create_simple_product();
		$this->product_helper->mark_as_synced( $product_2, $this->generate_google_product_mock() );

		// The first page returns product_1 only (limit 1).
		$first_page = $this->product_repository->find_synced_product_ids_after_id( 0, 1 );
		$this->assertEquals( [ $product_1->get_id() ], $first_page );

		// Simulate the batch consumer unsyncing product_1 before the next page is fetched.
		$this->product_helper->mark_as_unsynced( $product_1 );

		// Resuming from the cursor still finds product_2, even though product_1 no longer
		// matches the synced filter — an OFFSET-based query would have skipped it instead.
		$second_page = $this->product_repository->find_synced_product_ids_after_id( max( $first_page ), 1 );
		$this->assertEquals( [ $product_2->get_id() ], $second_page );
	}

	public function test_find_synced_product_ids_after_id_respects_limit() {
		for ( $i = 0; $i < 3; $i++ ) {
			$p = WC_Helper_Product::create_simple_product();
			$this->product_helper->mark_as_synced( $p, $this->generate_google_product_mock() );
		}

		$ids = $this->product_repository->find_synced_product_ids_after_id( 0, 2 );

		$this->assertCount( 2, $ids );
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
			$this->product_repository->find_sync_ready_products()->get_product_ids()
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

	public function test_find_expiring_product_ids_respects_cursor() {
		$product_1 = WC_Helper_Product::create_simple_product();
		$this->product_helper->mark_as_synced( $product_1, $this->generate_google_product_mock() );
		$this->product_meta->update_synced_at( $product_1, strtotime( '-30 days' ) );

		$product_2 = WC_Helper_Product::create_simple_product();
		$this->product_helper->mark_as_synced( $product_2, $this->generate_google_product_mock() );
		$this->product_meta->update_synced_at( $product_2, strtotime( '-30 days' ) );

		// Both products are expiring; passing the first ID as cursor should only return the second.
		$ids = $this->product_repository->find_expiring_product_ids( $product_1->get_id() );

		$this->assertEquals( [ $product_2->get_id() ], $ids );
	}

	public function test_find_expiring_product_ids_respects_limit() {
		for ( $i = 0; $i < 3; $i++ ) {
			$p = WC_Helper_Product::create_simple_product();
			$this->product_helper->mark_as_synced( $p, $this->generate_google_product_mock() );
			$this->product_meta->update_synced_at( $p, strtotime( '-30 days' ) );
		}

		$ids = $this->product_repository->find_expiring_product_ids( 0, 2 );

		$this->assertCount( 2, $ids );
	}

	public function test_find_queued_update_ids() {
		$product_1 = WC_Helper_Product::create_simple_product();
		$this->product_meta->update_sync_queued_at( $product_1, time() );

		$variable_product = WC_Helper_Product::create_variation_product();
		$variation        = wc_get_product( $variable_product->get_children()[0] );
		$this->product_meta->update_sync_queued_at( $variation, time() );

		$draft_product = WC_Helper_Product::create_simple_product( true, [ 'status' => 'draft' ] );
		$this->product_meta->update_sync_queued_at( $draft_product, time() );

		WC_Helper_Product::create_simple_product();

		$this->assertEquals(
			[ $product_1->get_id(), $variation->get_id(), $draft_product->get_id() ],
			$this->product_repository->find_queued_update_ids()
		);
	}

	public function test_find_queued_update_ids_respects_limit() {
		for ( $i = 0; $i < 3; $i++ ) {
			$product = WC_Helper_Product::create_simple_product();
			$this->product_meta->update_sync_queued_at( $product, time() );
		}

		$ids = $this->product_repository->find_queued_update_ids( 2 );

		$this->assertCount( 2, $ids );
	}

	public function test_find_expiring_product_ids_excludes_dont_sync_visibility() {
		$product = WC_Helper_Product::create_simple_product();
		$this->product_helper->mark_as_synced( $product, $this->generate_google_product_mock() );
		$this->product_meta->update_synced_at( $product, strtotime( '-30 days' ) );
		$this->product_meta->update_visibility( $product, \Automattic\WooCommerce\GoogleListingsAndAds\Value\ChannelVisibility::DONT_SYNC_AND_SHOW );

		$ids = $this->product_repository->find_expiring_product_ids();

		$this->assertNotContains( $product->get_id(), $ids );
	}

	public function test_find_expiring_product_ids_results_are_ordered_asc() {
		$ids_created = [];
		for ( $i = 0; $i < 3; $i++ ) {
			$p = WC_Helper_Product::create_simple_product();
			$this->product_helper->mark_as_synced( $p, $this->generate_google_product_mock() );
			$this->product_meta->update_synced_at( $p, strtotime( '-30 days' ) );
			$ids_created[] = $p->get_id();
		}

		$ids_found = $this->product_repository->find_expiring_product_ids();

		// Returned IDs should be a sorted subset of what was created.
		$intersection = array_values( array_intersect( $ids_found, $ids_created ) );
		$sorted       = $intersection;
		sort( $sorted );
		$this->assertEquals( $sorted, $intersection );
	}

	/**
	 * Every extra postmeta join that matches on post ID alone multiplies the rows scanned by the
	 * number of meta rows a product has, which stalled the query for hours on large stores.
	 * The expiring products query must join postmeta once, for synced_at only.
	 */
	public function test_find_expiring_product_ids_joins_postmeta_only_once() {
		global $wpdb;

		$requests = [];
		$capture  = function ( $request ) use ( &$requests ) {
			$requests[] = $request;
			return $request;
		};
		add_filter( 'posts_request', $capture );
		$this->product_repository->find_expiring_product_ids( 0, 10 );
		remove_filter( 'posts_request', $capture );

		$this->assertCount( 1, $requests );
		$this->assertSame( 1, substr_count( $requests[0], "JOIN {$wpdb->postmeta}" ) );
	}

	public function test_find_expiring_product_ids_includes_variations() {
		$simple = WC_Helper_Product::create_simple_product();
		$this->product_helper->mark_as_synced( $simple, $this->generate_google_product_mock() );
		$this->product_meta->update_synced_at( $simple, strtotime( '-30 days' ) );

		$variable      = WC_Helper_Product::create_variation_product();
		$variation_ids = $variable->get_children();
		foreach ( $variation_ids as $variation_id ) {
			$variation = wc_get_product( $variation_id );
			$this->product_helper->mark_as_synced( $variation, $this->generate_google_product_mock() );
			$this->product_meta->update_synced_at( $variation, strtotime( '-30 days' ) );
		}

		$this->assertEqualSets(
			array_merge( [ $simple->get_id() ], $variation_ids ),
			$this->product_repository->find_expiring_product_ids()
		);
	}

	public function test_find_expiring_product_ids_excludes_products_with_errors() {
		$with_errors = WC_Helper_Product::create_simple_product();
		$this->product_helper->mark_as_synced( $with_errors, $this->generate_google_product_mock() );
		$this->product_meta->update_synced_at( $with_errors, strtotime( '-30 days' ) );
		$this->product_meta->update_errors( $with_errors, [ 'Error 1' ] );

		$with_empty_errors = WC_Helper_Product::create_simple_product();
		$this->product_helper->mark_as_synced( $with_empty_errors, $this->generate_google_product_mock() );
		$this->product_meta->update_synced_at( $with_empty_errors, strtotime( '-30 days' ) );
		$with_empty_errors->update_meta_data( '_wc_gla_errors', '' );
		$with_empty_errors->save_meta_data();

		$without_errors = WC_Helper_Product::create_simple_product();
		$this->product_helper->mark_as_synced( $without_errors, $this->generate_google_product_mock() );
		$this->product_meta->update_synced_at( $without_errors, strtotime( '-30 days' ) );

		$this->assertEquals(
			[ $with_empty_errors->get_id(), $without_errors->get_id() ],
			$this->product_repository->find_expiring_product_ids()
		);
	}

	public function test_find_expiring_product_ids_includes_products_without_visibility() {
		$product = WC_Helper_Product::create_simple_product();
		$this->product_helper->mark_as_synced( $product, $this->generate_google_product_mock() );
		$this->product_meta->update_synced_at( $product, strtotime( '-30 days' ) );
		$this->product_meta->delete_visibility( $product );

		$this->assertEquals( [ $product->get_id() ], $this->product_repository->find_expiring_product_ids() );
	}

	public function test_find_expiring_product_ids_excludes_products_without_synced_at() {
		$never_synced = WC_Helper_Product::create_simple_product();

		$expired = WC_Helper_Product::create_simple_product();
		$this->product_helper->mark_as_synced( $expired, $this->generate_google_product_mock() );
		$this->product_meta->update_synced_at( $expired, strtotime( '-30 days' ) );

		$ids = $this->product_repository->find_expiring_product_ids();

		$this->assertNotContains( $never_synced->get_id(), $ids );
		$this->assertEquals( [ $expired->get_id() ], $ids );
	}

	public function test_find_expiring_product_ids_excludes_unpublished_products() {
		$expected = [];
		foreach ( [ 'publish', 'draft', 'pending', 'private' ] as $status ) {
			$product = WC_Helper_Product::create_simple_product( true, [ 'status' => $status ] );
			$this->product_helper->mark_as_synced( $product, $this->generate_google_product_mock() );
			$this->product_meta->update_synced_at( $product, strtotime( '-30 days' ) );

			if ( 'publish' === $status ) {
				$expected[] = $product->get_id();
			}
		}

		$this->assertEquals( $expected, $this->product_repository->find_expiring_product_ids() );
	}

	/**
	 * A disabled variation is stored as private. Variations skip the publish status check because
	 * ProductHelper::is_sync_ready() judges a variation by its parent's status. The query can
	 * therefore return disabled variations, which is_sync_ready() still rejects by default
	 * (is_visible() and woocommerce_hide_invisible_variations).
	 */
	public function test_find_expiring_product_ids_includes_disabled_variations_of_published_products() {
		$variable     = WC_Helper_Product::create_variation_product();
		$variation_id = $variable->get_children()[0];
		$variation    = wc_get_product( $variation_id );
		$variation->set_status( 'private' );
		$variation->save();
		$this->product_helper->mark_as_synced( $variation, $this->generate_google_product_mock() );
		$this->product_meta->update_synced_at( $variation, strtotime( '-30 days' ) );

		$this->assertEquals( [ $variation_id ], $this->product_repository->find_expiring_product_ids() );
	}

	/**
	 * The plugin never stores a NULL errors value, but other code can. NULL means no errors, so the
	 * product counts as valid. The meta_query rules this lookup replaced excluded it.
	 */
	public function test_find_expiring_product_ids_includes_products_with_null_errors() {
		global $wpdb;

		$product = WC_Helper_Product::create_simple_product();
		$this->product_helper->mark_as_synced( $product, $this->generate_google_product_mock() );
		$this->product_meta->update_synced_at( $product, strtotime( '-30 days' ) );
		$wpdb->insert(
			$wpdb->postmeta,
			[
				'post_id'    => $product->get_id(),
				'meta_key'   => '_wc_gla_errors',
				'meta_value' => null,
			]
		);

		$this->assertEquals( [ $product->get_id() ], $this->product_repository->find_expiring_product_ids() );
	}

	/**
	 * The plugin stores one visibility row per product, but other code can add more. Any
	 * dont-sync-and-show row excludes the product. The meta_query rules this lookup replaced
	 * included it as long as one row had another value.
	 */
	public function test_find_expiring_product_ids_excludes_products_with_any_dont_sync_visibility_row() {
		$product = WC_Helper_Product::create_simple_product();
		$this->product_helper->mark_as_synced( $product, $this->generate_google_product_mock() );
		$this->product_meta->update_synced_at( $product, strtotime( '-30 days' ) );
		// mark_as_synced() stored sync-and-show; add a second, conflicting row.
		add_post_meta( $product->get_id(), '_wc_gla_visibility', ChannelVisibility::DONT_SYNC_AND_SHOW );

		$this->assertEquals( [], $this->product_repository->find_expiring_product_ids() );
	}

	public function test_find_expiring_product_ids_applies_product_query_args_filter() {
		$product_ids = [];
		for ( $i = 0; $i < 2; $i++ ) {
			$product = WC_Helper_Product::create_simple_product();
			$this->product_helper->mark_as_synced( $product, $this->generate_google_product_mock() );
			$this->product_meta->update_synced_at( $product, strtotime( '-30 days' ) );
			$product_ids[] = $product->get_id();
		}

		$exclude_first = function ( $args ) use ( $product_ids ) {
			$args['exclude'] = [ $product_ids[0] ];
			return $args;
		};
		add_filter( 'woocommerce_gla_product_query_args', $exclude_first );
		$ids = $this->product_repository->find_expiring_product_ids();
		remove_filter( 'woocommerce_gla_product_query_args', $exclude_first );

		$this->assertEquals( [ $product_ids[1] ], $ids );
	}

	public function test_find_expiring_product_ids_applies_resubmit_expiry_days_filter() {
		$product = WC_Helper_Product::create_simple_product();
		$this->product_helper->mark_as_synced( $product, $this->generate_google_product_mock() );
		$this->product_meta->update_synced_at( $product, strtotime( '-21 days' ) );

		// Inside the default 25-day window.
		$this->assertEquals( [], $this->product_repository->find_expiring_product_ids() );

		$twenty_days = function () {
			return 20;
		};
		add_filter( 'woocommerce_gla_resubmit_expiry_days', $twenty_days );
		$ids = $this->product_repository->find_expiring_product_ids();
		remove_filter( 'woocommerce_gla_resubmit_expiry_days', $twenty_days );

		$this->assertEquals( [ $product->get_id() ], $ids );
	}

	/**
	 * @dataProvider resubmit_expiry_days_provider
	 *
	 * @param mixed $filtered The value the filter callback returns.
	 * @param int   $expected The window get_resubmit_expiry_days() returns.
	 */
	public function test_get_resubmit_expiry_days_validates_filtered_value( $filtered, int $expected ) {
		$callback = function () use ( $filtered ) {
			return $filtered;
		};
		add_filter( 'woocommerce_gla_resubmit_expiry_days', $callback );
		$days = ProductRepository::get_resubmit_expiry_days();
		remove_filter( 'woocommerce_gla_resubmit_expiry_days', $callback );

		$this->assertSame( $expected, $days );
	}

	public function resubmit_expiry_days_provider(): array {
		return [
			'shorter window'     => [ 10, 10 ],
			'numeric string'     => [ '10', 10 ],
			'above the default'  => [ 40, 25 ],
			'zero'               => [ 0, 1 ],
			'negative'           => [ -5, 1 ],
			'non-numeric string' => [ 'ten', 25 ],
			'null'               => [ null, 25 ],
		];
	}

	public function test_find_expiring_product_ids_removes_its_where_filter() {
		global $wp_filter;

		$count_where_callbacks = function () use ( &$wp_filter ): int {
			return isset( $wp_filter['posts_where'] ) ? count( $wp_filter['posts_where']->callbacks, COUNT_RECURSIVE ) : 0;
		};

		$before = $count_where_callbacks();

		$this->product_repository->find_expiring_product_ids();
		$this->assertSame( $before, $count_where_callbacks() );

		$throw = function () {
			throw new \RuntimeException( 'Query failed' );
		};
		add_filter( 'woocommerce_gla_product_query_args', $throw );
		try {
			$this->product_repository->find_expiring_product_ids();
			$this->fail( 'Expected the query to throw.' );
		} catch ( \RuntimeException $e ) {
			$this->assertSame( 'Query failed', $e->getMessage() );
		} finally {
			remove_filter( 'woocommerce_gla_product_query_args', $throw );
		}
		$this->assertSame( $before, $count_where_callbacks() );
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
		// A simple synced product.
		$product_1 = WC_Helper_Product::create_simple_product();
		$this->product_helper->mark_as_synced( $product_1, $this->generate_google_product_mock() );

		// A synced product with 5 failed delete attempts.
		$product_2 = WC_Helper_Product::create_simple_product();
		$this->product_helper->mark_as_synced( $product_2, $this->generate_google_product_mock() );
		$this->product_meta->update_failed_delete_attempts( $product_2, 5 );

		// A trashed product that is marked as not_synced in SyncStatus.
		$product_3 = WC_Helper_Product::create_simple_product( true, [ 'status' => 'trash' ] );
		$this->product_helper->mark_as_unsynced( $product_3 );

		// A simple product with status publish which its catalog visibility is hidden.
		$product_4 = WC_Helper_Product::create_simple_product( true, [ 'status' => 'publish' ] );
		$product_4->set_catalog_visibility( 'hidden' );
		$product_4->save();
		$this->product_helper->mark_as_unsynced( $product_4 );

		$ids = [ $product_1->get_id(), $product_2->get_id(), $product_3->get_id(), $product_4->get_id() ];

		$this->assertEquals(
			[ $product_1->get_id(), $product_3->get_id(), $product_4->get_id() ],
			$this->product_repository->find_delete_product_ids( $ids )
		);
	}

	/**
	 * WPML scopes every post query to the active language, so all product queries must
	 * run in the all-languages context. Verify the repository routes its query through
	 * WPML::run_in_all_languages() (mocked to return without hitting the database).
	 */
	public function test_queries_run_in_all_languages_context() {
		$meta_handler   = $this->createMock( ProductMetaHandler::class );
		$product_filter = $this->createMock( ProductFilter::class );
		$wpml           = $this->createMock( WPML::class );

		$wpml->expects( $this->once() )
			->method( 'run_in_all_languages' )
			->willReturn( [ 101, 202 ] );

		$repository = new ProductRepository( $meta_handler, $product_filter, $wpml );

		$this->assertSame( [ 101, 202 ], $repository->find_ids( [ 'status' => 'publish' ] ) );
	}

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->product_meta       = $this->container->get( ProductMetaHandler::class );
		$this->product_helper     = $this->container->get( ProductHelper::class );
		$this->product_repository = $this->container->get( ProductRepository::class );
	}
}
