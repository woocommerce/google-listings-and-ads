<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Merchant;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Proxy as Middleware;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\SiteVerification;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter\AccountController;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\MerchantAccountState;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\RESTControllerUnitTest;
use Exception;
use Google\Service\ShoppingContent\Account;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class AccountControllerTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\MerchantCenter
 *
 * @since   x.x.x
 *
 * @property MockObject|Middleware            $middleware
 * @property MockObject|Merchant              $merchant
 * @property MockObject|MerchantCenterService $mc_service
 * @property MockObject|SiteVerification      $site_verification
 * @property MockObject|OptionsInterface      $options
 * @property AccountController                $controller
 * @property MerchantAccountState             $account_state
 */
class AccountControllerTest extends RESTControllerUnitTest {

	/**
	 * Routes that this endpoint creates.
	 *
	 * @var array
	 */
	protected $routes = [
		'/wc/gla/mc/accounts',
		'/wc/gla/mc/accounts/claim-overwrite',
		'/wc/gla/mc/accounts/switch-url',
		'/wc/gla/mc/connection',
		'/wc/gla/mc/setup',
	];

	/**
	 * The endpoint schema.
	 *
	 * @var array Keys are property names, values are supported context.
	 */
	protected $properties = [
		'id'         => [ 'view', 'edit' ],
		'subaccount' => [ 'view' ],
	];

	/**
	 * Runs before each test is executed.
	 */
	public function setUp() {
		parent::setUp();

		$this->middleware        = $this->createMock( Middleware::class );
		$this->merchant          = $this->createMock( Merchant::class );
		$this->mc_service        = $this->createMock( MerchantCenterService::class );
		$this->site_verification = $this->createMock( SiteVerification::class );
		$this->options           = $this->createMock( OptionsInterface::class );

		$this->account_state = new MerchantAccountState();
		$this->account_state->set_options_object( $this->options );

		$this->controller = new AccountController(
			$this->server,
			$this->middleware,
			$this->merchant,
			$this->account_state,
			$this->mc_service,
			$this->site_verification
		);
		$this->controller->set_options_object( $this->options );

		$this->register_test_routes();
	}

	public function test_get_accounts() {
		$accounts = [
			[
				'id'         => 12345,
				'subaccount' => true,
			],
			[
				'id'         => 23456,
				'subaccount' => false,
			],
		];

		$this->middleware->expects( $this->any() )
			->method( 'get_merchant_ids' )
			->willReturn( $accounts );

		$response = $this->do_request( '/wc/gla/mc/accounts', 'GET' );
	 	$this->assertExpectedResponse( $response, 200 );
	 	$this->assertEquals( $accounts, $response->data );
	}

	public function test_create_new_account() {
		$merchant_id = 12345;

		$this->options->expects( $this->any() )
			->method( 'get_merchant_id' )
			->willReturn( 0 );

		$this->middleware->expects( $this->any() )
			->method( 'create_merchant_account' )
			->willReturn( $merchant_id );

		$this->site_verification->expects( $this->any() )
			->method( 'insert' )
			->willReturn( true );

		$response = $this->do_request( '/wc/gla/mc/accounts', 'POST' );
		$this->assertExpectedResponse( $response, 200 );
		$this->assertEquals( $merchant_id, $response->data['id'] );
	}

	public function test_link_existing_account() {
		$merchant_id = 12345;

		$this->options->expects( $this->any() )
			->method( 'get_merchant_id' )
			->will( $this->onConsecutiveCalls( 0, $merchant_id ) );

		$this->site_verification->expects( $this->any() )
			->method( 'insert' )
			->willReturn( true );

		$response = $this->do_request(
			'/wc/gla/mc/accounts',
			'POST',
			[
				'id' => $merchant_id,
			]
		);
		$this->assertExpectedResponse( $response, 200 );
		$this->assertEquals( $merchant_id, $response->data['id'] );
	}

