<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Merchant;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Proxy as Middleware;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\SiteVerification;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter\AccountController;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\MerchantAccountState;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\RESTControllerUnitTest;
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
 * @property MockObject|MerchantAccountState  $account_state
 * @property MockObject|MerchantCenterService $mc_service
 * @property MockObject|SiteVerification      $site_verification
 * @property AccountController                $controller
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
		$this->account_state     = $this->createMock( MerchantAccountState::class );
		$this->mc_service        = $this->createMock( MerchantCenterService::class );
		$this->site_verification = $this->createMock( SiteVerification::class );

		$this->controller = new AccountController(
			$this->server,
			$this->middleware,
			$this->merchant,
			$this->account_state,
			$this->mc_service,
			$this->site_verification
		);

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
			->willReturn(  $accounts );

		$response = $this->do_request( '/wc/gla/mc/accounts', 'GET' );
	 	$this->assertExpectedResponse( $response, 200 );
	 	$this->assertEquals( $accounts, $response->data );
	}

}
