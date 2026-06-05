<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Product;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\MerchantApiException;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Models\ProductInput;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services\MapiProductInputsService;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\TargetAudience;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\BatchProductHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductMetaHandler;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductSyncer;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductSyncerException;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\ContainerAwareUnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\ProductTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\SyncStatus;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Exception as GoogleException;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\Product as GoogleProduct;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use WC_Helper_Product;
use WC_Product;

/**
 * Class ProductSyncerTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Product
 */
class ProductSyncerTest extends ContainerAwareUnitTest {

	use ProductTrait;

	/** @var MockObject|MapiProductInputsService $mapi_inputs */
	protected $mapi_inputs;

	/** @var MockObject|TargetAudience $target_audience */
	protected $target_audience;

	/** @var MockObject|MerchantCenterService $merchant_center */
	protected $merchant_center;

	/** @var ProductMetaHandler $product_meta */
	protected $product_meta;

	/** @var BatchProductHelper $batch_helper */
	protected $batch_helper;

	/** @var ProductHelper $product_helper */
	protected $product_helper;

	/** @var ProductSyncer $product_syncer */
	protected $product_syncer;

	/** @var ProductRepository $product_repository */
	protected $product_repository;

	/** @var WC $wc */
	protected $wc;

	public function test_update() {
		// $synced_products:   products that were successfully synced to Merchant Center
		// $rejected_products: products that have errors and were rejected by Google API
		[ $synced_products, $rejected_products ] = $this->create_multiple_simple_product_sets( 2, 2 );

		$batch_helper = $this->getMockBuilder( BatchProductHelper::class )
								->setMethods( [ 'generate_mapi_update_entries' ] )
								->setConstructorArgs(
									[
										$this->product_meta,
										$this->product_helper,
										$this->target_audience,
									]
								)
								->getMock();
		$batch_helper->expects( $this->once() )
			->method( 'generate_mapi_update_entries' )
			->willReturnCallback(
				function ( array $products ) {
					return array_map(
						function ( WC_Product $product ) {
							return [
								'product' => $product,
								'country' => 'US',
								'input'   => new ProductInput( "gla_{$product->get_id()}", 'en', 'US', [ 'title' => $product->get_title() ] ),
							];
						},
						$products
					);
				}
			);

		$this->mock_mapi_inputs( $synced_products, $rejected_products );
		$product_syncer = $this->get_product_syncer( [ 'batch_helper' => $batch_helper ] );

		$products = array_merge( $synced_products, $rejected_products );
		$results  = $product_syncer->update( $products );
		$this->assert_update_results_are_valid( $results, $synced_products, $rejected_products );
	}

	/**
	 * Mock MapiProductInputsService::insert_many to succeed for synced products and
	 * fail for rejected products.
	 *
	 * @param array $synced_products   WC product IDs.
	 * @param array $rejected_products WC product IDs.
	 */
	protected function mock_mapi_inputs( array $synced_products, array $rejected_products ): void {
		$this->mapi_inputs->expects( $this->any() )
			->method( 'insert_many' )
			->willReturnCallback(
				function ( array $inputs ) use ( $synced_products, $rejected_products ) {
					$successes = [];
					$failures  = [];
					foreach ( $inputs as $index => $input ) {
						$product_id = (int) str_replace( 'gla_', '', $input->get_offer_id() );
						if ( isset( $synced_products[ $product_id ] ) ) {
							$successes[ $index ] = $input;
						} elseif ( isset( $rejected_products[ $product_id ] ) ) {
							$failures[ $index ] = new MerchantApiException( 500, [], 'Internal Error!' );
						}
					}

					return [
						'successes' => $successes,
						'failures'  => $failures,
					];
				}
			);
	}

	protected function assert_update_results_are_valid( $results, $synced_products, $rejected_products ) {
		$this->assertEquals( 1, did_action( 'woocommerce_gla_batch_updated_products' ) );
		$this->assertEquals( 1, did_action( 'woocommerce_gla_batch_retry_update_products' ) );

		$this->assertCount( count( $synced_products ), $results->get_products() );
		foreach ( $results->get_products() as $product_entry ) {
			$wc_product = wc_get_product( $product_entry->get_wc_product_id() );
			$this->assertTrue( $this->product_helper->is_product_synced( $wc_product ) );
			$this->assertInstanceOf( GoogleProduct::class, $product_entry->get_google_product() );
		}

		$this->assertCount( count( $rejected_products ), $results->get_errors() );
		foreach ( $results->get_errors() as $error_entry ) {
			$wc_product = wc_get_product( $error_entry->get_wc_product_id() );
			$this->assertNotEmpty( $error_entry->get_errors() );
			$this->assertNotEmpty( $this->product_meta->get_errors( $wc_product ) );
			$this->assertEquals( SyncStatus::HAS_ERRORS, $this->product_meta->get_sync_status( $wc_product ) );
			$this->assertEquals( 1, $this->product_meta->get_failed_sync_attempts( $wc_product ) );
		}
	}

