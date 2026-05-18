<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google\Mapi\Services;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\MerchantApiClient;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\MerchantApiException;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Models\Product;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services\MapiProductsService;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Promise\Create;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class MapiProductsServiceTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google\Mapi\Services
 */
class MapiProductsServiceTest extends UnitTest {

	protected const MERCHANT_ID = 12345;

	/** @var MockObject|MerchantApiClient */
	protected $client;

	/** @var MockObject|OptionsInterface */
	protected $options;

	/** @var MapiProductsService */
	protected $service;

	public function setUp(): void {
		parent::setUp();

		$this->client  = $this->createMock( MerchantApiClient::class );
		$this->options = $this->createMock( OptionsInterface::class );
		$this->options->method( 'get_merchant_id' )->willReturn( self::MERCHANT_ID );

		$this->service = new MapiProductsService( $this->client );
		$this->service->set_options_object( $this->options );
	}

	public function test_get_calls_expected_path_and_returns_product_dto() {
		$response = [
			'name'              => 'accounts/12345/products/abc',
			'offerId'           => 'sku42',
			'productAttributes' => [
				'title' => 'Test product',
			],
		];

		$this->client->expects( $this->once() )
			->method( 'get' )
			->with( 'products/v1/accounts/12345/products/abc' )
			->willReturn( $response );

		$product = $this->service->get( 'abc' );

		$this->assertInstanceOf( Product::class, $product );
		$this->assertSame( 'abc', $product->get_id() );
		$this->assertSame( 'sku42', $product->get_offer_id() );
		$this->assertSame( 'Test product', $product->get_title() );
	}

	public function test_get_propagates_merchant_api_exception() {
		$this->client->method( 'get' )
			->willThrowException( new MerchantApiException( 404, [], __METHOD__ ) );

		$this->expectException( MerchantApiException::class );

		$this->service->get( 'missing' );
	}

	public function test_get_many_fans_out_and_keys_results_by_id() {
		$this->client->method( 'get_async' )
			->willReturnCallback(
				function ( string $path ) {
					$id = substr( $path, strrpos( $path, '/' ) + 1 );
					return Create::promiseFor(
						[
							'name'    => $path,
							'offerId' => $id,
						]
					);
				}
			);

		$results = $this->service->get_many( [ 'a', 'b', 'c' ] );

		$this->assertCount( 3, $results );
		$this->assertContainsOnlyInstancesOf( Product::class, $results );
		$this->assertSame( 'a', $results['a']->get_offer_id() );
		$this->assertSame( 'b', $results['b']->get_offer_id() );
		$this->assertSame( 'c', $results['c']->get_offer_id() );
	}

	public function test_get_many_drops_rejected_entries_without_affecting_others() {
		$this->client->method( 'get_async' )
			->willReturnCallback(
				function ( string $path ) {
					if ( false !== strpos( $path, '/bad' ) ) {
						return Create::rejectionFor( new MerchantApiException( 500, [], __METHOD__ ) );
					}

					$id = substr( $path, strrpos( $path, '/' ) + 1 );
					return Create::promiseFor(
						[
							'name'    => $path,
							'offerId' => $id,
						]
					);
				}
			);

		$results = $this->service->get_many( [ 'good1', 'bad', 'good2' ] );

		$this->assertCount( 2, $results );
		$this->assertArrayHasKey( 'good1', $results );
		$this->assertArrayHasKey( 'good2', $results );
		$this->assertArrayNotHasKey( 'bad', $results );
	}
}
