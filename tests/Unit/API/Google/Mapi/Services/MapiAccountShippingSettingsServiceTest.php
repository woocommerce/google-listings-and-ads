<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google\Mapi\Services;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\MerchantApiClient;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services\MapiAccountShippingSettingsService;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class MapiAccountShippingSettingsServiceTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google\Mapi\Services
 */
class MapiAccountShippingSettingsServiceTest extends UnitTest {

	protected const MERCHANT_ID = 12345;
	protected const PATH        = 'accounts/v1/accounts/12345/shippingSettings';

	/** @var MockObject|MerchantApiClient */
	protected $client;

	/** @var MockObject|OptionsInterface */
	protected $options;

	/** @var MapiAccountShippingSettingsService */
	protected $service;

	public function setUp(): void {
		parent::setUp();

		$this->client  = $this->createMock( MerchantApiClient::class );
		$this->options = $this->createMock( OptionsInterface::class );
		$this->options->method( 'get_merchant_id' )->willReturn( self::MERCHANT_ID );

		$this->service = new MapiAccountShippingSettingsService( $this->client );
		$this->service->set_options_object( $this->options );
	}

	public function test_insert_shipping_settings() {
		$body     = [ 'services' => [] ];
		$response = [
			'name' => 'accounts/12345/shippingSettings',
			'etag' => 'abc',
		];

		$this->client->expects( $this->once() )
			->method( 'post' )
			->with( self::PATH . ':insert', $body )
			->willReturn( $response );

		$this->assertSame( $response, $this->service->insert_shipping_settings( $body ) );
	}
}
