<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Product;

use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\AttributeMappingRulesQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\BatchInvalidProductEntry;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\BatchProductEntry;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\BatchProductIDRequestEntry;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\BatchProductRequestEntry;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\BatchProductResponse;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\GoogleProductService;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\TargetAudience;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\BatchProductHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductFactory;
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

	/** @var MockObject|GoogleProductService $google_service */
	protected $google_service;

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

	/** @var AttributeMappingRulesQuery $rules_query */
	protected $rules_query;

	/** @var WC $wc */
	protected $wc;

	public function test_update() {
		$validator       = $this->createMock( ValidatorInterface::class );
		$product_factory = $this->container->get( ProductFactory::class );
		$batch_helper    = $this->getMockBuilder( BatchProductHelper::class )
								->setMethods( [ 'validate_and_generate_update_request_entries' ] )
								->setConstructorArgs(
									[
										$this->product_meta,
										$this->product_helper,
										$validator,
										$product_factory,
										$this->target_audience,
										$this->rules_query,
									]
								)
								->getMock();
		$batch_helper->expects( $this->once() )
			->method( 'validate_and_generate_update_request_entries' )
			->willReturnCallback(
				function ( array $products ) {
					return array_map(
						function ( WC_Product $product ) {
							return new BatchProductRequestEntry( $product->get_id(), $this->generate_adapted_product( $product ) );
						},
						$products
					);
				}
			);

		// $synced_products:   products that were successfully synced to Merchant Center
		// $rejected_products: products that have errors and were rejected by Google API
		[ $synced_products, $rejected_products ] = $this->create_multiple_simple_product_sets( 2, 2 );

		$this->mock_google_service( $synced_products, $rejected_products );
		$product_syncer = $this->get_product_syncer( [ 'batch_helper' => $batch_helper ] );

		$products = array_merge( $synced_products, $rejected_products );
		$results  = $product_syncer->update( $products );
		$this->assert_update_results_are_valid( $results, $synced_products, $rejected_products );
	}

	public function test_update_by_batch_requests() {
		// $synced_products:   products that were successfully synced to Merchant Center
		// $rejected_products: products that have errors and were rejected by Google API
		[ $synced_products, $rejected_products ] = $this->create_multiple_simple_product_sets( 2, 2 );

		$this->mock_google_service( $synced_products, $rejected_products );

		$product_entries = array_map(
			function ( WC_Product $product ) {
				return new BatchProductRequestEntry( $product->get_id(), $this->generate_adapted_product( $product ) );
			},
			array_merge( $synced_products, $rejected_products )
		);

		$results = $this->product_syncer->update_by_batch_requests( $product_entries );
		$this->assert_update_results_are_valid( $results, $synced_products, $rejected_products );
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

		$this->mock_google_service( $deleted_products, $rejected_products );

		$products = array_merge( $deleted_products, $rejected_products );

		// first we mark all products as synced
		array_walk(
			$products,
			function ( WC_Product $product ) {
				$this->product_helper->mark_as_synced( $product, $this->generate_google_product_mock() );
			}
		);

		$results = $this->product_syncer->delete( $products );
		$this->assert_delete_results_are_valid( $results, $deleted_products, $rejected_products );
	}

	public function test_delete_by_batch_requests() {
		// $deleted_products:  products that were successfully synced and then deleted from Merchant Center
		// $rejected_products: products that were synced but deleting them resulted in errors and were rejected by Google API
		[ $deleted_products, $rejected_products ] = $this->create_multiple_simple_product_sets( 2, 2 );

		$this->mock_google_service( $deleted_products, $rejected_products );

		$products = array_merge( $deleted_products, $rejected_products );

		// first we mark all products as synced
		array_walk(
			$products,
			function ( WC_Product $product ) {
				$this->product_helper->mark_as_synced( $product, $this->generate_google_product_mock() );
			}
		);

		// generate delete request entries
		$product_entries = array_map(
			function ( WC_Product $product ) {
				return new BatchProductIDRequestEntry( $product->get_id(), $this->generate_google_id( $product ) );
			},
			$products
		);

		$results = $this->product_syncer->delete_by_batch_requests( $product_entries );
		$this->assert_delete_results_are_valid( $results, $deleted_products, $rejected_products );
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
		// $not_found_products: products that were synced but deleting them resulted in a not-found error and were rejected by Google API
		[ $deleted_products, $not_found_products ] = $this->create_multiple_simple_product_sets( 2, 2 );

		$this->google_service->expects( $this->once() )
			->method( 'delete_batch' )
			->willReturnCallback(
				function ( array $product_entries ) use ( $deleted_products, $not_found_products ) {
					$errors  = [];
					$entries = [];
					foreach ( $product_entries as $product_entry ) {
						if ( isset( $deleted_products[ $product_entry->get_wc_product_id() ] ) ) {
							$entries[] = new BatchProductEntry( $product_entry->get_wc_product_id(), null );
						} elseif ( isset( $not_found_products[ $product_entry->get_wc_product_id() ] ) ) {
							$errors[] = new BatchInvalidProductEntry( $product_entry->get_wc_product_id(), $product_entry->get_product_id(), [ GoogleProductService::NOT_FOUND_ERROR_REASON => 'Not Found!' ] );
						}
					}

					return new BatchProductResponse( $entries, $errors );
				}
			);

		$products = array_merge( $deleted_products, $not_found_products );

		// first we mark all products as synced
		array_walk(
			$products,
			function ( WC_Product $product ) {
				$this->product_helper->mark_as_synced( $product, $this->generate_google_product_mock() );
			}
		);

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

	public function test_update_by_batch_requests_fails_if_merchant_center_not_setup() {
		$product = WC_Helper_Product::create_simple_product();

		$merchant_center = $this->createMock( MerchantCenterService::class );
		$merchant_center->expects( $this->any() )
						->method( 'is_connected' )
						->willReturn( false );
		$this->product_syncer = $this->get_product_syncer( [ 'merchant_center' => $merchant_center ] );

		$this->expectException( ProductSyncerException::class );
		$this->product_syncer->update_by_batch_requests( [ new BatchProductRequestEntry( $product->get_id(), $this->generate_adapted_product( $product ) ) ] );
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

	public function test_delete_by_batch_requests_fails_if_merchant_center_not_setup() {
		$product = WC_Helper_Product::create_simple_product();

		$merchant_center = $this->createMock( MerchantCenterService::class );
		$merchant_center->expects( $this->any() )
						->method( 'is_connected' )
						->willReturn( false );
		$this->product_syncer = $this->get_product_syncer( [ 'merchant_center' => $merchant_center ] );

		$this->expectException( ProductSyncerException::class );
		$this->product_syncer->delete_by_batch_requests( [ new BatchProductIDRequestEntry( $product->get_id(), $this->generate_google_id( $product ) ) ] );
	}

	public function test_update_by_batch_requests_throws_exception_if_google_api_call_fails() {
		$product = WC_Helper_Product::create_simple_product();

		$this->google_service->expects( $this->any() )
			->method( 'insert_batch' )
			->willThrowException( new GoogleException() );

		$this->expectException( ProductSyncerException::class );
		$this->product_syncer->update_by_batch_requests( [ new BatchProductRequestEntry( $product->get_id(), $this->generate_adapted_product( $product ) ) ] );
	}

	public function test_delete_by_batch_requests_throws_exception_if_google_api_call_fails() {
		$product = WC_Helper_Product::create_simple_product();

		$this->google_service->expects( $this->any() )
			->method( 'delete_batch' )
			->willThrowException( new GoogleException() );

		$this->expectException( ProductSyncerException::class );
		$this->product_syncer->delete_by_batch_requests( [ new BatchProductIDRequestEntry( $product->get_id(), $this->generate_google_id( $product ) ) ] );
	}

	public function test_delete_by_batch_requests_no_retry_if_product_is_unavailable_in_database() {
		// $deleted_products:  products that were successfully synced and then deleted from Merchant Center
		// $rejected_products: products that were synced but deleting them resulted in errors and were rejected by Google API
		[ $deleted_products, $rejected_products ] = $this->create_multiple_simple_product_sets( 2, 1 );

		$this->mock_google_service( $deleted_products, $rejected_products );

		$products = array_merge( $deleted_products, $rejected_products );

		// generate delete request entries
		$product_entries = array_map(
			function ( WC_Product $product ) {
				return new BatchProductIDRequestEntry( $product->get_id(), $this->generate_google_id( $product ) );
			},
			$products
		);

		// force delete all products
		array_walk(
			$products,
			function ( WC_Product $product ) {
				$product->delete( true );
				$product->save();
			}
		);

		$results = $this->product_syncer->delete_by_batch_requests( $product_entries );
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

	public function test_delete_by_batch_requests_should_delete_trashed_product() {
		// $deleted_products:  products that were successfully synced and then deleted from Merchant Center
		// $rejected_products: products that were synced but deleting them resulted in errors and were rejected by Google API
		[ $deleted_products, $rejected_products ] = $this->create_multiple_simple_product_sets( 2, 1 );

		$this->mock_google_service( $deleted_products, $rejected_products );

		$products = array_merge( $deleted_products, $rejected_products );

		// generate delete request entries
		$product_entries = array_map(
			function ( WC_Product $product ) {
				return new BatchProductIDRequestEntry( $product->get_id(), $this->generate_google_id( $product ) );
			},
			$products
		);

		// first we mark all products as synced and
		// set the product status to trash
		array_walk(
			$products,
			function ( WC_Product $product ) {
				$this->product_helper->mark_as_synced( $product, $this->generate_google_product_mock() );
				$product->set_status( 'trash' );
				$product->save();
			}
		);

		$results = $this->product_syncer->delete_by_batch_requests( $product_entries );

		$result_product_ids = array_map(
			function ( $product_entry ) {
				return $product_entry->get_wc_product_id();
			},
			$results->get_products()
		);

		$delete_ready_product_ids = array_keys( $deleted_products );

		$this->assertEqualsCanonicalizing( $result_product_ids, $delete_ready_product_ids );
		$this->assert_delete_results_are_valid( $results, $deleted_products, $rejected_products );
	}

	public function test_delete_by_batch_requests_should_delete_catalog_visibility_hidden_product() {
		// $deleted_products:  products that were successfully synced and then deleted from Merchant Center
		// $rejected_products: products that were synced but deleting them resulted in errors and were rejected by Google API
		[ $deleted_products, $rejected_products ] = $this->create_multiple_simple_product_sets( 2, 1 );

		$this->mock_google_service( $deleted_products, $rejected_products );

		$products = array_merge( $deleted_products, $rejected_products );

		// generate delete request entries
		$product_entries = array_map(
			function ( WC_Product $product ) {
				return new BatchProductIDRequestEntry( $product->get_id(), $this->generate_google_id( $product ) );
			},
			$products
		);

		// first we mark all products as synced and
		// set the product's catalog visibility to hidden
		array_walk(
			$products,
			function ( WC_Product $product ) {
				$this->product_helper->mark_as_synced( $product, $this->generate_google_product_mock() );
				$product->set_catalog_visibility( 'hidden' );
				$product->save();
			}
		);

		$results = $this->product_syncer->delete_by_batch_requests( $product_entries );

		$result_product_ids = array_map(
			function ( $product_entry ) {
				return $product_entry->get_wc_product_id();
			},
			$results->get_products()
		);

		$delete_ready_product_ids = array_keys( $deleted_products );

		$this->assertEqualsCanonicalizing( $result_product_ids, $delete_ready_product_ids );
		$this->assert_delete_results_are_valid( $results, $deleted_products, $rejected_products );
	}

	public function test_delete_by_batch_requests_should_delete_draft_product() {
		// $deleted_products:  products that were successfully synced and then deleted from Merchant Center
		// $rejected_products: products that were synced but deleting them resulted in errors and were rejected by Google API
		[ $deleted_products, $rejected_products ] = $this->create_multiple_simple_product_sets( 2, 1 );

		$this->mock_google_service( $deleted_products, $rejected_products );

		$products = array_merge( $deleted_products, $rejected_products );

		// generate delete request entries
		$product_entries = array_map(
			function ( WC_Product $product ) {
				return new BatchProductIDRequestEntry( $product->get_id(), $this->generate_google_id( $product ) );
			},
			$products
		);

		// first we mark all products as synced and
		// set the product status to draft
		array_walk(
			$products,
			function ( WC_Product $product ) {
				$this->product_helper->mark_as_synced( $product, $this->generate_google_product_mock() );
				$product->set_status( 'draft' );
				$product->save();
			}
		);

		$results = $this->product_syncer->delete_by_batch_requests( $product_entries );

		$result_product_ids = array_map(
			function ( $product_entry ) {
				return $product_entry->get_wc_product_id();
			},
			$results->get_products()
		);

		$delete_ready_product_ids = array_keys( $deleted_products );

		$this->assertEqualsCanonicalizing( $result_product_ids, $delete_ready_product_ids );
		$this->assert_delete_results_are_valid( $results, $deleted_products, $rejected_products );
	}

	protected function mock_google_service( $successful_products, $failed_products ): void {
		$callback = function ( array $product_entries ) use ( $successful_products, $failed_products ) {
			$errors  = [];
			$entries = [];
			foreach ( $product_entries as $product_entry ) {
				$google_product = $this->generate_google_product_mock();
				if ( isset( $successful_products[ $product_entry->get_wc_product_id() ] ) ) {
					$entries[] = new BatchProductEntry( $product_entry->get_wc_product_id(), $google_product );
				} elseif ( isset( $failed_products[ $product_entry->get_wc_product_id() ] ) ) {
					$errors[] = new BatchInvalidProductEntry(
						$product_entry->get_wc_product_id(),
						$google_product->getId(),
						[
							'Error',
							GoogleProductService::INTERNAL_ERROR_REASON => 'Internal Error!',
						]
					);
				}
			}

			return new BatchProductResponse( $entries, $errors );
		};

		$this->google_service->expects( $this->any() )
			->method( 'insert_batch' )
			->willReturnCallback( $callback );

		$this->google_service->expects( $this->any() )
			->method( 'delete_batch' )
			->willReturnCallback( $callback );
	}

	public function test_update_throws_exception_when_mc_is_blocked() {
		$merchant_center = $this->createMock( MerchantCenterService::class );
		$merchant_center->expects( $this->once() )
			->method( 'is_ready_for_syncing' )
			->willReturn( true );
		$merchant_center->expects( $this->once() )
			->method( 'should_push' )
			->willReturn( false );
		$this->product_syncer = $this->get_product_syncer( [ 'merchant_center' => $merchant_center ] );

		$this->expectException( ProductSyncerException::class );

		$this->product_syncer->update( [] );
	}

	public function test_update_by_batch_throws_exception_when_mc_is_blocked() {
		$merchant_center = $this->createMock( MerchantCenterService::class );
		$merchant_center->expects( $this->once() )
			->method( 'is_ready_for_syncing' )
			->willReturn( true );
		$merchant_center->expects( $this->once() )
			->method( 'should_push' )
			->willReturn( false );
		$this->product_syncer = $this->get_product_syncer( [ 'merchant_center' => $merchant_center ] );

		$this->expectException( ProductSyncerException::class );

		$this->product_syncer->update_by_batch_requests( [] );
	}

	public function test_delete_throws_exception_when_mc_is_blocked() {
		$merchant_center = $this->createMock( MerchantCenterService::class );
		$merchant_center->expects( $this->once() )
			->method( 'is_ready_for_syncing' )
			->willReturn( true );
		$merchant_center->expects( $this->once() )
			->method( 'should_push' )
			->willReturn( false );
		$this->product_syncer = $this->get_product_syncer( [ 'merchant_center' => $merchant_center ] );

		$this->expectException( ProductSyncerException::class );

		$this->product_syncer->delete( [] );
	}

	public function test_delete_by_batch_throws_exception_when_mc_is_blocked() {
		$merchant_center = $this->createMock( MerchantCenterService::class );
		$merchant_center->expects( $this->once() )
			->method( 'is_ready_for_syncing' )
			->willReturn( true );
		$merchant_center->expects( $this->once() )
			->method( 'should_push' )
			->willReturn( false );
		$this->product_syncer = $this->get_product_syncer( [ 'merchant_center' => $merchant_center ] );

		$this->expectException( ProductSyncerException::class );

		$this->product_syncer->delete_by_batch_requests( [] );
	}

	/**
	 * Function to return an instance of ProductSyncer.
	 *
	 * @param object[] $args
	 */
	private function get_product_syncer( $args = [] ): ProductSyncer {
		$args['google_service']     = $args['google_service'] ?? $this->google_service;
		$args['batch_helper']       = $args['batch_helper'] ?? $this->batch_helper;
		$args['product_helper']     = $args['product_helper'] ?? $this->product_helper;
		$args['merchant_center']    = $args['merchant_center'] ?? $this->merchant_center;
		$args['wc']                 = $args['wc'] ?? $this->wc;
		$args['product_repository'] = $args['product_repository'] ?? $this->product_repository;

		return new ProductSyncer(
			$args['google_service'],
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

		$this->google_service = $this->createMock( GoogleProductService::class );
		$this->rules_query    = $this->createMock( AttributeMappingRulesQuery::class );

		$this->product_meta       = $this->container->get( ProductMetaHandler::class );
		$this->batch_helper       = $this->container->get( BatchProductHelper::class );
		$this->product_helper     = $this->container->get( ProductHelper::class );
		$this->wc                 = $this->container->get( WC::class );
		$this->product_repository = $this->container->get( ProductRepository::class );
		$this->product_syncer     = $this->get_product_syncer();
	}
}
