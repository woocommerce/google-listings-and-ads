<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Product;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\GoogleProductService;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductMetaHandler;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductSyncer;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\ContainerAwareUnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\ProductMetaTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\ProductTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\SettingsTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\ChannelVisibility;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\MCStatus;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\SyncStatus;
use PHPUnit\Framework\MockObject\MockObject;
use WC_Helper_Product;
use WC_Product;

/**
 * Class ProductHelperTest
 *
 * @group Helpers
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Product
 */
class ProductHelperTest extends ContainerAwareUnitTest {

	use ProductMetaTrait;
	use ProductTrait;
	use SettingsTrait;

	/** @var ProductMetaHandler $product_meta */
	protected $product_meta;

	/** @var ProductHelper $product_helper */
	protected $product_helper;

	/** @var WC $wc */
	protected $wc;

	/**
	 * @param string $callback
	 *
	 * @dataProvider return_test_product_callbacks
	 */
	public function test_mark_as_synced( string $callback ) {
		$product        = call_user_func( $callback );
		$google_product = $this->generate_google_product_mock();

		// add some random errors residue from previous sync attempts
		$this->product_meta->update_errors( $product, [ 'Error 1', 'Error 2' ] );
		$this->product_meta->update_failed_sync_attempts( $product, 1 );
		$this->product_meta->update_sync_failed_at( $product, 12345 );

		$this->product_helper->mark_as_synced( $product, $google_product, [ $google_product->getTargetCountry() ] );

		$this->assertGreaterThan( 0, $this->product_meta->get_synced_at( $product ) );
		$this->assertEquals( SyncStatus::SYNCED, $this->product_meta->get_sync_status( $product ) );
		$this->assertEquals( [ $google_product->getTargetCountry() => $google_product->getId() ], $this->product_meta->get_google_ids( $product ) );
		$this->assertEquals( ChannelVisibility::SYNC_AND_SHOW, $this->product_meta->get_visibility( $product ) );
		$this->assertEmpty( $this->product_meta->get_errors( $product ) );
		$this->assertEmpty( $this->product_meta->get_failed_sync_attempts( $product ) );
		$this->assertEmpty( $this->product_meta->get_sync_failed_at( $product ) );
	}

	/**
	 * @param string $callback
	 *
	 * @dataProvider return_test_product_callbacks
	 */
	public function test_mark_as_synced_keeps_existing_google_ids( string $callback ) {
		$product        = call_user_func( $callback );
		$google_product = $this->generate_google_product_mock();

		$this->product_meta->update_google_ids( $product, [ 'AU' => 'online:en:AU:gla_1' ] );

		$this->product_helper->mark_as_synced( $product, $google_product );

		$this->assertEqualSets(
			[
				'AU'                                => 'online:en:AU:gla_1',
				$google_product->getTargetCountry() => $google_product->getId(),
			],
			$this->product_meta->get_google_ids( $product )
		);
	}

	/**
	 * @param string $callback
	 *
	 * @dataProvider return_test_product_callbacks
	 */
	public function test_mark_as_synced_keys_google_ids_by_feed_label_when_set( string $callback ) {
		$product        = call_user_func( $callback );
		$google_product = $this->createMock( \Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\Product::class );
		$google_product->method( 'getId' )->willReturn( 'online:en:US:gla_999' );
		$google_product->method( 'getTargetCountry' )->willReturn( 'US' );
		$google_product->method( 'getFeedLabel' )->willReturn( 'MY-FEED' );

		$this->product_helper->mark_as_synced( $product, $google_product, [ 'MY-FEED' ] );

		$google_ids = $this->product_meta->get_google_ids( $product );
		$this->assertArrayHasKey( 'MY-FEED', $google_ids );
		$this->assertArrayNotHasKey( 'US', $google_ids );
		$this->assertSame( 'online:en:US:gla_999', $google_ids['MY-FEED'] );
	}

	/**
	 * @param string $callback
	 *
	 * @dataProvider return_test_product_callbacks
	 */
	public function test_mark_as_synced_stores_two_languages_under_distinct_keys( string $callback ) {
		$product = call_user_func( $callback );

		$default_language_product = $this->createMock( \Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\Product::class );
		$default_language_product->method( 'getId' )->willReturn( 'online:en:BE:gla_1' );
		$default_language_product->method( 'getFeedLabel' )->willReturn( 'BE' );

		$french_product = $this->createMock( \Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\Product::class );
		$french_product->method( 'getId' )->willReturn( 'online:fr:BE-FR:gla_1' );
		$french_product->method( 'getFeedLabel' )->willReturn( 'BE-FR' );

		$this->product_helper->mark_as_synced( $product, $default_language_product );
		$this->product_helper->mark_as_synced( $product, $french_product );

		$this->assertEqualSets(
			[
				'BE'    => 'online:en:BE:gla_1',
				'BE-FR' => 'online:fr:BE-FR:gla_1',
			],
			$this->product_meta->get_google_ids( $product )
		);
	}

	public function test_mark_as_synced_clears_errors_using_language_applicable_labels() {
		$product = WC_Helper_Product::create_simple_product();

		$google_product = $this->createMock( \Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\Product::class );
		$google_product->method( 'getId' )->willReturn( 'online:fr:BE-FR:gla_1' );
		$google_product->method( 'getFeedLabel' )->willReturn( 'BE-FR' );
		$google_product->method( 'getContentLanguage' )->willReturn( 'fr' );

		$this->product_meta->update_errors( $product, [ 'Error 1' ] );

		$this->product_helper->mark_as_synced( $product, $google_product, [ 'BE-FR' ] );

		$this->assertEmpty( $this->product_meta->get_errors( $product ) );
	}

	/**
	 * @param string $callback
	 *
	 * @dataProvider return_test_product_callbacks
	 */
	public function test_mark_as_synced_deletes_errors_when_main_target_countries_synced( string $callback ) {
		$product        = call_user_func( $callback );
		$google_product = $this->generate_google_product_mock();

		// add some random errors residue from previous sync attempts
		$this->product_meta->update_errors( $product, [ 'Error 1', 'Error 2' ] );
		$this->product_meta->update_failed_sync_attempts( $product, 1 );
		$this->product_meta->update_sync_failed_at( $product, 12345 );

		$this->product_helper->mark_as_synced( $product, $google_product, [ $google_product->getTargetCountry() ] );

		$this->assertEmpty( $this->product_meta->get_errors( $product ) );
		$this->assertEmpty( $this->product_meta->get_failed_sync_attempts( $product ) );
		$this->assertEmpty( $this->product_meta->get_sync_failed_at( $product ) );
	}

