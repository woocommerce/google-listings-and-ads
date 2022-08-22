<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Ads;

use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AccountService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Ads;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsConversionAction;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\BillingSetupStatus;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Merchant;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Middleware;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\ExceptionWithResponseData;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\AdsAccountState;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\Container\Container;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class AccountServiceTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Ads
 *
 * @property MockObject|Ads                 $ads
 * @property MockObject|AdsConversionAction $conversion_action
 * @property MockObject|Merchant            $merchant
 * @property MockObject|Middleware          $middleware
 * @property MockObject|AdsAccountState     $state
 * @property AccountService                 $account
 * @property Container                      $container
 * @property OptionsInterface               $options
 */
class AccountServiceTest extends UnitTest {

	protected const TEST_ACCOUNT_ID        = 1234567890;
	protected const TEST_OLD_ACCOUNT_ID    = 2345678901;
	protected const TEST_MERCHANT_ID       = 78123456;
	protected const TEST_BILLING_URL       = 'https://domain.test/billing/setup/';
	protected const TEST_CURRENCY          = 'EUR';
	protected const TEST_ACCOUNTS          = [
		[
			'id'   => self::TEST_ACCOUNT_ID,
			'name' => 'Ads Account',
		],
		[
			'id'   => self::TEST_OLD_ACCOUNT_ID,
			'name' => 'Other Account',
		],
	];
	protected const TEST_STEP_DATA         = [
		'sub_account'       => true,
		'created_timestamp' => 1643833342,
	];
	protected const TEST_CONNECTED_DATA    = [
		'id'       => SELF::TEST_ACCOUNT_ID,
		'currency' => 'EUR',
		'symbol'   => '€',
		'status'   => 'connected',
	];
	protected const TEST_INCOMPLETE_DATA   = [
		'id'       => SELF::TEST_ACCOUNT_ID,
		'currency' => 'EUR',
		'symbol'   => '€',
		'status'   => 'incomplete',
		'step'     => 'billing'
	];
	protected const TEST_DISCONNECTED_DATA = [
		'id'       => 0,
		'currency' => null,
		'symbol'   => '$',
		'status'   => 'disconnected',
	];
	protected const TEST_CONVERSION_ACTION = [
		'id'   => 12345678,
		'name' => 'Test Action',
	];

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->ads               = $this->createMock( Ads::class );
		$this->conversion_action = $this->createMock( AdsConversionAction::class );
		$this->merchant          = $this->createMock( Merchant::class );
		$this->middleware        = $this->createMock( Middleware::class );
		$this->state             = $this->createMock( AdsAccountState::class );
		$this->options           = $this->createMock( OptionsInterface::class );

		$this->container = new Container();
		$this->container->share( Ads::class, $this->ads );
		$this->container->share( AdsConversionAction::class, $this->conversion_action );
		$this->container->share( Merchant::class, $this->merchant );
		$this->container->share( Middleware::class, $this->middleware );
		$this->container->share( AdsAccountState::class, $this->state );

