<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\Ads;

use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AdsService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Ads;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsConversionAction;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\BillingSetupStatus;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Merchant;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Proxy as Middleware;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Ads\AccountController;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\AdsAccountState;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\RESTControllerUnitTest;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class AccountControllerTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\Ads
 *
 * @property MockObject|Middleware          $middleware
 * @property MockObject|Ads                 $ads,
 * @property MockObject|AdsService          $ads_service,
 * @property MockObject|AdsConversionAction $ads_conversion_action,
 * @property MockObject|Merchant            $merchant
 * @property MockObject|OptionsInterface    $options
 * @property AccountController              $controller
 * @property AdsAccountState                $account_state
 */
class AccountControllerTest extends RESTControllerUnitTest {

	/**
	 * Routes that this endpoint creates.
	 *
	 * @var array
	 */
	protected $routes = [
		'/wc/gla/ads/accounts',
		'/wc/gla/ads/connection',
		'/wc/gla/ads/billing-status',
	];

	/**
	 * The endpoint schema.
	 *
	 * @var array Keys are property names, values are supported context.
	 */
	protected $properties = [
		'id'          => [ 'view', 'edit' ],
		'billing_url' => [ 'view', 'edit' ],
	];

	/**
	 * Runs before each test is executed.
	 */
	public function setUp() {
		parent::setUp();

		$this->middleware            = $this->createMock( Middleware::class );
		$this->ads                   = $this->createMock( Ads::class );
		$this->ads_service           = $this->createMock( AdsService::class );
		$this->ads_conversion_action = $this->createMock( AdsConversionAction::class );
		$this->merchant              = $this->createMock( Merchant::class );
		$this->options               = $this->createMock( OptionsInterface::class );

		$this->account_state = new AdsAccountState();
		$this->account_state->set_options_object( $this->options );

		$this->controller = new AccountController(
			$this->server,
			$this->middleware,
			$this->ads,
			$this->account_state,
			$this->ads_service,
			$this->ads_conversion_action,
			$this->merchant
		);
		$this->controller->set_options_object( $this->options );

		$this->register_test_routes();
	}

	public function test_get_accounts() {
		$accounts = [
			12345,
			23456,
		];

		$this->middleware->expects( $this->any() )
			->method( 'get_ads_account_ids' )
			->willReturn( $accounts );

		$response = $this->do_request( '/wc/gla/ads/accounts', 'GET' );
	 	$this->assertExpectedResponse( $response, 200 );
	 	$this->assertEquals( $accounts, $response->data );
	}

	public function test_unable_to_get_accounts() {
		$this->middleware->expects( $this->any() )
	 		->method( 'get_ads_account_ids' )
	 		->will(
				$this->throwException( new Exception( 'Error', 403 ) )
			);

		$response = $this->do_request( '/wc/gla/ads/accounts', 'GET' );
		$this->assertExpectedResponse( $response, 403 );
	}

	public function test_create_new_account() {
		$ads_id = 12345;

		$this->options->expects( $this->any() )
			->method( 'get_ads_id' )
			->willReturn( 0 );

		$this->middleware->expects( $this->any() )
			->method( 'create_ads_account' )
			->willReturn(
				[
					'id'          => $ads_id,
					'billing_url' => 'https://billingurl.test',
				]
			);

		$response = $this->do_request( '/wc/gla/ads/accounts', 'POST' );
		$this->assertExpectedResponse( $response, 428 );
		$this->assertEquals( BillingSetupStatus::UNKNOWN, $response->data['billing_status'] );
	}

	public function test_continue_create_new_account() {
		$ads_id = 12345;

		$this->options->expects( $this->any() )
			->method( 'get_ads_id' )
			->willReturn( $ads_id );

		$this->ads->expects( $this->any() )
			->method( 'get_billing_status' )
			->willReturn( BillingSetupStatus::APPROVED );

		$this->options->expects( $this->any() )
			->method( 'get_merchant_id' )
			->willReturn( 23456 );

		$this->expected_account_state(
			[
				'set_id'            => [
					'status' => AdsAccountState::STEP_DONE,
				],
				'billing'           => [
					'status' => AdsAccountState::STEP_PENDING,
				],
				'link_merchant'     => [
					'status' => AdsAccountState::STEP_PENDING,
				],
				'conversion_action' => [
					'status' => AdsAccountState::STEP_PENDING,
				],
			],
		);

		$response = $this->do_request( '/wc/gla/ads/accounts', 'POST' );
		$this->assertExpectedResponse( $response, 200 );
		$this->assertEquals( $ads_id, $response->data['id'] );
	}

