<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Product;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Models\ProductInput;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\AttributeMappingRulesQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidClass;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\BatchInvalidProductEntry;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\BatchProductEntry;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\BatchProductIDRequestEntry;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\TargetAudience;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\BatchProductHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\AttributeManager;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\Brand;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\Color;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductMetaHandler;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\ContainerAwareUnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\ProductMetaTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\ProductTrait;
use PHPUnit\Framework\MockObject\MockObject;
use WC_Helper_Product;
use WC_Product;

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

	/** @var MockObject|TargetAudience $target_audience */
	protected $target_audience;

	/** @var BatchProductHelper $batch_product_helper */
	protected $batch_product_helper;

	/** @var WC $wc */
	protected $wc;

	/** @var AttributeMappingRulesQuery $rules_query */
	protected $rules_query;

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

		$this->target_audience->expects( $this->once() )
			->method( 'get_target_countries' )
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

	public function test_generate_stale_countries_delete_entries() {
		$products         = $this->create_and_return_supported_test_products();
		$stale_product    = $products[0];
		$stale_product_id = $stale_product->get_id();

		$this->target_audience->expects( $this->once() )
			->method( 'get_main_target_country' )
			->willReturn( 'US' );

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

		$this->target_audience->expects( $this->once() )
			->method( 'get_main_target_country' )
			->willReturn( 'US' );

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
		$this->target_audience->expects( $this->any() )->method( 'get_main_target_country' )->willReturn( 'US' );
		$this->target_audience->expects( $this->any() )->method( 'get_target_countries' )->willReturn( [ 'US' ] );
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

	public function test_generate_skips_unchanged_recently_synced_product() {
		$this->target_audience->expects( $this->any() )->method( 'get_main_target_country' )->willReturn( 'US' );
		$this->target_audience->expects( $this->any() )->method( 'get_target_countries' )->willReturn( [ 'US' ] );
		$this->rules_query->expects( $this->any() )->method( 'get_results' )->willReturn( [] );

		$product = WC_Helper_Product::create_simple_product();

		// First pass builds the entry and its payload hash.
		$entries = $this->batch_product_helper->generate_mapi_update_entries( [ $product ] );
		$this->assertCount( 1, $entries );
		$hash = $entries[0]['hash'];

		// Simulate a successful sync of that payload.
		$this->product_meta->update_sync_hash( $product, $hash );
		$this->product_meta->update_synced_at( $product, time() );

		// Unchanged and recently synced: skipped.
		$this->assertEmpty( $this->batch_product_helper->generate_mapi_update_entries( [ $product ] ) );

		// Stale sync (older than the expiry window): not skipped, so it gets refreshed.
		$this->product_meta->update_synced_at( $product, time() - ( 26 * DAY_IN_SECONDS ) );
		$this->assertCount( 1, $this->batch_product_helper->generate_mapi_update_entries( [ $product ] ) );
	}

	public function test_force_resync_filter_includes_unchanged_product() {
		$this->target_audience->expects( $this->any() )->method( 'get_main_target_country' )->willReturn( 'US' );
		$this->target_audience->expects( $this->any() )->method( 'get_target_countries' )->willReturn( [ 'US' ] );
		$this->rules_query->expects( $this->any() )->method( 'get_results' )->willReturn( [] );

		$product = WC_Helper_Product::create_simple_product();
		$entries = $this->batch_product_helper->generate_mapi_update_entries( [ $product ] );
		$this->product_meta->update_sync_hash( $product, $entries[0]['hash'] );
		$this->product_meta->update_synced_at( $product, time() );

		// Would be skipped without the filter.
		$this->assertEmpty( $this->batch_product_helper->generate_mapi_update_entries( [ $product ] ) );

		add_filter( 'woocommerce_gla_force_product_resync', '__return_true' );
		$forced = $this->batch_product_helper->generate_mapi_update_entries( [ $product ] );
		remove_filter( 'woocommerce_gla_force_product_resync', '__return_true' );

		$this->assertCount( 1, $forced );
	}

	public function test_freshness_filter_is_clamped_to_the_expiry_window() {
		$this->target_audience->expects( $this->any() )->method( 'get_main_target_country' )->willReturn( 'US' );
		$this->target_audience->expects( $this->any() )->method( 'get_target_countries' )->willReturn( [ 'US' ] );
		$this->rules_query->expects( $this->any() )->method( 'get_results' )->willReturn( [] );

		$product = WC_Helper_Product::create_simple_product();
		$entries = $this->batch_product_helper->generate_mapi_update_entries( [ $product ] );
		$this->product_meta->update_sync_hash( $product, $entries[0]['hash'] );
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
		$this->target_audience      = $this->createMock( TargetAudience::class );
		$this->product_meta         = $this->container->get( ProductMetaHandler::class );
		$this->wc                   = $this->container->get( WC::class );
		$this->product_helper       = new ProductHelper( $this->product_meta, $this->wc, $this->target_audience );
		$this->rules_query          = $this->createMock( AttributeMappingRulesQuery::class );
		$this->batch_product_helper = new BatchProductHelper( $this->product_meta, $this->product_helper, $this->target_audience, $this->rules_query, $this->container->get( AttributeManager::class ) );
	}
}
