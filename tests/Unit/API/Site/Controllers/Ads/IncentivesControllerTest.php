<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\Ads;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionSchedulerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsIncentives;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Ads\IncentivesController;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\ExceptionWithResponseData;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\CheckUnclaimedIncentive;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\StartHook;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\RESTControllerUnitTest;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;

/**
 * Class IncentivesControllerTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\Ads
 */
class IncentivesControllerTest extends RESTControllerUnitTest {

	/** @var MockObject|AdsIncentives $ads_incentives */
	protected $ads_incentives;

	/** @var MockObject|WC $wc */
	protected $wc;

	/** @var MockObject|ActionSchedulerInterface $action_scheduler */
	protected $action_scheduler;

	/** @var MockObject|CheckUnclaimedIncentive $check_unclaimed_incentive */
	protected $check_unclaimed_incentive;

	/** @var MockObject|OptionsInterface $options */
	protected $options;

	/** @var IncentivesController $controller */
	protected $controller;

	protected const ROUTE_INCENTIVES = '/wc/gla/ads/incentives';

	protected const EMPTY_RESPONSE = [
		'type'                  => 'CYO_INCENTIVE',
		'termsAndConditionsUrl' => '',
		'incentives'            => [],
	];

	public function setUp(): void {
		parent::setUp();

		$this->ads_incentives            = $this->createMock( AdsIncentives::class );
		$this->wc                        = $this->createMock( WC::class );
		$this->action_scheduler          = $this->createMock( ActionSchedulerInterface::class );
		$this->check_unclaimed_incentive = $this->createMock( CheckUnclaimedIncentive::class );
		$this->options                   = $this->createMock( OptionsInterface::class );

		$this->wc->method( 'get_base_country' )->willReturn( 'GB' );
		$this->check_unclaimed_incentive->method( 'get_start_hook' )
			->willReturn( new StartHook( 'gla/jobs/check_unclaimed_incentive/start' ) );

		$this->controller = new IncentivesController(
			$this->server,
			$this->ads_incentives,
			$this->wc,
			$this->action_scheduler,
			$this->check_unclaimed_incentive
		);
		$this->controller->set_options_object( $this->options );
		$this->controller->register();
	}

	public function test_get_incentives_success() {
		$incentives = [
			'type'                  => 'CYO_INCENTIVE',
			'termsAndConditionsUrl' => 'https://ads.google.com/intl/en_uk/home/terms-and-conditions/incentives/?bc=UK',
			'incentives'            => [
				[
					'id'                    => '2378556534',
					'type'                  => 'ACQUISITION',
					'offer'                 => 'low',
					'termsAndConditionsUrl' => 'https://ads.google.com/intl/en_uk/home/terms-and-conditions/incentives/?bc=UK&bid=nickel',
					'requirement'           => [
						'spend' => [
							'awardAmount'    => [
								'currencyCode' => 'GBP',
								'units'        => '800',
							],
							'requiredAmount' => [
								'currencyCode' => 'GBP',
								'units'        => '1250',
							],
						],
					],
				],
				[
					'id'                    => '1995402192',
					'type'                  => 'ACQUISITION',
					'offer'                 => 'medium',
					'termsAndConditionsUrl' => 'https://ads.google.com/intl/en_uk/home/terms-and-conditions/incentives/?bc=UK&bid=sodium',
					'requirement'           => [
						'spend' => [
							'awardAmount'    => [
								'currencyCode' => 'GBP',
								'units'        => '1600',
							],
							'requiredAmount' => [
								'currencyCode' => 'GBP',
								'units'        => '3200',
							],
						],
					],
				],
				[
					'id'                    => '7056154833',
					'type'                  => 'ACQUISITION',
					'offer'                 => 'high',
					'termsAndConditionsUrl' => 'https://ads.google.com/intl/en_uk/home/terms-and-conditions/incentives/?bc=UK&bid=technetium',
					'requirement'           => [
						'spend' => [
							'awardAmount'    => [
								'currencyCode' => 'GBP',
								'units'        => '2500',
							],
							'requiredAmount' => [
								'currencyCode' => 'GBP',
								'units'        => '5000',
							],
						],
					],
				],
			],
		];

		$this->ads_incentives->expects( $this->once() )
			->method( 'fetch_incentives' )
			->willReturn( $incentives );

		$response = $this->do_request( self::ROUTE_INCENTIVES, 'GET' );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertSame( $incentives, $response->get_data() );
	}

