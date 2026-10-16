<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google\Mapi\Services;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\MerchantApiClient;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services\MapiAccountHomepageService;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class MapiAccountHomepageServiceTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google\Mapi\Services
 */
class MapiAccountHomepageServiceTest extends UnitTest {

	protected const MERCHANT_ID   = 12345;
	protected const HOMEPAGE_PATH = 'accounts/v1/accounts/12345/homepage';

	/** @var MockObject|MerchantApiClient */
	protected $client;

	/** @var MockObject|OptionsInterface */
	protected $options;

	/** @var MapiAccountHomepageService */
	protected $service;

	public function setUp(): void {
		parent::setUp();

		$this->client  = $this->createMock( MerchantApiClient::class );
		$this->options = $this->createMock( OptionsInterface::class );
		$this->options->method( 'get_merchant_id' )->willReturn( self::MERCHANT_ID );

		$this->service = new MapiAccountHomepageService( $this->client );
		$this->service->set_options_object( $this->options );
	}

	public function test_get_homepage() {
		$homepage = [
			'name'    => 'accounts/12345/homepage',
			'uri'     => 'https://store.example',
			'claimed' => true,
		];

		$this->client->expects( $this->once() )
			->method( 'get' )
			->with( self::HOMEPAGE_PATH )
			->willReturn( $homepage );

		$this->assertSame( $homepage, $this->service->get_homepage() );
	}

	public function test_get_homepage_with_explicit_merchant_id() {
		$this->client->expects( $this->once() )
			->method( 'get' )
			->with( 'accounts/v1/accounts/999/homepage' )
			->willReturn( [ 'claimed' => false ] );

		$this->assertSame( [ 'claimed' => false ], $this->service->get_homepage( 999 ) );
	}

	public function test_claim() {
		$homepage = [ 'claimed' => true ];

		$this->client->expects( $this->once() )
			->method( 'post' )
			->with( self::HOMEPAGE_PATH . ':claim', [ 'overwrite' => false ] )
			->willReturn( $homepage );

		$this->assertSame( $homepage, $this->service->claim() );
	}

	public function test_claim_with_overwrite() {
		$this->client->expects( $this->once() )
			->method( 'post' )
			->with( self::HOMEPAGE_PATH . ':claim', [ 'overwrite' => true ] )
			->willReturn( [ 'claimed' => true ] );

		$this->assertSame( [ 'claimed' => true ], $this->service->claim( true ) );
	}
}