	/**
	 * @param string $callback
	 *
	 * @dataProvider return_test_product_callbacks
	 */
	public function test_mark_as_synced_doesnt_delete_errors_unless_main_target_countries_synced( string $callback ) {
		$product        = call_user_func( $callback );
		$google_product = $this->generate_google_product_mock();

		// add some random errors residue from previous sync attempts
		$this->product_meta->update_errors( $product, [ 'Error 1', 'Error 2' ] );
		$this->product_meta->update_failed_sync_attempts( $product, 1 );
		$this->product_meta->update_sync_failed_at( $product, 12345 );

		$this->product_helper->mark_as_synced( $product, $google_product, [ 'AU', 'CA' ] );

		$this->assertEquals( [ 'Error 1', 'Error 2' ], $this->product_meta->get_errors( $product ) );
		$this->assertEquals( 1, $this->product_meta->get_failed_sync_attempts( $product ) );
		$this->assertEquals( 12345, $this->product_meta->get_sync_failed_at( $product ) );
	}

	public function test_mark_as_synced_updates_both_variation_and_parent() {
		$google_product = $this->generate_google_product_mock();
		$parent         = WC_Helper_Product::create_variation_product();
		$variation      = $this->wc->get_product( $parent->get_children()[0] );

		// add some random errors residue from previous sync attempts
		$this->product_meta->update_errors( $variation, [ 'Error 1', 'Error 2' ] );
		$this->product_meta->update_failed_sync_attempts( $variation, 1 );
		$this->product_meta->update_sync_failed_at( $variation, 12345 );
		$this->product_meta->update_errors( $parent, [ $parent->get_id() => [ 'Error 1', 'Error 2' ] ] );
		$this->product_meta->update_failed_sync_attempts( $parent, 1 );
		$this->product_meta->update_sync_failed_at( $parent, 12345 );

		$this->product_helper->mark_as_synced( $variation, $google_product, [ $google_product->getTargetCountry() ] );

		// get the updated parent object from DB
		$parent = $this->wc->get_product( $variation->get_parent_id() );

		// visibility is only updated for the parent
		$this->assertEquals( ChannelVisibility::SYNC_AND_SHOW, $this->product_meta->get_visibility( $parent ) );

		foreach ( [ $parent, $variation ] as $product ) {
			$this->assertGreaterThan( 0, $this->product_meta->get_synced_at( $product ) );
			$this->assertEquals( SyncStatus::SYNCED, $this->product_meta->get_sync_status( $product ) );
			$this->assertEquals( [ $google_product->getTargetCountry() => $google_product->getId() ], $this->product_meta->get_google_ids( $product ) );
			$this->assertEmpty( $this->product_meta->get_errors( $product ) );
			$this->assertEmpty( $this->product_meta->get_failed_sync_attempts( $product ) );
			$this->assertEmpty( $this->product_meta->get_sync_failed_at( $product ) );
		}
	}

	public function test_mark_as_synced_does_not_update_parent_if_orphan_variation() {
		$google_product = $this->generate_google_product_mock();
		$parent         = WC_Helper_Product::create_variation_product();
		$variation      = $this->wc->get_product( $parent->get_children()[0] );

		// make the variation orphan by setting its parent to 0
		$variation->set_parent_id( 0 );
		$variation->save();

		$this->product_helper->mark_as_synced( $variation, $google_product, [ $google_product->getTargetCountry() ] );

		// get the updated parent object from DB
		$parent = $this->wc->get_product( $parent->get_id() );

		$this->assertGreaterThan( 0, $this->product_meta->get_synced_at( $variation ) );
		$this->assertEquals( SyncStatus::SYNCED, $this->product_meta->get_sync_status( $variation ) );
		$this->assertEquals( [ $google_product->getTargetCountry() => $google_product->getId() ], $this->product_meta->get_google_ids( $variation ) );

		$this->assertEmpty( $this->product_meta->get_synced_at( $parent ) );
		$this->assertEmpty( $this->product_meta->get_sync_status( $parent ) );
		$this->assertEmpty( $this->product_meta->get_google_ids( $parent ) );
	}

	/**
	 * @param string $callback
	 *
	 * @dataProvider return_test_product_callbacks
	 */
	public function test_mark_as_unsynced( string $callback ) {
		$product = call_user_func( $callback );
		// First mark the product as synced to update its meta data
		$this->product_helper->mark_as_synced( $product, $this->generate_google_product_mock() );
		$this->product_helper->update_sync_hash( $product, 'somehash', 'en', 'US' );

		$this->product_helper->mark_as_unsynced( $product );

		$this->assertEmpty( $this->product_meta->get_synced_at( $product ) );
		$this->assertEquals( SyncStatus::NOT_SYNCED, $this->product_meta->get_sync_status( $product ) );
		$this->assertEmpty( $this->product_meta->get_google_ids( $product ) );
		$this->assertEmpty( $this->product_meta->get_errors( $product ) );
		$this->assertEmpty( $this->product_meta->get_failed_sync_attempts( $product ) );
		$this->assertEmpty( $this->product_meta->get_sync_failed_at( $product ) );
		$this->assertEmpty( $this->product_meta->get_sync_hash( $product ) );
	}

	public function test_update_sync_hash_keys_by_language_and_feed_label() {
		$product = WC_Helper_Product::create_simple_product();

		$this->product_helper->update_sync_hash( $product, 'hash-us', 'en', 'US' );
		$this->product_helper->update_sync_hash( $product, 'hash-de', 'de', 'DE-DE-EUR' );

		$this->assertSame(
			[
				'en|US'        => 'hash-us',
				'de|DE-DE-EUR' => 'hash-de',
			],
			$this->product_meta->get_sync_hash( $product )
		);

		// Updating one entry leaves the other keys untouched.
		$this->product_helper->update_sync_hash( $product, 'hash-us-2', 'en', 'US' );
		$this->assertSame(
			[
				'en|US'        => 'hash-us-2',
				'de|DE-DE-EUR' => 'hash-de',
			],
			$this->product_meta->get_sync_hash( $product )
		);
	}

	public function test_update_sync_hash_replaces_a_legacy_string_value() {
		$product = WC_Helper_Product::create_simple_product();
		$product->update_meta_data( '_wc_gla_sync_hash', 'legacy-hash' );
		$product->save_meta_data();

		$this->product_helper->update_sync_hash( $product, 'hash-us', 'en', 'US' );

		$this->assertSame( [ 'en|US' => 'hash-us' ], $this->product_meta->get_sync_hash( $product ) );
	}

