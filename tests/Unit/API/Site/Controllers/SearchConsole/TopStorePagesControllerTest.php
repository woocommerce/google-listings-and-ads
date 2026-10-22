<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\SearchConsole;

use Automattic\WooCommerce\GoogleListingsAndAds\API\SearchConsole\Connection;
use Automattic\WooCommerce\GoogleListingsAndAds\API\SearchConsole\SearchConsoleApiException;
use Automattic\WooCommerce\GoogleListingsAndAds\API\SearchConsole\TopStorePagesService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\SearchConsole\TopStorePagesController;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\ServiceBasedMerchantState;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\RESTControllerUnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\Container\Container;
use DateTimeInterface;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class TopStorePagesControllerTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\SearchConsole
 */
class TopStorePagesControllerTest extends RESTControllerUnitTest {

	protected const ROUTE = '/wc/gla/search-console/top-store-pages';

	/** @var MockObject|Connection $connection */
	protected $connection;

	/** @var MockObject|TopStorePagesService $service */
	protected $service;

	/** @var array Option values a test changed, keyed by option name, restored after the test. */
	protected $changed_options = [];

	public function setUp(): void {
		parent::setUp();

		$this->connection = $this->createMock( Connection::class );
		$this->service    = $this->createMock( TopStorePagesService::class );

		$controller = new TopStorePagesController( $this->server, $this->connection, $this->service );
		$container  = new Container();
		$container->addShared( WP::class, new WP() );
		$controller->set_container( $container );
		$controller->register();
	}

	public function tearDown(): void {
		// The container's options object caches values in memory, which the database rollback doesn't reset.
		$options = woogle_get_container()->get( OptionsInterface::class );
		foreach ( $this->changed_options as $name => $value ) {
			if ( null === $value ) {
				$options->delete( $name );
			} else {
				$options->update( $name, $value );
			}
		}

		parent::tearDown();
	}

	protected function update_option( string $name, $value ): void {
		$options = woogle_get_container()->get( OptionsInterface::class );

		if ( ! array_key_exists( $name, $this->changed_options ) ) {
			$this->changed_options[ $name ] = $options->get( $name );
		}

		if ( null === $value ) {
			$options->delete( $name );
		} else {
			$options->update( $name, $value );
		}
	}

	protected function connect_property( string $property = 'sc-domain:example.com' ): void {
		$this->connection->method( 'is_connected_from_stored_data' )->willReturn( true );
		$this->connection->method( 'get_connection_data' )->willReturn( [ 'property' => $property ] );
	}

	public function test_returns_the_top_store_pages_for_the_requested_range() {
		$rows = [
			[
				'post_id'     => 7,
				'name'        => 'About us',
				'url'         => 'https://example.com/about/',
				'clicks'      => 12,
				'impressions' => 120,
				'ctr'         => 0.1,
				'position'    => 3.5,
			],
			[
				'post_id'     => null,
				'name'        => '/shop/',
				'url'         => 'https://example.com/shop/',
				'clicks'      => 4,
				'impressions' => 80,
				'ctr'         => 0.05,
				'position'    => 6.0,
			],
		];

		$this->connect_property();
		$this->service->method( 'get_top_store_pages_limit' )->willReturn( 100 );
		$this->service->expects( $this->once() )
			->method( 'get_top_store_pages' )
			->with(
				'sc-domain:example.com',
				$this->callback( fn( DateTimeInterface $after ) => '2026-09-01' === $after->format( 'Y-m-d' ) ),
				$this->callback( fn( DateTimeInterface $before ) => '2026-09-30' === $before->format( 'Y-m-d' ) ),
				100
			)
			->willReturn( $rows );

		$response = $this->do_request(
			self::ROUTE,
			'GET',
			[
				'after'  => '2026-09-01',
				'before' => '2026-09-30',
			]
		);

		$this->assertEquals(
			[
				'pages' => $rows,
				'limit' => 100,
			],
			$response->get_data()
		);
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_is_available_to_service_based_merchants() {
		$this->update_option( OptionsInterface::SUPPORTED_PRODUCTS_CONFIRMED, null );
		$this->update_option( OptionsInterface::IS_SERVICE_BASED_MERCHANT, 'yes' );
		$this->assertTrue( woogle_get_container()->get( ServiceBasedMerchantState::class )->is_service_based_merchant() );

		$this->connect_property();
		$this->service->method( 'get_top_store_pages_limit' )->willReturn( 100 );
		$this->service->expects( $this->once() )->method( 'get_top_store_pages' )->willReturn( [] );

		$response = $this->do_request( self::ROUTE, 'GET' );

		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_requires_a_connected_verified_property() {
		$this->connection->method( 'is_connected_from_stored_data' )->willReturn( false );
		$this->service->expects( $this->never() )->method( 'get_top_store_pages' );

		$response = $this->do_request( self::ROUTE, 'GET' );

		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( [ 'message' => 'Search Console is not connected to a verified property.' ], $response->get_data() );
	}

	public function test_reports_a_service_error_with_its_status() {
		$this->connect_property();
		$this->service->method( 'get_top_store_pages' )->willThrowException(
			new SearchConsoleApiException( 403, [ 'error' => [ 'message' => 'Forbidden' ] ], __METHOD__ )
		);

		$response = $this->do_request( self::ROUTE, 'GET' );

		$this->assertEquals( 403, $response->get_status() );
	}

	public function test_rejects_a_malformed_date() {
		$this->service->expects( $this->never() )->method( 'get_top_store_pages' );

		$response = $this->do_request( self::ROUTE, 'GET', [ 'after' => 'not-a-date' ] );

		$this->assertEquals( 400, $response->get_status() );
	}
}
