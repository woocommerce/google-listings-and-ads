<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google\Mapi\Services;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\MerchantApiClient;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services\MapiDataSourcesService;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class MapiDataSourcesServiceTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google\Mapi\Services
 */
class MapiDataSourcesServiceTest extends UnitTest {

	protected const MERCHANT_ID = 12345;
	protected const LIST_PATH   = 'datasources/v1/accounts/12345/dataSources';

	/** @var MockObject|MerchantApiClient */
	protected $client;

	/** @var MockObject|OptionsInterface */
	protected $options;

	/** @var MapiDataSourcesService */
	protected $service;

	public function setUp(): void {
		parent::setUp();

		$this->client  = $this->createMock( MerchantApiClient::class );
		$this->options = $this->createMock( OptionsInterface::class );
		$this->options->method( 'get_merchant_id' )->willReturn( self::MERCHANT_ID );

		$this->service = new MapiDataSourcesService( $this->client );
		$this->service->set_options_object( $this->options );
	}

	public function test_returns_cached_value_without_api_call() {
		$this->options->method( 'get' )->willReturn( 'accounts/12345/dataSources/999' );
		$this->client->expects( $this->never() )->method( 'get' );
		$this->client->expects( $this->never() )->method( 'post' );

		$this->assertSame( 'accounts/12345/dataSources/999', $this->service->ensure_primary_data_source() );
	}

	public function test_reuses_plugin_labeled_source() {
		$this->options->method( 'get' )->willReturn( '' );
		$this->client->expects( $this->once() )
			->method( 'get' )
			->with( self::LIST_PATH )
			->willReturn(
				[
					'dataSources' => [
						[
							'name'                     => 'accounts/12345/dataSources/200',
							'displayName'              => 'Google for WooCommerce',
							'primaryProductDataSource' => [],
						],
					],
				]
			);
		$this->options->expects( $this->once() )
			->method( 'update' )
			->with( OptionsInterface::MAPI_PRIMARY_DATA_SOURCE, 'accounts/12345/dataSources/200' );

		$this->assertSame( 'accounts/12345/dataSources/200', $this->service->ensure_primary_data_source() );
	}

	public function test_creates_new_source_when_no_plugin_labeled_source_exists() {
		$this->options->method( 'get' )->willReturn( '' );
		// Existing non-plugin sources must be ignored; MAPI will auto-move products
		// on the next productInputs.insert under the new data source.
		$this->client->method( 'get' )->willReturn(
			[
				'dataSources' => [
					[
						'name'                     => 'accounts/12345/dataSources/100',
						'displayName'              => 'Content API',
						'primaryProductDataSource' => [],
					],
					[
						'name'        => 'accounts/12345/dataSources/300',
						'displayName' => 'Some File Feed',
						'fileInput'   => [],
					],
				],
			]
		);
		$this->client->expects( $this->once() )
			->method( 'post' )
			->with(
				self::LIST_PATH,
				$this->callback(
					function ( $body ) {
						return 'Google for WooCommerce' === $body['displayName']
							&& isset( $body['primaryProductDataSource'] )
							&& ! isset( $body['fileInput'] );
					}
				)
			)
			->willReturn( [ 'name' => 'accounts/12345/dataSources/777' ] );

		$this->assertSame( 'accounts/12345/dataSources/777', $this->service->ensure_primary_data_source() );
	}

	public function test_install_skips_when_no_merchant_id() {
		$options = $this->createMock( OptionsInterface::class );
		$options->method( 'get_merchant_id' )->willReturn( 0 );

		$service = new MapiDataSourcesService( $this->client );
		$service->set_options_object( $options );

		$this->client->expects( $this->never() )->method( 'get' );
		$this->client->expects( $this->never() )->method( 'post' );

		$service->install( '1.0.0', '1.1.0' );
	}

	public function test_install_resolves_data_source_when_connected() {
		$this->options->method( 'get' )->willReturn( '' );
		$this->client->method( 'get' )->willReturn(
			[
				'dataSources' => [
					[
						'name'                     => 'accounts/12345/dataSources/200',
						'displayName'              => 'Google for WooCommerce',
						'primaryProductDataSource' => [],
					],
				],
			]
		);
		$this->options->expects( $this->once() )
			->method( 'update' )
			->with( OptionsInterface::MAPI_PRIMARY_DATA_SOURCE, 'accounts/12345/dataSources/200' );

		$this->service->install( '1.0.0', '1.1.0' );
	}
}