	public function test_mark_as_unsynced_remove_sync_status_for_unsyncable_products() {
		$product = WC_Helper_Product::create_simple_product();
		$this->product_helper->mark_as_synced( $product, $this->generate_google_product_mock() );
		$product->set_status( 'publish' );
		$product->save();
		$this->product_meta->update_visibility( $product, ChannelVisibility::DONT_SYNC_AND_SHOW );

		$this->product_helper->mark_as_unsynced( $product );

		$this->assertEmpty( $this->product_meta->get_synced_at( $product ) );
		$this->assertEquals( null, $this->product_meta->get_sync_status( $product ) );
		$this->assertEmpty( $this->product_meta->get_google_ids( $product ) );
		$this->assertEmpty( $this->product_meta->get_errors( $product ) );
		$this->assertEmpty( $this->product_meta->get_failed_sync_attempts( $product ) );
		$this->assertEmpty( $this->product_meta->get_sync_failed_at( $product ) );
	}

	public function test_mark_as_unsynced_updates_both_variation_and_parent() {
		$parent    = WC_Helper_Product::create_variation_product();
		$variation = $this->wc->get_product( $parent->get_children()[0] );

		// First mark the product as synced to update its meta data
		$this->product_helper->mark_as_synced( $variation, $this->generate_google_product_mock() );
		$this->product_helper->update_sync_hash( $variation, 'somehash', 'en', 'US' );
		$this->product_helper->update_sync_hash( $parent, 'somehash', 'en', 'US' );

		$this->product_helper->mark_as_unsynced( $variation );

		// get the updated parent object from DB
		$parent = $this->wc->get_product( $variation->get_parent_id() );

		foreach ( [ $parent, $variation ] as $product ) {
			$this->assertEmpty( $this->product_meta->get_synced_at( $product ) );
			$this->assertEquals( SyncStatus::NOT_SYNCED, $this->product_meta->get_sync_status( $product ) );
			$this->assertEmpty( $this->product_meta->get_google_ids( $product ) );
			$this->assertEmpty( $this->product_meta->get_errors( $product ) );
			$this->assertEmpty( $this->product_meta->get_failed_sync_attempts( $product ) );
			$this->assertEmpty( $this->product_meta->get_sync_failed_at( $product ) );
			$this->assertEmpty( $this->product_meta->get_sync_hash( $product ) );
		}
	}

	public function test_mark_as_unsynced_does_not_update_parent_if_orphan_variation() {
		$parent    = WC_Helper_Product::create_variation_product();
		$variation = $this->wc->get_product( $parent->get_children()[0] );

		// First mark the product as synced to update its meta data
		$this->product_helper->mark_as_synced( $variation, $this->generate_google_product_mock() );
		$this->product_helper->update_sync_hash( $variation, 'somehash', 'en', 'US' );
		$this->product_helper->update_sync_hash( $parent, 'somehash', 'en', 'US' );

		// make the variation orphan by setting its parent to 0
		$variation->set_parent_id( 0 );
		$variation->save();

		$this->product_helper->mark_as_unsynced( $variation );

		// get the updated parent object from DB
		$parent = $this->wc->get_product( $parent->get_id() );

		$this->assertEmpty( $this->product_meta->get_synced_at( $variation ) );
		// Orphaned variation is not syncable so the sync status
		// will be deleted when calling mark_as_unsynced.
		$this->assertEquals( null, $this->product_meta->get_sync_status( $variation ) );
		$this->assertEmpty( $this->product_meta->get_google_ids( $variation ) );
		$this->assertEmpty( $this->product_meta->get_sync_hash( $variation ) );

		$this->assertNotEmpty( $this->product_meta->get_synced_at( $parent ) );
		$this->assertEquals( SyncStatus::SYNCED, $this->product_meta->get_sync_status( $parent ) );
		$this->assertNotEmpty( $this->product_meta->get_google_ids( $parent ) );
		$this->assertSame( [ 'en|US' => 'somehash' ], $this->product_meta->get_sync_hash( $parent ) );
	}

	/**
	 * @param string $callback
	 *
	 * @dataProvider return_test_product_callbacks
	 */
	public function test_remove_google_id( string $callback ) {
		$product = call_user_func( $callback );
		$this->product_meta->update_google_ids(
			$product,
			[
				'AU' => 'online:en:AU:gla_1',
				'US' => 'online:en:US:gla_1',
			]
		);

		$this->product_helper->remove_google_id( $product, 'online:en:US:gla_1' );

		$this->assertEquals( [ 'AU' => 'online:en:AU:gla_1' ], $this->product_meta->get_google_ids( $product ) );
	}

	/**
	 * @param string $callback
	 *
	 * @dataProvider return_test_product_callbacks
	 */
	public function test_remove_google_id_marks_as_unsynced_if_empty_ids( string $callback ) {
		$product = call_user_func( $callback );
		$this->product_meta->update_google_ids( $product, [ 'US' => 'online:en:US:gla_1' ] );

		$this->product_helper->remove_google_id( $product, 'online:en:US:gla_1' );

		$this->assertEmpty( $this->product_meta->get_google_ids( $product ) );
		$this->assertFalse( $this->product_helper->is_product_synced( $product ) );
	}

	/**
	 * @param string $callback
	 *
	 * @dataProvider return_test_product_callbacks
	 */
	public function test_mark_as_invalid( string $callback ) {
		$product = call_user_func( $callback );
		$errors  = [
			'Error 1',
			'Error 2',
		];

		$this->product_helper->mark_as_invalid( $product, $errors );

		$this->assertEqualSets( $errors, $this->product_meta->get_errors( $product ) );
		$this->assertEquals( SyncStatus::HAS_ERRORS, $this->product_meta->get_sync_status( $product ) );

		// Visibility is updated for a product that has none set
		$this->assertEquals( ChannelVisibility::SYNC_AND_SHOW, $this->product_meta->get_visibility( $product ) );

		// Sync attempts should not be updated when no internal error is present
		$this->assertEmpty( $this->product_meta->get_failed_sync_attempts( $product ) );
		$this->assertEmpty( $this->product_meta->get_sync_failed_at( $product ) );
	}

	/**
	 * @param string $callback
	 *
	 * @dataProvider return_test_product_callbacks
	 */
	public function test_mark_as_invalid_updates_failed_sync_attempts_if_internal_error_exists( string $callback ) {
		$product = call_user_func( $callback );
		$errors  = [
			'Error 1',
			'Error 2',
			GoogleProductService::INTERNAL_ERROR_REASON => 'Internal error',
		];

		$this->product_helper->mark_as_invalid( $product, $errors );

		$this->assertGreaterThan( 0, $this->product_meta->get_failed_sync_attempts( $product ) );
		$this->assertGreaterThan( 0, $this->product_meta->get_sync_failed_at( $product ) );
	}

