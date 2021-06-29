<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Product;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidClass;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\BatchInvalidProductEntry;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\BatchProductEntry;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\BatchProductIDRequestEntry;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\BatchProductRequestEntry;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\GoogleProductService;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\BatchProductHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductFactory;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductMetaHandler;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\WCProductAdapter;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\ProductMetaTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\ProductTrait;
use Google_Service_ShoppingContent_Product as GoogleProduct;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use WC_Product;
use WC_Product_Variable;
use WP_UnitTestCase;

defined( 'ABSPATH' ) || exit;

/**
 * Class BatchProductHelperTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Product
 *
 * @property MockObject|ProductMetaHandler    $product_meta
 * @property MockObject|ProductHelper         $product_helper
 * @property MockObject|ValidatorInterface    $validator
 * @property MockObject|ProductFactory        $product_factory
 * @property MockObject|MerchantCenterService $merchant_center
 * @property BatchProductHelper               $batch_product_helper
 */
class BatchProductHelperTest extends WP_UnitTestCase {

	use ProductMetaTrait;
	use ProductTrait;

	/**
	 * @dataProvider return_blank_test_products
	 */
	public function test_filter_synced_products_all_synced( array $products ) {
		$this->product_helper->expects( $this->exactly( \count( $products ) ) )
							 ->method( 'is_product_synced' )
							 ->willReturn( true );
		$results = $this->batch_product_helper->filter_synced_products( $products );
		$this->assertCount( \count( $products ), $results );
		$this->assertEqualSets( $products, $results );
	}

	/**
	 * @dataProvider return_blank_test_products
	 */
	public function test_filter_synced_products_none_synced( array $products ) {
		$this->product_helper->expects( $this->exactly( \count( $products ) ) )
							 ->method( 'is_product_synced' )
							 ->willReturn( false );
		$results = $this->batch_product_helper->filter_synced_products( $products );
		$this->assertEmpty( $results );
	}

	public function test_mark_as_synced() {
		$wc_product     = new WC_Product();
		$google_product = new GoogleProduct();

		$this->product_helper->expects( $this->once() )
							 ->method( 'get_wc_product' )
							 ->willReturn( $wc_product );

		$this->product_helper->expects( $this->once() )
							 ->method( 'mark_as_synced' )
							 ->with(
								 $this->equalTo( $wc_product ),
								 $this->equalTo( $google_product ),
							 );

		$batch_entry = new BatchProductEntry( 1, $google_product );

		$this->batch_product_helper->mark_as_synced( $batch_entry );
	}

	public function test_mark_as_synced_error_if_no_google_product_provided() {
		$wc_product = new WC_Product();

		$this->product_helper->expects( $this->once() )
							 ->method( 'get_wc_product' )
							 ->willReturn( $wc_product );

		$batch_entry = new BatchProductEntry( 1, null );

		$this->expectException( InvalidClass::class );

		$this->batch_product_helper->mark_as_synced( $batch_entry );
	}

	public function test_mark_as_unsynced() {
		$wc_product = new WC_Product();

		$this->product_helper->expects( $this->once() )
							 ->method( 'get_wc_product' )
							 ->willReturn( $wc_product );

		$this->product_helper->expects( $this->once() )
							 ->method( 'mark_as_unsynced' )
							 ->with( $this->equalTo( $wc_product ) );

		$batch_entry = new BatchProductEntry( 1, null );

		$this->batch_product_helper->mark_as_unsynced( $batch_entry );
	}

	public function test_mark_as_invalid() {
		$wc_product = new WC_Product();
		$errors     = [
			'Error 1',
			'Error 2',
		];

		$this->product_helper->expects( $this->once() )
							 ->method( 'get_wc_product' )
							 ->willReturn( $wc_product );

		$this->product_helper->expects( $this->once() )
							 ->method( 'mark_as_invalid' )
							 ->with(
								 $this->equalTo( $wc_product ),
								 $this->equalTo( $errors ),
							 );

		$batch_entry = new BatchInvalidProductEntry( 1, 'online:en:US:gla_1', $errors );

		$this->batch_product_helper->mark_as_invalid( $batch_entry );
	}

	/**
	 * @dataProvider return_blank_test_products
	 */
	public function test_generate_delete_request_entries( array $products ) {
		$this->product_helper->expects( $this->any() )
							 ->method( 'get_synced_google_product_ids' )
							 ->willReturnCallback( [ $this, 'generate_google_ids' ] );

		$results = $this->batch_product_helper->generate_delete_request_entries( $products );

		// the number of results can be bigger because of variable products
		$this->assertGreaterThanOrEqual( \count( $products ), \count( $results ) );

		$this->assertContainsOnlyInstancesOf( BatchProductIDRequestEntry::class, $results );

		foreach ( $results as $google_id => $request_entry ) {
			$this->assertEquals( $google_id, $request_entry->get_product_id() );

			// check that the assigned Google ID is correctly mapped to the WooCommerce product ID
			$this->assertEquals( $this->generate_google_ids( $request_entry->get_wc_product_id() )[0], $request_entry->get_product_id() );
		}
	}

