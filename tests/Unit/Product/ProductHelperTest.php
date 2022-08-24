<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Product;

use Automattic\WooCommerce\GoogleListingsAndAds\Google\GoogleProductService;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\TargetAudience;
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
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Product
 *
 * @property ProductMetaHandler        $product_meta
 * @property WC                        $wc
 * @property MockObject|TargetAudience $target_audience
 * @property ProductHelper             $product_helper
 */
class ProductHelperTest extends ContainerAwareUnitTest {

	use ProductMetaTrait;
	use ProductTrait;
	use SettingsTrait;

	/**
	 * @param WC_Product $product
	 *
	 * @dataProvider return_test_products
	 */
	public function test_mark_as_synced( WC_Product $product ) {
		$google_product = $this->generate_google_product_mock();

		$this->target_audience->expects( $this->any() )
			->method( 'get_target_countries' )
			->willReturn( [ $this->get_sample_target_country() ] );

		// add some random errors residue from previous sync attempts
		$this->product_meta->update_errors( $product, [ 'Error 1', 'Error 2' ] );
		$this->product_meta->update_failed_sync_attempts( $product, 1 );
		$this->product_meta->update_sync_failed_at( $product, 12345 );

		$this->product_helper->mark_as_synced( $product, $google_product );

		$this->assertGreaterThan( 0, $this->product_meta->get_synced_at( $product ) );
		$this->assertEquals( SyncStatus::SYNCED, $this->product_meta->get_sync_status( $product ) );
		$this->assertEquals( [ $google_product->getTargetCountry() => $google_product->getId() ], $this->product_meta->get_google_ids( $product ) );
		$this->assertEquals( ChannelVisibility::SYNC_AND_SHOW, $this->product_meta->get_visibility( $product ) );
		$this->assertEmpty( $this->product_meta->get_errors( $product ) );
		$this->assertEmpty( $this->product_meta->get_failed_sync_attempts( $product ) );
		$this->assertEmpty( $this->product_meta->get_sync_failed_at( $product ) );
	}