	public function test_mark_as_invalid_updates_both_variation_and_parent() {
		$errors        = [
			'Error 1',
			'Error 2',
		];
		$parent_errors = [
			'some_variation_id' => [
				'Another Variation Error 1',
				'Another Variation Error 2',
			],
		];

		$parent    = WC_Helper_Product::create_variation_product();
		$variation = $this->wc->get_product( $parent->get_children()[0] );

		// Set some random errors for the parent product
		$this->product_meta->update_errors( $parent, $parent_errors );

		$this->product_helper->mark_as_invalid( $variation, $errors );

		// get the updated parent object from DB
		$parent = $this->wc->get_product( $variation->get_parent_id() );

		// Visibility is updated for a parent product that has none set
		$this->assertEquals( ChannelVisibility::SYNC_AND_SHOW, $this->product_meta->get_visibility( $parent ) );

		$this->assertEqualSets( $errors, $this->product_meta->get_errors( $variation ) );
		$this->assertEqualSets( array_merge( [ $variation->get_id() => $errors ], $parent_errors ), $this->product_meta->get_errors( $parent ) );

		foreach ( [ $parent, $variation ] as $product ) {
			$this->assertEquals( SyncStatus::HAS_ERRORS, $this->product_meta->get_sync_status( $product ) );

			// Sync attempts should not be updated when no internal error is present
			$this->assertEmpty( $this->product_meta->get_failed_sync_attempts( $product ) );
			$this->assertEmpty( $this->product_meta->get_sync_failed_at( $product ) );
		}
	}

	public function test_mark_as_invalid_does_not_update_parent_if_orphan_variation() {
		$errors = [
			'Error 1',
			'Error 2',
		];

		$parent    = WC_Helper_Product::create_variation_product();
		$variation = $this->wc->get_product( $parent->get_children()[0] );

		// make the variation orphan by setting its parent to 0
		$variation->set_parent_id( 0 );
		$variation->save();

		$this->product_helper->mark_as_invalid( $variation, $errors );

		// get the updated parent object from DB
		$parent = $this->wc->get_product( $parent->get_id() );

		$this->assertEqualSets( $errors, $this->product_meta->get_errors( $variation ) );
		$this->assertEquals( SyncStatus::HAS_ERRORS, $this->product_meta->get_sync_status( $variation ) );

		$this->assertEmpty( $this->product_meta->get_errors( $parent ) );
		$this->assertEmpty( $this->product_meta->get_sync_status( $parent ) );
	}

	/**
	 * @param string $callback
	 *
	 * @dataProvider return_test_product_callbacks
	 */
	public function test_mark_as_pending( string $callback ) {
		$product = call_user_func( $callback );
		$this->product_helper->mark_as_pending( $product );

		$this->assertEquals( SyncStatus::PENDING, $this->product_meta->get_sync_status( $product ) );
	}

	public function test_mark_as_pending_updates_both_variation_and_parent() {
		$parent    = WC_Helper_Product::create_variation_product();
		$variation = $this->wc->get_product( $parent->get_children()[0] );

		$this->product_helper->mark_as_pending( $variation );

		// get the updated parent object from DB
		$parent = $this->wc->get_product( $variation->get_parent_id() );

		$this->assertEquals( SyncStatus::PENDING, $this->product_meta->get_sync_status( $variation ) );
		$this->assertEquals( SyncStatus::PENDING, $this->product_meta->get_sync_status( $parent ) );
	}

	public function test_mark_as_pending_does_not_update_parent_if_orphan_variation() {
		$parent    = WC_Helper_Product::create_variation_product();
		$variation = $this->wc->get_product( $parent->get_children()[0] );

		// make the variation orphan by setting its parent to 0
		$variation->set_parent_id( 0 );
		$variation->save();

		$this->product_helper->mark_as_pending( $variation );

		// get the updated parent object from DB
		$parent = $this->wc->get_product( $parent->get_id() );

		$this->assertEquals( SyncStatus::PENDING, $this->product_meta->get_sync_status( $variation ) );
		$this->assertEmpty( $this->product_meta->get_sync_status( $parent ) );
	}

	/**
	 * @param string $callback
	 *
	 * @dataProvider return_test_product_callbacks
	 */
	public function test_get_synced_google_product_ids( string $callback ) {
		$product = call_user_func( $callback );
		$this->product_meta->update_google_ids( $product, [ 'US' => 'online:en:US:gla_1' ] );

		$this->assertEquals( [ 'US' => 'online:en:US:gla_1' ], $this->product_helper->get_synced_google_product_ids( $product ) );
	}

	/**
	 * Test a legitimate Google product ID both with and without namespacing.
	 *
	 * @return void
	 */
	public function test_get_wc_product_id() {
		$google_id  = 'online:en:US:gla_1234567';
		$product_id = $this->product_helper->get_wc_product_id( $google_id );

		$this->assertEquals( 1234567, $product_id );

		$google_id  = 'gla_4567890';
		$product_id = $this->product_helper->get_wc_product_id( $google_id );

		$this->assertEquals( 4567890, $product_id );
	}

	/**
	 * Confirm we support converting a product ID without the `gla_` prefix.
	 */
	public function test_get_wc_product_id_without_prefix() {
		$google_id  = 'online:en:US:1234567';
		$product_id = $this->product_helper->get_wc_product_id( $google_id );

		$this->assertEquals( 1234567, $product_id );
	}

	/**
	 * If we don't add the prefix confirm it only accepts a fully numeric ID.
	 */
	public function test_get_wc_product_id_without_prefix_only_numeric() {
		$google_id  = 'online:en:US:123abc';
		$product_id = $this->product_helper->get_wc_product_id( $google_id );

		$this->assertEquals( 0, $product_id );

		$google_id  = 'online:en:US:abc123';
		$product_id = $this->product_helper->get_wc_product_id( $google_id );

		$this->assertEquals( 0, $product_id );
	}

	/**
	 * Confirm `gla_` prefix must be at the beginning and followed by a numeric ID.
	 */
	public function test_get_wc_product_id_returns_zero_if_no_id_matches() {
		$google_id  = 'online:en:US:invalid_gla_123';
		$product_id = $this->product_helper->get_wc_product_id( $google_id );

		$this->assertEquals( 0, $product_id );

		$google_id  = 'online:en:US:gla_invalid_id_1';
		$product_id = $this->product_helper->get_wc_product_id( $google_id );

		$this->assertEquals( 0, $product_id );
	}

	public function test_get_wc_product_id_custom_map_filter_with_prefixed_id() {
		add_filter(
			'woocommerce_gla_get_wc_product_id',
			function ( $wc_product_id, $mc_product_id ) {
				if ( $mc_product_id === 'some_custom_mc_product_id' ) {
					$wc_product_id = 55;
				}
				return $wc_product_id;
			},
			10,
			2
		);
		// Custom map found, prefixed MC product ID
		$this->assertEquals( 55, $this->product_helper->get_wc_product_id( 'online:en:US:some_custom_mc_product_id' ) );
		// Custom map found, simple MC product ID
		$this->assertEquals( 55, $this->product_helper->get_wc_product_id( 'some_custom_mc_product_id' ) );
		// No custom map found, prefixed MC product ID
		$this->assertEquals( 1234567, $this->product_helper->get_wc_product_id( 'online:en:US:gla_1234567' ) );
		// No custom map found, simple MC product ID
		$this->assertEquals( 1234567, $this->product_helper->get_wc_product_id( 'gla_1234567' ) );
		// Invalid ID, not WC product or custom map
		$this->assertEquals( 0, $this->product_helper->get_wc_product_id( 'online:en:US:not_gla_or_mapped' ) );
	}