		$this->account = new AccountService( $this->container );
		$this->account->set_options_object( $this->options );
	}

	public function test_get_accounts() {
		$this->ads->expects( $this->once() )
			->method( 'get_ads_accounts' )
			->willReturn( self::TEST_ACCOUNTS );

		$this->assertEquals( self::TEST_ACCOUNTS, $this->account->get_accounts() );
	}

	public function test_get_accounts_with_api_exception() {
		$this->ads->expects( $this->once() )
			->method( 'get_ads_accounts' )
			->willThrowException( new Exception( 'error' ) );

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'error' );
		$this->account->get_accounts();
	}

	public function test_get_connected_account() {
		$this->options->expects( $this->once() )
			->method( 'get_ads_id' )
			->willReturn( self::TEST_ACCOUNT_ID );

		$this->options->method( 'get' )
			->with( OptionsInterface::ADS_ACCOUNT_CURRENCY )
			->willReturn( self::TEST_CURRENCY );

		$this->assertEquals( self::TEST_CONNECTED_DATA, $this->account->get_connected_account() );
	}

	public function test_get_connected_account_incomplete() {
		$this->options->expects( $this->once() )
			->method( 'get_ads_id' )
			->willReturn( self::TEST_ACCOUNT_ID );

		$this->options->method( 'get' )
			->with( OptionsInterface::ADS_ACCOUNT_CURRENCY )
			->willReturn( self::TEST_CURRENCY );

		$this->state->expects( $this->once() )
			->method( 'last_incomplete_step' )
			->willReturn( 'billing' );

		$this->state->expects( $this->once() )
			->method( 'get_step_data' )
			->with( 'set_id' )
			->willReturn( self::TEST_STEP_DATA );

		$this->assertEquals(
			self::TEST_INCOMPLETE_DATA +
			self::TEST_STEP_DATA,
			$this->account->get_connected_account()
		);
	}

	public function test_get_disconnected_account() {
		$this->options->expects( $this->once() )
			->method( 'get_ads_id' )
			->willReturn( 0 );

		$this->assertEquals( self::TEST_DISCONNECTED_DATA, $this->account->get_connected_account() );
	}

	public function test_use_existing_account_already_connected() {
		$this->options->expects( $this->once() )
			->method( 'get_ads_id' )
			->willReturn( self::TEST_OLD_ACCOUNT_ID );

		$this->expectException( Exception::class );
		$this->expectExceptionMessage(
			sprintf(
				'Ads account %1$d already connected.',
				self::TEST_OLD_ACCOUNT_ID
			)
		);
		$this->account->use_existing_account( self::TEST_ACCOUNT_ID );
	}

	public function test_use_existing_account_same_account() {
		$this->options->expects( $this->once() )
			->method( 'get_ads_id' )
			->willReturn( self::TEST_ACCOUNT_ID );

		$this->state->expects( $this->once() )
			->method( 'get' )
			->willReturn(
				[
					'set_id' => [ 'status' => AdsAccountState::STEP_DONE ],
				]
			);

		$this->middleware->expects( $this->never() )
			->method( 'link_ads_account' );

		$this->state->expects( $this->never() )
			->method( 'update' );

		$this->account->use_existing_account( self::TEST_ACCOUNT_ID );
	}

	public function test_use_existing_account() {
		$this->options->expects( $this->once() )
			->method( 'get_ads_id' )
			->willReturn( 0 );

		$this->state->expects( $this->once() )
			->method( 'get' )
			->willReturn(
				[
					'set_id' => [ 'status' => AdsAccountState::STEP_PENDING ],
				]
			);

		$this->middleware->expects( $this->once() )
			->method( 'link_ads_account' )
			->with( self::TEST_ACCOUNT_ID );

		$this->state->expects( $this->once() )
			->method( 'update' )
			->with(
				[
					'set_id'  => [ 'status' => AdsAccountState::STEP_DONE ],
					'billing' => [ 'status' => AdsAccountState::STEP_DONE ],
				]
			);

		$this->account->use_existing_account( self::TEST_ACCOUNT_ID );
	}

	public function test_setup_account_step_set_id() {
		$this->options->expects( $this->once() )
			->method( 'get_ads_id' )
			->willReturn( 0 );

		$this->state->expects( $this->once() )
			->method( 'get' )
			->willReturn(
				[
					'set_id' => [ 'status' => AdsAccountState::STEP_PENDING ],
				]
			);

		$this->middleware->expects( $this->once() )
			->method( 'create_ads_account' )
			->willReturn( [ 'id' => self::TEST_ACCOUNT_ID ] );

		$this->state->expects( $this->once() )
			->method( 'update' );

		$this->assertEquals( [ 'id' => self::TEST_ACCOUNT_ID ], $this->account->setup_account() );
	}

	public function test_setup_account_step_billing() {
		$this->options->expects( $this->once() )
			->method( 'get_ads_id' )
			->willReturn( self::TEST_ACCOUNT_ID );

		$this->state->expects( $this->once() )
			->method( 'get' )
			->willReturn(
				[
					'billing' => [ 'status' => AdsAccountState::STEP_PENDING ],
				]
			);

		$this->ads->expects( $this->once() )
			->method( 'get_billing_status' )
			->willReturn( BillingSetupStatus::APPROVED );

		$this->assertEquals( [ 'id' => self::TEST_ACCOUNT_ID ], $this->account->setup_account() );
	}

	public function test_setup_account_step_billing_pending() {
		$this->options->expects( $this->once() )
			->method( 'get_ads_id' )
			->willReturn( self::TEST_ACCOUNT_ID );

		$this->state->expects( $this->once() )
			->method( 'get' )
			->willReturn(
				[
					'billing' => [ 'status' => AdsAccountState::STEP_PENDING ],
				]
			);

		$this->ads->expects( $this->once() )
			->method( 'get_billing_status' )
			->willReturn( BillingSetupStatus::PENDING );

		$this->expectException( ExceptionWithResponseData::class );
		$this->expectExceptionCode( 428 );
		$this->expectExceptionMessage( 'Billing setup must be completed.' );

		$this->account->setup_account();
	}

	public function test_setup_account_step_link_merchant() {
		$this->options->expects( $this->any() )
			->method( 'get_ads_id' )
			->willReturn( self::TEST_ACCOUNT_ID );

		$this->options->expects( $this->any() )
			->method( 'get_merchant_id' )
			->willReturn( self::TEST_MERCHANT_ID );

		$this->state->expects( $this->once() )
			->method( 'get' )
			->willReturn(
				[
					'link_merchant' => [ 'status' => AdsAccountState::STEP_PENDING ],
				]
			);

		$this->merchant->expects( $this->once() )
			->method( 'link_ads_id' )
			->with( self::TEST_ACCOUNT_ID );

		$this->ads->expects( $this->once() )
			->method( 'accept_merchant_link' )
			->with( self::TEST_MERCHANT_ID );

		$this->assertEquals( [ 'id' => self::TEST_ACCOUNT_ID ], $this->account->setup_account() );
	}

	public function test_setup_account_step_link_merchant_no_ads_id() {
		$this->options->expects( $this->any() )
			->method( 'get_ads_id' )
			->willReturn( 0 );

		$this->options->expects( $this->any() )
			->method( 'get_merchant_id' )
			->willReturn( self::TEST_MERCHANT_ID );

		$this->state->expects( $this->once() )
			->method( 'get' )
			->willReturn(
				[
					'link_merchant' => [ 'status' => AdsAccountState::STEP_PENDING ],
				]
			);

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'An Ads account must be connected' );

		$this->account->setup_account();
	}

	public function test_setup_account_step_link_merchant_no_merchant_id() {
		$this->options->expects( $this->any() )
			->method( 'get_merchant_id' )
			->willReturn( 0 );

		$this->state->expects( $this->once() )
			->method( 'get' )
			->willReturn(
				[
					'link_merchant' => [ 'status' => AdsAccountState::STEP_PENDING ],
				]
			);

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'A Merchant Center account must be connected' );

		$this->account->setup_account();
	}

	public function test_setup_account_step_conversion_action() {
		$this->options->expects( $this->once() )
			->method( 'get_ads_id' )
			->willReturn( self::TEST_ACCOUNT_ID );

		$this->state->expects( $this->once() )
			->method( 'get' )
			->willReturn(
				[
					'conversion_action' => [ 'status' => AdsAccountState::STEP_PENDING ],
				]
			);

		$this->conversion_action->expects( $this->once() )
			->method( 'create_conversion_action' )
			->willReturn( self::TEST_CONVERSION_ACTION );

		$this->options->expects( $this->once() )
			->method( 'update' )
			->with(
				OptionsInterface::ADS_CONVERSION_ACTION,
				self::TEST_CONVERSION_ACTION
			);

		$this->assertEquals( [ 'id' => self::TEST_ACCOUNT_ID ], $this->account->setup_account() );
	}

	public function test_setup_account_step_invalid() {
		$this->state->expects( $this->once() )
			->method( 'get' )
			->willReturn(
				[
					'invalid' => [ 'status' => AdsAccountState::STEP_PENDING ],
				]
			);

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Unknown ads account creation step invalid' );

		$this->account->setup_account();
	}

	public function test_get_billing_status_approved() {
		$this->ads->expects( $this->once() )
			->method( 'get_billing_status' )
			->willReturn( BillingSetupStatus::APPROVED );

		$this->assertEquals(
			[ 'status' => BillingSetupStatus::APPROVED ],
			$this->account->get_billing_status()
		);
	}

	public function test_get_billing_status_pending() {
		$this->ads->expects( $this->once() )
			->method( 'get_billing_status' )
			->willReturn( BillingSetupStatus::PENDING );

		$this->options->expects( $this->once() )
			->method( 'get' )
			->with( OptionsInterface::ADS_BILLING_URL )
			->willReturn( self::TEST_BILLING_URL );

		$this->assertEquals(
			[
				'status'      => BillingSetupStatus::PENDING,
				'billing_url' => self::TEST_BILLING_URL,
			],
			$this->account->get_billing_status()
		);
	}

	public function test_disconnect() {
		$this->options->expects( $this->exactly( 7 ) )
			->method( 'delete' )
			->withConsecutive(
				[ OptionsInterface::ADS_ACCOUNT_CURRENCY ],
				[ OptionsInterface::ADS_ACCOUNT_STATE ],
				[ OptionsInterface::ADS_BILLING_URL ],
				[ OptionsInterface::ADS_CONVERSION_ACTION ],
				[ OptionsInterface::ADS_ID ],
				[ OptionsInterface::ADS_SETUP_COMPLETED_AT ],
				[ OptionsInterface::CAMPAIGN_CONVERT_STATUS ]
			);

		$this->account->disconnect();
	}

}
