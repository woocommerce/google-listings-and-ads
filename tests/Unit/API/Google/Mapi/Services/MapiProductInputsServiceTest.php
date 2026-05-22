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
	protected const DS_EN_US    = 'accounts/12345/dataSources/777';
	protected const DS_FR_CA    = 'accounts/12345/dataSources/888';

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
		$this->data_sources->method( 'ensure_data_source_for' )
			->willReturnCallback(
				function ( string $language, string $feed ) {
					if ( 'en' === $language && 'US' === $feed ) {
						return self::DS_EN_US;
					}
					if ( 'fr' === $language && 'CA' === $feed ) {
						return self::DS_FR_CA;
					}
					return 'accounts/12345/dataSources/unknown';
				}
			);

		$this->options = $this->createMock( OptionsInterface::class );
		$this->options->method( 'get_merchant_id' )->willReturn( self::MERCHANT_ID );

		$this->service = new MapiProductInputsService( $this->client, $this->data_sources );
		$this->service->set_options_object( $this->options );
	}

	protected function expected_path( string $data_source ): string {
		return 'products/v1/accounts/12345/productInputs:insert?dataSource=' . rawurlencode( $data_source );
	}

	protected function make_input( string $offer_id = 'sku42', string $language = 'en', string $feed = 'US' ): ProductInput {
		return new ProductInput( $offer_id, $language, $feed, [ 'title' => 'Test' ] );
	}

	public function test_insert_resolves_data_source_from_input_and_posts() {
		$input = $this->make_input();

		$this->client->expects( $this->once() )
			->method( 'post' )
			->with( $this->expected_path( self::DS_EN_US ), $input->to_array() )
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

	public function test_insert_routes_different_market_to_a_different_data_source() {
		$input = $this->make_input( 'sku42', 'fr', 'CA' );

		$this->client->expects( $this->once() )
			->method( 'post' )
			->with( $this->expected_path( self::DS_FR_CA ), $input->to_array() )
			->willReturn(
				[
					'name'    => 'accounts/12345/productInputs/online~fr~CA~sku42',
					'offerId' => 'sku42',
				]
			);

		$this->service->insert( $input );
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

	public function test_insert_many_routes_each_input_to_its_own_data_source() {
		$paths_seen = [];

		$this->client->method( 'request_async' )
			->willReturnCallback(
				function ( string $method, string $path, array $body ) use ( &$paths_seen ) {
					$paths_seen[ $body['offerId'] ] = $path;

					return Create::promiseFor(
						[
							'name'    => 'accounts/12345/productInputs/' . $body['offerId'],
							'offerId' => $body['offerId'],
						]
					);
				}
			);

		$this->service->insert_many(
			[
				$this->make_input( 'us_sku', 'en', 'US' ),
				$this->make_input( 'ca_sku', 'fr', 'CA' ),
			]
		);

		$this->assertSame( $this->expected_path( self::DS_EN_US ), $paths_seen['us_sku'] );
		$this->assertSame( $this->expected_path( self::DS_FR_CA ), $paths_seen['ca_sku'] );
	}
}