	public function test_get_wc_product_title() {
		$product = WC_Helper_Product::create_simple_product();

		$google_id     = 'online:en:US:gla_' . $product->get_id();
		$product_title = $this->product_helper->get_wc_product_title( $google_id );

		$this->assertEquals( $product->get_title(), $product_title );
	}

	public function test_get_wc_product_title_returns_google_id_if_product_cant_be_found() {
		$google_id     = 'online:en:US:gla_123456789';
		$product_title = $this->product_helper->get_wc_product_title( $google_id );

		$this->assertEquals( $google_id, $product_title );
	}

	public function test_get_wc_product() {
		$product = WC_Helper_Product::create_simple_product();
		$result  = $this->product_helper->get_wc_product( $product->get_id() );

		$this->assertEquals( $product, $result );
	}

	/**
	 * @param string $callback
	 *
	 * @dataProvider return_test_product_callbacks
	 */
	public function test_is_product_synced( string $callback ) {
		$product = call_user_func( $callback );
		$this->product_helper->mark_as_synced( $product, $this->generate_google_product_mock() );
		$is_product_synced = $this->product_helper->is_product_synced( $product );
		$this->assertTrue( $is_product_synced );
	}

	/**
	 * @param string $callback
	 *
	 * @dataProvider return_test_product_callbacks
	 */
	public function test_is_product_synced_return_false_if_no_google_id( string $callback ) {
		$product = call_user_func( $callback );
		$this->product_helper->mark_as_synced( $product, $this->generate_google_product_mock() );
		$this->product_meta->delete_google_ids( $product );
		$is_product_synced = $this->product_helper->is_product_synced( $product );
		$this->assertFalse( $is_product_synced );
	}

	/**
	 * @param string $callback
	 *
	 * @dataProvider return_test_product_callbacks
	 */
	public function test_is_product_synced_return_false_if_no_synced_at( string $callback ) {
		$product = call_user_func( $callback );
		$this->product_helper->mark_as_synced( $product, $this->generate_google_product_mock() );
		$this->product_meta->delete_synced_at( $product );
		$is_product_synced = $this->product_helper->is_product_synced( $product );
		$this->assertFalse( $is_product_synced );
	}

	/**
	 * @param string $callback
	 *
	 * @dataProvider return_test_product_callbacks
	 */
	public function test_is_sync_ready_visible_published( string $callback ) {
		$product = call_user_func( $callback );
		$product->set_status( 'publish' );
		$product->save();
		$this->product_meta->update_visibility( $product, ChannelVisibility::SYNC_AND_SHOW );
		$result = $this->product_helper->is_sync_ready( $product );
		$this->assertTrue( $result );
	}

	/**
	 * @param string $callback
	 *
	 * @dataProvider return_test_product_callbacks
	 */
	public function test_is_sync_ready_not_visible_published( string $callback ) {
		$product = call_user_func( $callback );
		$product->set_status( 'publish' );
		$product->save();
		$this->product_meta->update_visibility( $product, ChannelVisibility::DONT_SYNC_AND_SHOW );
		$result = $this->product_helper->is_sync_ready( $product );
		$this->assertFalse( $result );
	}

	/**
	 * @param string $callback
	 *
	 * @dataProvider return_test_product_callbacks
	 */
	public function test_is_sync_ready_visible_not_published( string $callback ) {
		$product = call_user_func( $callback );
		$product->set_status( 'draft' );
		$product->save();
		$this->product_meta->update_visibility( $product, ChannelVisibility::SYNC_AND_SHOW );
		$result = $this->product_helper->is_sync_ready( $product );
		$this->assertFalse( $result );
	}

	public function test_is_sync_ready_out_of_stock() {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_status( 'publish' );
		$product->set_stock_status( 'outofstock' );
		$product->save();
		$this->product_meta->update_visibility( $product, ChannelVisibility::SYNC_AND_SHOW );

		update_option( 'woocommerce_hide_out_of_stock_items', 'yes' );
		$this->assertFalse( $this->product_helper->is_sync_ready( $product ) );
		update_option( 'woocommerce_hide_out_of_stock_items', 'no' );
		$this->assertTrue( $this->product_helper->is_sync_ready( $product ) );
	}

	public function test_is_sync_ready_variation_out_of_stock() {
		$parent = WC_Helper_Product::create_variation_product();
		$parent->set_status( 'publish' );
		$parent->save();
		$this->product_meta->update_visibility( $parent, ChannelVisibility::SYNC_AND_SHOW );

		$variation_1 = $this->wc->get_product( $parent->get_children()[0] );
		$variation_1->set_stock_status( 'outofstock' );
		$variation_1->save();

		update_option( 'woocommerce_hide_out_of_stock_items', 'yes' );
		$this->assertFalse( $this->product_helper->is_sync_ready( $variation_1 ) );
		update_option( 'woocommerce_hide_out_of_stock_items', 'no' );
		$this->assertTrue( $this->product_helper->is_sync_ready( $variation_1 ) );
	}

	public function test_is_sync_ready_variation_with_no_price() {
		$parent = WC_Helper_Product::create_variation_product();
		$parent->set_status( 'publish' );
		$parent->save();
		$this->product_meta->update_visibility( $parent, ChannelVisibility::SYNC_AND_SHOW );

		$variation_1 = $this->wc->get_product( $parent->get_children()[0] );
		$variation_1->set_price( '' );
		$variation_1->set_regular_price( '' );
		$variation_1->save();

		$this->assertFalse( $this->product_helper->is_sync_ready( $variation_1 ) );
	}

	public function test_is_sync_ready_variation_disabled() {
		$parent = WC_Helper_Product::create_variation_product();
		$parent->set_status( 'publish' );
		$parent->save();
		$this->product_meta->update_visibility( $parent, ChannelVisibility::SYNC_AND_SHOW );

		$variation_1 = $this->wc->get_product( $parent->get_children()[0] );
		$variation_1->set_status( 'private' );
		$variation_1->save();

		$this->assertFalse( $this->product_helper->is_sync_ready( $variation_1 ) );
	}

