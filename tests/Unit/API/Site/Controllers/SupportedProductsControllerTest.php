<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\SupportedProductsController;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\ServiceBasedMerchantState;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\RESTControllerUnitTest;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class SupportedProductsControllerTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers
 */
class SupportedProductsControllerTest extends RESTControllerUnitTest {

	protected const ROUTE = '/wc/gla/merchant/supported-products';

	/** @var MockObject|ServiceBasedMerchantState $service_based_merchant_state */
	protected $service_based_merchant_state;

	public function setUp(): void {
		parent::setUp();

		$this->service_based_merchant_state = $this->createMock( ServiceBasedMerchantState::class );
		$this->controller                   = new SupportedProductsController( $this->server, $this->service_based_merchant_state );
		$this->controller->register();
	}

	public function test_route_registered(): void {
		$this->assertArrayHasKey( self::ROUTE, $this->server->get_routes() );
	}

	public function test_confirm_supported_products(): void {
		$this->service_based_merchant_state->expects( $this->once() )
			->method( 'confirm_supported_products' );

		$response = $this->do_request( self::ROUTE, 'POST', [ 'confirmed' => true ] );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals(
			[
				'confirmed'              => true,
				'service_based_merchant' => false,
			],
			$response->get_data()
		);
	}

	public function test_confirmation_must_be_explicitly_true(): void {
		$this->service_based_merchant_state->expects( $this->never() )
			->method( 'confirm_supported_products' );

		$response = $this->do_request( self::ROUTE, 'POST', [ 'confirmed' => false ] );

		$this->assertEquals( 400, $response->get_status() );
	}
}
