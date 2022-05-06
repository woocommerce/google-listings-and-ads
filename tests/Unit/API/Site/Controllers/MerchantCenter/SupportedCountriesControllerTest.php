<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter\SupportedCountriesController;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\GoogleHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\RESTControllerUnitTest;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class SupportedCountriesControllerTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\MerchantCenter
 *
 * @property MockObject|GoogleHelper      $google_helper
 * @property MockObject|WC                $wc
 * @property SupportedCountriesController $supported_countries_controller
 */
class SupportedCountriesControllerTest extends RESTControllerUnitTest {

	const ROUTE = '/wc/gla/mc/countries';

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->wc            = $this->createMock( WC::class );
		$this->google_helper = $this->createMock( GoogleHelper::class );

		$this->supported_countries_controller = new SupportedCountriesController( $this->server,  $this->wc, $this->google_helper );
		$this->supported_countries_controller->register();
	}

	public function test_get_countries() {
		$countries_data = [
			'US' => 'United States',
			'GB' => 'United Kingdom',
			'TW' => 'Taiwan',
			'CN' => 'China',
		];

		$mc_supported_countries_currencies_data = [
			'US' => 'USD',
			'GB' => 'GBP',
			'TW' => 'TWD',
		];

		$expected = [
			'countries' => [
				'US' => [
					'name'     => 'United States',
					'currency' => 'USD',
				],
				'GB' =>[
					'name'     => 'United Kingdom',
					'currency' => 'GBP',
				],
				'TW' =>[
					'name'     => 'Taiwan',
					'currency' => 'TWD',
				],
			],
		];

		$this->wc->expects( $this->once() )
			->method( 'get_countries' )
			->willReturn( $countries_data );

		$this->google_helper->expects( $this->once() )
			->method( 'get_mc_supported_countries_currencies' )
			->willReturn( $mc_supported_countries_currencies_data );

		$response = $this->do_request( self::ROUTE, 'GET' );

		$this->assertEquals( $expected, $response->get_data() );
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_get_countries_with_continents() {
		$countries_params = [
			'continents' => true,
		];

		$countries_data = [
			'US' => 'United States',
			'GB' => 'United Kingdom',
			'TW' => 'Taiwan',
			'CN' => 'China',
		];

		$mc_supported_countries_currencies_data = [
			'US' => 'USD',
			'GB' => 'GBP',
			'TW' => 'TWD',
		];

		$continents_data = [
			'EU' => [
				'name' => 'Europe',
				'countries' => [
					'AD',
					'AL',
					'GB',
				],
			],
			'NA' => [
				'name' => 'North America',
				'countries' => [
					'AG',
					'AI',
					'US',
				],
			],
			'AS' => [
				'name' => 'Asia',
				'countries' => [
					'JP',
					'TW',
					'SG',
					'CN',
				],
			],
		];

		$supported_countries_of_continent = [
			'EU' => [ 'GB' => 'GB' ],
			'NA' => [ 'US' => 'US' ],
			'AS' => [ 'TW' => 'TW' ],
		];

		$expected = [
			'countries' => [
				'US' => [
					'name'     => 'United States',
					'currency' => 'USD',
				],
				'GB' =>[
					'name'     => 'United Kingdom',
					'currency' => 'GBP',
				],
				'TW' =>[
					'name'     => 'Taiwan',
					'currency' => 'TWD',
				],
			],
			'continents' => [
				'EU' => [
					'name' => 'Europe',
					'countries' => [
						'GB',
					],
				],
				'NA' => [
					'name' => 'North America',
					'countries' => [
						'US',
					],
				],
				'AS' => [
					'name' => 'Asia',
					'countries' => [
						'TW',
					],
				],
			],
		];

		$this->wc->expects( $this->once() )
			->method( 'get_countries' )
			->willReturn( $countries_data );

		$this->google_helper->expects( $this->once() )
			->method( 'get_mc_supported_countries_currencies' )
			->willReturn( $mc_supported_countries_currencies_data );

		$this->wc->expects( $this->once() )
			->method( 'get_continents' )
			->willReturn( $continents_data );

		$this->google_helper->expects( $this->exactly( 3 ) )
			->method( 'get_supported_countries_from_continent' )
			->willReturnOnConsecutiveCalls(
				$supported_countries_of_continent['EU'],
				$supported_countries_of_continent['NA'],
				$supported_countries_of_continent['AS']
			);

		$response = $this->do_request( self::ROUTE, 'GET', $countries_params );

		$this->assertEquals( $expected, $response->get_data() );
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_get_countries_with_wrong_continents_type() {
		$countries_params = [
			'continents' => '',
		];

		$response = $this->do_request( self::ROUTE, 'GET', $countries_params );

		$this->assertEquals( 'rest_invalid_param', $response->get_data()['code'] );
		$this->assertEquals( 'Invalid parameter(s): continents', $response->get_data()['message'] );
		$this->assertEquals( 400, $response->get_status() );
	}
}