	public function test_delete() {
		// $deleted_products:  products that were successfully synced and then deleted from Merchant Center
		// $rejected_products: products that were synced but deleting them resulted in errors and were rejected by Google API
		[ $deleted_products, $rejected_products ] = $this->create_multiple_simple_product_sets( 2, 2 );

		$products = array_merge( $deleted_products, $rejected_products );

		array_walk(
			$products,
			function ( WC_Product $product ) {
				$this->product_helper->mark_as_synced(
					$product,
					$this->generate_google_product_mock( "en~US~gla_{$product->get_id()}", 'US' )
				);
			}
		);

		$this->mock_mapi_delete( $deleted_products, $rejected_products, 500 );

		$results = $this->product_syncer->delete( $products );
		$this->assert_delete_results_are_valid( $results, $deleted_products, $rejected_products );
	}

	/**
	 * Mock MapiProductInputsService::delete_many to succeed for $synced products
	 * and fail with the given HTTP status for $rejected products.
	 *
	 * @param array $synced_products   WC product IDs.
	 * @param array $rejected_products WC product IDs.
	 * @param int   $failure_status    HTTP status to use for failures (e.g. 500 to retry, 404 for not-found).
	 */
	protected function mock_mapi_delete( array $synced_products, array $rejected_products, int $failure_status ): void {
		$this->mapi_inputs->expects( $this->any() )
			->method( 'delete_many' )
			->willReturnCallback(
				function ( array $inputs ) use ( $synced_products, $rejected_products, $failure_status ) {
					$successes = [];
					$failures  = [];
					foreach ( $inputs as $index => $input ) {
						$product_id = (int) str_replace( 'gla_', '', $input->get_offer_id() );
						if ( isset( $synced_products[ $product_id ] ) ) {
							$successes[ $index ] = $input;
						} elseif ( isset( $rejected_products[ $product_id ] ) ) {
							$failures[ $index ] = new MerchantApiException( $failure_status, [], 'MAPI error' );
						}
					}

					return [
						'successes' => $successes,
						'failures'  => $failures,
					];
				}
			);
	}

	public function test_delete_by_id_map() {
		// $deleted_products:  products that were successfully synced and then deleted from Merchant Center
		// $rejected_products: products that were synced but deleting them resulted in errors and were rejected by Google API
		[ $deleted_products, $rejected_products ] = $this->create_multiple_simple_product_sets( 2, 2 );

		$products = array_merge( $deleted_products, $rejected_products );

		array_walk(
			$products,
			function ( WC_Product $product ) {
				$this->product_helper->mark_as_synced(
					$product,
					$this->generate_google_product_mock( "en~US~gla_{$product->get_id()}", 'US' )
				);
			}
		);

		$this->mock_mapi_delete( $deleted_products, $rejected_products, 500 );

		$product_id_map = [];
		foreach ( $products as $product ) {
			$product_id_map[ "en~US~gla_{$product->get_id()}" ] = $product->get_id();
		}

		$results = $this->product_syncer->delete_by_id_map( $product_id_map );
		$this->assert_delete_results_are_valid( $results, $deleted_products, $rejected_products );
	}

	public function test_delete_by_id_map_skips_malformed_ids() {
		$this->mapi_inputs->expects( $this->never() )
			->method( 'delete_many' );

		$results = $this->product_syncer->delete_by_id_map( [ 'not-mapi-shape' => 99 ] );

		$this->assertEmpty( $results->get_products() );
		$this->assertEmpty( $results->get_errors() );
	}

	protected function assert_delete_results_are_valid( $results, $deleted_products, $rejected_products ) {
		$this->assertEquals( 1, did_action( 'woocommerce_gla_batch_deleted_products' ) );
		$this->assertEquals( 1, did_action( 'woocommerce_gla_batch_retry_delete_products' ) );

		$this->assertCount( count( $deleted_products ), $results->get_products() );
		foreach ( $results->get_products() as $product_entry ) {
			$wc_product = wc_get_product( $product_entry->get_wc_product_id() );
			// product is no longer synced if delete succeeds
			$this->assertFalse( $this->product_helper->is_product_synced( $wc_product ) );
		}

		$this->assertCount( count( $rejected_products ), $results->get_errors() );
		foreach ( $results->get_errors() as $error_entry ) {
			$wc_product = wc_get_product( $error_entry->get_wc_product_id() );
			$this->assertNotEmpty( $error_entry->get_errors() );
			// product remains synced if delete failed
			$this->assertTrue( $this->product_helper->is_product_synced( $wc_product ) );
			// first failed delete attempt
			$this->assertEquals( 1, $this->product_meta->get_failed_delete_attempts( $wc_product ) );
		}
	}

