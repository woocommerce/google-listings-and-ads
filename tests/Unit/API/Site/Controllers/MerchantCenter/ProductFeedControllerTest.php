<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter\ProductFeedController;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\ProductFeedQueryHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\SyncHealth\ProductJourney;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\RESTControllerUnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\ChannelVisibility;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class ProductFeedControllerTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\MerchantCenter
 */
class ProductFeedControllerTest extends RESTControllerUnitTest {

	/** @var MockObject|ProductFeedQueryHelper $query_helper */
	protected $query_helper;

	protected const ROUTE = '/wc/gla/mc/product-feed';

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->query_helper = $this->createMock( ProductFeedQueryHelper::class );
		$this->controller   = new ProductFeedController( $this->server, $this->query_helper );
		$this->controller->register();
	}

	/**
	 * @dataProvider invalid_status_filters
	 *
	 * @param array $params
	 */
	public function test_invalid_status_filters_are_rejected( array $params ) {
		$this->query_helper->expects( $this->never() )->method( 'get' );

		$response = $this->do_request( self::ROUTE, 'GET', $params );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'rest_invalid_param', $response->get_data()['code'] );
	}

	/**
	 * @return array
	 */
	public function invalid_status_filters(): array {
		return [
			'retired journey segment' => [ [ 'journey' => 'live' ] ],
			'unknown visibility'      => [ [ 'visibility' => 'hidden' ] ],
		];
	}

	public function test_valid_status_filters_and_unregistered_params_reach_the_query() {
		$this->query_helper->expects( $this->once() )
			->method( 'get' )
			->with(
				$this->callback(
					function ( $request ) {
						// Params the route does not register keep their old, unvalidated handling.
						return ProductJourney::DISAPPROVED === $request['journey']
							&& ChannelVisibility::DONT_SYNC_AND_SHOW === $request['visibility']
							&& 'total_sales' === $request['orderby']
							&& '1,2' === $request['ids'];
					}
				)
			)
			->willReturn( [] );
		$this->query_helper->method( 'count' )->willReturn( 0 );

		$response = $this->do_request(
			self::ROUTE,
			'GET',
			[
				'journey'    => ProductJourney::DISAPPROVED,
				'visibility' => ChannelVisibility::DONT_SYNC_AND_SHOW,
				'orderby'    => 'total_sales',
				'ids'        => '1,2',
			]
		);

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame(
			[
				'products' => [],
				'total'    => 0,
				'page'     => 1,
			],
			$response->get_data()
		);
	}
}
