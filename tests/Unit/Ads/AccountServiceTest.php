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
use Automattic\WooCommerce\GoogleListingsAndAds\Options\MerchantAccountState;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\TransientsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\Container\Container;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Connection;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class AccountServiceTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Ads
 */
class AccountServiceTest extends UnitTest {

	/** @var MockObject|Ads $ads */
	protected $ads;

	/** @var MockObject|AdsConversionAction $conversion_action */
	protected $conversion_action;

	/** @var MockObject|Merchant $merchant */
	protected $merchant;

	/** @var MockObject|Middleware $middleware */
	protected $middleware;

	/** @var MockObject|AdsAccountState $state */
	protected $state;

	/** @var MockObject|MerchantAccountState $merchant_state */
	protected $merchant_state;

	/** @var MockObject|TransientsInterface $transients */
	protected $transients;

	/** @var AccountService $account */
	protected $account;

	/** @var Container $container */
	protected $container;

	/** @var OptionsInterface $options */
	protected $options;

	/** @var Connection $connection */
	protected $connection;

	protected const TEST_ACCOUNT_ID        = 1234567890;
	protected const TEST_ACCOUNT_OCID      = 9876543210;
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
		'id'       => self::TEST_ACCOUNT_ID,
		'currency' => 'EUR',
		'symbol'   => '€',
		'status'   => 'connected',
	];
	protected const TEST_INCOMPLETE_DATA   = [
		'id'       => self::TEST_ACCOUNT_ID,
		'currency' => 'EUR',
		'symbol'   => '€',
		'status'   => 'incomplete',
		'step'     => 'billing',
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
		$this->merchant_state    = $this->createMock( MerchantAccountState::class );
		$this->options           = $this->createMock( OptionsInterface::class );
		$this->transients        = $this->createMock( TransientsInterface::class );
		$this->connection        = $this->createMock( Connection::class );

		$this->container = new Container();
		$this->container->share( Ads::class, $this->ads );
		$this->container->share( AdsConversionAction::class, $this->conversion_action );
		$this->container->share( Merchant::class, $this->merchant );
		$this->container->share( Middleware::class, $this->middleware );
		$this->container->share( AdsAccountState::class, $this->state );
		$this->container->share( MerchantAccountState::class, $this->merchant_state );
		$this->container->share( TransientsInterface::class, $this->transients );
		$this->container->share( Connection::class, $this->connection );

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

	public function test_setup_account_step_account_access() {
		// Mock return values
		$this->options->method( 'get' )
			->with( OptionsInterface::ADS_BILLING_URL, '' )
			->willReturn( self::TEST_BILLING_URL );

		$this->connection->method( 'get_status' )
			->willReturn( [ 'email' => 'test@domain.com' ] );

		$this->ads->method( 'has_access' )
			->with( 'test@domain.com' )
			->willReturn( true );

		// Expectations.
		$this->options->expects( $this->any() )
			->method( 'get_ads_id' )
			->willReturn( self::TEST_ACCOUNT_ID );

		$this->state->expects( $this->once() )
			->method( 'get' )
			->willReturn(
				[
					'account_access'    => [ 'status' => null ],
					'conversion_action' => [ 'status' => null ],
				]
			);

		// Account access should be marked as completed.
		$this->state->expects( $this->once() )
			->method( 'complete_step' )
			->with( 'account_access' );

		// The conversion action should run.
		$this->conversion_action->expects( $this->once() )
			->method( 'create_conversion_action' );

		$this->account->setup_account();
	}

	public function test_setup_account_step_account_access_without_access() {
		// Mock return values
		$this->options->method( 'get' )
			->with( OptionsInterface::ADS_BILLING_URL, '' )
			->willReturn( self::TEST_BILLING_URL );

		$this->connection->method( 'get_status' )
			->willReturn( [ 'email' => 'test@domain.com' ] );

		$this->ads->method( 'has_access' )
			->with( 'test@domain.com' )
			->willReturn( false );

		// Expectations.
		$this->options->expects( $this->any() )
			->method( 'get_ads_id' )
			->willReturn( self::TEST_ACCOUNT_ID );

		$this->state->expects( $this->once() )
			->method( 'get' )
			->willReturn(
				[
					'account_access'    => [ 'status' => null ],
					'conversion_action' => [ 'status' => null ],
				]
			);

		$this->expectException( ExceptionWithResponseData::class );
		$this->expectExceptionCode( 428 );
		$this->expectExceptionMessage( 'Account must be accepted before completing setup.' );

		// The conversion action should not be run.
		$this->conversion_action->expects( $this->never() )
			->method( 'create_conversion_action' );

		$this->account->setup_account();
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
		$ads_account_state = [
			'link_merchant' => [ 'status' => AdsAccountState::STEP_PENDING ],
		];

		$this->options->expects( $this->any() )
			->method( 'get_ads_id' )
			->willReturn( self::TEST_ACCOUNT_ID );

		$this->options->expects( $this->any() )
			->method( 'get_merchant_id' )
			->willReturn( 0 );

		$this->state->expects( $this->once() )
			->method( 'get' )
			->willReturn( $ads_account_state );

		$this->state->expects( $this->once() )
			->method( 'update' )
			->with( $ads_account_state );

		$this->middleware->expects( $this->never() )
			->method( 'link_ads_account' );

		$this->assertEquals( [ 'id' => self::TEST_ACCOUNT_ID ], $this->account->setup_account() );
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

		$this->connection->expects( $this->once() )
			->method( 'get_status' )
			->willReturn( [] );

		$this->options->expects( $this->exactly( 2 ) )
			->method( 'get' )
			->withConsecutive(
				[ OptionsInterface::ADS_BILLING_URL ],
				[ OptionsInterface::ADS_ACCOUNT_OCID, null ]
			)
			->willReturnOnConsecutiveCalls(
				self::TEST_BILLING_URL,
				null
			);

		$this->assertEquals(
			[
				'status'      => BillingSetupStatus::PENDING,
				'billing_url' => self::TEST_BILLING_URL,
			],
			$this->account->get_billing_status()
		);
	}

	public function test_get_billing_status_returns_deeplink_url() {
		$this->ads->expects( $this->once() )
			->method( 'get_billing_status' )
			->willReturn( BillingSetupStatus::PENDING );

		$this->options->expects( $this->exactly( 2 ) )
			->method( 'get' )
			->withConsecutive(
				[ OptionsInterface::ADS_BILLING_URL ],
				[ OptionsInterface::ADS_ACCOUNT_OCID, null ]
			)
			->willReturnOnConsecutiveCalls(
				self::TEST_BILLING_URL,
				self::TEST_ACCOUNT_OCID
			);

		$this->connection->expects( $this->once() )
			->method( 'get_status' )
			->willReturn( [ 'email' => 'test@gmail.com' ] );

		$this->ads->expects( $this->once() )
			->method( 'has_access' )
			->with( 'test@gmail.com' )
			->willReturn( true );

		$this->assertEquals(
			[
				'status'      => BillingSetupStatus::PENDING,
				'billing_url' => 'https://ads.google.com/aw/signup/payment?ocid=' . self::TEST_ACCOUNT_OCID,
			],
			$this->account->get_billing_status()
		);
	}

	public function test_get_ads_account_has_access() {
		$this->connection->method( 'get_status' )
			->willReturn( [ 'email' => 'test@domain.com' ] );

		$this->options->expects( $this->once() )
			->method( 'get_ads_id' )
			->willReturn( self::TEST_ACCOUNT_ID );

		$this->state->expects( $this->once() )
			->method( 'complete_step' )
			->with( 'account_access' );

		$this->options->method( 'get' )
			->with( OptionsInterface::ADS_BILLING_URL, '' )
			->willReturn( self::TEST_BILLING_URL );

		$this->state->method( 'last_incomplete_step' )
			->willReturn( 'conversion_action' );

		$this->ads->method( 'has_access' )
			->with( 'test@domain.com' )
			->willReturn( true );

		$this->assertEquals(
			[
				'has_access'  => true,
				'step'        => 'conversion_action',
				'invite_link' => self::TEST_BILLING_URL,
			],
			$this->account->get_ads_account_has_access()
		);
	}

	public function test_get_ads_account_has_access_not_claimed() {
		$this->connection->method( 'get_status' )
			->willReturn( [ 'email' => 'test@domain.com' ] );

		$this->options->expects( $this->once() )
			->method( 'get_ads_id' )
			->willReturn( self::TEST_ACCOUNT_ID );

		$this->options->method( 'get' )
			->with( OptionsInterface::ADS_BILLING_URL, '' )
			->willReturn( self::TEST_BILLING_URL );

		$this->state->method( 'last_incomplete_step' )
			->willReturn( 'account_access' );

		$this->ads->method( 'has_access' )
			->with( 'test@domain.com' )
			->willReturn( false );

		$this->assertEquals(
			[
				'has_access'  => false,
				'step'        => 'account_access',
				'invite_link' => self::TEST_BILLING_URL,
			],
			$this->account->get_ads_account_has_access()
		);
	}

	public function test_get_ads_account_has_access_without_google_id() {
		$this->connection->method( 'get_status' )
			->willReturn( [] );

		$this->options->expects( $this->once() )
			->method( 'get_ads_id' )
			->willReturn( self::TEST_ACCOUNT_ID );

		$this->options->method( 'get' )
			->with( OptionsInterface::ADS_BILLING_URL, '' )
			->willReturn( '' );

		$this->ads->expects( $this->never() )
			->method( 'has_access' );

		$this->account->get_ads_account_has_access();
	}

	public function test_get_ads_account_has_access_without_ads_id() {
		$this->options->expects( $this->once() )
			->method( 'get_ads_id' )
			->willReturn( 0 );

		$this->connection->expects( $this->never() )
			->method( 'get_status' );

		$this->ads->expects( $this->never() )
			->method( 'has_access' );

		$this->account->get_ads_account_has_access();
	}

	public function test_disconnect() {
		$this->options->expects( $this->exactly( 8 ) )
			->method( 'delete' )
			->withConsecutive(
				[ OptionsInterface::ADS_ACCOUNT_CURRENCY ],
				[ OptionsInterface::ADS_ACCOUNT_OCID ],
				[ OptionsInterface::ADS_ACCOUNT_STATE ],
				[ OptionsInterface::ADS_BILLING_URL ],
				[ OptionsInterface::ADS_CONVERSION_ACTION ],
				[ OptionsInterface::ADS_ID ],
				[ OptionsInterface::ADS_SETUP_COMPLETED_AT ],
				[ OptionsInterface::CAMPAIGN_CONVERT_STATUS ]
			);

		$this->transients->expects( $this->exactly( 1 ) )
			->method( 'delete' )
			->withConsecutive(
				[ TransientsInterface::ADS_CAMPAIGN_COUNT ],
			);

		$this->account->disconnect();
	}
}
