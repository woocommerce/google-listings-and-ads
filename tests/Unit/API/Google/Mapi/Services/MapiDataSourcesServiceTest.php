<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google\Mapi\Services;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\MerchantApiClient;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\MerchantApiException;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services\MapiDataSourcesService;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\GoogleHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\UnsupportedContentLanguageException;

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

	/** @var MockObject|GoogleHelper */
	protected $google_helper;

	/** @var MockObject|OptionsInterface */
	protected $options;

	/** @var MapiDataSourcesService */
	protected $service;

	public function setUp(): void {
		parent::setUp();

		$this->client        = $this->createMock( MerchantApiClient::class );
		$this->google_helper = $this->createMock( GoogleHelper::class );
		$this->google_helper->method( 'get_mc_supported_languages' )->willReturn(
			[
				'en' => 'en',
				'fr' => 'fr',
				'de' => 'de',
			]
		);
		$this->options = $this->createMock( OptionsInterface::class );
		$this->options->method( 'get_merchant_id' )->willReturn( self::MERCHANT_ID );

		$this->service = new MapiDataSourcesService( $this->client, $this->google_helper );
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
	public function test_rejects_an_unsupported_content_language_without_calling_the_api() {
		$this->options->method( 'get' )->willReturn( [] );
		$this->client->expects( $this->never() )->method( 'get' );
		$this->client->expects( $this->never() )->method( 'post' );
		$this->options->expects( $this->never() )->method( 'update' );

		$this->expectException( UnsupportedContentLanguageException::class );

		$this->service->ensure_data_source_for( 'sr', 'DZ' );
	}

	public function test_rejects_an_unsupported_content_language_for_promotions_too() {
		$this->options->method( 'get' )->willReturn( [] );
		$this->client->expects( $this->never() )->method( 'post' );

		$this->expectException( UnsupportedContentLanguageException::class );

		$this->service->ensure_promotion_data_source_for( 'ka', 'GE' );
	}

	public function test_an_already_resolved_language_keeps_working_even_if_absent_from_the_local_list() {
		$this->options->method( 'get' )->willReturn(
			[ 'product|sr|DZ' => 'accounts/12345/dataSources/999' ]
		);
		// The cached name is still verified, but the language is never re-checked against the list.
		$this->client->expects( $this->once() )
			->method( 'get' )
			->with( 'datasources/v1/accounts/12345/dataSources/999' )
			->willReturn( [ 'name' => 'accounts/12345/dataSources/999' ] );
		$this->client->expects( $this->never() )->method( 'post' );

		$this->assertSame(
			'accounts/12345/dataSources/999',
			$this->service->ensure_data_source_for( 'sr', 'DZ' )
		);
	}
}