	public function test_delete_removes_google_id_of_not_found_products() {
		// $deleted_products:  products that were successfully synced and then deleted from Merchant Center
		// $not_found_products: products that were synced but deleting them returned 404 (already gone)
		[ $deleted_products, $not_found_products ] = $this->create_multiple_simple_product_sets( 2, 2 );

		$products = array_merge( $deleted_products, $not_found_products );

		array_walk(
			$products,
			function ( WC_Product $product ) {
				$this->product_helper->mark_as_synced(
					$product,
					$this->generate_google_product_mock( "en~US~gla_{$product->get_id()}", 'US' )
				);
			}
		);

		$this->mock_mapi_delete( $deleted_products, $not_found_products, 404 );

		$results = $this->product_syncer->delete( $products );

		$this->assertCount( 2, $results->get_products() );
		foreach ( $results->get_products() as $product_entry ) {
			$wc_product = wc_get_product( $product_entry->get_wc_product_id() );
			// product is no longer synced if delete succeeds
			$this->assertFalse( $this->product_helper->is_product_synced( $wc_product ) );
		}

		$this->assertCount( 2, $results->get_errors() );
		foreach ( $results->get_errors() as $error_entry ) {
			$wc_product = wc_get_product( $error_entry->get_wc_product_id() );
			$this->assertNotEmpty( $error_entry->get_errors() );
			// product is no longer synced if Google API returns Not Found error for it
			$this->assertFalse( $this->product_helper->is_product_synced( $wc_product ) );
		}
	}

	public function test_update_fails_if_merchant_center_not_setup() {
		$product = WC_Helper_Product::create_simple_product();

		$merchant_center = $this->createMock( MerchantCenterService::class );
		$merchant_center->expects( $this->any() )
						->method( 'is_connected' )
						->willReturn( false );
		$this->product_syncer = $this->get_product_syncer( [ 'merchant_center' => $merchant_center ] );

		$this->expectException( ProductSyncerException::class );
		$this->product_syncer->update( [ $product ] );
	}

	public function test_delete_fails_if_merchant_center_not_setup() {
		$product = WC_Helper_Product::create_simple_product();

		$merchant_center = $this->createMock( MerchantCenterService::class );
		$merchant_center->expects( $this->any() )
						->method( 'is_connected' )
						->willReturn( false );
		$this->product_syncer = $this->get_product_syncer( [ 'merchant_center' => $merchant_center ] );

		$this->expectException( ProductSyncerException::class );
		$this->product_syncer->delete( [ $product ] );
	}

	public function test_delete_by_id_map_fails_if_merchant_center_not_setup() {
		$product = WC_Helper_Product::create_simple_product();

		$merchant_center = $this->createMock( MerchantCenterService::class );
		$merchant_center->expects( $this->any() )
						->method( 'is_connected' )
						->willReturn( false );
		$this->product_syncer = $this->get_product_syncer( [ 'merchant_center' => $merchant_center ] );

		$this->expectException( ProductSyncerException::class );
		$this->product_syncer->delete_by_id_map( [ "en~US~gla_{$product->get_id()}" => $product->get_id() ] );
	}

	public function test_delete_by_id_map_throws_exception_if_google_api_call_fails() {
		$product = WC_Helper_Product::create_simple_product();

		$this->mapi_inputs->expects( $this->any() )
			->method( 'delete_many' )
			->willThrowException( new GoogleException() );

		$this->expectException( ProductSyncerException::class );
		$this->product_syncer->delete_by_id_map( [ "en~US~gla_{$product->get_id()}" => $product->get_id() ] );
	}

	public function test_delete_by_id_map_no_retry_if_product_is_unavailable_in_database() {
		// $deleted_products:  products that were successfully synced and then deleted from Merchant Center
		// $rejected_products: products that were synced but deleting them resulted in errors and were rejected by Google API
		[ $deleted_products, $rejected_products ] = $this->create_multiple_simple_product_sets( 2, 1 );

		$this->mock_mapi_delete( $deleted_products, $rejected_products, 500 );

		$products = array_merge( $deleted_products, $rejected_products );

		$product_id_map = [];
		foreach ( $products as $product ) {
			$product_id_map[ "en~US~gla_{$product->get_id()}" ] = $product->get_id();
		}

		// force delete all products
		array_walk(
			$products,
			function ( WC_Product $product ) {
				$product->delete( true );
				$product->save();
			}
		);

		$results = $this->product_syncer->delete_by_id_map( $product_id_map );
		$this->assertEquals( 0, did_action( 'woocommerce_gla_batch_retry_delete_products' ) );

		$result_product_ids = array_map(
			function ( $product_entry ) {
				return $product_entry->get_wc_product_id();
			},
			$results->get_products()
		);

		$delete_ready_product_ids = array_keys( $deleted_products );

		$this->assertEqualsCanonicalizing( $result_product_ids, $delete_ready_product_ids );
	}