	public function test_is_sync_ready_variation_disabled_but_hide_invisible_filter_returns_false() {
		add_filter(
			'woocommerce_hide_invisible_variations',
			function () {
				return false;
			}
		);

		$parent = WC_Helper_Product::create_variation_product();
		$parent->set_status( 'publish' );
		$parent->save();
		$this->product_meta->update_visibility( $parent, ChannelVisibility::SYNC_AND_SHOW );

		$variation_1 = $this->wc->get_product( $parent->get_children()[0] );
		$parent->set_status( 'private' );
		$variation_1->save();

		$this->assertTrue( $this->product_helper->is_sync_ready( $variation_1 ) );
	}

	public function test_is_sync_ready_variable_out_of_stock() {
		update_option( 'woocommerce_hide_out_of_stock_items', 'yes' );

		$parent = WC_Helper_Product::create_variation_product();
		$parent->set_status( 'publish' );
		$parent->save();
		$this->product_meta->update_visibility( $parent, ChannelVisibility::SYNC_AND_SHOW );

		foreach ( $parent->get_children() as $variation_id ) {
			$variation = $this->wc->get_product( $variation_id );
			$variation->set_stock_status( 'outofstock' );
			$variation->save();
		}
		$parent->set_stock_status( 'outofstock' );
		$parent->save();

		update_option( 'woocommerce_hide_out_of_stock_items', 'yes' );
		$this->assertFalse( $this->product_helper->is_sync_ready( $parent ) );
		update_option( 'woocommerce_hide_out_of_stock_items', 'no' );
		$this->assertTrue( $this->product_helper->is_sync_ready( $parent ) );
	}

	public function test_is_sync_ready_variation_parent_not_visible_but_published() {
		$parent = WC_Helper_Product::create_variation_product();
		$parent->set_status( 'publish' );
		$parent->save();
		$this->product_meta->update_visibility( $parent, ChannelVisibility::DONT_SYNC_AND_SHOW );

		$variation = $this->wc->get_product( $parent->get_children()[0] );
		$variation->set_status( 'publish' );
		$variation->save();
		$this->product_meta->update_visibility( $variation, ChannelVisibility::SYNC_AND_SHOW );
		$this->assertFalse( $this->product_helper->is_sync_ready( $variation ) );
	}

	public function test_is_sync_ready_variation_parent_visible_but_not_published() {
		$parent = WC_Helper_Product::create_variation_product();
		$parent->set_status( 'draft' );
		$parent->save();
		$this->product_meta->update_visibility( $parent, ChannelVisibility::SYNC_AND_SHOW );

		$variation = $this->wc->get_product( $parent->get_children()[0] );
		$variation->set_status( 'publish' );
		$variation->save();
		$this->product_meta->update_visibility( $variation, ChannelVisibility::SYNC_AND_SHOW );

		$this->assertFalse( $this->product_helper->is_sync_ready( $variation ) );
	}

	public function test_is_sync_ready_variation_parent_hidden_in_catalog() {
		$parent = WC_Helper_Product::create_variation_product();
		$parent->set_status( 'publish' );
		$parent->set_catalog_visibility( 'hidden' );
		$parent->save();
		$this->product_meta->update_visibility( $parent, ChannelVisibility::SYNC_AND_SHOW );

		$variation = $this->wc->get_product( $parent->get_children()[0] );
		$variation->set_status( 'publish' );
		$variation->save();
		$this->product_meta->update_visibility( $variation, ChannelVisibility::SYNC_AND_SHOW );
		$this->assertFalse( $this->product_helper->is_sync_ready( $variation ) );
	}

	public function test_is_sync_ready_variation_returns_false_if_orphan() {
		$parent = WC_Helper_Product::create_variation_product();
		$parent->set_status( 'publish' );
		$parent->save();
		$this->product_meta->update_visibility( $parent, ChannelVisibility::SYNC_AND_SHOW );

		$variation = $this->wc->get_product( $parent->get_children()[0] );
		$variation->set_status( 'publish' );
		$variation->save();
		$this->product_meta->update_visibility( $variation, ChannelVisibility::SYNC_AND_SHOW );

		$this->assertTrue( $this->product_helper->is_sync_ready( $variation ) );

		// make the variation orphan by setting its parent to 0
		$variation->set_parent_id( 0 );
		$variation->save();

		$this->assertFalse( $this->product_helper->is_sync_ready( $variation ) );
	}

	/**
	 * @param string $callback
	 *
	 * @dataProvider return_test_product_callbacks
	 */
	public function test_is_sync_failed_recently( string $callback ) {
		$product = call_user_func( $callback );
		$this->product_meta->update_failed_sync_attempts( $product, ProductSyncer::FAILURE_THRESHOLD + 5 );
		$this->product_meta->update_sync_failed_at( $product, strtotime( '+1 year' ) );
		$this->assertTrue( $this->product_helper->is_sync_failed_recently( $product ) );
	}

	/**
	 * @param string $callback
	 *
	 * @dataProvider return_test_product_callbacks
	 */
	public function test_is_sync_failed_recently_less_than_threshold( string $callback ) {
		$product = call_user_func( $callback );
		$this->product_meta->update_failed_sync_attempts( $product, ProductSyncer::FAILURE_THRESHOLD - 1 );
		$this->product_meta->update_sync_failed_at( $product, strtotime( '+1 year' ) );
		$this->assertFalse( $this->product_helper->is_sync_failed_recently( $product ) );
	}

	/**
	 * @param string $callback
	 *
	 * @dataProvider return_test_product_callbacks
	 */
	public function test_is_sync_failed_recently_old_failure_but_more_than_threshold( string $callback ) {
		$product = call_user_func( $callback );
		$this->product_meta->update_failed_sync_attempts( $product, ProductSyncer::FAILURE_THRESHOLD + 5 );
		$this->product_meta->update_sync_failed_at( $product, strtotime( '-1 year' ) );
		$this->assertFalse( $this->product_helper->is_sync_failed_recently( $product ) );
	}

	/**
	 * @param string $callback
	 *
	 * @dataProvider return_test_product_callbacks
	 */
	public function test_get_channel_visibility( string $callback ) {
		$product = call_user_func( $callback );
		$this->product_meta->update_visibility( $product, ChannelVisibility::DONT_SYNC_AND_SHOW );
		$result = $this->product_helper->get_channel_visibility( $product );
		$this->assertEquals( ChannelVisibility::DONT_SYNC_AND_SHOW, $result );
	}

	public function test_get_channel_visibility_variation_product_inherits_from_parent() {
		$parent    = WC_Helper_Product::create_variation_product();
		$variation = $this->wc->get_product( $parent->get_children()[0] );
		$this->product_meta->update_visibility( $parent, ChannelVisibility::DONT_SYNC_AND_SHOW );
		$this->product_meta->update_visibility( $variation, ChannelVisibility::SYNC_AND_SHOW );
		$this->assertEquals( ChannelVisibility::DONT_SYNC_AND_SHOW, $this->product_helper->get_channel_visibility( $variation ) );
	}