	public function test_claim_overwrite_required() {
		$merchant_id = 12345;

		$this->options->expects( $this->any() )
			->method( 'get_merchant_id' )
			->will( $this->onConsecutiveCalls( 0, $merchant_id ) );

		$this->expected_account_state(
			[
				'claim' => [
					'status'  => MerchantAccountState::STEP_PENDING,
					'message' => '',
					'data'    => [],
				],
			]
		);

		$this->merchant->expects( $this->any() )
			->method( 'claimwebsite' )
			->will(
				$this->throwException( new Exception( 'Error', 403 ) )
			);

		$response = $this->do_request( '/wc/gla/mc/accounts', 'POST' );
		$this->assertExpectedResponse( $response, 403 );
		$this->assertEquals( $merchant_id, $response->data['id'] );
		$this->assertEquals( $this->clean_site_url(), $response->data['website_url'] );
	}

	public function test_overwrite_claim_for_standalone_account() {
		$merchant_id = 12345;

		$this->options->expects( $this->any() )
			->method( 'get_merchant_id' )
			->will( $this->onConsecutiveCalls( 0, $merchant_id ) );

		$this->expected_account_state(
			[
				'set_id' => [
					'status'  => MerchantAccountState::STEP_DONE,
					'message' => '',
					'data'    => [
						'from_mca' => false,
					],
				],
				'claim' => [
					'status'  => MerchantAccountState::STEP_PENDING,
					'message' => '',
					'data'    => [],
				],
			]
		);

		$this->merchant->expects( $this->any() )
			->method( 'claimwebsite' )
			->will(
				$this->throwException( new Exception( 'Error', 403 ) )
			);

		$response = $this->do_request( '/wc/gla/mc/accounts', 'POST' );
		$this->assertExpectedResponse( $response, 406 );
		$this->assertEquals( $merchant_id, $response->data['id'] );
		$this->assertEquals( $this->clean_site_url(), $response->data['website_url'] );
	}

	public function test_already_claimed_url_is_different() {
		$merchant_id = 12345;

		$this->options->expects( $this->any() )
			->method( 'get_merchant_id' )
			->willReturn( 0 );

		$this->merchant->expects( $this->any() )
			->method( 'is_website_claimed' )
			->willReturn( true );

		$account = $this->createMock( Account::class );
		$account->expects( $this->any() )
			->method( 'getWebsiteUrl' )
			->willReturn( 'https://accounturl.test' );

		$this->merchant->expects( $this->any() )
			->method( 'get_account' )
			->willReturn( $account );

		$response = $this->do_request(
			'/wc/gla/mc/accounts',
			'POST',
			[
				'id' => $merchant_id,
			]
		);
		$this->assertExpectedResponse( $response, 409 );
		$this->assertEquals( $merchant_id, $response->data['id'] );
		$this->assertEquals( 'accounturl.test', $response->data['claimed_url'] );
		$this->assertEquals( $this->clean_site_url(), $response->data['new_url'] );
	}

	public function test_wait_for_account_create_to_complete() {
		$merchant_id = 12345;

		$this->options->expects( $this->any() )
			->method( 'get_merchant_id' )
			->willReturn( 0 );

		$this->expected_account_state(
			[
				'link' => [
					'status'  => MerchantAccountState::STEP_PENDING,
					'message' => '',
					'data'    => [],
				],
			]
		);

		$this->middleware->expects( $this->any() )
			->method( 'link_merchant_to_mca' )
			->will(
				$this->throwException( new Exception( 'Error', 401 ) )
			);

		$response = $this->do_request( '/wc/gla/mc/accounts', 'POST' );
		$this->assertExpectedResponse( $response, 503 );
		$this->assertArrayHasKey( 'retry_after', $response->data );
	}

	public function test_account_already_connected() {
		$merchant_id = 12345;

		$this->options->expects( $this->any() )
			->method( 'get_merchant_id' )
			->willReturn( $merchant_id );

		$this->mc_service->expects( $this->any() )
			->method( 'is_setup_complete' )
			->willReturn( true );

		$response = $this->do_request( '/wc/gla/mc/accounts', 'POST' );
		$this->assertExpectedResponse( $response, 400 );
		$this->assertEquals( $merchant_id, $response->data['id'] );
		$this->assertArrayHasKey( 'message', $response->data );
	}