	public function test_generate_delete_request_entries_variable_product() {
		$products = [
			$this->generate_variable_product_mock( 3 ),
		];

		$this->product_helper->expects( $this->any() )
							 ->method( 'get_synced_google_product_ids' )
							 ->willReturnCallback( [ $this, 'generate_google_ids' ] );

		$results = $this->batch_product_helper->generate_delete_request_entries( $products );

		$this->assertCount( 3, $results );
	}

	public function test_generate_delete_request_entries_including_invalid_product() {
		$products = [
			$this->generate_simple_product_mock(),
			new BatchProductEntry( 0, null ),
		];
		$this->expectException( InvalidClass::class );
		$this->batch_product_helper->generate_delete_request_entries( $products );
	}

	/**
	 * @dataProvider return_blank_test_products
	 */
	public function test_generate_delete_request_entries_skips_if_no_synced_google_id_exists( array $products ) {
		// skip one product from the list
		$skipped_product = $products[0];
		$this->product_helper->expects( $this->any() )
							 ->method( 'get_synced_google_product_ids' )
							 ->willReturnCallback(
								 function ( WC_Product $product ) use ( $skipped_product ) {
									 if ( $product === $skipped_product ) {
										 return null;
									 }

									 return $this->generate_google_ids( $product );
								 }
							 );

		$results = $this->batch_product_helper->generate_delete_request_entries( $products );

		$skipped_product_google_id = $this->generate_google_ids( $skipped_product )[0];
		$this->assertArrayNotHasKey( $skipped_product_google_id, $results );
	}

	/**
	 * @dataProvider return_blank_test_products
	 */
	public function test_validate_and_generate_update_request_entries( array $products ) {
		$this->product_helper->expects( $this->any() )
							 ->method( 'is_sync_ready' )
							 ->willReturn( true );
		$this->validator->expects( $this->any() )
						->method( 'validate' )
						->willReturn( [] );

		$results = $this->batch_product_helper->validate_and_generate_update_request_entries( $products );

		// the number of results can be bigger because of variable products
		$this->assertGreaterThanOrEqual( \count( $products ), \count( $results ) );

		$this->assertContainsOnlyInstancesOf( BatchProductRequestEntry::class, $results );

		// the products (including variations if a variable product) sent to the method should ALL be returned as results
		$results_product_ids = array_map(
			function ( BatchProductRequestEntry $request_entry ) {
				return $request_entry->get_wc_product_id();
			},
			$results
		);
		$param_product_ids   = [];
		foreach ( $products as $product ) {
			if ( $product instanceof WC_Product_Variable ) {
				foreach ( $product->get_children() as $child ) {
					$param_product_ids[] = $child->get_id();
				}
			} else {
				$param_product_ids[] = $product->get_id();
			}
		}
		$this->assertEqualSets( $param_product_ids, $results_product_ids );
	}

	/**
	 * @dataProvider return_blank_test_products
	 */
	public function test_validate_and_generate_update_request_entries_skips_invalid_product( array $products ) {
		$this->product_helper->expects( $this->any() )
							 ->method( 'is_sync_ready' )
							 ->willReturn( true );

		// skip one product from the list
		$invalid_product         = $products[0];
		$invalid_product_adapted = $this->generate_adapted_product( $invalid_product );

		$this->product_factory->expects( $this->any() )
							  ->method( 'create' )
							  ->willReturnCallback(
								  function ( WC_Product $product, string $target_country ) use ( $invalid_product, $invalid_product_adapted ) {
									  if ( $product === $invalid_product ) {
										  return $invalid_product_adapted;
									  }

									  return $this->generate_adapted_product( $product );
								  }
							  );

		$this->validator->expects( $this->any() )
						->method( 'validate' )
						->willReturnCallback(
							function ( WCProductAdapter $product ) use ( $invalid_product_adapted ) {
								if ( $product === $invalid_product_adapted ) {
									$violation_example = $this->createMock( ConstraintViolation::class );
									$violations        = new ConstraintViolationList();
									$violations->add( $violation_example );

									return $violations;
								}

								return [];
							}
						);

		$results = $this->batch_product_helper->validate_and_generate_update_request_entries( $products );

		$results_product_ids = array_map(
			function ( BatchProductRequestEntry $request_entry ) {
				return $request_entry->get_wc_product_id();
			},
			$results
		);

		$this->assertNotContains( $invalid_product->get_id(), $results_product_ids );
	}

	/**
	 * @dataProvider return_blank_test_products
	 */
	public function test_validate_and_generate_update_request_entries_skips_not_sync_ready( array $products ) {
		// skip one product from the list
		$skipped_product = $products[0];

		$this->validator->expects( $this->any() )
						->method( 'validate' )
						->willReturn( [] );

		$this->product_helper->expects( $this->any() )
							 ->method( 'is_sync_ready' )
							 ->willReturnCallback(
								 function ( WC_Product $product ) use ( $skipped_product ) {
									 if ( $product === $skipped_product ) {
										 return false;
									 }

									 return true;
								 }
							 );

		$results = $this->batch_product_helper->validate_and_generate_update_request_entries( $products );

		$results_product_ids = array_map(
			function ( BatchProductRequestEntry $request_entry ) {
				return $request_entry->get_wc_product_id();
			},
			$results
		);

		$this->assertNotContains( $skipped_product->get_id(), $results_product_ids );
	}

