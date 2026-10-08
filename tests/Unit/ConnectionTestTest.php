<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\MapiPaths;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\MerchantApiClient;
use Automattic\WooCommerce\GoogleListingsAndAds\ConnectionTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionMethod;

defined( 'ABSPATH' ) || exit;

/**
 * Class ConnectionTestTest
 *
 * Covers the MAPI data-source helpers added to the Connection Test debug page.
 * The class is a WP-admin action dispatcher, so its public register()/handle_actions()
 * surface is driven by superglobals and WordPress core functions; these tests target
 * the private, side-effect-free helpers instead, via Reflection.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit
 */
class ConnectionTestTest extends UnitTest {

	/** @var ConnectionTest */
	protected $page;

	public function setUp(): void {
		parent::setUp();

		$this->page = new ConnectionTest();
	}

	/**
	 * Invoke a private method via Reflection.
	 *
	 * @param string $method Method name.
	 * @param array  $args   Arguments to pass.
	 *
	 * @return mixed
	 */
	protected function invoke( string $method, array $args ) {
		$reflection = new ReflectionMethod( ConnectionTest::class, $method );

		// setAccessible() is required on PHP < 8.1 and a deprecated no-op from 8.1 onward.
		if ( PHP_VERSION_ID < 80100 ) {
			$reflection->setAccessible( true );
		}

		return $reflection->invokeArgs( $this->page, $args );
	}

	public function test_describe_mapi_data_source_detects_product_source_without_file_input() {
		$described = $this->invoke(
			'describe_mapi_data_source',
			[
				[
					'name'                     => 'accounts/123/dataSources/800',
					'displayName'              => 'Products US',
					'primaryProductDataSource' => [ 'feedLabel' => 'US' ],
				],
			]
		);

		$this->assertSame(
			[
				'id'             => '800',
				'kind'           => 'API (product)',
				'display_name'   => 'Products US',
				'target_country' => '',
				'feed_label'     => 'US',
				'has_file_input' => false,
			],
			$described
		);
	}

	public function test_describe_mapi_data_source_detects_promotion_source() {
		$described = $this->invoke(
			'describe_mapi_data_source',
			[
				[
					'name'                => 'accounts/123/dataSources/801',
					'displayName'         => 'Promotions US',
					'promotionDataSource' => [ 'targetCountry' => 'US' ],
				],
			]
		);

		$this->assertSame( 'API (promotion)', $described['kind'] );
		$this->assertSame( 'US', $described['target_country'] );
		$this->assertFalse( $described['has_file_input'] );
	}

	public function test_describe_mapi_data_source_detects_file_input_source_regardless_of_other_fields() {
		$described = $this->invoke(
			'describe_mapi_data_source',
			[
				[
					'name'                     => 'accounts/123/dataSources/802',
					'primaryProductDataSource' => [ 'feedLabel' => 'US' ],
					'fileInput'                => [ 'fetchUri' => 'https://example.com/feed.xml' ],
				],
			]
		);

		$this->assertSame( 'file', $described['kind'] );
		$this->assertTrue( $described['has_file_input'] );
	}

	public function test_describe_mapi_data_source_treats_empty_file_input_as_clean() {
		$described = $this->invoke(
			'describe_mapi_data_source',
			[
				[
					'name'      => 'accounts/123/dataSources/803',
					'fileInput' => [],
				],
			]
		);

		$this->assertFalse( $described['has_file_input'] );
		$this->assertSame( 'other', $described['kind'] );
	}

	public function test_describe_mapi_data_source_defaults_missing_display_name_to_empty_string() {
		$described = $this->invoke(
			'describe_mapi_data_source',
			[ [ 'name' => 'accounts/123/dataSources/804' ] ]
		);

		$this->assertSame( '', $described['display_name'] );
		$this->assertSame( 'other', $described['kind'] );
	}

	public function test_resolve_mapi_data_source_matches_full_resource_name() {
		$inventory = [
			'accounts/123/dataSources/800' => [ 'id' => '800' ],
		];

		$this->assertSame(
			'accounts/123/dataSources/800',
			$this->invoke( 'resolve_mapi_data_source', [ $inventory, 'accounts/123/dataSources/800' ] )
		);
	}