	public function test_invalid_claim_overwrite() {
		$merchant_id = 12345;

		$response = $this->do_request(
			'/wc/gla/mc/accounts/claim-overwrite',
			'POST',
			[
				'id' => $merchant_id,
			]
		);
		$this->assertExpectedResponse( $response, 400 );
	}

	public function test_claim_overwrite() {
		$merchant_id = 12345;

		$this->expected_account_state(
			[
				'set_id' => [
					'status'  => MerchantAccountState::STEP_DONE,
				],
				'claim' => [
					'status'  => MerchantAccountState::STEP_PENDING,
					'message' => '',
					'data'    => [
						'overwrite_required' => true,
					],
				],
			]
		);

		$this->options->expects( $this->any() )
			->method( 'get_merchant_id' )
			->willReturn( $merchant_id );

		$response = $this->do_request(
			'/wc/gla/mc/accounts/claim-overwrite',
			'POST',
			[
				'id' => $merchant_id,
			]
		);
		$this->assertExpectedResponse( $response, 200 );
		$this->assertEquals( $merchant_id, $response->data['id'] );
	}

	public function test_invalid_switch_url() {
		$merchant_id = 12345;

		$response = $this->do_request(
			'/wc/gla/mc/accounts/switch-url',
			'POST',
			[
				'id' => $merchant_id,
			]
		);
		$this->assertExpectedResponse( $response, 400 );
	}

	public function test_switch_url() {
		$merchant_id = 12345;

		$this->expected_account_state(
			[
				'set_id' => [
					'status'  => $this->onConsecutiveCalls(
						MerchantAccountState::STEP_PENDING,
						MerchantAccountState::STEP_DONE
					),
					'message' => '',
					'data'    => [
						'old_url' => 'oldurl.test',
					],
				],
			]
		);

		$this->options->expects( $this->any() )
			->method( 'get_merchant_id' )
			->willReturn( $merchant_id );

		$response = $this->do_request(
			'/wc/gla/mc/accounts/switch-url',
			'POST',
			[
				'id' => $merchant_id,
			]
		);
		$this->assertExpectedResponse( $response, 200 );
		$this->assertEquals( $merchant_id, $response->data['id'] );
	}

	public function test_connection_status() {
		$status = [
			'id'     => '12345',
			'status' => 'connected',
		];
		$this->mc_service->expects( $this->any() )
			->method( 'get_connected_status' )
			->willReturn( $status );

		$response = $this->do_request( '/wc/gla/mc/connection', 'GET' );
		$this->assertExpectedResponse( $response, 200 );
		$this->assertEquals( $status, $response->data );
	}

	public function test_disconnect() {
		$response = $this->do_request( '/wc/gla/mc/connection', 'DELETE' );
		$this->assertExpectedResponse( $response, 200 );
		$this->assertEquals( 'success', $response->data['status'] );
	}

	public function test_is_setup() {
		$status = [
			'status' => 'complete',
		];
		$this->mc_service->expects( $this->any() )
			->method( 'get_setup_status' )
			->willReturn( $status );

		$response = $this->do_request( '/wc/gla/mc/setup', 'GET' );
		$this->assertExpectedResponse( $response, 200 );
		$this->assertEquals( $status, $response->data );
	}

	protected function clean_site_url(): string {
		return preg_replace( '#^https?://#', '', untrailingslashit( site_url() ) );
	}

	protected function expected_account_state( array $state ) {
		$this->options->expects( $this->any() )
			->method( 'get' )
			->will(
				$this->returnCallback(
					function( $arg ) use ( $state ) {
						if ( OptionsInterface::MERCHANT_ACCOUNT_STATE === $arg ) {
							return $state;
						}
					}
				)
			);
	}
}