	public function test_link_existing_account() {
		$ads_id = 12345;

		$this->options->expects( $this->any() )
			->method( 'get_ads_id' )
			->will( $this->onConsecutiveCalls( 0, $ads_id, $ads_id, $ads_id ) );

		$this->options->expects( $this->any() )
			->method( 'get_merchant_id' )
			->willReturn( 23456 );

		$this->expected_account_state(
			[
				[
					'set_id'  => [
						'status' => AdsAccountState::STEP_PENDING,
					],
					'billing' => [
						'status' => AdsAccountState::STEP_PENDING,
					],
				],
				[
					'set_id'            => [
						'status' => AdsAccountState::STEP_DONE,
					],
					'billing'           => [
						'status' => AdsAccountState::STEP_DONE,
					],
					'link_merchant'     => [
						'status' => AdsAccountState::STEP_PENDING,
					],
					'conversion_action' => [
						'status' => AdsAccountState::STEP_PENDING,
					],
				],
			],
			true
		);

		$response = $this->do_request(
			'/wc/gla/ads/accounts',
			'POST',
			[
				'id' => $ads_id,
			]
		);
		$this->assertExpectedResponse( $response, 200 );
		$this->assertEquals( $ads_id, $response->data['id'] );
	}

	public function test_continue_link_existing_account() {
		$ads_id = 12345;

		$this->options->expects( $this->any() )
					  ->method( 'get_ads_id' )
					  ->willReturn( $ads_id );

		$this->ads->expects( $this->any() )
				  ->method( 'get_billing_status' )
				  ->willReturn( BillingSetupStatus::APPROVED );

		$this->options->expects( $this->any() )
					  ->method( 'get_merchant_id' )
					  ->willReturn( 23456 );

		$this->expected_account_state(
			[
				'set_id'            => [
					'status' => AdsAccountState::STEP_DONE,
				],
				'billing'           => [
					'status' => AdsAccountState::STEP_PENDING,
				],
				'link_merchant'     => [
					'status' => AdsAccountState::STEP_PENDING,
				],
				'conversion_action' => [
					'status' => AdsAccountState::STEP_PENDING,
				],
			],
		);

		$response = $this->do_request(
			'/wc/gla/ads/accounts',
			'POST',
			[ 'id' => $ads_id ]
		);
		$this->assertExpectedResponse( $response, 200 );
		$this->assertEquals( $ads_id, $response->data['id'] );
	}

	public function test_continue_link_new_account() {
		$ads_id = 12345;

		$this->options->expects( $this->any() )
					  ->method( 'get_ads_id' )
					  ->willReturn( $ads_id );

		$this->ads->expects( $this->any() )
				  ->method( 'get_billing_status' )
				  ->willReturn( BillingSetupStatus::APPROVED );

		$this->options->expects( $this->any() )
					  ->method( 'get_merchant_id' )
					  ->willReturn( 23456 );

		$this->expected_account_state(
			[
				'set_id'            => [
					'status' => AdsAccountState::STEP_PENDING,
				],
				'billing'           => [
					'status' => AdsAccountState::STEP_PENDING,
				],
				'link_merchant'     => [
					'status' => AdsAccountState::STEP_PENDING,
				],
				'conversion_action' => [
					'status' => AdsAccountState::STEP_PENDING,
				],
			],
		);

		$response = $this->do_request( '/wc/gla/ads/accounts', 'POST' );
		$this->assertExpectedResponse( $response, 200 );
		$this->assertEquals( $ads_id, $response->data['id'] );
	}

	public function test_link_invalid_merchant() {
		$this->options->expects( $this->any() )
					  ->method( 'get_merchant_id' )
					  ->willReturn( 0 );

		$this->expected_account_state(
			[
				'set_id'        => [
					'status' => AdsAccountState::STEP_DONE,
				],
				'billing'       => [
					'status' => AdsAccountState::STEP_DONE,
				],
				'link_merchant' => [
					'status' => AdsAccountState::STEP_PENDING,
				],
			]
		);

		$response = $this->do_request( '/wc/gla/ads/accounts', 'POST' );
		$this->assertExpectedResponse( $response, 400 );
	}

