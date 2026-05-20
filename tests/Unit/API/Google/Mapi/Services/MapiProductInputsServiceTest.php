<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google\Mapi\Services;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\MerchantApiClient;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\MerchantApiException;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Models\ProductInput;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services\MapiDataSourcesService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services\MapiProductInputsService;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Promise\Create;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class MapiProductInputsServiceTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google\Mapi\Services
 */
class MapiProductInputsServiceTest extends UnitTest {

	protected const MERCHANT_ID = 12345;
	protected const DATA_SOURCE = 'accounts/12345/dataSources/777';

	/** @var MockObject|MerchantApiClient */
	protected $client;

	/** @var MockObject|MapiDataSourcesService */
	protected $data_sources;

	/** @var MockObject|OptionsInterface */
	protected $options;

	/** @var MapiProductInputsService */
	protected $service;

	public function setUp(): void {
		parent::setUp();

		$this->client       = $this->createMock( MerchantApiClient::class );
		$this->data_sources = $this->createMock( MapiDataSourcesService::class );
		$this->data_sources->method( 'ensure_primary_data_source' )->willReturn( self::DATA_SOURCE );

		$this->options = $this->createMock( OptionsInterface::class );
		$this->options->method( 'get_merchant_id' )->willReturn( self::MERCHANT_ID );

		$this->service = new MapiProductInputsService( $this->client, $this->data_sources );
		$this->service->set_options_object( $this->options );
	}

	protected function expected_path(): string {
		return 'products/v1/accounts/12345/productInputs:insert?dataSource=' . rawurlencode( self::DATA_SOURCE );
	}

	protected function make_input( string $offer_id = 'sku42' ): ProductInput {
		return new ProductInput( $offer_id, 'en', 'US', [ 'title' => 'Test' ] );
	}

	public function test_insert_posts_to_expected_path_with_serialized_body() {
		$input = $this->make_input();

		$this->client->expects( $this->once() )
			->method( 'post' )
			->with( $this->expected_path(), $input->to_array() )
			->willReturn(
				[
					'name'    => 'accounts/12345/productInputs/online~en~US~sku42',
					'offerId' => 'sku42',
				]
			);

		$result = $this->service->insert( $input );

		$this->assertInstanceOf( ProductInput::class, $result );
		$this->assertSame( 'accounts/12345/productInputs/online~en~US~sku42', $result->get_name() );
		$this->assertSame( 'sku42', $result->get_offer_id() );
	}

	public function test_insert_propagates_merchant_api_exception() {
		$this->client->method( 'post' )
			->willThrowException( new MerchantApiException( 400, [], __METHOD__ ) );

		$this->expectException( MerchantApiException::class );

		$this->service->insert( $this->make_input() );
	}

	public function test_insert_many_keys_successes_and_failures_by_index() {
		$this->client->method( 'request_async' )
			->willReturnCallback(
				function ( string $method, string $path, array $body ) {
					if ( 'bad' === $body['offerId'] ) {
						return Create::rejectionFor( new MerchantApiException( 500, [], __METHOD__ ) );
					}

					return Create::promiseFor(
						[
							'name'    => 'accounts/12345/productInputs/' . $body['offerId'],
							'offerId' => $body['offerId'],
						]
					);
				}
			);

		$result = $this->service->insert_many(
			[
				$this->make_input( 'good1' ),
				$this->make_input( 'bad' ),
				$this->make_input( 'good2' ),
			]
		);

		$this->assertCount( 2, $result['successes'] );
		$this->assertCount( 1, $result['failures'] );
		$this->assertArrayHasKey( 0, $result['successes'] );
		$this->assertArrayHasKey( 2, $result['successes'] );
		$this->assertArrayHasKey( 1, $result['failures'] );
		$this->assertInstanceOf( ProductInput::class, $result['successes'][0] );
		$this->assertInstanceOf( MerchantApiException::class, $result['failures'][1] );
		$this->assertSame( 'good1', $result['successes'][0]->get_offer_id() );
	}
}
