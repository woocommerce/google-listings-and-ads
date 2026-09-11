<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Product;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\MerchantApiException;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Models\ProductInput;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services\MapiDataSourcesService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services\MapiProductInputsService;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\AttributeMappingRulesQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\BatchProductIDRequestEntry;
use Automattic\WooCommerce\GoogleListingsAndAds\Integration\WPML;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\AccountReconnect;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MarketService;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\BatchProductHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\AttributeManager;
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
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Exception\ConnectException;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Exception\RequestException;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Psr7\Request;
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

	/** @var MockObject|MarketService $market_service */
	protected $market_service;

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

	/** @var AttributeMappingRulesQuery $rules_query */
	protected $rules_query;

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
										$this->createMock( ValidatorInterface::class ),
										$this->container->get( ProductFactory::class ),
										$this->rules_query,
										$this->market_service,
										$this->createMock( WPML::class ),
										$this->container->get( AttributeManager::class ),
										$this->createMock( MapiDataSourcesService::class ),
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
	 * @param int   $failure_status    HTTP status to use for failures (500 or 429 to retry).
	 */
	protected function mock_mapi_inputs( array $synced_products, array $rejected_products, int $failure_status = 500 ): void {
		$this->mapi_inputs->expects( $this->any() )
			->method( 'insert_many' )
			->willReturnCallback(
				function ( array $inputs ) use ( $synced_products, $rejected_products, $failure_status ) {
					$successes = [];
					$failures  = [];
					foreach ( $inputs as $index => $input ) {
						$product_id = (int) str_replace( 'gla_', '', $input->get_offer_id() );
						if ( isset( $synced_products[ $product_id ] ) ) {
							$successes[ $index ] = $input;
						} elseif ( isset( $rejected_products[ $product_id ] ) ) {
							$failures[ $index ] = new MerchantApiException( $failure_status, [], 'Internal Error!' );
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

	public function test_update_rate_limited_products_are_retried_not_dropped() {
		[ $synced_products, $rejected_products ] = $this->create_multiple_simple_product_sets( 2, 2 );

		$batch_helper = $this->getMockBuilder( BatchProductHelper::class )
								->setMethods( [ 'generate_mapi_update_entries' ] )
								->setConstructorArgs(
									[
										$this->product_meta,
										$this->product_helper,
										$this->createMock( ValidatorInterface::class ),
										$this->container->get( ProductFactory::class ),
										$this->rules_query,
										$this->market_service,
										$this->createMock( WPML::class ),
										$this->container->get( AttributeManager::class ),
										$this->createMock( MapiDataSourcesService::class ),
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

		$this->mock_mapi_inputs( $synced_products, $rejected_products, 429 );
		$product_syncer = $this->get_product_syncer( [ 'batch_helper' => $batch_helper ] );

		$product_syncer->update( array_merge( $synced_products, $rejected_products ) );

		// A 429 is transient: rate-limited products are rescheduled, not dropped as invalid.
		$this->assertEquals( 1, did_action( 'woocommerce_gla_batch_retry_update_products' ) );
		foreach ( $rejected_products as $product ) {
			$wc_product = wc_get_product( $product->get_id() );
			$this->assertEquals( SyncStatus::HAS_ERRORS, $this->product_meta->get_sync_status( $wc_product ) );
		}
	}

	public function test_delete_rate_limited_products_are_retried_not_dropped() {
		[ $deleted_products, $rejected_products ] = $this->create_multiple_simple_product_sets( 2, 2 );
		$products                                 = array_merge( $deleted_products, $rejected_products );

		array_walk(
			$products,
			function ( WC_Product $product ) {
				$this->product_helper->mark_as_synced(
					$product,
					$this->generate_google_product_mock( "en~US~gla_{$product->get_id()}", 'US' )
				);
			}
		);

		$this->mock_mapi_delete( $deleted_products, $rejected_products, 429 );

		$this->product_syncer->delete( $products );

		// A 429 is transient: rate-limited deletes are rescheduled, not dropped as invalid.
		$this->assertEquals( 1, did_action( 'woocommerce_gla_batch_retry_delete_products' ) );
	}

	public function test_sync_concurrency_is_filterable() {
		[ $synced_products ] = $this->create_multiple_simple_product_sets( 1, 0 );

		$batch_helper = $this->getMockBuilder( BatchProductHelper::class )
								->setMethods( [ 'generate_mapi_update_entries' ] )
								->setConstructorArgs(
									[
										$this->product_meta,
										$this->product_helper,
										$this->createMock( ValidatorInterface::class ),
										$this->container->get( ProductFactory::class ),
										$this->rules_query,
										$this->market_service,
										$this->createMock( WPML::class ),
										$this->container->get( AttributeManager::class ),
										$this->createMock( MapiDataSourcesService::class ),
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

		$captured_concurrency = null;
		$this->mapi_inputs->expects( $this->once() )
			->method( 'insert_many' )
			->willReturnCallback(
				function ( array $inputs, int $concurrency ) use ( &$captured_concurrency ) {
					$captured_concurrency = $concurrency;

					return [
						'successes' => $inputs,
						'failures'  => [],
					];
				}
			);

		add_filter(
			'woocommerce_gla_mapi_product_concurrency',
			function () {
				return 25;
			}
		);
		$this->get_product_syncer( [ 'batch_helper' => $batch_helper ] )->update( $synced_products );
		remove_all_filters( 'woocommerce_gla_mapi_product_concurrency' );

		$this->assertEquals( 25, $captured_concurrency );
	}

	public function test_update_stores_the_sync_hash_on_success() {
		[ $synced_products ] = $this->create_multiple_simple_product_sets( 1, 0 );
		$product             = reset( $synced_products );

		$batch_helper = $this->getMockBuilder( BatchProductHelper::class )
								->setMethods( [ 'generate_mapi_update_entries' ] )
								->setConstructorArgs(
									[
										$this->product_meta,
										$this->product_helper,
										$this->createMock( ValidatorInterface::class ),
										$this->container->get( ProductFactory::class ),
										$this->rules_query,
										$this->market_service,
										$this->createMock( WPML::class ),
										$this->container->get( AttributeManager::class ),
										$this->createMock( MapiDataSourcesService::class ),
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
								'hash'    => 'testhash123',
							];
						},
						$products
					);
				}
			);

		$this->mapi_inputs->expects( $this->once() )
			->method( 'insert_many' )
			->willReturnCallback(
				function ( array $inputs ) {
					return [
						'successes' => $inputs,
						'failures'  => [],
					];
				}
			);

		$this->get_product_syncer( [ 'batch_helper' => $batch_helper ] )->update( $synced_products );

		// The hash is stored under the entry's own (content language, feed label) key.
		$this->assertEquals( [ 'en|US' => 'testhash123' ], $this->product_meta->get_sync_hash( $product ) );
	}

	public function test_update_connection_errors_are_retried_not_dropped() {
		[ , $rejected_products ] = $this->create_multiple_simple_product_sets( 0, 1 );

		$batch_helper = $this->getMockBuilder( BatchProductHelper::class )
								->setMethods( [ 'generate_mapi_update_entries' ] )
								->setConstructorArgs(
									[
										$this->product_meta,
										$this->product_helper,
										$this->createMock( ValidatorInterface::class ),
										$this->container->get( ProductFactory::class ),
										$this->rules_query,
										$this->market_service,
										$this->createMock( WPML::class ),
										$this->container->get( AttributeManager::class ),
										$this->createMock( MapiDataSourcesService::class ),
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

		$this->mapi_inputs->expects( $this->once() )
			->method( 'insert_many' )
			->willReturnCallback(
				function ( array $inputs ) {
					$failures = [];
					foreach ( array_keys( $inputs ) as $index ) {
						$failures[ $index ] = new ConnectException( 'Connection timed out', new Request( 'POST', 'https://example.test' ) );
					}

					return [
						'successes' => [],
						'failures'  => $failures,
					];
				}
			);

		$this->get_product_syncer( [ 'batch_helper' => $batch_helper ] )->update( $rejected_products );

		// A connection error is transient: the product is rescheduled, not marked permanently invalid.
		$this->assertEquals( 1, did_action( 'woocommerce_gla_batch_retry_update_products' ) );
	}

	public function test_update_no_response_transport_errors_are_retried_not_dropped() {
		[ , $rejected_products ] = $this->create_multiple_simple_product_sets( 0, 1 );

		$batch_helper = $this->getMockBuilder( BatchProductHelper::class )
								->setMethods( [ 'generate_mapi_update_entries' ] )
								->setConstructorArgs(
									[
										$this->product_meta,
										$this->product_helper,
										$this->createMock( ValidatorInterface::class ),
										$this->container->get( ProductFactory::class ),
										$this->rules_query,
										$this->market_service,
										$this->createMock( WPML::class ),
										$this->container->get( AttributeManager::class ),
										$this->createMock( MapiDataSourcesService::class ),
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

		$this->mapi_inputs->expects( $this->once() )
			->method( 'insert_many' )
			->willReturnCallback(
				function ( array $inputs ) {
					$failures = [];
					foreach ( array_keys( $inputs ) as $index ) {
						$failures[ $index ] = new RequestException( 'connection reset', new Request( 'POST', 'https://example.test' ) );
					}

					return [
						'successes' => [],
						'failures'  => $failures,
					];
				}
			);

		$this->get_product_syncer( [ 'batch_helper' => $batch_helper ] )->update( $rejected_products );

		// A transport error with no HTTP response is transient: the product is rescheduled.
		$this->assertEquals( 1, did_action( 'woocommerce_gla_batch_retry_update_products' ) );
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

		// first we mark all products as synced, tracked under the same Google IDs
		// used in the delete request entries below
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

	public function test_delete_by_batch_requests_keeps_tracking_for_entries_not_in_the_request() {
		$product = WC_Helper_Product::create_simple_product();

		$us_google_id = 'en~US~gla_' . $product->get_id();
		$fr_google_id = 'fr~BE-FR-EUR~gla_' . $product->get_id();
		$this->product_helper->mark_as_synced( $product, $this->generate_google_product_mock( $us_google_id, 'US' ) );
		$this->product_helper->mark_as_synced( $product, $this->generate_google_product_mock( $fr_google_id, 'BE-FR-EUR' ) );

		$this->mock_mapi_delete( [ $product->get_id() => $product ], [], 500 );

		$this->product_syncer->delete_by_batch_requests(
			[ new BatchProductIDRequestEntry( $product->get_id(), $fr_google_id ) ]
		);

		$wc_product = wc_get_product( $product->get_id() );
		$this->assertTrue( $this->product_helper->is_product_synced( $wc_product ) );
		$this->assertSame( [ 'US' => $us_google_id ], $this->product_meta->get_google_ids( $wc_product ) );
	}

	public function test_delete_by_id_map_skips_malformed_ids() {
		$this->mapi_inputs->expects( $this->never() )
			->method( 'delete_many' );

		$results = $this->product_syncer->delete_by_id_map( [ 'not-mapi-shape' => 99 ] );

		$this->assertEmpty( $results->get_products() );
		$this->assertEmpty( $results->get_errors() );
	}

	public function test_delete_by_id_map_deletes_legacy_colon_id() {
		// The resync cleanup path must delete products stored under the
		// pre-MAPI Content API id, converting it to the MAPI identity instead of skipping it.
		$product   = WC_Helper_Product::create_simple_product();
		$legacy_id = "online:en:US:gla_{$product->get_id()}";
		$this->product_helper->mark_as_synced( $product, $this->generate_google_product_mock( $legacy_id, 'US' ) );

		$captured = null;
		$this->mapi_inputs->expects( $this->once() )
			->method( 'delete_many' )
			->willReturnCallback(
				function ( array $inputs ) use ( &$captured ) {
					$captured = $inputs[0];
					return [
						'successes' => [ 0 => $inputs[0] ],
						'failures'  => [],
					];
				}
			);

		$results = $this->product_syncer->delete_by_id_map( [ $legacy_id => $product->get_id() ] );

		$this->assertInstanceOf( ProductInput::class, $captured );
		$this->assertSame( 'en', $captured->get_content_language() );
		$this->assertSame( 'US', $captured->get_feed_label() );
		$this->assertSame( "gla_{$product->get_id()}", $captured->get_offer_id() );
		$this->assertCount( 1, $results->get_products() );
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

	public function test_update_wraps_auth_failure_from_entry_generation() {
		$product = WC_Helper_Product::create_simple_product();

		$batch_helper = $this->getMockBuilder( BatchProductHelper::class )
								->setMethods( [ 'generate_mapi_update_entries' ] )
								->setConstructorArgs(
									[
										$this->product_meta,
										$this->product_helper,
										$this->createMock( ValidatorInterface::class ),
										$this->container->get( ProductFactory::class ),
										$this->rules_query,
										$this->market_service,
										$this->createMock( WPML::class ),
										$this->container->get( AttributeManager::class ),
										$this->createMock( MapiDataSourcesService::class ),
									]
								)
								->getMock();
		$batch_helper->method( 'generate_mapi_update_entries' )
			->willThrowException( AccountReconnect::jetpack_disconnected() );

		$product_syncer = $this->get_product_syncer( [ 'batch_helper' => $batch_helper ] );

		// The account-wide auth failure surfaces as a ProductSyncerException, not a raw
		// AccountReconnect, so update() keeps its documented contract.
		try {
			$product_syncer->update( [ $product ] );
			$this->fail( 'Expected a ProductSyncerException.' );
		} catch ( ProductSyncerException $exception ) {
			$this->assertInstanceOf( AccountReconnect::class, $exception->getPrevious() );
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
		$this->market_service  = $this->createMock( MarketService::class );
		$this->merchant_center = $this->createMock( MerchantCenterService::class );
		$this->merchant_center->expects( $this->any() )
			->method( 'is_ready_for_syncing' )
			->willReturn( true );

		$this->merchant_center->expects( $this->any() )
			->method( 'should_push' )
			->willReturn( true );

		$this->mapi_inputs = $this->createMock( MapiProductInputsService::class );
		$this->rules_query = $this->createMock( AttributeMappingRulesQuery::class );

		$this->product_meta       = $this->container->get( ProductMetaHandler::class );
		$this->batch_helper       = $this->container->get( BatchProductHelper::class );
		$this->product_helper     = $this->container->get( ProductHelper::class );
		$this->wc                 = $this->container->get( WC::class );
		$this->product_repository = $this->container->get( ProductRepository::class );
		$this->product_syncer     = $this->get_product_syncer();
	}
}