	/**
	 * @param WC_Product $product
	 *
	 * @dataProvider return_test_products
	 */
	public function test_mark_as_synced_keeps_existing_google_ids( WC_Product $product ) {
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
	 * @param WC_Product $product
	 *
	 * @dataProvider return_test_products
	 */
	public function test_mark_as_synced_doesnt_delete_errors_unless_all_target_countries_synced( WC_Product $product ) {
		$google_product = $this->generate_google_product_mock();

		$this->target_audience->expects( $this->any() )
							  ->method( 'get_target_countries' )
							  ->willReturn( [ 'AU', $google_product->getTargetCountry() ] );

		// add some random errors residue from previous sync attempts
		$this->product_meta->update_errors( $product, [ 'Error 1', 'Error 2' ] );
		$this->product_meta->update_failed_sync_attempts( $product, 1 );
		$this->product_meta->update_sync_failed_at( $product, 12345 );

		$this->product_helper->mark_as_synced( $product, $google_product );

		$this->assertEquals( [ 'Error 1', 'Error 2' ], $this->product_meta->get_errors( $product ) );
		$this->assertEquals( 1, $this->product_meta->get_failed_sync_attempts( $product ) );
		$this->assertEquals( 12345, $this->product_meta->get_sync_failed_at( $product ) );

		$google_product_2 = $this->generate_google_product_mock( null, 'AU' );

		$this->product_helper->mark_as_synced( $product, $google_product_2 );

		$this->assertEmpty( $this->product_meta->get_errors( $product ) );
		$this->assertEmpty( $this->product_meta->get_failed_sync_attempts( $product ) );
		$this->assertEmpty( $this->product_meta->get_sync_failed_at( $product ) );
	}

	public function test_mark_as_synced_updates_both_variation_and_parent() {
		$google_product = $this->generate_google_product_mock();
		$parent         = WC_Helper_Product::create_variation_product();
		$variation      = $this->wc->get_product( $parent->get_children()[0] );

		$this->target_audience->expects( $this->any() )
							  ->method( 'get_target_countries' )
							  ->willReturn( [ $this->get_sample_target_country() ] );

		// add some random errors residue from previous sync attempts
		$this->product_meta->update_errors( $variation, [ 'Error 1', 'Error 2' ] );
		$this->product_meta->update_failed_sync_attempts( $variation, 1 );
		$this->product_meta->update_sync_failed_at( $variation, 12345 );
		$this->product_meta->update_errors( $parent, [ $parent->get_id() => [ 'Error 1', 'Error 2' ] ] );
		$this->product_meta->update_failed_sync_attempts( $parent, 1 );
		$this->product_meta->update_sync_failed_at( $parent, 12345 );

		$this->product_helper->mark_as_synced( $variation, $google_product );

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

		$this->target_audience->expects( $this->any() )
							  ->method( 'get_target_countries' )
							  ->willReturn( [ $this->get_sample_target_country() ] );

		$this->product_helper->mark_as_synced( $variation, $google_product );

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
	 * @param WC_Product $product
	 *
	 * @dataProvider return_test_products
	 */
	public function test_mark_as_unsynced( WC_Product $product ) {
		// First mark the product as synced to update its meta data
		$this->product_helper->mark_as_synced( $product, $this->generate_google_product_mock() );

		$this->product_helper->mark_as_unsynced( $product );

		$this->assertEmpty( $this->product_meta->get_synced_at( $product ) );
		$this->assertEquals( SyncStatus::NOT_SYNCED, $this->product_meta->get_sync_status( $product ) );
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
		}
	}

	public function test_mark_as_unsynced_does_not_update_parent_if_orphan_variation() {
		$parent    = WC_Helper_Product::create_variation_product();
		$variation = $this->wc->get_product( $parent->get_children()[0] );

		// First mark the product as synced to update its meta data
		$this->product_helper->mark_as_synced( $variation, $this->generate_google_product_mock() );

		// make the variation orphan by setting its parent to 0
		$variation->set_parent_id( 0 );
		$variation->save();

		$this->product_helper->mark_as_unsynced( $variation );

		// get the updated parent object from DB
		$parent = $this->wc->get_product( $parent->get_id() );

		$this->assertEmpty( $this->product_meta->get_synced_at( $variation ) );
		$this->assertEquals( SyncStatus::NOT_SYNCED, $this->product_meta->get_sync_status( $variation ) );
		$this->assertEmpty( $this->product_meta->get_google_ids( $variation ) );

		$this->assertNotEmpty( $this->product_meta->get_synced_at( $parent ) );
		$this->assertEquals( SyncStatus::SYNCED, $this->product_meta->get_sync_status( $parent ) );
		$this->assertNotEmpty( $this->product_meta->get_google_ids( $parent ) );
	}

	/**
	 * @param WC_Product $product
	 *
	 * @dataProvider return_test_products
	 */
	public function test_remove_google_id( WC_Product $product ) {
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
	 * @param WC_Product $product
	 *
	 * @dataProvider return_test_products
	 */
	public function test_remove_google_id_marks_as_unsynced_if_empty_ids( WC_Product $product ) {
		$this->product_meta->update_google_ids( $product, [ 'US' => 'online:en:US:gla_1' ] );

		$this->product_helper->remove_google_id( $product, 'online:en:US:gla_1' );

		$this->assertEmpty( $this->product_meta->get_google_ids( $product ) );
		$this->assertFalse( $this->product_helper->is_product_synced( $product ) );
	}

	/**
	 * @param WC_Product $product
	 *
	 * @dataProvider return_test_products
	 */
	public function test_mark_as_invalid( WC_Product $product ) {
		$errors = [
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
	 * @param WC_Product $product
	 *
	 * @dataProvider return_test_products
	 */
	public function test_mark_as_invalid_updates_failed_sync_attempts_if_internal_error_exists( WC_Product $product ) {
		$errors = [
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
	 * @param WC_Product $product
	 *
	 * @dataProvider return_test_products
	 */
	public function test_mark_as_pending( WC_Product $product ) {
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
	 * @param WC_Product $product
	 *
	 * @dataProvider return_test_products
	 */
	public function test_get_synced_google_product_ids( WC_Product $product ) {
		$this->product_meta->update_google_ids( $product, [ 'US' => 'online:en:US:gla_1' ] );

		$this->assertEquals( [ 'US' => 'online:en:US:gla_1' ], $this->product_helper->get_synced_google_product_ids( $product ) );
	}

	public function test_get_wc_product_id() {
		$google_id  = 'online:en:US:gla_1234567';
		$product_id = $this->product_helper->get_wc_product_id( $google_id );

		$this->assertEquals( 1234567, $product_id );
	}

	public function test_get_wc_product_id_returns_zero_if_no_id_matches() {
		$google_id  = 'online:en:US:gla_invalid_id_1';
		$product_id = $this->product_helper->get_wc_product_id( $google_id );

		$this->assertEquals( 0, $product_id );
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
	 * @param WC_Product $product
	 *
	 * @dataProvider return_test_products
	 */
	public function test_is_product_synced( WC_Product $product ) {
		$this->product_helper->mark_as_synced( $product, $this->generate_google_product_mock() );
		$is_product_synced = $this->product_helper->is_product_synced( $product );
		$this->assertTrue( $is_product_synced );
	}

	/**
	 * @param WC_Product $product
	 *
	 * @dataProvider return_test_products
	 */
	public function test_is_product_synced_return_false_if_no_google_id( WC_Product $product ) {
		$this->product_helper->mark_as_synced( $product, $this->generate_google_product_mock() );
		$this->product_meta->delete_google_ids( $product );
		$is_product_synced = $this->product_helper->is_product_synced( $product );
		$this->assertFalse( $is_product_synced );
	}

	/**
	 * @param WC_Product $product
	 *
	 * @dataProvider return_test_products
	 */
	public function test_is_product_synced_return_false_if_no_synced_at( WC_Product $product ) {
		$this->product_helper->mark_as_synced( $product, $this->generate_google_product_mock() );
		$this->product_meta->delete_synced_at( $product );
		$is_product_synced = $this->product_helper->is_product_synced( $product );
		$this->assertFalse( $is_product_synced );
	}

	/**
	 * @param WC_Product $product
	 *
	 * @dataProvider return_test_products
	 */
	public function test_is_sync_ready_visible_published( WC_Product $product ) {
		$product->set_status( 'publish' );
		$product->save();
		$this->product_meta->update_visibility( $product, ChannelVisibility::SYNC_AND_SHOW );
		$result = $this->product_helper->is_sync_ready( $product );
		$this->assertTrue( $result );
	}

	/**
	 * @param WC_Product $product
	 *
	 * @dataProvider return_test_products
	 */
	public function test_is_sync_ready_not_visible_published( WC_Product $product ) {
		$product->set_status( 'publish' );
		$product->save();
		$this->product_meta->update_visibility( $product, ChannelVisibility::DONT_SYNC_AND_SHOW );
		$result = $this->product_helper->is_sync_ready( $product );
		$this->assertFalse( $result );
	}

	/**
	 * @param WC_Product $product
	 *
	 * @dataProvider return_test_products
	 */
	public function test_is_sync_ready_visible_not_published( WC_Product $product ) {
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
	 * @param WC_Product $product
	 *
	 * @dataProvider return_test_products
	 */
	public function test_is_sync_failed_recently( WC_Product $product ) {
		$this->product_meta->update_failed_sync_attempts( $product, ProductSyncer::FAILURE_THRESHOLD + 5 );
		$this->product_meta->update_sync_failed_at( $product, strtotime( '+1 year' ) );
		$this->assertTrue( $this->product_helper->is_sync_failed_recently( $product ) );
	}

	/**
	 * @param WC_Product $product
	 *
	 * @dataProvider return_test_products
	 */
	public function test_is_sync_failed_recently_less_than_threshold( WC_Product $product ) {
		$this->product_meta->update_failed_sync_attempts( $product, ProductSyncer::FAILURE_THRESHOLD - 1 );
		$this->product_meta->update_sync_failed_at( $product, strtotime( '+1 year' ) );
		$this->assertFalse( $this->product_helper->is_sync_failed_recently( $product ) );
	}

	/**
	 * @param WC_Product $product
	 *
	 * @dataProvider return_test_products
	 */
	public function test_is_sync_failed_recently_old_failure_but_more_than_threshold( WC_Product $product ) {
		$this->product_meta->update_failed_sync_attempts( $product, ProductSyncer::FAILURE_THRESHOLD + 5 );
		$this->product_meta->update_sync_failed_at( $product, strtotime( '-1 year' ) );
		$this->assertFalse( $this->product_helper->is_sync_failed_recently( $product ) );
	}

	/**
	 * @param WC_Product $product
	 *
	 * @dataProvider return_test_products
	 */
	public function test_get_channel_visibility( WC_Product $product ) {
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
	 * @param WC_Product $product
	 *
	 * @dataProvider return_test_products
	 */
	public function test_get_sync_status( WC_Product $product ) {
		$this->product_meta->update_sync_status( $product, SyncStatus::SYNCED );
		$this->assertEquals( SyncStatus::SYNCED, $this->product_helper->get_sync_status( $product ) );
	}

	/**
	 * @param WC_Product $product
	 *
	 * @dataProvider return_test_products
	 */
	public function test_get_mc_status( WC_Product $product ) {
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
	 * @param WC_Product $product
	 *
	 * @dataProvider return_test_products
	 */
	public function test_get_validation_errors( WC_Product $product ) {
		$errors = [
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
	 * @param WC_Product $product
	 *
	 * @dataProvider return_test_products
	 */
	public function test_get_validation_errors_returns_as_is_if_keys_arent_product_ids( WC_Product $product ) {
		$errors = [
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
	 * @param WC_Product $product
	 *
	 * @dataProvider return_test_products
	 */
	public function test_increment_failed_delete_attempt( WC_Product $product ) {
		$this->product_meta->update_failed_delete_attempts( $product, 1 );
		$this->product_helper->increment_failed_delete_attempt( $product );

		$this->assertEquals( 2, $this->product_meta->get_failed_delete_attempts( $product ) );
	}

	/**
	 * @param WC_Product $product
	 *
	 * @dataProvider return_test_products
	 */
	public function test_is_delete_failed_threshold_reached( WC_Product $product ) {
		$this->product_meta->update_failed_delete_attempts( $product, 4 );
		$this->assertFalse( $this->product_helper->is_delete_failed_threshold_reached( $product ) );

		$this->product_helper->increment_failed_delete_attempt( $product );
		$this->assertTrue( $this->product_helper->is_delete_failed_threshold_reached( $product ) );
	}


	/**
	 * @param WC_Product $product
	 *
	 * @dataProvider return_test_products
	 */
	public function test_increment_failed_update_attempt( WC_Product $product ) {
		$this->product_meta->update_failed_sync_attempts( $product, 99 );

		$this->product_helper->increment_failed_update_attempt( $product );

		$this->assertEquals( 100, $this->product_meta->get_failed_sync_attempts( $product ) );
	}

	/**
	 * @param WC_Product $product
	 *
	 * @dataProvider return_test_products
	 */
	public function test_is_update_failed_threshold_reached( WC_Product $product ) {
		$this->product_meta->update_failed_sync_attempts( $product, 4 );
		$this->assertFalse( $this->product_helper->is_update_failed_threshold_reached( $product ) );

		$this->product_helper->increment_failed_update_attempt( $product );
		$this->assertTrue( $this->product_helper->is_update_failed_threshold_reached( $product ) );
	}

	/**
	 * @return array
	 */
	public function return_test_products(): array {
		// variation products are provided separately to related tests
		return [
			[ WC_Helper_Product::create_simple_product() ],
			[ WC_Helper_Product::create_variation_product() ], // WC_Product_Variable
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
		$this->product_meta    = $this->container->get( ProductMetaHandler::class );
		$this->wc              = $this->container->get( WC::class );
		$this->target_audience = $this->createMock( TargetAudience::class );
		$this->product_helper  = new ProductHelper( $this->product_meta, $this->wc, $this->target_audience );
	}
}
