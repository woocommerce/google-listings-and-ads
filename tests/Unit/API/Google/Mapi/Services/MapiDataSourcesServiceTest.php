<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google\Mapi\Services;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\MerchantApiClient;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\MerchantApiException;
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

	public function test_returns_cached_value_after_verifying_it_exists() {
		$this->options->method( 'get' )->willReturn(
			[
				'product|en|US' => 'accounts/12345/dataSources/999',
			]
		);
		// A cache hit is verified once with a dataSources.get before it is trusted.
		$this->client->expects( $this->once() )
			->method( 'get' )
			->with( 'datasources/v1/accounts/12345/dataSources/999' )
			->willReturn( [ 'name' => 'accounts/12345/dataSources/999' ] );
		$this->client->expects( $this->never() )->method( 'post' );

		$this->assertSame(
			'accounts/12345/dataSources/999',
			$this->service->ensure_data_source_for( 'en', 'US' )
		);
	}

	public function test_verifies_cached_name_only_once_per_request() {
		$this->options->method( 'get' )->willReturn(
			[
				'product|en|US' => 'accounts/12345/dataSources/999',
			]
		);
		// Two resolutions of the same pair in one request verify the name only once.
		$this->client->expects( $this->once() )
			->method( 'get' )
			->with( 'datasources/v1/accounts/12345/dataSources/999' )
			->willReturn( [ 'name' => 'accounts/12345/dataSources/999' ] );

		$this->service->ensure_data_source_for( 'en', 'US' );
		$this->service->ensure_data_source_for( 'en', 'US' );
	}

	public function test_drops_cached_name_and_re_resolves_when_verification_returns_404() {
		$this->options->method( 'get' )->willReturn(
			[
				'product|en|US' => 'accounts/12345/dataSources/stale',
			]
		);

		// Verification 404s (source gone), then discovery lists the real source.
		$this->client->expects( $this->exactly( 2 ) )
			->method( 'get' )
			->withConsecutive(
				[ 'datasources/v1/accounts/12345/dataSources/stale' ],
				[ self::LIST_PATH ]
			)
			->willReturnOnConsecutiveCalls(
				$this->throwException( new MerchantApiException( 404, [ 'error' => [ 'message' => 'Data source with id stale was not found.' ] ], 'get' ) ),
				[
					'dataSources' => [
						[
							'name'                     => 'accounts/12345/dataSources/fresh',
							'displayName'              => 'Google for WooCommerce (en/US)',
							'primaryProductDataSource' => [
								'contentLanguage' => 'en',
								'feedLabel'       => 'US',
							],
						],
					],
				]
			);

		// The stale entry is cleared, then the re-resolved name is written back.
		$this->options->expects( $this->exactly( 2 ) )
			->method( 'update' )
			->withConsecutive(
				[ OptionsInterface::MAPI_DATA_SOURCES, [] ],
				[ OptionsInterface::MAPI_DATA_SOURCES, [ 'product|en|US' => 'accounts/12345/dataSources/fresh' ] ]
			);

		$this->assertSame(
			'accounts/12345/dataSources/fresh',
			$this->service->ensure_data_source_for( 'en', 'US' )
		);
	}

	public function test_forget_data_source_for_removes_entry_so_next_resolution_bypasses_cache() {
		// Stateful option so forget()'s write is visible to the following resolution.
		$stored = [ OptionsInterface::MAPI_DATA_SOURCES => [ 'product|en|US' => 'accounts/12345/dataSources/999' ] ];
		$this->options->method( 'get' )->willReturnCallback(
			function ( string $key, $fallback = false ) use ( &$stored ) {
				return $stored[ $key ] ?? $fallback;
			}
		);
		$this->options->method( 'update' )->willReturnCallback(
			function ( string $key, $value ) use ( &$stored ) {
				$stored[ $key ] = $value;
				return true;
			}
		);

		// forget() clears the entry; the next resolution then discovers rather than verifying a cache hit.
		$this->client->expects( $this->once() )
			->method( 'get' )
			->with( self::LIST_PATH )
			->willReturn(
				[
					'dataSources' => [
						[
							'name'                     => 'accounts/12345/dataSources/new',
							'displayName'              => 'Google for WooCommerce (en/US)',
							'primaryProductDataSource' => [
								'contentLanguage' => 'en',
								'feedLabel'       => 'US',
							],
						],
					],
				]
			);

		$this->service->forget_data_source_for( 'en', 'US' );

		$this->assertSame(
			'accounts/12345/dataSources/new',
			$this->service->ensure_data_source_for( 'en', 'US' )
		);
		$this->assertSame(
			[ 'product|en|US' => 'accounts/12345/dataSources/new' ],
			$stored[ OptionsInterface::MAPI_DATA_SOURCES ]
		);
	}

	public function test_forget_promotion_data_source_for_removes_only_the_promotion_entry() {
		$stored = [
			OptionsInterface::MAPI_DATA_SOURCES => [
				'product|en|US'   => 'accounts/12345/dataSources/100',
				'promotion|en|US' => 'accounts/12345/dataSources/300',
			],
		];
		$this->options->method( 'get' )->willReturnCallback(
			function ( string $key, $fallback = false ) use ( &$stored ) {
				return $stored[ $key ] ?? $fallback;
			}
		);
		$this->options->method( 'update' )->willReturnCallback(
			function ( string $key, $value ) use ( &$stored ) {
				$stored[ $key ] = $value;
				return true;
			}
		);

		$this->service->forget_promotion_data_source_for( 'en', 'US' );

		$this->assertSame(
			[ 'product|en|US' => 'accounts/12345/dataSources/100' ],
			$stored[ OptionsInterface::MAPI_DATA_SOURCES ]
		);
	}

	public function test_is_missing_data_source_failure_only_matches_data_source_404s() {
		$this->assertTrue(
			MapiDataSourcesService::is_missing_data_source_failure(
				new MerchantApiException( 404, [ 'error' => [ 'message' => '[dataSource] Data source with id 999 was not found.' ] ], __METHOD__ )
			)
		);
		// A 404 that is not about the data source, and a non-404, are both left alone.
		$this->assertFalse(
			MapiDataSourcesService::is_missing_data_source_failure(
				new MerchantApiException( 404, [ 'error' => [ 'message' => 'The resource was not found.' ] ], __METHOD__ )
			)
		);
		$this->assertFalse(
			MapiDataSourcesService::is_missing_data_source_failure(
				new MerchantApiException( 500, [ 'error' => [ 'message' => 'data source blew up' ] ], __METHOD__ )
			)
		);
		$this->assertFalse( MapiDataSourcesService::is_missing_data_source_failure( null ) );
	}

	public function test_is_channel_mismatch_failure_only_matches_the_channel_mismatch_400() {
		$this->assertTrue(
			MapiDataSourcesService::is_channel_mismatch_failure(
				new MerchantApiException( 400, [ 'error' => [ 'message' => '[dataSource] The provided data source channel does not match product channel.' ] ], __METHOD__ )
			)
		);
		// A 400 about something else, and a non-400, are both left alone.
		$this->assertFalse(
			MapiDataSourcesService::is_channel_mismatch_failure(
				new MerchantApiException( 400, [ 'error' => [ 'message' => 'Invalid price.' ] ], __METHOD__ )
			)
		);
		$this->assertFalse(
			MapiDataSourcesService::is_channel_mismatch_failure(
				new MerchantApiException( 404, [ 'error' => [ 'message' => 'The provided data source channel does not match product channel.' ] ], __METHOD__ )
			)
		);
		$this->assertFalse( MapiDataSourcesService::is_channel_mismatch_failure( null ) );
	}

	public function test_recreate_data_source_for_bypasses_discovery_and_forces_a_fresh_online_source() {
		// GOOWOO-921: a channel-mismatch 400 means the cached/matched source cannot be trusted,
		// but simply forgetting the cache and re-resolving would deterministically re-list and
		// re-adopt the exact same undetectable-as-local-only source again. recreate_data_source_for()
		// skips discovery entirely and forces a fresh API-created source with explicit online
		// destinations, guaranteeing forward progress.
		$this->options->method( 'get' )->willReturn(
			[ 'product|en|US' => 'accounts/12345/dataSources/500' ]
		);
		$this->client->expects( $this->never() )->method( 'get' );
		$this->client->expects( $this->once() )
			->method( 'post' )
			->with(
				self::LIST_PATH,
				$this->callback(
					function ( $body ) {
						return 'en' === ( $body['primaryProductDataSource']['contentLanguage'] ?? null )
							&& 'US' === ( $body['primaryProductDataSource']['feedLabel'] ?? null )
							&& ! empty( $body['primaryProductDataSource']['destinations'] );
					}
				)
			)
			->willReturn( [ 'name' => 'accounts/12345/dataSources/700' ] );
		$this->options->expects( $this->once() )
			->method( 'update' )
			->with(
				OptionsInterface::MAPI_DATA_SOURCES,
				[ 'product|en|US' => 'accounts/12345/dataSources/700' ]
			);

		$this->assertSame(
			'accounts/12345/dataSources/700',
			$this->service->recreate_data_source_for( 'en', 'US' )
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
							'displayName'              => 'Google for WooCommerce (en/US)',
							'primaryProductDataSource' => [
								'contentLanguage' => 'en',
								'feedLabel'       => 'US',
							],
						],
					],
				]
			);
		// Already the plugin's own source: adopted without a rename.
		$this->client->expects( $this->never() )->method( 'patch' );
		$this->options->expects( $this->once() )
			->method( 'update' )
			->with(
				OptionsInterface::MAPI_DATA_SOURCES,
				[ 'product|en|US' => 'accounts/12345/dataSources/100' ]
			);

		$this->assertSame(
			'accounts/12345/dataSources/100',
			$this->service->ensure_data_source_for( 'en', 'US' )
		);
	}

	public function test_adopts_and_renames_a_foreign_primary_source() {
		// GOOWOO-805: a pre-existing primary source (e.g. the legacy "Content API" one) is adopted
		// and renamed in place, not duplicated into a new source, so its products keep their place
		// and get re-attributed to the Merchant API.
		$this->options->method( 'get' )->willReturn( [] );
		$this->client->method( 'get' )->willReturn(
			[
				'dataSources' => [
					[
						'name'                     => 'accounts/12345/dataSources/100',
						'displayName'              => 'Content API',
						'primaryProductDataSource' => [
							'contentLanguage' => 'en',
							'feedLabel'       => 'US',
						],
					],
				],
			]
		);
		$this->client->expects( $this->never() )->method( 'post' );
		$this->client->expects( $this->once() )
			->method( 'patch' )
			->with(
				self::LIST_PATH . '/100?updateMask=displayName',
				[ 'displayName' => 'Google for WooCommerce (en/US)' ]
			);

		$this->assertSame(
			'accounts/12345/dataSources/100',
			$this->service->ensure_data_source_for( 'en', 'US' )
		);
	}

	public function test_still_returns_the_source_when_the_rename_fails() {
		// GOOWOO-805 follow-up: a failed rename (e.g. transient MAPI error) must not fail the
		// sync — the pre-existing source is still usable, only its label update is skipped.
		$this->options->method( 'get' )->willReturn( [] );
		$this->client->method( 'get' )->willReturn(
			[
				'dataSources' => [
					[
						'name'                     => 'accounts/12345/dataSources/100',
						'displayName'              => 'Content API',
						'primaryProductDataSource' => [
							'contentLanguage' => 'en',
							'feedLabel'       => 'US',
						],
					],
				],
			]
		);
		$this->client->method( 'patch' )->willThrowException(
			new MerchantApiException( 500, [], 'patch' )
		);
		$this->options->expects( $this->once() )
			->method( 'update' )
			->with(
				OptionsInterface::MAPI_DATA_SOURCES,
				[ 'product|en|US' => 'accounts/12345/dataSources/100' ]
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
				[ 'product|fr|CA' => 'accounts/12345/dataSources/777' ]
			);

		$this->assertSame(
			'accounts/12345/dataSources/777',
			$this->service->ensure_data_source_for( 'fr', 'CA' )
		);
	}

	public function test_preserves_other_market_cache_entries_when_resolving_a_new_market() {
		$this->options->method( 'get' )->willReturn(
			[ 'product|en|US' => 'accounts/12345/dataSources/100' ]
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
					'product|en|US' => 'accounts/12345/dataSources/100',
					'product|fr|CA' => 'accounts/12345/dataSources/200',
				]
			);

		$this->assertSame(
			'accounts/12345/dataSources/200',
			$this->service->ensure_data_source_for( 'fr', 'CA' )
		);
	}

	public function test_finds_matching_data_source_on_a_later_page() {
		$this->options->method( 'get' )->willReturn( [] );

		$this->client->expects( $this->exactly( 2 ) )
			->method( 'get' )
			->withConsecutive(
				[ self::LIST_PATH ],
				[ self::LIST_PATH . '?pageToken=page-2' ]
			)
			->willReturnOnConsecutiveCalls(
				[
					'dataSources'   => [
						[
							'name'                     => 'accounts/12345/dataSources/100',
							'primaryProductDataSource' => [
								'contentLanguage' => 'fr',
								'feedLabel'       => 'CA',
							],
						],
					],
					'nextPageToken' => 'page-2',
				],
				[
					'dataSources' => [
						[
							'name'                     => 'accounts/12345/dataSources/200',
							'displayName'              => 'Google for WooCommerce (en/US)',
							'primaryProductDataSource' => [
								'contentLanguage' => 'en',
								'feedLabel'       => 'US',
							],
						],
					],
				]
			);

		// A match on a later page must be reused, never duplicated.
		$this->client->expects( $this->never() )->method( 'post' );

		$this->assertSame(
			'accounts/12345/dataSources/200',
			$this->service->ensure_data_source_for( 'en', 'US' )
		);
	}

	public function test_returns_cached_promotion_data_source_after_verifying_it_exists() {
		$this->options->method( 'get' )->willReturn(
			[
				'promotion|en|US' => 'accounts/12345/dataSources/300',
			]
		);
		// The shared verification runs for promotion sources too.
		$this->client->expects( $this->once() )
			->method( 'get' )
			->with( 'datasources/v1/accounts/12345/dataSources/300' )
			->willReturn( [ 'name' => 'accounts/12345/dataSources/300' ] );
		$this->client->expects( $this->never() )->method( 'post' );

		$this->assertSame(
			'accounts/12345/dataSources/300',
			$this->service->ensure_promotion_data_source_for( 'en', 'US' )
		);
	}

	public function test_reuses_existing_promotion_data_source_matching_language_and_country() {
		$this->options->method( 'get' )->willReturn( [] );
		$this->client->expects( $this->once() )
			->method( 'get' )
			->with( self::LIST_PATH )
			->willReturn(
				[
					'dataSources' => [
						[
							'name'                => 'accounts/12345/dataSources/300',
							'displayName'         => 'Google for WooCommerce (en/US)',
							'promotionDataSource' => [
								'contentLanguage' => 'en',
								'targetCountry'   => 'US',
							],
						],
					],
				]
			);
		// Already the plugin's own source: adopted without a rename.
		$this->client->expects( $this->never() )->method( 'patch' );
		$this->options->expects( $this->once() )
			->method( 'update' )
			->with(
				OptionsInterface::MAPI_DATA_SOURCES,
				[ 'promotion|en|US' => 'accounts/12345/dataSources/300' ]
			);

		$this->assertSame(
			'accounts/12345/dataSources/300',
			$this->service->ensure_promotion_data_source_for( 'en', 'US' )
		);
	}

	public function test_adopts_and_renames_a_foreign_promotion_source() {
		// GOOWOO-805: a pre-existing promotion source that is not the plugin's own is adopted and
		// renamed in place, mirroring the product-source behavior.
		$this->options->method( 'get' )->willReturn( [] );
		$this->client->method( 'get' )->willReturn(
			[
				'dataSources' => [
					[
						'name'                => 'accounts/12345/dataSources/300',
						'displayName'         => 'Content API promotions',
						'promotionDataSource' => [
							'contentLanguage' => 'en',
							'targetCountry'   => 'US',
						],
					],
				],
			]
		);
		$this->client->expects( $this->never() )->method( 'post' );
		$this->client->expects( $this->once() )
			->method( 'patch' )
			->with(
				self::LIST_PATH . '/300?updateMask=displayName',
				[ 'displayName' => 'Google for WooCommerce (en/US)' ]
			);

		$this->assertSame(
			'accounts/12345/dataSources/300',
			$this->service->ensure_promotion_data_source_for( 'en', 'US' )
		);
	}

	public function test_promotion_lookup_ignores_product_data_sources() {
		$this->options->method( 'get' )->willReturn( [] );
		$this->client->method( 'get' )->willReturn(
			[
				'dataSources' => [
					[
						'name'                     => 'accounts/12345/dataSources/100',
						'primaryProductDataSource' => [
							'contentLanguage' => 'en',
							'feedLabel'       => 'US',
						],
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
							&& 'en' === $body['promotionDataSource']['contentLanguage']
							&& 'US' === $body['promotionDataSource']['targetCountry']
							&& ! isset( $body['primaryProductDataSource'] );
					}
				)
			)
			->willReturn( [ 'name' => 'accounts/12345/dataSources/888' ] );

		$this->assertSame(
			'accounts/12345/dataSources/888',
			$this->service->ensure_promotion_data_source_for( 'en', 'US' )
		);
	}

	public function test_creates_new_promotion_data_source_when_none_match() {
		$this->options->method( 'get' )->willReturn( [] );
		$this->client->method( 'get' )->willReturn( [ 'dataSources' => [] ] );
		$this->client->expects( $this->once() )
			->method( 'post' )
			->willReturn( [ 'name' => 'accounts/12345/dataSources/888' ] );
		$this->options->expects( $this->once() )
			->method( 'update' )
			->with(
				OptionsInterface::MAPI_DATA_SOURCES,
				[ 'promotion|fr|CA' => 'accounts/12345/dataSources/888' ]
			);

		$this->assertSame(
			'accounts/12345/dataSources/888',
			$this->service->ensure_promotion_data_source_for( 'fr', 'CA' )
		);
	}

	public function test_drops_cached_promotion_data_source_when_its_verification_404s() {
		$this->options->method( 'get' )->willReturn(
			[
				'promotion|en|US' => 'accounts/12345/dataSources/300',
			]
		);
		// The shared verification runs for promotion sources too, and a 404 falls through to a list.
		$this->client->expects( $this->exactly( 2 ) )
			->method( 'get' )
			->withConsecutive(
				[ 'datasources/v1/accounts/12345/dataSources/300' ],
				[ self::LIST_PATH ]
			)
			->willReturnOnConsecutiveCalls(
				$this->throwException( new MerchantApiException( 404, [ 'error' => [ 'message' => 'Promotion data source 300 was not found.' ] ], 'get' ) ),
				[ 'dataSources' => [] ]
			);
		$this->client->expects( $this->once() )
			->method( 'post' )
			->willReturn( [ 'name' => 'accounts/12345/dataSources/777' ] );
		// The stale entry is cleared, then the re-resolved name is written back.
		$this->options->expects( $this->exactly( 2 ) )
			->method( 'update' )
			->withConsecutive(
				[ OptionsInterface::MAPI_DATA_SOURCES, [] ],
				[ OptionsInterface::MAPI_DATA_SOURCES, [ 'promotion|en|US' => 'accounts/12345/dataSources/777' ] ]
			);

		$this->assertSame(
			'accounts/12345/dataSources/777',
			$this->service->ensure_promotion_data_source_for( 'en', 'US' )
		);
	}

	public function test_ignores_file_input_data_sources_when_matching_product_sources() {
		// GooWoo 921: a pre-existing legacy file-feed data source matching the
		// (language, country) pair is skipped in favor of a new API-created source
		// rather than being adopted, since MAPI item inserts are rejected with a 400
		// ("API data sources cannot have a fileInput field set") on a file-input source.
		$this->options->method( 'get' )->willReturn( [] );
		$this->client->expects( $this->once() )
			->method( 'get' )
			->with( self::LIST_PATH )
			->willReturn(
				[
					'dataSources' => [
						[
							'name'                     => 'accounts/12345/dataSources/500',
							'displayName'              => 'Legacy file feed (en/US)',
							'fileInput'                => [ 'latestUploadedSource' => [] ] ,
							'primaryProductDataSource' => [
								'contentLanguage' => 'en',
								'feedLabel'       => 'US',
							],
						],
					],
				]
			);
		$this->client->expects( $this->once() )
			->method( 'post' )
			->willReturn( [ 'name' => 'accounts/12345/dataSources/600' ] );
		$this->options->expects( $this->once() )
			->method( 'update' )
			->with(
				OptionsInterface::MAPI_DATA_SOURCES,
				[ 'product|en|US' => 'accounts/12345/dataSources/600' ]
			);

		$this->assertSame(
			'accounts/12345/dataSources/600',
			$this->service->ensure_data_source_for( 'en', 'US' )
		);
	}

	public function test_drops_cached_file_input_product_data_source_and_re_resolves() {
		// GooWoo 921 (recovery side): a cache entry pointing to a file-input source
		// — the bug scenario, where a pre-store-upgrade file feed had been cached as the
		// plugin's own product source — is replaced with a fresh API-created source
		// rather than being trusted, so the first write no longer 400s.
		$this->options->method( 'get' )->willReturn(
			[
				'product|en|US' => 'accounts/12345/dataSources/500',
			]
		);
		// The cache-hit check queries the stale entry by name; the source is still on the
		// account with fileInput set, so the entry is evicted and a new API source is created
		// instead of adopting the file source (which would 400 on the next insert).
		$this->client->expects( $this->exactly( 2 ) )
			->method( 'get' )
			->withConsecutive(
				[ 'datasources/v1/accounts/12345/dataSources/500' ],
				[ self::LIST_PATH ]
			)
			->willReturnOnConsecutiveCalls(
				[
					'name'                     => 'accounts/12345/dataSources/500',
					'displayName'              => 'Legacy file feed (en/US)',
					'fileInput'                => [
						'latestUploadedSource' => [],
					],
					'primaryProductDataSource' => [
						'contentLanguage' => 'en',
						'feedLabel'       => 'US',
					],
				],
				[
					'dataSources' => [
						[
							'name'                     => 'accounts/12345/dataSources/500',
							'displayName'              => 'Legacy file feed (en/US)',
							'fileInput'                => [
								'latestUploadedSource' => [],
							],
							'primaryProductDataSource' => [
								'contentLanguage' => 'en',
								'feedLabel'       => 'US',
							],
						],
					],
				]
			);
		$this->client->expects( $this->once() )
			->method( 'post' )
			->willReturn( [ 'name' => 'accounts/12345/dataSources/600' ] );
		// The stale entry is cleared, then the re-resolved name is written back.
		$this->options->expects( $this->exactly( 2 ) )
			->method( 'update' )
			->withConsecutive(
				[ OptionsInterface::MAPI_DATA_SOURCES, [] ],
				[ OptionsInterface::MAPI_DATA_SOURCES, [ 'product|en|US' => 'accounts/12345/dataSources/600' ] ]
			);

		$this->assertSame(
			'accounts/12345/dataSources/600',
			$this->service->ensure_data_source_for( 'en', 'US' )
		);
	}

	public function test_ignores_file_input_data_sources_when_matching_promotion_sources() {
		// GooWoo 921: a legacy file-feed data source matching the promotion
		// (language, country) pair must not be adopted, since promotion inserts are rejected
		// with a 400 ("API data sources cannot have a fileInput field set") on a file-input source.
		$this->options->method( 'get' )->willReturn( [] );
		$this->client->expects( $this->once() )
			->method( 'get' )
			->with( self::LIST_PATH )
			->willReturn(
				[
					'dataSources' => [
						[
							'name'                => 'accounts/12345/dataSources/550',
							'displayName'         => 'Legacy promo file feed (en/US)',
							'fileInput'           => [
								'latestUploadedSource' => [],
							],
							'promotionDataSource' => [
								'contentLanguage' => 'en',
								'targetCountry'   => 'US',
							],
						],
					],
				]
			);
		$this->client->expects( $this->once() )
			->method( 'post' )
			->willReturn( [ 'name' => 'accounts/12345/dataSources/650' ] );
		$this->options->expects( $this->once() )
			->method( 'update' )
			->with(
				OptionsInterface::MAPI_DATA_SOURCES,
				[ 'promotion|en|US' => 'accounts/12345/dataSources/650' ]
			);

		$this->assertSame(
			'accounts/12345/dataSources/650',
			$this->service->ensure_promotion_data_source_for( 'en', 'US' )
		);
	}

	public function test_drops_cached_file_input_promotion_data_source_and_re_resolves() {
		// GooWoo 921 (recovery side): for the promotion path, a cache entry pointing
		// to a file-input source is replaced with a fresh API-created promotion source.
		$this->options->method( 'get' )->willReturn(
			[
				'promotion|en|US' => 'accounts/12345/dataSources/550',
			]
		);
		// The cache-hit check queries the stale entry by name; the source is still on the
		// account with fileInput set, so the entry is evicted and a new API source is created
		// instead of adopting the file source.
		$this->client->expects( $this->exactly( 2 ) )
			->method( 'get' )
			->withConsecutive(
				[ 'datasources/v1/accounts/12345/dataSources/550' ],
				[ self::LIST_PATH ]
			)
			->willReturnOnConsecutiveCalls(
				[
					'name'                => 'accounts/12345/dataSources/550',
					'displayName'         => 'Legacy promo file feed (en/US)',
					'fileInput'           => [
						'latestUploadedSource' => [],
					],
					'promotionDataSource' => [
						'contentLanguage' => 'en',
						'targetCountry'   => 'US',
					],
				],
				[
					'dataSources' => [
						[
							'name'                => 'accounts/12345/dataSources/550',
							'displayName'         => 'Legacy promo file feed (en/US)',
							'fileInput'           => [
								'latestUploadedSource' => [],
							],
							'promotionDataSource' => [
								'contentLanguage' => 'en',
								'targetCountry'   => 'US',
							],
						],
					],
				]
			);
		$this->client->expects( $this->once() )
			->method( 'post' )
			->willReturn( [ 'name' => 'accounts/12345/dataSources/650' ] );
		// The stale entry is cleared, then the re-resolved name is written back.
		$this->options->expects( $this->exactly( 2 ) )
			->method( 'update' )
			->withConsecutive(
				[ OptionsInterface::MAPI_DATA_SOURCES, [] ],
				[ OptionsInterface::MAPI_DATA_SOURCES, [ 'promotion|en|US' => 'accounts/12345/dataSources/650' ] ]
			);

		$this->assertSame(
			'accounts/12345/dataSources/650',
			$this->service->ensure_promotion_data_source_for( 'en', 'US' )
		);
	}

	public function test_keeps_cached_source_which_has_no_file_input() {
		// A cached source without fileInput is trusted as-is: the file-input skip must not
		// regress the standard cache-hit path (no rename, no re-creation — it was already
		// settled when it was first discovered/adopted).
		$this->options->method( 'get' )->willReturn(
			[ 'product|en|US' => 'accounts/12345/dataSources/900' ]
		);
		$this->client->expects( $this->once() )
			->method( 'get' )
			->with( 'datasources/v1/accounts/12345/dataSources/900' )
			->willReturn(
				[
					'name'                     => 'accounts/12345/dataSources/900',
					'displayName'              => 'Google for WooCommerce (en/US)',
					'primaryProductDataSource' => [
						'contentLanguage' => 'en',
						'feedLabel'       => 'US',
					],
				]
			);
		$this->client->expects( $this->never() )->method( 'patch' );
		$this->client->expects( $this->never() )->method( 'post' );

		$this->assertSame(
			'accounts/12345/dataSources/900',
			$this->service->ensure_data_source_for( 'en', 'US' )
		);
	}

	public function test_ignores_local_channel_data_sources_when_matching_product_sources() {
		// GOOWOO-921: a pre-existing data source whose destinations are all local (e.g. Google
		// inferred LOCAL_PRODUCTS at creation because the account has local inventory) is skipped
		// in favor of a new API-created source, since MAPI item inserts into it are rejected with
		// a 400 ("The provided data source channel does not match product channel").
		$this->options->method( 'get' )->willReturn( [] );
		$this->client->expects( $this->once() )
			->method( 'get' )
			->with( self::LIST_PATH )
			->willReturn(
				[
					'dataSources' => [
						[
							'name'                     => 'accounts/12345/dataSources/500',
							'displayName'              => 'Google for WooCommerce (en/US)',
							'primaryProductDataSource' => [
								'contentLanguage' => 'en',
								'feedLabel'       => 'US',
								'destinations'    => [
									[
										'destination' => 'LOCAL_INVENTORY_ADS',
										'state'       => 'ENABLED',
									],
									[
										'destination' => 'FREE_LOCAL_LISTINGS',
										'state'       => 'ENABLED',
									],
								],
							],
						],
					],
				]
			);
		$this->client->expects( $this->once() )
			->method( 'post' )
			->willReturn( [ 'name' => 'accounts/12345/dataSources/600' ] );
		$this->options->expects( $this->once() )
			->method( 'update' )
			->with(
				OptionsInterface::MAPI_DATA_SOURCES,
				[ 'product|en|US' => 'accounts/12345/dataSources/600' ]
			);

		$this->assertSame(
			'accounts/12345/dataSources/600',
			$this->service->ensure_data_source_for( 'en', 'US' )
		);
	}

	public function test_ignores_a_local_source_carrying_an_unrecognised_destination() {
		// GOOWOO-921 hardening: local-only detection takes an enabled destination as proof of
		// online capability only when it is one Google is known to serve online products
		// through. A destination outside both lists (here a hypothetical future one) is no
		// evidence either way, so a source that is otherwise all-local is still skipped.
		// Reading "not local" as "online" would re-adopt the source and kill sync again.
		$this->options->method( 'get' )->willReturn( [] );
		$this->client->expects( $this->once() )
			->method( 'get' )
			->with( self::LIST_PATH )
			->willReturn(
				[
					'dataSources' => [
						[
							'name'                     => 'accounts/12345/dataSources/500',
							'displayName'              => 'Google for WooCommerce (en/US)',
							'primaryProductDataSource' => [
								'contentLanguage' => 'en',
								'feedLabel'       => 'US',
								'destinations'    => [
									[
										'destination' => 'LOCAL_INVENTORY_ADS',
										'state'       => 'ENABLED',
									],
									[
										'destination' => 'FREE_LOCAL_LISTINGS',
										'state'       => 'ENABLED',
									],
									[
										'destination' => 'SOME_FUTURE_SURFACE',
										'state'       => 'ENABLED',
									],
								],
							],
						],
					],
				]
			);
		$this->client->expects( $this->once() )
			->method( 'post' )
			->willReturn( [ 'name' => 'accounts/12345/dataSources/600' ] );
		$this->options->expects( $this->once() )
			->method( 'update' )
			->with(
				OptionsInterface::MAPI_DATA_SOURCES,
				[ 'product|en|US' => 'accounts/12345/dataSources/600' ]
			);

		$this->assertSame(
			'accounts/12345/dataSources/600',
			$this->service->ensure_data_source_for( 'en', 'US' )
		);
	}

	public function test_adopts_a_source_whose_online_destination_is_one_the_plugin_never_requests() {
		// The flip side: a merchant-configured source may reach online shoppers through a
		// destination the plugin would never ask for when creating one of its own. That is still
		// positive evidence of online capability, so the source is adopted rather than skipped.
		$this->options->method( 'get' )->willReturn( [] );
		$this->client->expects( $this->once() )
			->method( 'get' )
			->with( self::LIST_PATH )
			->willReturn(
				[
					'dataSources' => [
						[
							'name'                     => 'accounts/12345/dataSources/500',
							'displayName'              => 'Google for WooCommerce (en/US)',
							'primaryProductDataSource' => [
								'contentLanguage' => 'en',
								'feedLabel'       => 'US',
								'destinations'    => [
									[
										'destination' => 'FREE_LOCAL_LISTINGS',
										'state'       => 'ENABLED',
									],
									[
										'destination' => 'YOUTUBE_SHOPPING',
										'state'       => 'ENABLED',
									],
								],
							],
						],
					],
				]
			);
		$this->client->expects( $this->never() )->method( 'post' );
		$this->options->expects( $this->once() )
			->method( 'update' )
			->with(
				OptionsInterface::MAPI_DATA_SOURCES,
				[ 'product|en|US' => 'accounts/12345/dataSources/500' ]
			);

		$this->assertSame(
			'accounts/12345/dataSources/500',
			$this->service->ensure_data_source_for( 'en', 'US' )
		);
	}

	public function test_ignores_legacy_local_data_sources_when_matching_product_sources() {
		// GOOWOO-921: a data source flagged legacyLocal (Google's own "products of this data
		// source are only targeting local destinations" marker) is skipped even without an
		// explicit destinations list, since it is unusable for online item inserts either way.
		$this->options->method( 'get' )->willReturn( [] );
		$this->client->expects( $this->once() )
			->method( 'get' )
			->with( self::LIST_PATH )
			->willReturn(
				[
					'dataSources' => [
						[
							'name'                     => 'accounts/12345/dataSources/510',
							'displayName'              => 'Local Feed Partnership',
							'primaryProductDataSource' => [
								'contentLanguage' => 'en',
								'feedLabel'       => 'US',
								'legacyLocal'     => true,
							],
						],
					],
				]
			);
		$this->client->expects( $this->once() )
			->method( 'post' )
			->willReturn( [ 'name' => 'accounts/12345/dataSources/610' ] );

		$this->assertSame(
			'accounts/12345/dataSources/610',
			$this->service->ensure_data_source_for( 'en', 'US' )
		);
	}

	public function test_ignores_a_source_that_is_both_file_input_and_local_only() {
		// is_unusable_data_source() composes the fileInput check (built for GOOWOO-921's first
		// variant) and the local-only check (built for its second) with OR. This locks in that
		// the composition still skips a source exhibiting both properties at once, so a future
		// change to either sub-check can't silently stop covering this combination.
		$this->options->method( 'get' )->willReturn( [] );
		$this->client->expects( $this->once() )
			->method( 'get' )
			->with( self::LIST_PATH )
			->willReturn(
				[
					'dataSources' => [
						[
							'name'                     => 'accounts/12345/dataSources/520',
							'displayName'              => 'Legacy local file feed (en/US)',
							'fileInput'                => [ 'latestUploadedSource' => [] ],
							'primaryProductDataSource' => [
								'contentLanguage' => 'en',
								'feedLabel'       => 'US',
								'legacyLocal'     => true,
							],
						],
					],
				]
			);
		$this->client->expects( $this->once() )
			->method( 'post' )
			->willReturn( [ 'name' => 'accounts/12345/dataSources/620' ] );

		$this->assertSame(
			'accounts/12345/dataSources/620',
			$this->service->ensure_data_source_for( 'en', 'US' )
		);
	}

	public function test_drops_cached_local_channel_product_data_source_and_re_resolves() {
		// GOOWOO-921 (recovery side): a cache entry pointing to a data source that turns out to
		// be local-only is replaced with a fresh API-created source rather than trusted, so the
		// first write no longer 400s on a channel mismatch.
		$this->options->method( 'get' )->willReturn(
			[
				'product|en|US' => 'accounts/12345/dataSources/500',
			]
		);
		$this->client->expects( $this->exactly( 2 ) )
			->method( 'get' )
			->withConsecutive(
				[ 'datasources/v1/accounts/12345/dataSources/500' ],
				[ self::LIST_PATH ]
			)
			->willReturnOnConsecutiveCalls(
				[
					'name'                     => 'accounts/12345/dataSources/500',
					'displayName'              => 'Google for WooCommerce (en/US)',
					'primaryProductDataSource' => [
						'contentLanguage' => 'en',
						'feedLabel'       => 'US',
						'destinations'    => [
							[
								'destination' => 'LOCAL_INVENTORY_ADS',
								'state'       => 'ENABLED',
							],
						],
					],
				],
				[
					'dataSources' => [
						[
							'name'                     => 'accounts/12345/dataSources/500',
							'displayName'              => 'Google for WooCommerce (en/US)',
							'primaryProductDataSource' => [
								'contentLanguage' => 'en',
								'feedLabel'       => 'US',
								'destinations'    => [
									[
										'destination' => 'LOCAL_INVENTORY_ADS',
										'state'       => 'ENABLED',
									],
								],
							],
						],
					],
				]
			);
		$this->client->expects( $this->once() )
			->method( 'post' )
			->willReturn( [ 'name' => 'accounts/12345/dataSources/600' ] );
		$this->options->expects( $this->exactly( 2 ) )
			->method( 'update' )
			->withConsecutive(
				[ OptionsInterface::MAPI_DATA_SOURCES, [] ],
				[ OptionsInterface::MAPI_DATA_SOURCES, [ 'product|en|US' => 'accounts/12345/dataSources/600' ] ]
			);

		$this->assertSame(
			'accounts/12345/dataSources/600',
			$this->service->ensure_data_source_for( 'en', 'US' )
		);
	}

	public function test_creates_new_product_data_source_with_online_destinations_only() {
		// GOOWOO-921: without an explicit destinations list, Google may infer local-only
		// destinations at creation time on an account with local inventory. Explicitly requesting
		// only the online destinations prevents that inheritance.
		$this->options->method( 'get' )->willReturn( [] );
		$this->client->method( 'get' )->willReturn( [ 'dataSources' => [] ] );
		$this->client->expects( $this->once() )
			->method( 'post' )
			->with(
				self::LIST_PATH,
				$this->callback(
					function ( $body ) {
						return [
							[
								'destination' => 'SHOPPING_ADS',
								'state'       => 'ENABLED',
							],
							[
								'destination' => 'FREE_LISTINGS',
								'state'       => 'ENABLED',
							],
						] === ( $body['primaryProductDataSource']['destinations'] ?? null );
					}
				)
			)
			->willReturn( [ 'name' => 'accounts/12345/dataSources/700' ] );

		$this->assertSame(
			'accounts/12345/dataSources/700',
			$this->service->ensure_data_source_for( 'en', 'US' )
		);
	}

	public function test_creates_new_promotion_data_source_without_destinations_field() {
		// PromotionDataSource has no destinations field in the Merchant API; sending one would be
		// an invalid request, so promotion creation must never include it.
		$this->options->method( 'get' )->willReturn( [] );
		$this->client->method( 'get' )->willReturn( [ 'dataSources' => [] ] );
		$this->client->expects( $this->once() )
			->method( 'post' )
			->with(
				self::LIST_PATH,
				$this->callback(
					function ( $body ) {
						return ! isset( $body['promotionDataSource']['destinations'] );
					}
				)
			)
			->willReturn( [ 'name' => 'accounts/12345/dataSources/710' ] );

		$this->assertSame(
			'accounts/12345/dataSources/710',
			$this->service->ensure_promotion_data_source_for( 'en', 'US' )
		);
	}

	public function test_keeps_cached_name_when_verification_returns_a_non_404_error() {
		// The "only a 404 proves absence" contract on verify_cached_source() must hold even with
		// the composed unusable-source check now sitting next to the exception handling: a
		// transient error (auth, 5xx) must not evict a cached name that might otherwise be usable.
		$this->options->method( 'get' )->willReturn(
			[
				'product|en|US' => 'accounts/12345/dataSources/999',
			]
		);
		$this->client->expects( $this->once() )
			->method( 'get' )
			->with( 'datasources/v1/accounts/12345/dataSources/999' )
			->willThrowException( new MerchantApiException( 500, [], 'get' ) );
		$this->client->expects( $this->never() )->method( 'post' );
		$this->options->expects( $this->never() )->method( 'update' );

		$this->assertSame(
			'accounts/12345/dataSources/999',
			$this->service->ensure_data_source_for( 'en', 'US' )
		);
	}

	public function test_promotion_and_product_caches_do_not_collide() {
		// A product data source is cached under 'product|en|US'; resolving a promotion for the
		// same language/country must use a distinct key and never return the product source.
		$this->options->method( 'get' )->willReturn(
			[ 'product|en|US' => 'accounts/12345/dataSources/100' ]
		);
		$this->client->method( 'get' )->willReturn( [ 'dataSources' => [] ] );
		$this->client->method( 'post' )->willReturn(
			[ 'name' => 'accounts/12345/dataSources/300' ]
		);
		$this->options->expects( $this->once() )
			->method( 'update' )
			->with(
				OptionsInterface::MAPI_DATA_SOURCES,
				[
					'product|en|US'   => 'accounts/12345/dataSources/100',
					'promotion|en|US' => 'accounts/12345/dataSources/300',
				]
			);

		$this->assertSame(
			'accounts/12345/dataSources/300',
			$this->service->ensure_promotion_data_source_for( 'en', 'US' )
		);
	}
}
