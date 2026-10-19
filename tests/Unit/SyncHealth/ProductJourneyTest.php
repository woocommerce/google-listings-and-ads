<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\SyncHealth;

use Automattic\WooCommerce\GoogleListingsAndAds\Google\GoogleProductService;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductMetaHandler;
use Automattic\WooCommerce\GoogleListingsAndAds\SyncHealth\ProductJourney;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\ChannelVisibility;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\MCStatus;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\SyncStatus;
use WC_Helper_Product;

/**
 * Class ProductJourneyTest
 *
 * Runs the status SQL against real products, so counts, the table filter and the
 * per-row status are checked to agree.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\SyncHealth
 */
class ProductJourneyTest extends UnitTest {

	use PluginHelper;

	/** @var ProductJourney */
	protected $journey;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		global $wpdb;
		$this->journey = new ProductJourney( $wpdb );
	}

	public function test_products_map_to_the_five_statuses() {
		// Sync status, MC status, errors, visibility => expected status.
		$cases = [
			[ [ SyncStatus::SYNCED, MCStatus::APPROVED ], ProductJourney::APPROVED ],
			[ [ SyncStatus::SYNCED, MCStatus::PARTIALLY_APPROVED ], ProductJourney::APPROVED ],
			[ [ SyncStatus::SYNCED, MCStatus::EXPIRING ], ProductJourney::APPROVED ],
			[ [ SyncStatus::PENDING ], ProductJourney::PENDING_SYNC ],
			[ [ SyncStatus::HAS_ERRORS, null, [ GoogleProductService::INTERNAL_ERROR_REASON => 'Try again.' ] ], ProductJourney::PENDING_SYNC ],
			[ [ SyncStatus::SYNCED, MCStatus::PENDING ], ProductJourney::PENDING_REVIEW ],
			[ [ SyncStatus::SYNCED ], ProductJourney::PENDING_REVIEW ],
			[ [ SyncStatus::SYNCED, MCStatus::DISAPPROVED ], ProductJourney::DISAPPROVED ],
			[ [ SyncStatus::HAS_ERRORS, null, [ '[gtin] Invalid GTIN.' ] ], ProductJourney::DISAPPROVED ],
			[ [ SyncStatus::SYNCED, MCStatus::APPROVED, null, ChannelVisibility::DONT_SYNC_AND_SHOW ], ProductJourney::NOT_SYNCED ],
			[ [ SyncStatus::NOT_SYNCED ], ProductJourney::NOT_SYNCED ],
			[ [], ProductJourney::NOT_SYNCED ],
		];

		$expected = [];
		foreach ( $cases as list( $meta, $status ) ) {
			$expected[ $this->product( $meta ) ] = $status;
		}

		$segments = $this->journey->get_product_segments( array_keys( $expected ) );
		ksort( $expected );
		ksort( $segments );

		$this->assertSame( $expected, $segments );
	}

	public function test_counts_and_filter_agree_with_row_statuses() {
		$approved = $this->product( [ SyncStatus::SYNCED, MCStatus::EXPIRING ] );
		$pending  = $this->product( [ SyncStatus::PENDING ] );
		$hidden   = $this->product( [ SyncStatus::NOT_SYNCED, null, null, ChannelVisibility::DONT_SYNC_AND_SHOW ] );
		$never    = $this->product( [] );

		// A variable product counts once, as its parent: its variations carry their own
		// sync status (pending here, like the parent) but are not rows of the table.
		$variable = WC_Helper_Product::create_variation_product();
		foreach ( array_merge( [ $variable->get_id() ], $variable->get_children() ) as $id ) {
			$this->set_meta( $id, ProductMetaHandler::KEY_SYNC_STATUS, SyncStatus::PENDING );
		}

		// Drafts are not listed in the Product Feed table.
		$draft = WC_Helper_Product::create_simple_product( true, [ 'status' => 'draft' ] );
		$this->set_meta( $draft->get_id(), ProductMetaHandler::KEY_SYNC_STATUS, SyncStatus::PENDING );

		$this->assertSame(
			[
				ProductJourney::APPROVED       => 1,
				ProductJourney::PENDING_SYNC   => 2,
				ProductJourney::PENDING_REVIEW => 0,
				ProductJourney::DISAPPROVED    => 0,
				ProductJourney::NOT_SYNCED     => 2,
			],
			$this->journey->get_counts()
		);

		$this->assertSame( [ $approved ], $this->filter( ProductJourney::APPROVED ) );
		$this->assertEqualsCanonicalizing( [ $pending, $variable->get_id() ], $this->filter( ProductJourney::PENDING_SYNC ) );
		$this->assertEqualsCanonicalizing( [ $hidden, $never ], $this->filter( ProductJourney::NOT_SYNCED ) );
		$this->assertNull( $this->journey->get_product_ids_sql( 'live' ) );
		$this->assertSame( [], $this->journey->get_product_segments( array_merge( [ $draft->get_id() ], $variable->get_children() ) ) );
	}

	public function test_products_hidden_from_the_catalog_are_not_synced_whatever_their_earlier_results() {
		$visible = [];
		$hidden  = [];
		foreach ( [ 'visible', 'catalog', 'search', 'hidden' ] as $catalog_visibility ) {
			$product = WC_Helper_Product::create_simple_product( true, [ 'catalog_visibility' => $catalog_visibility ] );
			$this->set_meta( $product->get_id(), ProductMetaHandler::KEY_SYNC_STATUS, SyncStatus::SYNCED );
			$this->set_meta( $product->get_id(), ProductMetaHandler::KEY_MC_STATUS, MCStatus::APPROVED );

			// The same answer as the product page, which uses is_visible().
			if ( $product->is_visible() ) {
				$visible[] = $product->get_id();
			} else {
				$hidden[] = $product->get_id();
			}
		}
		$this->assertCount( 2, $hidden, '"Search only" and "Hidden" are not visible outside a search.' );

		$this->assertSame( 2, $this->journey->get_counts()[ ProductJourney::NOT_SYNCED ] );
		$this->assertEqualsCanonicalizing( $hidden, $this->filter( ProductJourney::NOT_SYNCED ) );
		$this->assertEqualsCanonicalizing( $visible, $this->filter( ProductJourney::APPROVED ) );
		$this->assertSame( array_fill_keys( $hidden, ProductJourney::NOT_SYNCED ), $this->journey->get_product_segments( $hidden ) );
	}

	/**
	 * @dataProvider hide_out_of_stock_settings
	 *
	 * @param string $hide_out_of_stock The woocommerce_hide_out_of_stock_items option.
	 * @param string $expected          The out-of-stock product's status.
	 */
	public function test_out_of_stock_products_are_not_synced_only_when_the_store_hides_them( string $hide_out_of_stock, string $expected ) {
		update_option( 'woocommerce_hide_out_of_stock_items', $hide_out_of_stock );
		$product = WC_Helper_Product::create_simple_product( true, [ 'stock_status' => 'outofstock' ] );
		$this->set_meta( $product->get_id(), ProductMetaHandler::KEY_SYNC_STATUS, SyncStatus::SYNCED );
		$this->set_meta( $product->get_id(), ProductMetaHandler::KEY_MC_STATUS, MCStatus::APPROVED );

		$this->assertSame( ProductJourney::APPROVED === $expected, $product->is_visible() );
		$this->assertSame( [ $product->get_id() => $expected ], $this->journey->get_product_segments( [ $product->get_id() ] ) );
	}

	/**
	 * @return array
	 */
	public function hide_out_of_stock_settings(): array {
		return [
			'out-of-stock items shown'  => [ 'no', ProductJourney::APPROVED ],
			'out-of-stock items hidden' => [ 'yes', ProductJourney::NOT_SYNCED ],
		];
	}

	/**
	 * Run the table filter SQL for one status.
	 *
	 * @param string $segment
	 *
	 * @return int[]
	 */
	protected function filter( string $segment ): array {
		global $wpdb;

		return array_map( 'intval', $wpdb->get_col( $this->journey->get_product_ids_sql( $segment ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Prepared by ProductJourney.
	}

	/**
	 * Create a published simple product with the given sync meta.
	 *
	 * @param array $meta Sync status, MC status, errors, visibility (each optional).
	 *
	 * @return int Product ID.
	 */
	protected function product( array $meta ): int {
		list( $sync_status, $mc_status, $errors, $visibility ) = array_pad( $meta, 4, null );

		$id = WC_Helper_Product::create_simple_product()->get_id();

		$this->set_meta( $id, ProductMetaHandler::KEY_SYNC_STATUS, $sync_status );
		$this->set_meta( $id, ProductMetaHandler::KEY_MC_STATUS, $mc_status );
		$this->set_meta( $id, ProductMetaHandler::KEY_ERRORS, $errors );
		$this->set_meta( $id, ProductMetaHandler::KEY_VISIBILITY, $visibility );

		return $id;
	}

	/**
	 * @param int        $id
	 * @param string     $key Unprefixed meta key.
	 * @param mixed|null $value Skipped when null.
	 */
	protected function set_meta( int $id, string $key, $value ): void {
		if ( null !== $value ) {
			update_post_meta( $id, $this->prefix_meta_key( $key ), $value );
		}
	}
}
