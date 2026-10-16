<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google\Mapi\Services;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\MerchantApiClient;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services\MapiAccountRegionsService;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class MapiAccountRegionsServiceTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google\Mapi\Services
 */
class MapiAccountRegionsServiceTest extends UnitTest {

	protected const MERCHANT_ID = 12345;
	protected const PATH        = 'accounts/v1/accounts/12345/regions';

	/** @var MockObject|MerchantApiClient */
	protected $client;

	/** @var MockObject|OptionsInterface */
	protected $options;

	/** @var MapiAccountRegionsService */
	protected $service;

	public function setUp(): void {
		parent::setUp();

		$this->client  = $this->createMock( MerchantApiClient::class );
		$this->options = $this->createMock( OptionsInterface::class );
		$this->options->method( 'get_merchant_id' )->willReturn( self::MERCHANT_ID );

		$this->service = new MapiAccountRegionsService( $this->client );
		$this->service->set_options_object( $this->options );
	}

	public function test_insert_region() {
		$region   = [ 'displayName' => 'zone1' ];
		$response = [ 'name' => 'accounts/12345/regions/zone1' ];

		$this->client->expects( $this->once() )
			->method( 'post' )
			->with( self::PATH . '?regionId=zone1', $region )
			->willReturn( $response );

		$this->assertSame( $response, $this->service->insert_region( 'zone1', $region ) );
	}

	public function test_update_region() {
		$region = [ 'displayName' => 'zone1' ];

		$this->client->expects( $this->once() )
			->method( 'patch' )
			->with( self::PATH . '/zone1?updateMask=displayName%2CpostalCodeArea', $region )
			->willReturn( [ 'name' => 'accounts/12345/regions/zone1' ] );

		$this->service->update_region( 'zone1', $region, 'displayName,postalCodeArea' );
	}
}
