<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\SearchConsole;

use Automattic\WooCommerce\GoogleListingsAndAds\API\SearchConsole\SearchConsoleApiClient;
use Automattic\WooCommerce\GoogleListingsAndAds\API\SearchConsole\SitesService;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class SitesServiceTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\SearchConsole
 */
class SitesServiceTest extends UnitTest {

	/** @var MockObject|SearchConsoleApiClient $client */
	protected $client;

	/** @var SitesService $service */
	protected $service;

	protected const STORE_URL = 'https://example.com';

	public function setUp(): void {
		parent::setUp();

		$this->client  = $this->createMock( SearchConsoleApiClient::class );
		$this->service = new SitesService( $this->client );
	}

	public function test_list_sites_returns_site_entries() {
		$this->client->method( 'get' )->with( 'sites' )->willReturn(
			[
				'siteEntry' => [
					[
						'siteUrl'         => 'https://example.com/',
						'permissionLevel' => 'siteOwner',
					],
				],
			]
		);

		$this->assertEquals(
			[
				[
					'siteUrl'         => 'https://example.com/',
					'permissionLevel' => 'siteOwner',
				],
			],
			$this->service->list_sites()
		);
	}

	public function test_list_sites_returns_empty_array_when_no_site_entry_key() {
		$this->client->method( 'get' )->willReturn( [] );

		$this->assertEquals( [], $this->service->list_sites() );
	}

	public function test_create_site_puts_and_returns_constructed_entry() {
		$this->client->expects( $this->once() )
			->method( 'put' )
			->with( 'sites/' . rawurlencode( 'https://example.com/' ) );

		$this->assertEquals(
			[
				'siteUrl'         => 'https://example.com/',
				'permissionLevel' => SitesService::PERMISSION_UNVERIFIED,
			],
			$this->service->create_site( 'https://example.com/' )
		);
	}

	public function test_get_property_type_detects_domain_property() {
		$this->assertEquals( SitesService::PROPERTY_TYPE_DOMAIN, $this->service->get_property_type( 'sc-domain:example.com' ) );
	}

	public function test_get_property_type_detects_url_prefix_property() {
		$this->assertEquals( SitesService::PROPERTY_TYPE_URL_PREFIX, $this->service->get_property_type( 'https://example.com/' ) );
	}

	/**
	 * @dataProvider provide_single_match_scenarios
	 *
	 * @param array  $site_entries       Sites API `siteEntry` resources to return from `list_sites()`.
	 * @param string $expected_site_url  The `siteUrl` expected to be auto-resolved.
	 */
	public function test_resolve_property_auto_selects_single_usable_match( array $site_entries, string $expected_site_url ) {
		$this->client->method( 'get' )->willReturn( [ 'siteEntry' => $site_entries ] );

		$result = $this->service->resolve_property( self::STORE_URL );

		$this->assertNotNull( $result['resolved'] );
		$this->assertEquals( $expected_site_url, $result['resolved']['siteUrl'] );
		$this->assertFalse( $result['created'] );
	}

	public function provide_single_match_scenarios(): array {
		return [
			'exact url-prefix match'                => [
				[
					[
						'siteUrl'         => 'https://example.com/',
						'permissionLevel' => 'siteOwner',
					],
				],
				'https://example.com/',
			],
			'url-prefix covering a narrower path'   => [
				[
					[
						'siteUrl'         => 'https://example.com',
						'permissionLevel' => 'siteFullUser',
					],
				],
				'https://example.com',
			],
			'unverified url-prefix is still usable' => [
				[
					[
						'siteUrl'         => 'https://example.com/',
						'permissionLevel' => 'siteUnverifiedUser',
					],
				],
				'https://example.com/',
			],
			'verified domain property is usable'    => [
				[
					[
						'siteUrl'         => 'sc-domain:example.com',
						'permissionLevel' => 'siteOwner',
					],
				],
				'sc-domain:example.com',
			],
			'url-prefix favored as tiebreak when both are verified' => [
				[
					[
						'siteUrl'         => 'sc-domain:example.com',
						'permissionLevel' => 'siteOwner',
					],
					[
						'siteUrl'         => 'https://example.com/',
						'permissionLevel' => 'siteOwner',
					],
				],
				'https://example.com/',
			],
			'already-verified domain wins over an unverified url-prefix' => [
				[
					[
						'siteUrl'         => 'sc-domain:example.com',
						'permissionLevel' => 'siteOwner',
					],
					[
						'siteUrl'         => 'https://example.com/',
						'permissionLevel' => 'siteUnverifiedUser',
					],
				],
				'sc-domain:example.com',
			],
		];
	}