	public function test_get_channel_visibility_variation_product_returns_dont_sync_and_show_if_orphan() {
		$variable = WC_Helper_Product::create_variation_product();
		$this->product_meta->update_visibility( $variable, ChannelVisibility::SYNC_AND_SHOW );

		$variation = $this->wc->get_product( $variable->get_children()[0] );

		$this->assertEquals( ChannelVisibility::SYNC_AND_SHOW, $this->product_helper->get_channel_visibility( $variation ) );

		// make the variation orphan by setting its parent to 0
		$variation->set_parent_id( 0 );
		$variation->save();

		$this->assertEquals( ChannelVisibility::DONT_SYNC_AND_SHOW, $this->product_helper->get_channel_visibility( $variation ) );
	}

	/**
	 * @param string $callback
	 *
	 * @dataProvider return_test_product_callbacks
	 */
	public function test_update_channel_visibility( string $callback ) {
		$product = call_user_func( $callback );
		$this->assertNull( $this->product_meta->get_visibility( $product ) );

		$this->product_helper->update_channel_visibility( $product, ChannelVisibility::SYNC_AND_SHOW );
		$this->assertEquals( ChannelVisibility::SYNC_AND_SHOW, $this->product_meta->get_visibility( $product ) );

		$this->product_helper->update_channel_visibility( $product, ChannelVisibility::DONT_SYNC_AND_SHOW );
		$this->assertEquals( ChannelVisibility::DONT_SYNC_AND_SHOW, $this->product_meta->get_visibility( $product ) );
	}

	public function test_update_channel_visibility_variation_product_inherits_from_parent() {
		$parent    = WC_Helper_Product::create_variation_product();
		$variation = $this->wc->get_product( $parent->get_children()[0] );

		$this->product_helper->update_channel_visibility( $parent, ChannelVisibility::DONT_SYNC_AND_SHOW );
		$this->assertEquals( ChannelVisibility::DONT_SYNC_AND_SHOW, $this->product_meta->get_visibility( $parent ) );

		$this->product_helper->update_channel_visibility( $variation, ChannelVisibility::SYNC_AND_SHOW );

		// The `$parent` must be recreated to sync the latest product data updated by
		// a different instance.
		$parent = $this->wc->get_product( $parent->get_id() );
		$this->assertEquals( ChannelVisibility::SYNC_AND_SHOW, $this->product_meta->get_visibility( $parent ) );
	}

	/**
	 * @param string $callback
	 *
	 * @dataProvider return_test_product_callbacks
	 */
	public function test_update_channel_visibility_wont_update_if_invalid_value( string $callback ) {
		$product = call_user_func( $callback );
		$this->assertNull( $this->product_meta->get_visibility( $product ) );
		$this->product_helper->update_channel_visibility( $product, 'phpunit-test-disallowed-value' );
		$this->assertNull( $this->product_meta->get_visibility( $product ) );
	}

	public function test_update_channel_visibility_wont_update_if_orphan() {
		$variable  = WC_Helper_Product::create_variation_product();
		$variation = $this->wc->get_product( $variable->get_children()[0] );

		$this->product_helper->update_channel_visibility( $variable, ChannelVisibility::DONT_SYNC_AND_SHOW );
		$this->assertEquals( ChannelVisibility::DONT_SYNC_AND_SHOW, $this->product_meta->get_visibility( $variable ) );

		// Make the variation orphan by setting its parent to 0.
		$variation->set_parent_id( 0 );
		$variation->save();

		$this->product_helper->update_channel_visibility( $variation, ChannelVisibility::SYNC_AND_SHOW );

		// The `$variable` must be recreated to sync the latest product data therefore
		// it can determine whether a different instance has updated it.
		$variable = $this->wc->get_product( $variable->get_id() );
		$this->assertEquals( ChannelVisibility::DONT_SYNC_AND_SHOW, $this->product_meta->get_visibility( $variable ) );
	}

	/**
	 * @param string $callback
	 *
	 * @dataProvider return_test_product_callbacks
	 */
	public function test_get_sync_status( string $callback ) {
		$product = call_user_func( $callback );
		$this->product_meta->update_sync_status( $product, SyncStatus::SYNCED );
		$this->assertEquals( SyncStatus::SYNCED, $this->product_helper->get_sync_status( $product ) );
	}

	/**
	 * @param string $callback
	 *
	 * @dataProvider return_test_product_callbacks
	 */
	public function test_get_mc_status( string $callback ) {
		$product = call_user_func( $callback );
		$this->product_meta->update_mc_status( $product, MCStatus::APPROVED );
		$this->assertEquals( MCStatus::APPROVED, $this->product_helper->get_mc_status( $product ) );
	}

	public function test_get_mc_status_variation_product() {
		$parent    = WC_Helper_Product::create_variation_product();
		$variation = $this->wc->get_product( $parent->get_children()[0] );
		$this->product_meta->update_mc_status( $parent, MCStatus::APPROVED );
		$this->product_meta->update_mc_status( $variation, MCStatus::PENDING );
		$this->assertEquals( MCStatus::APPROVED, $this->product_helper->get_mc_status( $variation ) );
	}

	public function test_get_mc_status_variation_product_returns_null_if_orphan() {
		$parent    = WC_Helper_Product::create_variation_product();
		$variation = $this->wc->get_product( $parent->get_children()[0] );
		$this->product_meta->update_mc_status( $parent, MCStatus::APPROVED );

		$this->assertEquals( MCStatus::APPROVED, $this->product_helper->get_mc_status( $variation ) );

		// make the variation orphan by setting its parent to 0
		$variation->set_parent_id( 0 );
		$variation->save();

		$this->assertNull( $this->product_helper->get_mc_status( $variation ) );
	}

	public function test_maybe_swap_for_parent_ids() {
		$simple_publish = WC_Helper_Product::create_simple_product();
		$simple_trash   = WC_Helper_Product::create_simple_product( true, [ 'status' => 'trash' ] );
		$variable       = WC_Helper_Product::create_variation_product();
		$variation      = $this->wc->get_product( $variable->get_children()[0] );

		$product_ids = [
			$simple_publish->get_id(),
			$simple_trash->get_id(),
			$variable->get_id(),
			$variation->get_id(),
			999999, // not exist
		];

		// - Check product status
		// - Ignore product on error
		$new_product_ids = $this->product_helper->maybe_swap_for_parent_ids( $product_ids );
		$this->assertEquals(
			[
				$simple_publish->get_id(),
				$variable->get_id(),
			],
			array_values( $new_product_ids ),
		);

		// - Do not check product status
		// - Ignore product on error
		$new_product_ids = $this->product_helper->maybe_swap_for_parent_ids( $product_ids, false );
		$this->assertEquals(
			[
				$simple_publish->get_id(),
				$simple_trash->get_id(),
				$variable->get_id(),
			],
			array_values( $new_product_ids ),
		);

		// - Do not check product status
		// - Do not ignore product on error
		try {
			$new_product_ids = $this->product_helper->maybe_swap_for_parent_ids( $product_ids, false, false );
		} catch ( InvalidValue $exception ) {
			$this->assertEquals(
				'Invalid product ID: 999999',
				$exception->getMessage()
			);
		}
	}

