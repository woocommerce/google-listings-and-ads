<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\SearchConsole;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\SiteVerification;
use Automattic\WooCommerce\GoogleListingsAndAds\API\SearchConsole\Connection;
use Automattic\WooCommerce\GoogleListingsAndAds\API\SearchConsole\SearchConsoleApiException;
use Automattic\WooCommerce\GoogleListingsAndAds\API\SearchConsole\TopProductPagesService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\SearchConsole\TopProductPagesController;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\RESTControllerUnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\Container\Container;
use DateTimeInterface;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class TopProductPagesControllerTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\SearchConsole
 */
class TopProductPagesControllerTest extends RESTControllerUnitTest {

	protected const ROUTE = '/wc/gla/search-console/top-product-pages';

	/** @var MockObject|Connection $connection */
	protected $connection;

	/** @var MockObject|TopProductPagesService $service */
	protected $service;

	/** @var MockObject|OptionsInterface $options */
	protected $options;

	public function setUp(): void {
		parent::setUp();

		$this->connection = $this->getMockBuilder( Connection::class )->disableOriginalConstructor()->onlyMethods( [ 'get_connection_data' ] )->getMock();
		$this->service    = $this->createMock( TopProductPagesService::class );
		$this->options    = $this->createMock( OptionsInterface::class );

		$controller = new TopProductPagesController( $this->server, $this->connection, $this->service );
		$controller->set_options_object( $this->options );
		$container = new Container();
		$container->addShared( WP::class, new WP() );
		$controller->set_container( $container );
		$controller->register();
	}

	protected function connect_property( string $property = 'sc-domain:example.com', string $verified = SiteVerification::VERIFICATION_STATUS_VERIFIED, string $state = Connection::STATE_CONNECTED ): void {
		$this->connection->method( 'get_connection_data' )->willReturn(
			[
				'property' => $property,
				'verified' => $verified,
				'state'    => $state,
			]
		);
	}

	public function test_returns_the_top_product_pages_for_the_requested_range() {
		$rows = [
			[
				'product_id'  => 7,
				'name'        => 'Blue shirt',
				'clicks'      => 12,
				'impressions' => 120,
				'ctr'         => 0.1,
				'position'    => 3.5,
			],
		];

		$this->connect_property();
		$this->options->method( 'get_merchant_id' )->willReturn( 123 );
		$this->service->method( 'get_top_product_pages_limit' )->willReturn( 100 );
		$this->service->expects( $this->once() )
			->method( 'get_top_product_pages' )
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
				'products' => $rows,
				'limit'    => 100,
			],
			$response->get_data()
		);
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_is_not_available_without_a_merchant_center_connection() {
		$this->options->method( 'get_merchant_id' )->willReturn( 0 );
		$this->service->expects( $this->never() )->method( 'get_top_product_pages' );

		$response = $this->do_request( self::ROUTE, 'GET', [ 'after' => '2026-09-01' ] );

		$this->assertEquals( 404, $response->get_status() );
	}

	public function test_requires_a_connected_property() {
		$this->connection->method( 'get_connection_data' )->willReturn( [ 'property' => null ] );
		$this->options->method( 'get_merchant_id' )->willReturn( 123 );
		$this->service->expects( $this->never() )->method( 'get_top_product_pages' );

		$response = $this->do_request( self::ROUTE, 'GET' );

		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( [ 'message' => 'Search Console is not connected to a verified property.' ], $response->get_data() );
	}

	public function test_requires_a_verified_property() {
		$this->connect_property( 'sc-domain:example.com', SiteVerification::VERIFICATION_STATUS_UNVERIFIED );
		$this->options->method( 'get_merchant_id' )->willReturn( 123 );
		$this->service->expects( $this->never() )->method( 'get_top_product_pages' );

		$response = $this->do_request( self::ROUTE, 'GET' );

		$this->assertEquals( 400, $response->get_status() );
	}

	public function test_requires_a_connected_state() {
		$this->connect_property( 'sc-domain:example.com', SiteVerification::VERIFICATION_STATUS_VERIFIED, Connection::STATE_RECONNECT );
		$this->options->method( 'get_merchant_id' )->willReturn( 123 );
		$this->service->expects( $this->never() )->method( 'get_top_product_pages' );

		$response = $this->do_request( self::ROUTE, 'GET' );

		$this->assertEquals( 400, $response->get_status() );
	}

	public function test_reports_a_service_error_with_its_status() {
		$this->connect_property();
		$this->options->method( 'get_merchant_id' )->willReturn( 123 );
		$this->service->method( 'get_top_product_pages' )->willThrowException(
			new SearchConsoleApiException( 403, [ 'error' => [ 'message' => 'Forbidden' ] ], __METHOD__ )
		);

		$response = $this->do_request( self::ROUTE, 'GET' );

		$this->assertEquals( 403, $response->get_status() );
	}

	public function test_rejects_a_malformed_date() {
		$this->options->method( 'get_merchant_id' )->willReturn( 123 );
		$this->service->expects( $this->never() )->method( 'get_top_product_pages' );

		$response = $this->do_request( self::ROUTE, 'GET', [ 'after' => 'not-a-date' ] );

		$this->assertEquals( 400, $response->get_status() );
	}
}
