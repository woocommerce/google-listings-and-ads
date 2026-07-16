<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\Google\BatchProductIDRequestEntry;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\GoogleProductService;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\Error as GoogleError;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\Errors as GoogleErrors;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\Product as GoogleProduct;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\ProductsCustomBatchResponse;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\ProductsCustomBatchResponseEntry;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\Resource\Products;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class GoogleProductServiceTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Google
 */
class GoogleProductServiceTest extends UnitTest {

	/** @var MockObject|ShoppingContent $shopping_service */
	protected $shopping_service;

	/** @var MockObject|OptionsInterface $options */
	protected $options;

	/** @var GoogleProductService $product_service */
	protected $product_service;

	public function setUp(): void {
		parent::setUp();

		$this->shopping_service           = $this->createMock( ShoppingContent::class );
		$this->shopping_service->products = $this->createMock( Products::class );
		$this->options                    = $this->createMock( OptionsInterface::class );
		$this->options->method( 'get_merchant_id' )->willReturn( 12345 );

		$this->product_service = new GoogleProductService( $this->shopping_service );
		$this->product_service->set_options_object( $this->options );
	}

	public function test_delete_batch_success_entries_carry_the_requested_google_id() {
		$google_id = 'online:fr:BE-FR:gla_123';

		// Successful delete responses from Google have no product body.
		$response_entry = new ProductsCustomBatchResponseEntry();
		$response_entry->setBatchId( 0 );

		$response = new ProductsCustomBatchResponse();
		$response->setEntries( [ $response_entry ] );

		$this->shopping_service->products->method( 'custombatch' )->willReturn( $response );

		$result = $this->product_service->delete_batch(
			[ new BatchProductIDRequestEntry( 123, $google_id ) ]
		);

		$this->assertCount( 1, $result->get_products() );
		$entry = $result->get_products()[0];
		$this->assertSame( 123, $entry->get_wc_product_id() );
		$this->assertNotNull( $entry->get_google_product() );
		$this->assertSame( $google_id, $entry->get_google_product()->getId() );
	}

	public function test_delete_batch_keeps_the_response_product_when_present() {
		$response_product = new GoogleProduct();
		$response_product->setId( 'online:en:US:gla_9' );

		$response_entry = new ProductsCustomBatchResponseEntry();
		$response_entry->setBatchId( 0 );
		$response_entry->setProduct( $response_product );

		$response = new ProductsCustomBatchResponse();
		$response->setEntries( [ $response_entry ] );

		$this->shopping_service->products->method( 'custombatch' )->willReturn( $response );

		$result = $this->product_service->delete_batch(
			[ new BatchProductIDRequestEntry( 9, 'online:en:US:gla_9' ) ]
		);

		$this->assertCount( 1, $result->get_products() );
		$this->assertSame( 'online:en:US:gla_9', $result->get_products()[0]->get_google_product()->getId() );
	}

	public function test_snake_case_error_reasons_are_normalised_to_the_constant_spelling() {
		// Google sends `internal_error` / `not_found` while the plugin's reason
		// constants use `internalError` / `notFound`; without normalisation the
		// retry and failure-tracking logic never sees these errors.
		$internal_error = new GoogleError();
		$internal_error->setReason( 'internal_error' );
		$internal_error->setMessage( 'An internal error has occurred.' );

		$not_found = new GoogleError();
		$not_found->setReason( 'not_found' );
		$not_found->setMessage( 'The item could not be found.' );

		$errors = new GoogleErrors();
		$errors->setErrors( [ $internal_error, $not_found ] );

		$response_entry = new ProductsCustomBatchResponseEntry();
		$response_entry->setBatchId( 0 );
		$response_entry->setErrors( $errors );

		$response = new ProductsCustomBatchResponse();
		$response->setEntries( [ $response_entry ] );

		$this->shopping_service->products->method( 'custombatch' )->willReturn( $response );

		$result = $this->product_service->delete_batch(
			[ new BatchProductIDRequestEntry( 123, 'online:en:US:gla_123' ) ]
		);

		$this->assertCount( 1, $result->get_errors() );
		$invalid = $result->get_errors()[0];
		$this->assertTrue( $invalid->has_error( GoogleProductService::INTERNAL_ERROR_REASON ) );
		$this->assertTrue( $invalid->has_error( GoogleProductService::NOT_FOUND_ERROR_REASON ) );
	}

	public function test_camel_case_error_reasons_pass_through_unchanged() {
		$error = new GoogleError();
		$error->setReason( 'internalError' );
		$error->setMessage( 'An internal error has occurred.' );

		$errors = new GoogleErrors();
		$errors->setErrors( [ $error ] );

		$response_entry = new ProductsCustomBatchResponseEntry();
		$response_entry->setBatchId( 0 );
		$response_entry->setErrors( $errors );

		$response = new ProductsCustomBatchResponse();
		$response->setEntries( [ $response_entry ] );

		$this->shopping_service->products->method( 'custombatch' )->willReturn( $response );

		$result = $this->product_service->delete_batch(
			[ new BatchProductIDRequestEntry( 123, 'online:en:US:gla_123' ) ]
		);

		$this->assertCount( 1, $result->get_errors() );
		$this->assertTrue( $result->get_errors()[0]->has_error( GoogleProductService::INTERNAL_ERROR_REASON ) );
	}
}