	public function test_resolve_mapi_data_source_strips_api_version_prefix_and_leading_slash() {
		$inventory = [
			'accounts/123/dataSources/800' => [ 'id' => '800' ],
		];

		$reference = '/' . MapiPaths::DATASOURCES . '/accounts/123/dataSources/800';

		$this->assertSame(
			'accounts/123/dataSources/800',
			$this->invoke( 'resolve_mapi_data_source', [ $inventory, $reference ] )
		);
	}

	public function test_resolve_mapi_data_source_matches_by_numeric_id() {
		$inventory = [
			'accounts/123/dataSources/800' => [ 'id' => '800' ],
			'accounts/123/dataSources/801' => [ 'id' => '801' ],
		];

		$this->assertSame(
			'accounts/123/dataSources/801',
			$this->invoke( 'resolve_mapi_data_source', [ $inventory, '801' ] )
		);
	}

	public function test_resolve_mapi_data_source_returns_null_for_empty_reference() {
		$this->assertNull( $this->invoke( 'resolve_mapi_data_source', [ [], '' ] ) );
	}

	public function test_resolve_mapi_data_source_returns_null_when_nothing_matches() {
		$inventory = [ 'accounts/123/dataSources/800' => [ 'id' => '800' ] ];

		$this->assertNull( $this->invoke( 'resolve_mapi_data_source', [ $inventory, '999' ] ) );
	}

	public function test_list_mapi_data_sources_skips_entries_with_no_name_and_follows_pagination() {
		/** @var MockObject|MerchantApiClient $client */
		$client = $this->createMock( MerchantApiClient::class );

		$client->expects( $this->exactly( 2 ) )
			->method( 'get' )
			->willReturnOnConsecutiveCalls(
				[
					'dataSources'   => [
						[ 'name' => 'accounts/123/dataSources/800' ],
						[ 'name' => '' ], // No name: must be skipped.
					],
					'nextPageToken' => 'page-2',
				],
				[
					'dataSources' => [
						[ 'name' => 'accounts/123/dataSources/801' ],
					],
				]
			);

		$inventory = $this->invoke( 'list_mapi_data_sources', [ $client, 123 ] );

		$this->assertSame(
			[ 'accounts/123/dataSources/800', 'accounts/123/dataSources/801' ],
			array_keys( $inventory )
		);
	}

	public function test_remove_mapi_data_source_from_cache_clears_matching_entry() {
		/** @var MockObject|OptionsInterface $options */
		$options = $this->createMock( OptionsInterface::class );
		$options->method( 'get' )
			->with( OptionsInterface::MAPI_DATA_SOURCES, [] )
			->willReturn(
				[
					'en|US' => 'accounts/123/dataSources/800',
					'fr|FR' => 'accounts/123/dataSources/801',
				]
			);

		$options->expects( $this->once() )
			->method( 'update' )
			->with( OptionsInterface::MAPI_DATA_SOURCES, [ 'fr|FR' => 'accounts/123/dataSources/801' ] );

		$summary = $this->invoke(
			'remove_mapi_data_source_from_cache',
			[ $options, 'accounts/123/dataSources/800' ]
		);

		$this->assertSame( 'Resolved-data-source cache cleaned.', $summary );
	}

	public function test_remove_mapi_data_source_from_cache_leaves_cache_untouched_when_nothing_matches() {
		/** @var MockObject|OptionsInterface $options */
		$options = $this->createMock( OptionsInterface::class );
		$options->method( 'get' )
			->with( OptionsInterface::MAPI_DATA_SOURCES, [] )
			->willReturn( [ 'en|US' => 'accounts/123/dataSources/800' ] );

		$options->expects( $this->never() )->method( 'update' );

		$summary = $this->invoke(
			'remove_mapi_data_source_from_cache',
			[ $options, 'accounts/123/dataSources/999' ]
		);

		$this->assertSame( 'No resolved-data-source cache entry pointed at this data source.', $summary );
	}
}
