<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\DB;

use Automattic\WooCommerce\GoogleListingsAndAds\DB\ProductFeedQueryHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\MerchantIssueQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Table\MerchantIssueTable;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\UpdateMerchantProductStatuses;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantStatuses;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductMetaHandler;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;
use Automattic\WooCommerce\GoogleListingsAndAds\SyncHealth\ProductJourney;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\ChannelVisibility;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\MCStatus;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\SyncStatus;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\Container\Container;
use PHPUnit\Framework\MockObject\MockObject;
use WC_Helper_Product;
use WP_Query;
use WP_REST_Request;

/**
 * Class ProductFeedQueryHelperTest
 *
 * Products come from a mocked repository unless a test needs the real product
 * query; the merchant issue table is real, so the issue lookup runs its SQL.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\DB
 */
class ProductFeedQueryHelperTest extends UnitTest {

	use PluginHelper;

	/** @var MockObject|ProductRepository $product_repository */
	protected $product_repository;

	/** @var MockObject|MerchantCenterService $mc_service */
	protected $mc_service;

	/** @var MockObject|MerchantStatuses $merchant_statuses */
	protected $merchant_statuses;

	/** @var MockObject|ProductHelper $product_helper */
	protected $product_helper;

	/** @var MockObject|WP_REST_Request $request */
	protected $request;

	/** @var MockObject|UpdateMerchantProductStatuses $job */
	protected $job;

	/** @var MockObject|ProductJourney $product_journey */
	protected $product_journey;

	/** @var MerchantIssueTable $issue_table */
	protected $issue_table;

	/** @var ProductFeedQueryHelper $product_feed_query_helper */
	protected $product_feed_query_helper;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();
		global $wpdb;

		$this->issue_table = new MerchantIssueTable( new WP(), $wpdb );
		$this->issue_table->install();

		$this->product_repository = $this->createMock( ProductRepository::class );
		$this->mc_service         = $this->createMock( MerchantCenterService::class );
		$this->product_helper     = $this->createMock( ProductHelper::class );
		$this->request            = $this->createMock( WP_REST_Request::class );
		$this->job                = $this->createMock( UpdateMerchantProductStatuses::class );
		$this->product_journey    = $this->createMock( ProductJourney::class );

		// Real issue wording (get_presync_issue), stubbed refresh.
		$this->merchant_statuses = $this->getMockBuilder( MerchantStatuses::class )
			->onlyMethods( [ 'maybe_refresh_status_data' ] )
			->getMock();

