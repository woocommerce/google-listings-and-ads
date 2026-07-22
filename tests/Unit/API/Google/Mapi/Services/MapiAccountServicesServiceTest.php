<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google\Mapi\Services;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\MerchantApiClient;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services\MapiAccountServicesService;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class MapiAccountServicesServiceTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google\Mapi\Services
 */
class MapiAccountServicesServiceTest extends UnitTest {

	protected const MERCHANT_ID = 12345;
	protected const PATH        = 'accounts/v1/accounts/12345/services';

	/** @var MockObject|MerchantApiClient */
	protected $client;

	/** @var MockObject|OptionsInterface */
	protected $options;

	/** @var MapiAccountServicesService */
	protected $service;

	public function setUp(): void {
		parent::setUp();

		$this->client  = $this->createMock( MerchantApiClient::class );
		$this->options = $this->createMock( OptionsInterface::class );
		$this->options->method( 'get_merchant_id' )->willReturn( self::MERCHANT_ID );

		$this->service = new MapiAccountServicesService( $this->client );
		$this->service->set_options_object( $this->options );
	}

	public function test_get_services() {
		$services = [ [ 'name' => 'accounts/12345/services/x' ] ];

		$this->client->expects( $this->once() )
			->method( 'get' )
			->with( self::PATH )
			->willReturn( [ 'accountServices' => $services ] );

		$this->assertSame( $services, $this->service->get_services() );
	}

	public function test_get_services_empty() {
		$this->client->expects( $this->once() )
			->method( 'get' )
			->with( self::PATH )
			->willReturn( [] );

		$this->assertSame( [], $this->service->get_services() );
	}

	public function test_get_google_ads_link_found() {
		$link = [
			'provider'          => 'providers/GOOGLE_ADS',
			'externalAccountId' => '999',
			'handshake'         => [ 'approvalState' => 'ESTABLISHED' ],
		];

		$this->client->method( 'get' )->willReturn(
			[
				'accountServices' => [
					[
						'provider'          => 'providers/140802286',
						'externalAccountId' => '999',
					],
					$link,
				],
			]
		);

		$this->assertSame( $link, $this->service->get_google_ads_link( 999 ) );
	}

	public function test_get_google_ads_link_prefers_established() {
		$pending     = [
			'provider'          => 'providers/GOOGLE_ADS',
			'externalAccountId' => '999',
			'handshake'         => [ 'approvalState' => 'PENDING' ],
		];
		$established = [
			'provider'          => 'providers/GOOGLE_ADS',
			'externalAccountId' => '999',
			'handshake'         => [ 'approvalState' => 'ESTABLISHED' ],
		];

		$this->client->method( 'get' )->willReturn( [ 'accountServices' => [ $pending, $established ] ] );

		$this->assertSame( $established, $this->service->get_google_ads_link( 999 ) );
	}

	public function test_get_google_ads_link_none() {
		$this->client->method( 'get' )->willReturn(
			[
				'accountServices' => [
					[
						'provider'          => 'providers/GOOGLE_ADS',
						'externalAccountId' => '111',
					],
				],
			]
		);

		$this->assertNull( $this->service->get_google_ads_link( 999 ) );
	}

	public function test_propose_google_ads_link() {
		$response = [
			'name'      => 'accounts/12345/services/x',
			'handshake' => [ 'approvalState' => 'PENDING' ],
		];

		$this->client->expects( $this->once() )
			->method( 'post' )
			->with(
				self::PATH . ':propose',
				[
					'provider'       => 'providers/GOOGLE_ADS',
					'accountService' => [
						'externalAccountId'   => '999',
						'campaignsManagement' => (object) [],
					],
				]
			)
			->willReturn( $response );

		$this->assertSame( $response, $this->service->propose_google_ads_link( 999 ) );
	}
}