	public function test_link_merchant_with_ads_account() {
		$this->options->expects( $this->any() )
					  ->method( 'get_merchant_id' )
					  ->willReturn( 23456 );

		$this->options->expects( $this->any() )
					  ->method( 'get_ads_id' )
					  ->willReturn( 0 );

		$this->expected_account_state(
			[
				'set_id'        => [
					'status' => AdsAccountState::STEP_DONE,
				],
				'billing'       => [
					'status' => AdsAccountState::STEP_DONE,
				],
				'link_merchant' => [
					'status' => AdsAccountState::STEP_PENDING,
				],
			]
		);

		$response = $this->do_request( '/wc/gla/ads/accounts', 'POST' );
		$this->assertExpectedResponse( $response, 400 );
	}

	public function test_invalid_step() {
	 	$ads_id = 12345;

		$this->options->expects( $this->any() )
			->method( 'get_ads_id' )
			->willReturn( $ads_id );

		$this->expected_account_state(
			[
				'invalid' => [
					'status' => AdsAccountState::STEP_PENDING,
				],
			]
		);

		$response = $this->do_request( '/wc/gla/ads/accounts', 'POST' );
		$this->assertExpectedResponse( $response, 400 );
	}

	public function test_account_already_connected() {
		$ads_id       = 12345;
		$connected_id = 23456;

		$this->options->expects( $this->any() )
			->method( 'get_ads_id' )
			->willReturn( $connected_id );

		$response = $this->do_request(
			'/wc/gla/ads/accounts',
			'POST',
			[
				'id' => $ads_id,
			]
		);
		$this->assertExpectedResponse( $response, 400 );
		$this->assertStringContainsString( $connected_id, $response->data['message'] );
	}

	public function test_connection_status() {
		$status = [
			'id'       => '12345',
			'currency' => 'USD',
			'status'   => 'connected',
		];
		$this->middleware->expects( $this->any() )
			->method( 'get_connected_ads_account' )
			->willReturn( $status );

		$response = $this->do_request( '/wc/gla/ads/connection', 'GET' );
		$this->assertExpectedResponse( $response, 200 );
		$this->assertEquals( $status, $response->data );
	}

	public function test_disconnect() {
		$response = $this->do_request( '/wc/gla/ads/connection', 'DELETE' );
		$this->assertExpectedResponse( $response, 200 );
		$this->assertEquals( 'success', $response->data['status'] );
	}

	public function test_billing_status_approved() {
		$this->ads->expects( $this->any() )
			->method( 'get_billing_status' )
			->willReturn( BillingSetupStatus::APPROVED );

		$response = $this->do_request( '/wc/gla/ads/billing-status', 'GET' );
		$this->assertExpectedResponse( $response, 200 );
		$this->assertEquals( BillingSetupStatus::APPROVED, $response->data['status'] );
	}

	public function test_billing_status_pending() {
		$billing_url = 'https://billingurl.test';

		$this->ads->expects( $this->any() )
			->method( 'get_billing_status' )
			->willReturn( BillingSetupStatus::PENDING );

		$this->options->expects( $this->any() )
			->method( 'get' )
			->will(
				$this->returnCallback(
					function( $arg ) use ( $billing_url ) {
						if ( OptionsInterface::ADS_BILLING_URL === $arg ) {
							return $billing_url;
						}
					}
				)
			);

		$response = $this->do_request( '/wc/gla/ads/billing-status', 'GET' );
		$this->assertExpectedResponse( $response, 200 );
		$this->assertEquals( BillingSetupStatus::PENDING, $response->data['status'] );
		$this->assertEquals( $billing_url, $response->data['billing_url'] );
	}

	public function test_billing_status_failure() {
		$this->ads->expects( $this->any() )
			->method( 'get_billing_status' )
	 		->will(
				$this->throwException( new Exception( 'Error', 403 ) )
			);

		$response = $this->do_request( '/wc/gla/ads/billing-status', 'GET' );
		$this->assertExpectedResponse( $response, 403 );
	}

	/**
	 * Mock the ads account state to return specific state values.
	 * Parameter $state must contain states with a numeric index if $consecutive_states is true.
	 *
	 * @param array $state              Expected state values to return.
	 * @param bool  $consecutive_states If we expect consecutive states.
	 */
	protected function expected_account_state( array $state, bool $consecutive_states = false ) {
		$callback = function( $arg ) use ( $state, $consecutive_states ) {
			static $consecutive_index = 0;
			if ( OptionsInterface::ADS_ACCOUNT_STATE === $arg ) {
				return $consecutive_states ? $state[ $consecutive_index++ ] : $state;
			}
		};

		$this->options->expects( $this->any() )
			->method( 'get' )
			->will( $this->returnCallback( $callback ) );
	}
}