	public function test_resolve_property_treats_unverified_domain_as_not_usable_and_auto_creates() {
		$this->client->method( 'get' )->willReturn(
			[
				'siteEntry' => [
					[
						'siteUrl'         => 'sc-domain:example.com',
						'permissionLevel' => 'siteUnverifiedUser',
					],
				],
			]
		);

		$this->client->expects( $this->once() )
			->method( 'put' )
			->with( 'sites/' . rawurlencode( self::STORE_URL ) );

		$result = $this->service->resolve_property( self::STORE_URL );

		$this->assertTrue( $result['created'] );
		$this->assertEquals( self::STORE_URL, $result['resolved']['siteUrl'] );

		$unverified_domain_match = $result['matches'][0];
		$this->assertTrue( $unverified_domain_match['covers'] );
		$this->assertFalse( $unverified_domain_match['usable'] );
	}

	/**
	 * A restricted-access property (owned by a different account) has no path to
	 * verification through this plugin at all — it must be excluded entirely,
	 * not merely marked unusable, and the flow falls through to silent auto-create.
	 */
	public function test_resolve_property_excludes_restricted_access_property_entirely() {
		$this->client->method( 'get' )->willReturn(
			[
				'siteEntry' => [
					[
						'siteUrl'         => self::STORE_URL,
						'permissionLevel' => SitesService::PERMISSION_RESTRICTED,
					],
				],
			]
		);

		$this->client->expects( $this->once() )
			->method( 'put' )
			->with( 'sites/' . rawurlencode( self::STORE_URL ) );

		$result = $this->service->resolve_property( self::STORE_URL );

		$this->assertTrue( $result['created'] );
		$this->assertCount(
			0,
			array_filter( $result['matches'], fn( $m ) => SitesService::PERMISSION_RESTRICTED === $m['permissionLevel'] ),
			'A restricted-access property must not appear in matches at all.'
		);
	}

	public function test_resolve_property_excludes_restricted_access_domain_property_entirely() {
		$this->client->method( 'get' )->willReturn(
			[
				'siteEntry' => [
					[
						'siteUrl'         => 'sc-domain:example.com',
						'permissionLevel' => SitesService::PERMISSION_RESTRICTED,
					],
				],
			]
		);

		$result = $this->service->resolve_property( self::STORE_URL );

		$this->assertTrue( $result['created'] );
		// The restricted domain property was filtered out before matching even began, so the
		// only entry in `matches` is the newly created property — not the restricted one.
		$this->assertCount( 1, $result['matches'] );
		$this->assertEquals( self::STORE_URL, $result['matches'][0]['siteUrl'] );
	}

	public function test_resolve_property_auto_creates_url_prefix_when_no_usable_match() {
		$this->client->method( 'get' )->willReturn( [ 'siteEntry' => [] ] );

		$this->client->expects( $this->once() )
			->method( 'put' )
			->with( 'sites/' . rawurlencode( self::STORE_URL ) );

		$result = $this->service->resolve_property( self::STORE_URL );

		$this->assertTrue( $result['created'] );
		$this->assertEquals( self::STORE_URL, $result['resolved']['siteUrl'] );
		$this->assertEquals( SitesService::PERMISSION_UNVERIFIED, $result['resolved']['permissionLevel'] );

		$created_match = current( array_filter( $result['matches'], fn( $m ) => self::STORE_URL === $m['siteUrl'] ) );
		$this->assertTrue( $created_match['usable'], 'A freshly created url-prefix property is still usable even though unverified.' );
	}

