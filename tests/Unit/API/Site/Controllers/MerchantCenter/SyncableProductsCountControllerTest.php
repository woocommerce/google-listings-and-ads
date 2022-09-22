<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter\SyncableProductsCountController;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\TransientsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\RESTControllerUnitTest;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class SyncableProductsCountControllerTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\MerchantCenter
 *
 * @property TransientsInterface|MockObject $transients
 */
class SyncableProductsCountControllerTest extends RESTControllerUnitTest {

	protected const ROUTE_REQUEST = '/wc/gla/mc/syncable-products-count';
	private $transients;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->transients = $this->createMock( TransientsInterface::class );
		$this->controller = new SyncableProductsCountController( $this->server, $this->transients );
		$this->controller->register();
	}

	public function test_get_syncable_products_count() {
		$this->transients->expects( $this->exactly( 1 ) )
			->method( 'get' )
			->willReturn( 100 );

		$response = $this->do_request( self::ROUTE_REQUEST );
		$this->assertEquals( [ 'count' => 100 ], $response->get_data() );
	}

	public function test_get_syncable_products_count_null() {
		$response = $this->do_request( self::ROUTE_REQUEST );
		$this->assertEquals( [ 'count' => null ], $response->get_data() );
	}
}
