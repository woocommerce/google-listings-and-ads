<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Product;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidClass;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\BatchInvalidProductEntry;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\BatchProductEntry;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\BatchProductIDRequestEntry;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\BatchProductRequestEntry;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\GoogleProductService;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\TargetAudience;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\BatchProductHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductFactory;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductMetaHandler;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\WCProductAdapter;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\ContainerAwareUnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\ProductMetaTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\ProductTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\ChannelVisibility;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use WC_Helper_Product;
use WC_Product;
use WC_Product_Variable;
use WC_Product_Variation;

/**
 * Class BatchProductHelperTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Product
 *
 * @property WC                            $wc
 * @property ProductMetaHandler            $product_meta
 * @property ProductHelper                 $product_helper
 * @property MockObject|ValidatorInterface $validator
 * @property ProductFactory                $product_factory
 * @property MockObject|TargetAudience     $target_audience
 * @property BatchProductHelper            $batch_product_helper
 */
class BatchProductHelperTest extends ContainerAwareUnitTest {

	use ProductMetaTrait;
	use ProductTrait;

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

	public function test_validate_and_generate_update_request_entries() {
		$products = $this->create_and_return_supported_test_products();

		$this->target_audience->expects( $this->any() )
							  ->method( 'get_main_target_country' )
							  ->willReturn( 'US' );
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

	public function test_validate_and_generate_update_request_entries_skips_invalid_product() {
		$products = $this->create_and_return_supported_test_products();

		// skip one product from the list
		$invalid_product = $products[0];

		$this->validator->expects( $this->any() )
						->method( 'validate' )
						->willReturnCallback(
							function ( WCProductAdapter $product ) use ( $invalid_product ) {
								if ( $product->get_wc_product()->get_id() === $invalid_product->get_id() ) {
									$violation_example = $this->createMock( ConstraintViolation::class );
									$violations        = new ConstraintViolationList();
									$violations->add( $violation_example );

									return $violations;
								}

								return [];
							}
						);

		$this->target_audience->expects( $this->any() )
							  ->method( 'get_main_target_country' )
							  ->willReturn( 'US' );

		$results = $this->batch_product_helper->validate_and_generate_update_request_entries( $products );

		$results_product_ids = array_map(
			function ( BatchProductRequestEntry $request_entry ) {
				return $request_entry->get_wc_product_id();
			},
			$results
		);

		$this->assertNotContains( $invalid_product->get_id(), $results_product_ids );
	}

	public function test_validate_and_generate_update_request_entries_skips_not_sync_ready() {
		$products = $this->create_and_return_supported_test_products();

		// skip one product from the list
		$skipped_product = $products[0];
		if ( $skipped_product instanceof WC_Product_Variation ) {
			$this->product_meta->update_visibility( wc_get_product( $skipped_product->get_parent_id() ), ChannelVisibility::DONT_SYNC_AND_SHOW );
		} else {
			$this->product_meta->update_visibility( $skipped_product, ChannelVisibility::DONT_SYNC_AND_SHOW );
		}

		$this->target_audience->expects( $this->any() )
							  ->method( 'get_main_target_country' )
							  ->willReturn( 'US' );
		$this->validator->expects( $this->any() )
						->method( 'validate' )
						->willReturn( [] );

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

	public function test_generate_stale_products_request_entries() {
		$products         = $this->create_and_return_supported_test_products();
		$stale_product    = $products[0];
		$stale_product_id = $stale_product->get_id();

		$this->target_audience->expects( $this->once() )
							  ->method( 'get_target_countries' )
							  ->willReturn( [ 'US' ] );
		$this->target_audience->expects( $this->any() )
							  ->method( 'get_main_target_country' )
							  ->willReturn( 'US' );

		$stale_google_ids = [
			'AU' => "online:en:AU:gla_{$stale_product_id}",
			'DK' => "online:en:DK:gla_{$stale_product_id}",
			'US' => "online:en:US:gla_{$stale_product_id}",
		];
		$this->product_meta->update_google_ids( $stale_product, $stale_google_ids );

		$results = $this->batch_product_helper->generate_stale_products_request_entries( $products );

		$this->assertCount( 2, $results );
		$this->assertContainsOnlyInstancesOf( BatchProductIDRequestEntry::class, $results );
		$this->assertArrayHasKey( $stale_google_ids['AU'], $results );
		$this->assertArrayHasKey( $stale_google_ids['DK'], $results );

		foreach ( $results as $request_entry ) {
			$this->assertEquals( $stale_product_id, $request_entry->get_wc_product_id() );
		}
	}

	public function test_generate_stale_countries_request_entries() {
		$products         = $this->create_and_return_supported_test_products();
		$stale_product    = $products[0];
		$stale_product_id = $stale_product->get_id();

		$this->target_audience->expects( $this->once() )
							  ->method( 'get_main_target_country' )
							  ->willReturn( 'US' );

		$stale_google_ids = [
			'AU' => "online:en:AU:gla_{$stale_product_id}",
			'DK' => "online:en:DK:gla_{$stale_product_id}",
			'US' => "online:en:US:gla_{$stale_product_id}",
		];
		$this->product_meta->update_google_ids( $stale_product, $stale_google_ids );

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
	public function setUp() {
		parent::setUp();
		$this->target_audience      = $this->createMock( TargetAudience::class );
		$this->validator            = $this->createMock( ValidatorInterface::class );
		$this->product_meta         = $this->container->get( ProductMetaHandler::class );
		$this->product_factory      = $this->container->get( ProductFactory::class );
		$this->wc                   = $this->container->get( WC::class );
		$this->product_helper       = new ProductHelper( $this->product_meta, $this->wc, $this->target_audience );
		$this->batch_product_helper = new BatchProductHelper( $this->product_meta, $this->product_helper, $this->validator, $this->product_factory, $this->target_audience );
	}
}
