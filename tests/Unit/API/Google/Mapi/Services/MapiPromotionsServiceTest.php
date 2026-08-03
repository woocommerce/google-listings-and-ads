<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google\Mapi\Services;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\MerchantApiClient;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\MerchantApiException;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services\MapiPromotionsService;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class MapiPromotionsServiceTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google\Mapi\Services
 */
class MapiPromotionsServiceTest extends UnitTest {

	protected const MERCHANT_ID = 12345;
	protected const DATA_SOURCE = 'accounts/12345/dataSources/300';
	protected const INSERT_PATH = 'promotions/v1/accounts/12345/promotions:insert';

	/** @var MockObject|MerchantApiClient */
	protected $client;

	/** @var MockObject|OptionsInterface */
	protected $options;

	/** @var MapiPromotionsService */
	protected $service;

	public function setUp(): void {
		parent::setUp();

		$this->client  = $this->createMock( MerchantApiClient::class );
		$this->options = $this->createMock( OptionsInterface::class );
		$this->options->method( 'get_merchant_id' )->willReturn( self::MERCHANT_ID );

		$this->service = new MapiPromotionsService( $this->client );
		$this->service->set_options_object( $this->options );
	}

	public function test_insert_promotion_posts_to_expected_path_and_body() {
		$promotion = [
			'promotionId'       => 'gla_15',
			'contentLanguage'   => 'en',
			'targetCountry'     => 'US',
			'redemptionChannel' => [ 'ONLINE' ],
			'attributes'        => [ 'genericRedemptionCode' => 'SAVE10' ],
		];

		$this->client->expects( $this->once() )
			->method( 'post' )
			->with(
				self::INSERT_PATH,
				[
					'dataSource' => self::DATA_SOURCE,
					'promotion'  => $promotion,
				]
			)
			->willReturn( $promotion );

		$response = $this->service->insert_promotion( self::DATA_SOURCE, $promotion );

		$this->assertSame( 'gla_15', $response['promotionId'] );
	}

	public function test_insert_promotion_propagates_merchant_api_exception() {
		$this->client->method( 'post' )
			->willThrowException( new MerchantApiException( 403, [], __METHOD__ ) );

		$this->expectException( MerchantApiException::class );

		$this->service->insert_promotion( self::DATA_SOURCE, [ 'promotionId' => 'gla_1' ] );
	}
}