	public function test_get_incentives_success_usd() {
		$incentives = [
			'type'                  => 'CYO_INCENTIVE',
			'termsAndConditionsUrl' => 'https://ads.google.com/intl/en_us/home/terms-and-conditions/incentives/?bc=US',
			'incentives'            => [
				[
					'id'                    => '1234567890',
					'type'                  => 'ACQUISITION',
					'offer'                 => 'low',
					'termsAndConditionsUrl' => 'https://ads.google.com/intl/en_us/home/terms-and-conditions/incentives/?bc=US&bid=low',
					'requirement'           => [
						'spend' => [
							'awardAmount'    => [
								'currencyCode' => 'USD',
								'units'        => '500',
							],
							'requiredAmount' => [
								'currencyCode' => 'USD',
								'units'        => '500',
							],
						],
					],
				],
				[
					'id'                    => '2345678901',
					'type'                  => 'ACQUISITION',
					'offer'                 => 'medium',
					'termsAndConditionsUrl' => 'https://ads.google.com/intl/en_us/home/terms-and-conditions/incentives/?bc=US&bid=medium',
					'requirement'           => [
						'spend' => [
							'awardAmount'    => [
								'currencyCode' => 'USD',
								'units'        => '1000',
							],
							'requiredAmount' => [
								'currencyCode' => 'USD',
								'units'        => '1500',
							],
						],
					],
				],
				[
					'id'                    => '3456789012',
					'type'                  => 'ACQUISITION',
					'offer'                 => 'high',
					'termsAndConditionsUrl' => 'https://ads.google.com/intl/en_us/home/terms-and-conditions/incentives/?bc=US&bid=high',
					'requirement'           => [
						'spend' => [
							'awardAmount'    => [
								'currencyCode' => 'USD',
								'units'        => '1500',
							],
							'requiredAmount' => [
								'currencyCode' => 'USD',
								'units'        => '3000',
							],
						],
					],
				],
			],
		];

		$this->ads_incentives->expects( $this->once() )
			->method( 'fetch_incentives' )
			->willReturn( $incentives );

		$response = $this->do_request( self::ROUTE_INCENTIVES, 'GET' );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertSame( $incentives, $response->get_data() );
	}

	public function test_get_incentives_empty_response() {
		$this->ads_incentives->expects( $this->once() )
			->method( 'fetch_incentives' )
			->willReturn( self::EMPTY_RESPONSE );

		$response = $this->do_request( self::ROUTE_INCENTIVES, 'GET' );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertSame( self::EMPTY_RESPONSE, $response->get_data() );
	}

	public function test_get_incentives_no_incentive_type() {
		$no_incentive = [
			'type'                  => 'NO_INCENTIVE',
			'termsAndConditionsUrl' => '',
			'incentives'            => [],
		];

		$this->ads_incentives->expects( $this->once() )
			->method( 'fetch_incentives' )
			->willReturn( $no_incentive );

		$response = $this->do_request( self::ROUTE_INCENTIVES, 'GET' );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertSame( 'NO_INCENTIVE', $response->get_data()['type'] );
		$this->assertEmpty( $response->get_data()['incentives'] );
	}

	public function test_apply_incentive_success() {
		$this->ads_incentives->expects( $this->once() )
			->method( 'apply_incentive' )
			->with( '2378556534', 'GB' )
			->willReturn(
				[
					'coupon_code'   => 'abc123',
					'creation_time' => '2026-03-15 15:33:21',
				]
			);

		$response = $this->do_request(
			self::ROUTE_INCENTIVES,
			'POST',
			[ 'id' => '2378556534' ]
		);

		$this->assertEquals( 200, $response->get_status() );
		$this->assertSame( 'abc123', $response->get_data()['coupon_code'] );
		$this->assertSame( '2026-03-15 15:33:21', $response->get_data()['creation_time'] );
	}

	public function test_apply_incentive_api_exception() {
		$this->ads_incentives->expects( $this->once() )
			->method( 'apply_incentive' )
			->willThrowException(
				new ExceptionWithResponseData(
					'Error applying incentive: PERMISSION_DENIED',
					403,
					null,
					[ 'errors' => [ 'PERMISSION_DENIED' => 'The caller does not have permission' ] ]
				)
			);

		$this->options->expects( $this->once() )
			->method( 'update' )
			->with( OptionsInterface::ADS_INCENTIVE_APPLY_ERROR, 'error' );

		$this->action_scheduler->expects( $this->once() )
			->method( 'schedule_immediate' )
			->with( 'gla/jobs/check_unclaimed_incentive/start' );

		$response = $this->do_request(
			self::ROUTE_INCENTIVES,
			'POST',
			[ 'id' => '2378556534' ]
		);

		$this->assertEquals( 403, $response->get_status() );
		$this->assertArrayHasKey( 'errors', $response->get_data() );
	}

	public function test_apply_incentive_missing_id() {
		$response = $this->do_request(
			self::ROUTE_INCENTIVES,
			'POST',
			[]
		);

		$this->assertEquals( 400, $response->get_status() );
	}

	public function test_apply_incentive_failure_sets_flag_and_schedules_job() {
		$this->ads_incentives->expects( $this->once() )
			->method( 'apply_incentive' )
			->willThrowException( new RuntimeException( 'Unexpected API failure' ) );

		// Failure path: error flag must be raised.
		$this->options->expects( $this->once() )
			->method( 'update' )
			->with( OptionsInterface::ADS_INCENTIVE_APPLY_ERROR, 'error' );

		// Background job must be queued via the start hook.
		$this->action_scheduler->expects( $this->once() )
			->method( 'schedule_immediate' )
			->with( 'gla/jobs/check_unclaimed_incentive/start' );

		$response = $this->do_request(
			self::ROUTE_INCENTIVES,
			'POST',
			[ 'id' => '2378556534' ]
		);

		$this->assertEquals( 500, $response->get_status() );
	}
}
