<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google\Mapi\Services;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\MerchantApiClient;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services\MapiAccountBusinessInfoService;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class MapiAccountBusinessInfoServiceTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google\Mapi\Services
 */
class MapiAccountBusinessInfoServiceTest extends UnitTest {

	protected const MERCHANT_ID = 12345;
	protected const PATH        = 'accounts/v1/accounts/12345/businessInfo';

	/** @var MockObject|MerchantApiClient */
	protected $client;

	/** @var MockObject|OptionsInterface */
	protected $options;

	/** @var MapiAccountBusinessInfoService */
	protected $service;

	public function setUp(): void {
		parent::setUp();

		$this->client  = $this->createMock( MerchantApiClient::class );
		$this->options = $this->createMock( OptionsInterface::class );
		$this->options->method( 'get_merchant_id' )->willReturn( self::MERCHANT_ID );

		$this->service = new MapiAccountBusinessInfoService( $this->client );
		$this->service->set_options_object( $this->options );
	}

	public function test_get_business_info() {
		$business_info = [
			'name'    => 'accounts/12345/businessInfo',
			'address' => [
				'regionCode' => 'US',
				'postalCode' => '22211',
			],
		];

		$this->client->expects( $this->once() )
			->method( 'get' )
			->with( self::PATH )
			->willReturn( $business_info );

		$this->assertSame( $business_info, $this->service->get_business_info() );
	}

	public function test_update_business_info() {
		$business_info = [ 'address' => [ 'regionCode' => 'US' ] ];
		$response      = [
			'name'    => 'accounts/12345/businessInfo',
			'address' => [ 'regionCode' => 'US' ],
		];

		$this->client->expects( $this->once() )
			->method( 'patch' )
			->with( self::PATH . '?updateMask=address', $business_info )
			->willReturn( $response );

		$this->assertSame( $response, $this->service->update_business_info( $business_info, 'address' ) );
	}
}
