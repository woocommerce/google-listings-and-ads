<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\Ads;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\MerchantMetrics;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Ads\SetupCompleteController;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\AdsSetupCompleted;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\RESTControllerUnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\TrackingTrait;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class SetupCompleteControllerTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\Ads
 */
class SetupCompleteControllerTest extends RESTControllerUnitTest {

	use TrackingTrait;

	/** @var MockObject|MerchantMetrics $metrics */
	protected $metrics;

	/** @var SetupCompleteController $controller */
	protected $controller;

	protected $ads_setup_controller;

	protected const ROUTE = '/wc/gla/ads/setup/complete';

	public function setUp(): void {
		parent::setUp();

		$this->metrics              = $this->createMock( MerchantMetrics::class );
		$this->ads_setup_controller = new AdsSetupCompleted();
		$this->controller           = new SetupCompleteController( $this->server, $this->metrics );
		$this->controller->register();
	}

	public function test_setup_complete() {
		$this->metrics->expects( $this->once() )
			->method( 'get_campaign_count' )
			->willReturn( 1 );

		$this->expect_track_event(
			'ads_setup_completed',
			[
				'campaign_count' => 1,
			]
		);

		$response = $this->do_request( self::ROUTE, 'POST' );

		$this->assertEquals( 'success', $response->get_data()['status'] );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 1, did_action( 'woocommerce_gla_ads_setup_completed' ) );
	}

	public function test_gtg_is_enabled_post_onboarding() {
		// Mock $options object.
		$options = $this->createMock( \Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface::class );
		$options->method( 'get' )
			->willReturnMap(
				[
					[ \Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface::ADS_GTG_ENABLED, null, null ],
					[ \Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface::TOURS, [], [] ],
				]
			);

		$options->expects( $this->exactly( 3 ) )
			->method( 'update' )
			->withConsecutive(
				[ \Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface::ADS_SETUP_COMPLETED_AT, $this->isType( 'int' ) ],
				[ \Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface::ADS_GTG_ENABLED, true ],
				[
					\Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface::TOURS,
					[
						'google-tag-gateway-tour' => [
							'id'      => 'google-tag-gateway-tour',
							'checked' => true,
						],
					],
				]
			);

		$this->ads_setup_controller->set_options_object( $options );
		$this->ads_setup_controller->register();

		$this->do_request( self::ROUTE, 'POST' );
	}
}