	public function test_resolve_property_leaves_choice_to_merchant_on_multiple_usable_url_prefix_matches() {
		$this->client->method( 'get' )->willReturn(
			[
				'siteEntry' => [
					[
						'siteUrl'         => 'https://example.com/',
						'permissionLevel' => 'siteOwner',
					],
					[
						'siteUrl'         => 'https://example.com/store/',
						'permissionLevel' => 'siteOwner',
					],
				],
			]
		);

		$this->client->expects( $this->never() )->method( 'put' );

		$result = $this->service->resolve_property( 'https://example.com/store/checkout' );

		$this->assertNull( $result['resolved'] );
		$this->assertFalse( $result['created'] );
		$this->assertCount( 2, $result['matches'] );
	}

	public function test_resolve_property_excludes_unrelated_domains_entirely() {
		$this->client->method( 'get' )->willReturn(
			[
				'siteEntry' => [
					[
						'siteUrl'         => 'https://unrelated-site.com/',
						'permissionLevel' => 'siteOwner',
					],
				],
			]
		);

		$this->client->expects( $this->once() )->method( 'put' );

		$result = $this->service->resolve_property( self::STORE_URL );

		$this->assertTrue( $result['created'] );
		$this->assertCount( 0, array_filter( $result['matches'], fn( $m ) => 'https://unrelated-site.com/' === $m['siteUrl'] ) );
	}

	public function test_resolve_property_shows_domain_aligned_non_covering_property_as_greyed_out() {
		$this->client->method( 'get' )->willReturn(
			[
				'siteEntry' => [
					[
						'siteUrl'         => 'https://example.com/blog/',
						'permissionLevel' => 'siteOwner',
					],
				],
			]
		);

		$result = $this->service->resolve_property( 'https://example.com/store/' );

		$this->assertTrue( $result['created'] );

		$non_covering_match = current(
			array_filter( $result['matches'], fn( $m ) => 'https://example.com/blog/' === $m['siteUrl'] )
		);
		$this->assertFalse( $non_covering_match['covers'] );
		$this->assertFalse( $non_covering_match['usable'] );
	}

	/**
	 * The exact edge case flagged as the main risk pocket: a naive string-prefix
	 * check would incorrectly treat `/storefront` as covered by a property scoped
	 * to `/store`, since the former starts with the same characters.
	 */
	public function test_resolve_property_does_not_treat_similar_path_prefix_as_covering() {
		$this->client->method( 'get' )->willReturn(
			[
				'siteEntry' => [
					[
						'siteUrl'         => 'https://example.com/store',
						'permissionLevel' => 'siteOwner',
					],
				],
			]
		);

		$result = $this->service->resolve_property( 'https://example.com/storefront' );

		$this->assertTrue( $result['created'], 'A path-boundary mismatch must not be treated as a usable cover.' );

		$mismatched_match = current(
			array_filter( $result['matches'], fn( $m ) => 'https://example.com/store' === $m['siteUrl'] )
		);
		$this->assertFalse( $mismatched_match['covers'] );
	}

	public function test_resolve_property_treats_trailing_slash_variants_as_equivalent() {
		$this->client->method( 'get' )->willReturn(
			[
				'siteEntry' => [
					[
						'siteUrl'         => 'https://example.com',
						'permissionLevel' => 'siteOwner',
					],
				],
			]
		);

		$result = $this->service->resolve_property( 'https://example.com/' );

		$this->assertEquals( 'https://example.com', $result['resolved']['siteUrl'] );
	}

	public function test_resolve_property_domain_property_covers_a_subdomain_of_the_store() {
		$this->client->method( 'get' )->willReturn(
			[
				'siteEntry' => [
					[
						'siteUrl'         => 'sc-domain:example.com',
						'permissionLevel' => 'siteOwner',
					],
				],
			]
		);

		$result = $this->service->resolve_property( 'https://shop.example.com/' );

		$this->assertEquals( 'sc-domain:example.com', $result['resolved']['siteUrl'] );
	}
}