	public function test_validate_and_generate_update_request_entries_including_invalid_product() {
		$products = [
			$this->generate_simple_product_mock(),
			new BatchProductEntry( 0, null ),
		];
		$this->expectException( InvalidClass::class );
		$this->batch_product_helper->validate_and_generate_update_request_entries( $products );
	}

	public function test_get_internal_error_products() {
		$invalid_entries = [
			new BatchInvalidProductEntry(
				1,
				'online:en:US:gla_1',
				[
					GoogleProductService::INTERNAL_ERROR_REASON => 'Internal error!',
					'another-type-of-error'                     => 'Some other error!',
				]
			),
			new BatchInvalidProductEntry(
				2,
				'online:en:US:gla_2',
				[ 'another-type-of-error' => 'Some other error!' ]
			),
		];

		$results = $this->batch_product_helper->get_internal_error_products( $invalid_entries );

		$this->assertEqualSets( [ 1 ], $results );
	}

	/**
	 * @dataProvider return_blank_test_products
	 */
	public function test_generate_stale_products_request_entries( array $products ) {
		$stale_product    = $products[0];
		$stale_product_id = $stale_product->get_id();

		$this->merchant_center->expects( $this->once() )
							  ->method( 'get_target_countries' )
							  ->willReturn( [ 'US' ] );

		$stale_google_ids = [
			'AU' => "online:en:AU:gla_{$stale_product_id}",
			'DK' => "online:en:DK:gla_{$stale_product_id}",
			'US' => "online:en:US:gla_{$stale_product_id}",
		];
		$this->product_meta->expects( $this->any() )
						   ->method( 'get_google_ids' )
						   ->willReturnMap( [ [ $stale_product, $stale_google_ids ] ] );

		$results = $this->batch_product_helper->generate_stale_products_request_entries( $products );

		$this->assertCount( 2, $results );
		$this->assertContainsOnlyInstancesOf( BatchProductIDRequestEntry::class, $results );
		$this->assertArrayHasKey( $stale_google_ids['AU'], $results );
		$this->assertArrayHasKey( $stale_google_ids['DK'], $results );

		foreach ( $results as $request_entry ) {
			$this->assertEquals( $stale_product_id, $request_entry->get_wc_product_id() );
		}
	}

	/**
	 * @dataProvider return_blank_test_products
	 */
	public function test_generate_stale_countries_request_entries( array $products ) {
		$stale_product    = $products[0];
		$stale_product_id = $stale_product->get_id();

		$this->merchant_center->expects( $this->once() )
							  ->method( 'get_main_target_country' )
							  ->willReturn( 'US' );

		$stale_google_ids = [
			'AU' => "online:en:AU:gla_{$stale_product_id}",
			'DK' => "online:en:DK:gla_{$stale_product_id}",
			'US' => "online:en:US:gla_{$stale_product_id}",
		];
		$this->product_meta->expects( $this->any() )
						   ->method( 'get_google_ids' )
						   ->willReturnMap( [ [ $stale_product, $stale_google_ids ] ] );

		$results = $this->batch_product_helper->generate_stale_countries_request_entries( $products );

		$this->assertCount( 2, $results );
		$this->assertContainsOnlyInstancesOf( BatchProductIDRequestEntry::class, $results );
		$this->assertArrayHasKey( $stale_google_ids['AU'], $results );
		$this->assertArrayHasKey( $stale_google_ids['DK'], $results );

		foreach ( $results as $request_entry ) {
			$this->assertEquals( $stale_product_id, $request_entry->get_wc_product_id() );
		}
	}

	/**
	 * @return WC_Product[][]
	 */
	public function return_blank_test_products(): array {
		return [
			[
				[
					$this->generate_simple_product_mock(),
					$this->generate_variation_product_mock(),
				],
			],
			[
				[
					$this->generate_simple_product_mock(),
					$this->generate_variation_product_mock(),
					$this->generate_variable_product_mock(),
				],
			],
		];
	}

	/**
	 * Runs before each test is executed.
	 */
	public function setUp() {
		parent::setUp();
		$this->product_meta         = $this->get_product_meta_handler_mock();
		$this->product_helper       = $this->createMock( ProductHelper::class );
		$this->validator            = $this->createMock( ValidatorInterface::class );
		$this->product_factory      = $this->createMock( ProductFactory::class );
		$this->merchant_center      = $this->createMock( MerchantCenterService::class );
		$this->batch_product_helper = new BatchProductHelper( $this->product_meta, $this->product_helper, $this->validator, $this->product_factory, $this->merchant_center );
	}
}
