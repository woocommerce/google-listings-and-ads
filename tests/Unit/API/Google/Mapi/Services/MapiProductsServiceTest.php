<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google\Mapi\Services;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\MerchantApiClient;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Models\Product;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services\MapiProductsService;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
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

	public function test_list_page_requests_first_page_at_default_size_1000() {
		$this->client->expects( $this->once() )
			->method( 'get' )
			->with( 'products/v1/accounts/12345/products?pageSize=1000' )
			->willReturn(
				[
					'products'      => [
						[
							'name'    => 'accounts/12345/products/en~US~gla_1',
							'offerId' => 'gla_1',
						],
					],
					'nextPageToken' => 'tok/2',
				]
			);

		$page = $this->service->list_page();

		$this->assertCount( 1, $page['products'] );
		$this->assertInstanceOf( Product::class, $page['products'][0] );
		$this->assertSame( 'tok/2', $page['next_page_token'] );
	}

	public function test_list_page_clamps_page_size_to_api_bounds() {
		$matcher = $this->exactly( 2 );
		$this->client->expects( $matcher )
			->method( 'get' )
			->willReturnCallback(
				function ( string $path ) use ( $matcher ) {
					// The token is null, so pageSize is the final query argument; an
					// exact suffix match cannot be satisfied by a longer number.
					if ( 1 === $matcher->getInvocationCount() ) {
						$this->assertStringEndsWith( 'pageSize=1000', $path );
					} else {
						$this->assertStringEndsWith( 'pageSize=1', $path );
					}

					return [ 'products' => [] ];
				}
			);

		$this->service->list_page( null, 5000 );
		$this->service->list_page( null, 0 );
	}

	public function test_list_page_encodes_token_and_ends_pagination() {
		$this->client->expects( $this->once() )
			->method( 'get' )
			->with( 'products/v1/accounts/12345/products?pageSize=1000&pageToken=tok%2F2' )
			->willReturn( [ 'products' => [] ] );

		$page = $this->service->list_page( 'tok/2' );

		$this->assertSame( [], $page['products'] );
		$this->assertNull( $page['next_page_token'] );
	}

	public function test_list_follows_pagination_and_returns_products() {
		$this->client->expects( $this->exactly( 2 ) )
			->method( 'get' )
			->withConsecutive(
				[ 'products/v1/accounts/12345/products?pageSize=250' ],
				[ 'products/v1/accounts/12345/products?pageSize=250&pageToken=a%2Fb%3Dc' ]
			)
			->willReturnOnConsecutiveCalls(
				[
					'products'      => [
						[
							'name'    => 'accounts/12345/products/a',
							'offerId' => 'a',
						],
						[
							'name'    => 'accounts/12345/products/b',
							'offerId' => 'b',
						],
					],
					'nextPageToken' => 'a/b=c',
				],
				[
					'products' => [
						[
							'name'    => 'accounts/12345/products/c',
							'offerId' => 'c',
						],
					],
				]
			);

		$products = iterator_to_array( $this->service->list() );

		$this->assertCount( 3, $products );
		$this->assertContainsOnlyInstancesOf( Product::class, $products );
		$this->assertSame( 'a', $products[0]->get_offer_id() );
		$this->assertSame( 'c', $products[2]->get_offer_id() );
	}

	public function test_list_yields_nothing_when_no_products() {
		$this->client->expects( $this->once() )
			->method( 'get' )
			->with( 'products/v1/accounts/12345/products?pageSize=250' )
			->willReturn( [ 'products' => [] ] );

		$this->assertSame( [], iterator_to_array( $this->service->list() ) );
	}

	public function test_list_request_uses_custom_page_size() {
		$this->client->expects( $this->once() )
			->method( 'get' )
			->with( 'products/v1/accounts/12345/products?pageSize=100' )
			->willReturn( [ 'products' => [] ] );

		iterator_to_array( $this->service->list( 100 ) );
	}
}
