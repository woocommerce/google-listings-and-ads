<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\SearchConsole;

use Automattic\WooCommerce\GoogleListingsAndAds\API\SearchConsole\TopProductPagesService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\SearchConsole\TopProductPagesController;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\ServiceBasedMerchantState;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\RESTControllerUnitTest;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class TopProductPagesControllerTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\SearchConsole
 */
class TopProductPagesControllerTest extends RESTControllerUnitTest {

	protected const ROUTE = '/wc/gla/search-console/top-product-pages';

	/** @var MockObject|TopProductPagesService $service */
	protected $service;

	/** @var MockObject|ServiceBasedMerchantState $service_based_merchant_state */
	protected $service_based_merchant_state;

	public function setUp(): void {
		parent::setUp();

		$this->service                      = $this->createMock( TopProductPagesService::class );
		$this->service_based_merchant_state = $this->createMock( ServiceBasedMerchantState::class );

		$controller = new TopProductPagesController( $this->server, $this->service, $this->service_based_merchant_state );
		$controller->register();
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

		$this->service_based_merchant_state->method( 'is_service_based_merchant' )->willReturn( false );
		$this->service->expects( $this->once() )
			->method( 'get_top_product_pages' )
			->with( '2026-09-01', '2026-09-30' )
			->willReturn( $rows );

		$response = $this->do_request(
			self::ROUTE,
			'GET',
			[
				'start_date' => '2026-09-01',
				'end_date'   => '2026-09-30',
			]
		);

		$this->assertEquals( $rows, $response->get_data() );
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_is_not_available_to_service_based_merchants() {
		$this->service_based_merchant_state->method( 'is_service_based_merchant' )->willReturn( true );
		$this->service->expects( $this->never() )->method( 'get_top_product_pages' );

		$response = $this->do_request(
			self::ROUTE,
			'GET',
			[
				'start_date' => '2026-09-01',
				'end_date'   => '2026-09-30',
			]
		);

		$this->assertEquals( 404, $response->get_status() );
	}

	public function test_reports_a_service_error_with_its_status() {
		$this->service_based_merchant_state->method( 'is_service_based_merchant' )->willReturn( false );
		$this->service->method( 'get_top_product_pages' )
			->willThrowException( new Exception( 'Search Console is not connected.', 400 ) );

		$response = $this->do_request(
			self::ROUTE,
			'GET',
			[
				'start_date' => '2026-09-01',
				'end_date'   => '2026-09-30',
			]
		);

		$this->assertEquals( [ 'message' => 'Search Console is not connected.' ], $response->get_data() );
		$this->assertEquals( 400, $response->get_status() );
	}

	/**
	 * @dataProvider provide_invalid_params
	 *
	 * @param array $params Request params that must be rejected.
	 */
	public function test_rejects_missing_or_malformed_dates( array $params ) {
		$this->service->expects( $this->never() )->method( 'get_top_product_pages' );

		$response = $this->do_request( self::ROUTE, 'GET', $params );

		$this->assertEquals( 400, $response->get_status() );
	}

	public function provide_invalid_params(): array {
		return [
			'missing both'    => [ [] ],
			'missing end'     => [ [ 'start_date' => '2026-09-01' ] ],
			'wrong format'    => [
				[
					'start_date' => '09/01/2026',
					'end_date'   => '2026-09-30',
				],
			],
			'not a real date' => [
				[
					'start_date' => '2026-02-30',
					'end_date'   => '2026-09-30',
				],
			],
		];
	}
}