	public function test_maybe_swap_for_parent_id() {
		$simple = WC_Helper_Product::create_simple_product();

		$variable  = WC_Helper_Product::create_variation_product();
		$variation = $this->wc->get_product( $variable->get_children()[0] );

		$simple_product_id = $this->product_helper->maybe_swap_for_parent_id( $simple->get_id() );
		$this->assertEquals( $simple->get_id(), $simple_product_id );

		$variable_product_id = $this->product_helper->maybe_swap_for_parent_id( $variable->get_id() );
		$this->assertEquals( $variable->get_id(), $variable_product_id );

		$variation_parent_id = $this->product_helper->maybe_swap_for_parent_id( $variation->get_id() );
		$this->assertEquals( $variable->get_id(), $variation_parent_id );
	}

	public function test_maybe_swap_for_parent() {
		$simple = WC_Helper_Product::create_simple_product();

		$variable  = WC_Helper_Product::create_variation_product();
		$variation = $this->wc->get_product( $variable->get_children()[0] );

		$simple_product = $this->product_helper->maybe_swap_for_parent( $simple );
		$this->assertEquals( $simple->get_id(), $simple_product->get_id() );

		$variable_product = $this->product_helper->maybe_swap_for_parent( $variable );
		$this->assertEquals( $variable->get_id(), $variable_product->get_id() );

		$variation_parent = $this->product_helper->maybe_swap_for_parent( $variation );
		$this->assertEquals( $variable->get_id(), $variation_parent->get_id() );
	}

	/**
	 * @param string $callback
	 *
	 * @dataProvider return_test_product_callbacks
	 */
	public function test_get_validation_errors( string $callback ) {
		$product = call_user_func( $callback );
		$errors  = [
			1111 => [
				'Variation Error 1',
				'Variation Error 2',
			],
			1112 => [
				'Variation Error 1',
				'Variation Error 3',
			],
			1113 => [
				'Variation Error 1',
				'Variation Error 4',
			],
		];

		$this->product_meta->update_errors( $product, $errors );

		$this->assertEqualSets(
			[
				'Variation Error 1',
				'Variation Error 2',
				'Variation Error 3',
				'Variation Error 4',
			],
			$this->product_helper->get_validation_errors( $product )
		);
	}

	/**
	 * @param string $callback
	 *
	 * @dataProvider return_test_product_callbacks
	 */
	public function test_get_validation_errors_returns_as_is_if_keys_arent_product_ids( string $callback ) {
		$product = call_user_func( $callback );
		$errors  = [
			[
				'Variation Error 1',
				'Variation Error 2',
			],
			[
				'Variation Error 1',
				'Variation Error 3',
			],
			[
				'Variation Error 1',
				'Variation Error 4',
			],
		];

		$this->product_meta->update_errors( $product, $errors );

		$this->assertEquals(
			$errors,
			$this->product_helper->get_validation_errors( $product )
		);
	}

	/**
	 * @param string $callback
	 *
	 * @dataProvider return_test_product_callbacks
	 */
	public function test_increment_failed_delete_attempt( string $callback ) {
		$product = call_user_func( $callback );
		$this->product_meta->update_failed_delete_attempts( $product, 1 );
		$this->product_helper->increment_failed_delete_attempt( $product );

		$this->assertEquals( 2, $this->product_meta->get_failed_delete_attempts( $product ) );
	}

	/**
	 * @param string $callback
	 *
	 * @dataProvider return_test_product_callbacks
	 */
	public function test_is_delete_failed_threshold_reached( string $callback ) {
		$product = call_user_func( $callback );
		$this->product_meta->update_failed_delete_attempts( $product, 4 );
		$this->assertFalse( $this->product_helper->is_delete_failed_threshold_reached( $product ) );

		$this->product_helper->increment_failed_delete_attempt( $product );
		$this->assertTrue( $this->product_helper->is_delete_failed_threshold_reached( $product ) );
	}

	/**
	 * @param string $callback
	 *
	 * @dataProvider return_test_product_callbacks
	 */
	public function test_increment_failed_update_attempt( string $callback ) {
		$product = call_user_func( $callback );
		$this->product_meta->update_failed_sync_attempts( $product, 99 );

		$this->product_helper->increment_failed_update_attempt( $product );

		$this->assertEquals( 100, $this->product_meta->get_failed_sync_attempts( $product ) );
	}

	/**
	 * @param string $callback
	 *
	 * @dataProvider return_test_product_callbacks
	 */
	public function test_is_update_failed_threshold_reached( string $callback ) {
		$product = call_user_func( $callback );
		$this->product_meta->update_failed_sync_attempts( $product, 4 );
		$this->assertFalse( $this->product_helper->is_update_failed_threshold_reached( $product ) );

		$this->product_helper->increment_failed_update_attempt( $product );
		$this->assertTrue( $this->product_helper->is_update_failed_threshold_reached( $product ) );
	}

	public function test_get_offer_id() {
		$this->assertEquals( $this->product_helper->get_offer_id( 1 ), 'gla_1' );
	}

	public function test_get_filtered_offer_id() {
		add_filter(
			'woocommerce_gla_get_google_product_offer_id',
			function ( $value, $product_id ) {
				return "custom_{$product_id}";
			},
			10,
			2
		);

		$this->assertEquals( $this->product_helper->get_offer_id( 1 ), 'custom_1' );

		remove_filter(
			'woocommerce_gla_get_google_product_offer_id',
			function ( $value, $product_id ) {
				return "custom_{$product_id}";
			}
		);
	}

	/**
	 * - Variation products are provided separately to related tests.
	 * - Return callables to make the data provider itself efficient and without
	 *   side effects. Especially when using `--filter` to run test suites, all
	 *   data provider methods will still be called regardless of whether the
	 *   decorated test cases are included or not.
	 *
	 * @return array
	 */
	public function return_test_product_callbacks(): array {
		return [
			[ 'WC_Helper_Product::create_simple_product' ],
			[ 'WC_Helper_Product::create_variation_product' ], // WC_Product_Variable
		];
	}

	public function tearDown(): void {
		parent::tearDown();

		delete_option( 'woocommerce_hide_out_of_stock_items' );
		remove_all_filters( 'woocommerce_hide_invisible_variations' );
	}

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->product_meta   = $this->container->get( ProductMetaHandler::class );
		$this->wc             = $this->container->get( WC::class );
		$this->product_helper = new ProductHelper( $this->product_meta, $this->wc );
	}
}