	public function test_update_throws_exception_when_mc_is_blocked() {
		$merchant_center = $this->createMock( MerchantCenterService::class );
		$merchant_center->expects( $this->any() )
			->method( 'should_push' )
			->willReturn( true );
		$this->merchant_center->expects( $this->any() )
			->method( 'is_enabled_for_datatype' )
			->with( 'products' )
			->willReturn( false );
		$this->product_syncer = $this->get_product_syncer( [ 'merchant_center' => $merchant_center ] );

		$this->expectException( ProductSyncerException::class );

		$this->product_syncer->update( [] );
	}

	public function test_delete_throws_exception_when_mc_is_blocked() {
		$merchant_center = $this->createMock( MerchantCenterService::class );
		$merchant_center->expects( $this->any() )
			->method( 'should_push' )
			->willReturn( true );
		$this->merchant_center->expects( $this->any() )
			->method( 'is_enabled_for_datatype' )
			->with( 'products' )
			->willReturn( false );
		$this->product_syncer = $this->get_product_syncer( [ 'merchant_center' => $merchant_center ] );

		$this->expectException( ProductSyncerException::class );

		$this->product_syncer->delete( [] );
	}

	public function test_delete_by_id_map_throws_exception_when_mc_is_blocked() {
		$merchant_center = $this->createMock( MerchantCenterService::class );
		$merchant_center->expects( $this->any() )
			->method( 'should_push' )
			->willReturn( true );
		$this->merchant_center->expects( $this->any() )
			->method( 'is_enabled_for_datatype' )
			->with( 'products' )
			->willReturn( false );
		$this->product_syncer = $this->get_product_syncer( [ 'merchant_center' => $merchant_center ] );

		$this->expectException( ProductSyncerException::class );

		$this->product_syncer->delete_by_id_map( [] );
	}

	public function test_delete_mapi_entries_throws_exception_when_mc_is_blocked() {
		$merchant_center = $this->createMock( MerchantCenterService::class );
		$merchant_center->expects( $this->any() )
			->method( 'should_push' )
			->willReturn( true );
		$this->merchant_center->expects( $this->any() )
			->method( 'is_enabled_for_datatype' )
			->with( 'products' )
			->willReturn( false );
		$this->product_syncer = $this->get_product_syncer( [ 'merchant_center' => $merchant_center ] );

		$this->expectException( ProductSyncerException::class );

		// The cleanup jobs call delete_mapi_entries() directly, so it must validate too.
		$this->product_syncer->delete_mapi_entries( [] );
	}

	/**
	 * Function to return an instance of ProductSyncer.
	 *
	 * @param object[] $args
	 */
	private function get_product_syncer( $args = [] ): ProductSyncer {
		$args['mapi_inputs']        = $args['mapi_inputs'] ?? $this->mapi_inputs;
		$args['batch_helper']       = $args['batch_helper'] ?? $this->batch_helper;
		$args['product_helper']     = $args['product_helper'] ?? $this->product_helper;
		$args['merchant_center']    = $args['merchant_center'] ?? $this->merchant_center;
		$args['wc']                 = $args['wc'] ?? $this->wc;
		$args['product_repository'] = $args['product_repository'] ?? $this->product_repository;

		return new ProductSyncer(
			$args['mapi_inputs'],
			$args['batch_helper'],
			$args['product_helper'],
			$args['merchant_center'],
			$args['wc'],
			$args['product_repository'],
		);
	}

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->target_audience = $this->createMock( TargetAudience::class );
		$this->merchant_center = $this->createMock( MerchantCenterService::class );
		$this->merchant_center->expects( $this->any() )
			->method( 'is_ready_for_syncing' )
			->willReturn( true );

		$this->merchant_center->expects( $this->any() )
			->method( 'should_push' )
			->willReturn( true );

		$this->merchant_center->expects( $this->any() )
			->method( 'is_enabled_for_datatype' )
			->with( 'products' )
			->willReturn( true );

		$this->mapi_inputs = $this->createMock( MapiProductInputsService::class );

		$this->product_meta       = $this->container->get( ProductMetaHandler::class );
		$this->batch_helper       = $this->container->get( BatchProductHelper::class );
		$this->product_helper     = $this->container->get( ProductHelper::class );
		$this->wc                 = $this->container->get( WC::class );
		$this->product_repository = $this->container->get( ProductRepository::class );
		$this->product_syncer     = $this->get_product_syncer();
	}
}