		$this->product_feed_query_helper = $this->make_helper( $this->product_repository, $this->product_helper, $this->product_journey );
	}

	public function test_get_product_feed_merchant_center_is_connected() {
		$product = WC_Helper_Product::create_simple_product();

		$this->mc_service->expects( $this->any() )
			->method( 'is_connected' )
			->willReturn( true );

		$this->merchant_statuses->expects( $this->exactly( 1 ) )
			->method( 'maybe_refresh_status_data' )
			->willReturn( $this->job );

		$this->product_repository->expects( $this->exactly( 1 ) )
			->method( 'find' )
			->willReturn( [ $product ] );

		$this->product_helper->expects( $this->exactly( 1 ) )
			->method( 'get_mc_status' )
			->with( $product )
			->willReturn( MCStatus::APPROVED );

		$this->product_journey->expects( $this->once() )
			->method( 'get_product_segments' )
			->with( [ $product->get_id() ] )
			->willReturn( [ $product->get_id() => ProductJourney::APPROVED ] );

		$response = $this->product_feed_query_helper->get( $this->request );

		$this->assertEquals(
			[
				[
					'id'                => $product->get_id(),
					'title'             => $product->get_name(),
					'visible'           => true,
					'status'            => MCStatus::APPROVED,
					'image_url'         => wp_get_attachment_image_url( $product->get_image_id(), 'full' ),
					'price'             => $product->get_price(),
					'errors'            => [],
					'catalog_visible'   => true,
					'sync_status_group' => ProductJourney::APPROVED,
					'issues'            => [],
				],
			],
			$response
		);
	}

	public function test_get_product_feed_keeps_the_last_known_status_while_statuses_refresh() {
		$product = WC_Helper_Product::create_simple_product();

		$this->mc_service->expects( $this->any() )
			->method( 'is_connected' )
			->willReturn( true );

		// A refresh keeps the previous statuses until it replaces them, so the row's
		// status is not hidden, and no per-row lookup of the job is made.
		$this->job->expects( $this->never() )->method( 'is_scheduled' );

		$this->merchant_statuses->expects( $this->exactly( 1 ) )
			->method( 'maybe_refresh_status_data' )
			->willReturn( $this->job );

		$this->product_repository->expects( $this->exactly( 1 ) )
			->method( 'find' )
			->willReturn( [ $product ] );

		$this->product_helper->expects( $this->exactly( 1 ) )
			->method( 'get_mc_status' )
			->with( $product )
			->willReturn( MCStatus::APPROVED );

		$response = $this->product_feed_query_helper->get( $this->request );

		$this->assertEquals(
			[
				[
					'id'                => $product->get_id(),
					'title'             => $product->get_name(),
					'visible'           => true,
					'status'            => MCStatus::APPROVED,
					'image_url'         => wp_get_attachment_image_url( $product->get_image_id(), 'full' ),
					'price'             => $product->get_price(),
					'errors'            => [],
					'catalog_visible'   => true,
					'sync_status_group' => ProductJourney::NOT_SYNCED,
					'issues'            => [],
				],
			],
			$response
		);
	}

	public function test_get_product_feed_merchant_center_is_not_connected() {
		$this->mc_service->expects( $this->any() )
			->method( 'is_connected' )
			->willReturn( false );

		$this->merchant_statuses->expects( $this->exactly( 0 ) )
			->method( 'maybe_refresh_status_data' );

		$this->product_feed_query_helper->get( $this->request );
	}

	public function test_get_rolls_variation_issues_up_to_the_parent_and_appends_sync_errors() {
		$simple     = WC_Helper_Product::create_simple_product();
		$variable   = WC_Helper_Product::create_variation_product();
		$variations = $variable->get_children();
		$elsewhere  = WC_Helper_Product::create_simple_product();

		$this->insert_issue( $simple->get_id(), 'image_too_small', 'Image too small', 'DEMOTED' );
		$this->insert_issue( $simple->get_id(), 'missing_shipping', 'Missing shipping', 'DISAPPROVED' );
		// Copies of the local errors, which are read live from product meta instead.
		$this->insert_issue( $simple->get_id(), 'presync_error_gtin', 'Invalid GTIN [gtin]', 'error', 'pre-sync' );
		$this->insert_issue( $variations[0], 'missing_gtin', 'Missing GTIN', 'DEMOTED' );
		$this->insert_issue( $variations[1], 'missing_gtin', 'Missing GTIN', 'DISAPPROVED' );
		$this->insert_issue( $elsewhere->get_id(), 'other_product', 'Not on this page', 'DISAPPROVED' );

		$this->product_repository->method( 'find' )->willReturn( [ $simple, $variable ] );
		$this->product_helper->method( 'get_validation_errors' )->willReturnCallback(
			function ( $product ) use ( $simple ) {
				return $product->get_id() === $simple->get_id() ? [ '[gtin] Invalid GTIN.' ] : [ 'Temporary error.' ];
			}
		);

		$response = $this->product_feed_query_helper->get( $this->request );

		$this->assertSame(
			[
				$this->mc_issue( 'missing_shipping', 'Missing shipping', 'error' ),
				$this->mc_issue( 'image_too_small', 'Image too small', 'warning' ),
				$this->sync_issue( 'presync_error_gtin', 'Invalid GTIN [gtin]' ),
			],
			$response[0]['issues']
		);
		$this->assertSame(
			[
				// One entry for the issue both variations share, as an error.
				$this->mc_issue( 'missing_gtin', 'Missing GTIN', 'error' ),
				$this->sync_issue( 'presync_error_attrib_' . md5( 'Temporary error.' ), 'Temporary error.' ),
			],
			$response[1]['issues']
		);
	}

	public function test_get_skips_status_and_issue_lookups_without_products() {
		$this->product_repository->method( 'find' )->willReturn( [] );

		$this->product_journey->expects( $this->never() )->method( 'get_product_segments' );

		$this->assertSame( [], $this->product_feed_query_helper->get( $this->request ) );
	}

	public function test_visibility_filters_rows_and_total_on_the_effective_visibility() {
		$helper     = $this->make_real_helper();
		$no_setting = $this->product_with_status( null );
		$shown      = $this->product_with_status( null, null, ChannelVisibility::SYNC_AND_SHOW );
		$hidden     = $this->product_with_status( null, null, ChannelVisibility::DONT_SYNC_AND_SHOW );

		// Hidden from the store catalog: not synced whatever its setting.
		$not_in_catalog = WC_Helper_Product::create_simple_product( true, [ 'catalog_visibility' => 'hidden' ] )->get_id();
		update_post_meta( $not_in_catalog, $this->prefix_meta_key( ProductMetaHandler::KEY_VISIBILITY ), ChannelVisibility::SYNC_AND_SHOW );

		$request = new WP_REST_Request();
		$request->set_param( 'visibility', ChannelVisibility::SYNC_AND_SHOW );

		$this->assertEqualsCanonicalizing( [ $no_setting, $shown ], array_column( $helper->get( $request ), 'id' ) );
		$this->assertSame( 2, $helper->count( $request ) );

		$request->set_param( 'visibility', ChannelVisibility::DONT_SYNC_AND_SHOW );
		$rows = $helper->get( $request );

		$this->assertEqualsCanonicalizing( [ $hidden, $not_in_catalog ], array_column( $rows, 'id' ) );
		$this->assertSame( 2, $helper->count( $request ) );
		// `visible` stays the stored setting; `catalog_visible` tells why the row is not synced.
		$row = array_column( $rows, null, 'id' )[ $not_in_catalog ];
		$this->assertTrue( $row['visible'] );
		$this->assertFalse( $row['catalog_visible'] );
	}

	public function test_every_status_tab_count_equals_its_rows() {
		global $wpdb;
		$helper  = $this->make_real_helper();
		$journey = new ProductJourney( $wpdb );

		$this->product_with_status( SyncStatus::SYNCED, MCStatus::APPROVED );
		$this->product_with_status( SyncStatus::SYNCED, MCStatus::EXPIRING );
		$this->product_with_status( SyncStatus::PENDING );
		$this->product_with_status( SyncStatus::SYNCED );
		$this->product_with_status( SyncStatus::SYNCED, MCStatus::DISAPPROVED );
		$this->product_with_status( SyncStatus::SYNCED, MCStatus::APPROVED, ChannelVisibility::DONT_SYNC_AND_SHOW );
		$this->product_with_status( null );

		// Hidden from the store catalog after it was approved: counted as not synced.
		$not_in_catalog = WC_Helper_Product::create_simple_product( true, [ 'catalog_visibility' => 'hidden' ] )->get_id();
		update_post_meta( $not_in_catalog, $this->prefix_meta_key( ProductMetaHandler::KEY_SYNC_STATUS ), SyncStatus::SYNCED );
		update_post_meta( $not_in_catalog, $this->prefix_meta_key( ProductMetaHandler::KEY_MC_STATUS ), MCStatus::APPROVED );

		// A variable product is one row, whatever its variations' statuses.
		$variable = WC_Helper_Product::create_variation_product();
		foreach ( array_merge( [ $variable->get_id() ], $variable->get_children() ) as $id ) {
			update_post_meta( $id, $this->prefix_meta_key( ProductMetaHandler::KEY_SYNC_STATUS ), SyncStatus::PENDING );
		}

		// Not listed: drafts and unsupported types (grouped products' simple children are).
		foreach ( [
			WC_Helper_Product::create_simple_product( true, [ 'status' => 'draft' ] ),
			WC_Helper_Product::create_external_product(),
			WC_Helper_Product::create_grouped_product(),
		] as $unlisted ) {
			update_post_meta( $unlisted->get_id(), $this->prefix_meta_key( ProductMetaHandler::KEY_SYNC_STATUS ), SyncStatus::SYNCED );
			update_post_meta( $unlisted->get_id(), $this->prefix_meta_key( ProductMetaHandler::KEY_MC_STATUS ), MCStatus::APPROVED );
		}

		$counts  = $journey->get_counts();
		$request = new WP_REST_Request();

		$this->assertSame( array_sum( $counts ), $helper->count( $request ) );

		foreach ( ProductJourney::SEGMENTS as $segment ) {
			$request->set_param( 'journey', $segment );
			$rows = $helper->get( $request );

			$this->assertSame( $counts[ $segment ], $helper->count( $request ), $segment );
			$this->assertCount( $counts[ $segment ], $rows, $segment );
			foreach ( $rows as $row ) {
				$this->assertSame( $segment, $row['sync_status_group'], $segment );
			}
		}

		$this->assertSame( 2, $counts[ ProductJourney::APPROVED ] );
		$this->assertSame( 2, $counts[ ProductJourney::PENDING_SYNC ] );
	}

	public function test_posts_where_callbacks_tolerate_unexpected_hook_data() {
		$helper = $this->product_feed_query_helper;
		$query  = new WP_Query();

		// Fired without a WP_Query: left alone.
		$this->assertSame( ' AND 1=1', $helper->filter_posts_where( ' AND 1=1', 'not a query' ) );
		$this->assertSame( ' AND 1=1', $helper->journey_filter( ' AND 1=1', null ) );

		// Another callback broke the clause: other queries get it back untouched...
		$this->assertSame( [ 'junk' ], $helper->filter_posts_where( [ 'junk' ], $query ) );

		// ...and a feed query fails closed instead of widening to every post.
		$query->set( 'gla_search', 'shirt' );
		$this->assertSame( ' AND 1 = 0', $helper->filter_posts_where( [ 'junk' ], $query ) );

		// A malformed status filter matches nothing.
		$query = new WP_Query();
		$query->set( 'gla_journey', [ ProductJourney::APPROVED ] );
		$this->assertSame( ' AND 1=1 AND 1 = 0', $helper->filter_posts_where( ' AND 1=1', $query ) );
	}

	public function test_rows_say_whether_the_product_is_visible_in_the_store_catalog() {
		$helper  = $this->make_real_helper();
		$visible = $this->product_with_status( null );
		$hidden  = WC_Helper_Product::create_simple_product( true, [ 'catalog_visibility' => 'hidden' ] )->get_id();

		// A variable product uses the parent's own visibility, like its product page.
		$variable = WC_Helper_Product::create_variation_product();
		$variable->set_catalog_visibility( 'hidden' );
		$variable->save();

		$rows = array_column( $helper->get( new WP_REST_Request() ), 'catalog_visible', 'id' );

		$this->assertTrue( $rows[ $visible ] );
		$this->assertFalse( $rows[ $hidden ] );
		$this->assertFalse( $rows[ $variable->get_id() ] );
	}

	public function test_invalid_visibility_is_rejected() {
		$request = new WP_REST_Request();
		$request->set_param( 'visibility', 'hidden' );

		$this->product_repository->expects( $this->never() )->method( 'find_ids' );
		$this->expectException( InvalidValue::class );

		$this->product_feed_query_helper->count( $request );
	}

	public function test_empty_status_tab_lists_nothing_rather_than_every_product() {
		$helper = $this->make_real_helper();
		$this->product_with_status( SyncStatus::SYNCED, MCStatus::APPROVED );

		$request = new WP_REST_Request();
		$request->set_param( 'journey', ProductJourney::DISAPPROVED );

		$this->assertSame( [], $helper->get( $request ) );
		$this->assertSame( 0, $helper->count( $request ) );
	}

	public function test_journey_combines_with_ids() {
		$helper   = $this->make_real_helper();
		$approved = $this->product_with_status( SyncStatus::SYNCED, MCStatus::APPROVED );
		$pending  = $this->product_with_status( SyncStatus::PENDING );

		$request = new WP_REST_Request();
		$request->set_param( 'journey', ProductJourney::PENDING_SYNC );
		$request->set_param( 'ids', "{$approved},{$pending}" );

		$this->assertSame( [ $pending ], array_column( $helper->get( $request ), 'id' ) );
		$this->assertSame( ProductJourney::PENDING_SYNC, $helper->get( $request )[0]['sync_status_group'] );

		$request->set_param( 'ids', (string) $approved );

		$this->assertSame( 0, $helper->count( $request ) );
	}

	/**
	 * Build a helper with the given product services.
	 *
	 * @param ProductRepository $repository
	 * @param ProductHelper     $product_helper
	 * @param ProductJourney    $journey
	 *
	 * @return ProductFeedQueryHelper
	 */
	protected function make_helper( ProductRepository $repository, ProductHelper $product_helper, ProductJourney $journey ): ProductFeedQueryHelper {
		global $wpdb;

		$container = new Container();
		$container->addShared( MerchantCenterService::class, $this->mc_service );
		$container->addShared( MerchantStatuses::class, $this->merchant_statuses );
		$container->addShared( ProductHelper::class, $product_helper );
		$container->addShared( ProductJourney::class, $journey );
		$container->add(
			MerchantIssueQuery::class,
			function () use ( $wpdb ) {
				return new MerchantIssueQuery( $wpdb, $this->issue_table );
			}
		);

		$helper = new ProductFeedQueryHelper( $wpdb, $repository );
		$helper->set_container( $container );

		return $helper;
	}

	/**
	 * A helper running the real product query and status SQL.
	 *
	 * @return ProductFeedQueryHelper
	 */
	protected function make_real_helper(): ProductFeedQueryHelper {
		global $wpdb;

		return $this->make_helper(
			woogle_get_container()->get( ProductRepository::class ),
			woogle_get_container()->get( ProductHelper::class ),
			new ProductJourney( $wpdb )
		);
	}

	/**
	 * Create a published simple product with the given sync and Merchant Center status.
	 *
	 * @param string|null $sync_status
	 * @param string|null $mc_status
	 * @param string|null $visibility
	 *
	 * @return int Product ID.
	 */
	protected function product_with_status( ?string $sync_status, ?string $mc_status = null, ?string $visibility = null ): int {
		$id = WC_Helper_Product::create_simple_product()->get_id();

		if ( $visibility ) {
			update_post_meta( $id, $this->prefix_meta_key( ProductMetaHandler::KEY_VISIBILITY ), $visibility );
		}

		if ( $sync_status ) {
			update_post_meta( $id, $this->prefix_meta_key( ProductMetaHandler::KEY_SYNC_STATUS ), $sync_status );
		}
		if ( $mc_status ) {
			update_post_meta( $id, $this->prefix_meta_key( ProductMetaHandler::KEY_MC_STATUS ), $mc_status );
		}

		return $id;
	}

	/**
	 * Store a product issue the way MerchantStatuses does.
	 *
	 * @param int    $product_id
	 * @param string $code
	 * @param string $issue
	 * @param string $severity
	 * @param string $source
	 */
	protected function insert_issue( int $product_id, string $code, string $issue, string $severity, string $source = 'mc' ): void {
		global $wpdb;

		$wpdb->insert(
			$this->issue_table->get_name(),
			[
				'product_id'           => $product_id,
				'code'                 => $code,
				'issue'                => $issue,
				'severity'             => $severity,
				'product'              => 'Product',
				'action'               => "Fix {$code}",
				'action_url'           => "https://example.com/{$code}",
				'applicable_countries' => '["US"]',
				'source'               => $source,
				'type'                 => MerchantStatuses::TYPE_PRODUCT,
				'created_at'           => '2026-10-16 00:00:00',
			]
		);
	}

	/**
	 * The issue entry expected for an insert_issue() row.
	 *
	 * @param string $code
	 * @param string $issue
	 * @param string $severity
	 *
	 * @return array
	 */
	protected function mc_issue( string $code, string $issue, string $severity ): array {
		return [
			'issue'      => $issue,
			'code'       => $code,
			'action'     => "Fix {$code}",
			'action_url' => "https://example.com/{$code}",
			'severity'   => $severity,
			'source'     => 'merchant_center',
		];
	}

	/**
	 * The issue entry expected for a local sync error.
	 *
	 * @param string $code
	 * @param string $issue
	 *
	 * @return array
	 */
	protected function sync_issue( string $code, string $issue ): array {
		return [
			'issue'      => $issue,
			'code'       => $code,
			'action'     => 'Update this attribute in your product data',
			'action_url' => MerchantStatuses::PRESYNC_ISSUE_HELP_URL,
			'severity'   => 'error',
			'source'     => 'sync',
		];
	}
}
