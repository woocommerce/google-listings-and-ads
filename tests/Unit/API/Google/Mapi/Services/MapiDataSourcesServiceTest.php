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
		$this->options->method( 'get' )->willReturn(
			[
				'en|US' => 'accounts/12345/dataSources/999',
			]
		);
		$this->client->expects( $this->never() )->method( 'get' );
		$this->client->expects( $this->never() )->method( 'post' );

		$this->assertSame(
			'accounts/12345/dataSources/999',
			$this->service->ensure_data_source_for( 'en', 'US' )
		);
	}

	public function test_reuses_existing_data_source_matching_language_and_feed() {
		$this->options->method( 'get' )->willReturn( [] );
		$this->client->expects( $this->once() )
			->method( 'get' )
			->with( self::LIST_PATH )
			->willReturn(
				[
					'dataSources' => [
						[
							'name'                     => 'accounts/12345/dataSources/100',
							'displayName'              => 'Some existing source',
							'primaryProductDataSource' => [
								'contentLanguage' => 'en',
								'feedLabel'       => 'US',
							],
						],
					],
				]
			);
		$this->options->expects( $this->once() )
			->method( 'update' )
			->with(
				OptionsInterface::MAPI_DATA_SOURCES,
				[ 'en|US' => 'accounts/12345/dataSources/100' ]
			);

		$this->assertSame(
			'accounts/12345/dataSources/100',
			$this->service->ensure_data_source_for( 'en', 'US' )
		);
	}

	public function test_skips_existing_sources_with_different_language_or_feed() {
		$this->options->method( 'get' )->willReturn( [] );
		$this->client->method( 'get' )->willReturn(
			[
				'dataSources' => [
					[
						'name'                     => 'accounts/12345/dataSources/100',
						'displayName'              => 'Other market',
						'primaryProductDataSource' => [
							'contentLanguage' => 'fr',
							'feedLabel'       => 'CA',
						],
					],
					[
						'name'        => 'accounts/12345/dataSources/200',
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
						return 'Google for WooCommerce (en/US)' === $body['displayName']
							&& 'en' === $body['primaryProductDataSource']['contentLanguage']
							&& 'US' === $body['primaryProductDataSource']['feedLabel']
							&& ! isset( $body['fileInput'] );
					}
				)
			)
			->willReturn( [ 'name' => 'accounts/12345/dataSources/777' ] );

		$this->assertSame(
			'accounts/12345/dataSources/777',
			$this->service->ensure_data_source_for( 'en', 'US' )
		);
	}

	public function test_creates_new_source_when_none_match() {
		$this->options->method( 'get' )->willReturn( [] );
		$this->client->method( 'get' )->willReturn( [ 'dataSources' => [] ] );
		$this->client->expects( $this->once() )
			->method( 'post' )
			->willReturn( [ 'name' => 'accounts/12345/dataSources/777' ] );
		$this->options->expects( $this->once() )
			->method( 'update' )
			->with(
				OptionsInterface::MAPI_DATA_SOURCES,
				[ 'fr|CA' => 'accounts/12345/dataSources/777' ]
			);

		$this->assertSame(
			'accounts/12345/dataSources/777',
			$this->service->ensure_data_source_for( 'fr', 'CA' )
		);
	}

	public function test_preserves_other_market_cache_entries_when_resolving_a_new_market() {
		$this->options->method( 'get' )->willReturn(
			[ 'en|US' => 'accounts/12345/dataSources/100' ]
		);
		$this->client->method( 'get' )->willReturn( [ 'dataSources' => [] ] );
		$this->client->method( 'post' )->willReturn(
			[ 'name' => 'accounts/12345/dataSources/200' ]
		);
		$this->options->expects( $this->once() )
			->method( 'update' )
			->with(
				OptionsInterface::MAPI_DATA_SOURCES,
				[
					'en|US' => 'accounts/12345/dataSources/100',
					'fr|CA' => 'accounts/12345/dataSources/200',
				]
			);

		$this->assertSame(
			'accounts/12345/dataSources/200',
			$this->service->ensure_data_source_for( 'fr', 'CA' )
		);
	}
}
