<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Merchant;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Middleware;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\SiteVerification;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Table\MerchantIssueTable;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Table\ShippingRateTable;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Table\ShippingTimeTable;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\ApiNotReady;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\ExceptionWithResponseData;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\CleanupSyncedProducts;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\AccountService;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantStatuses;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\MerchantAccountState;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\MerchantTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\Container\Container;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class AccountServiceTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\MerchantCenter
 *
 * @property MockObject|CleanupSyncedProducts $cleanup_synced
 * @property MockObject|Merchant              $merchant
 * @property MockObject|MerchantCenterService $mc_service
 * @property MockObject|MerchantIssueTable    $issue_table
 * @property MockObject|MerchantStatuses      $merchant_statuses
 * @property MockObject|Middleware            $middleware
 * @property MockObject|OptionsInterface      $options
 * @property MockObject|SiteVerification      $site_verification
 * @property MockObject|ShippingRateTable     $rate_table
 * @property MockObject|ShippingTimeTable     $time_table
 * @property MockObject|MerchantAccountState  $state
 * @property AccountService                   $account
 * @property Container                        $container
 */
class AccountServiceTest extends UnitTest {

	use MerchantTrait;

	protected const TEST_ACCOUNT_ID     = 12345678;
	protected const TEST_OLD_ACCOUNT_ID = 23456781;
	protected const TEST_ACCOUNTS       = [
		[
			'id'         => self::TEST_ACCOUNT_ID,
			'subaccount' => true,
			'name'       => 'One',
			'domain'     => 'https://account.one',
		],
		[
			'id'         => 23456781,
			'subaccount' => true,
			'name'       => 'Two',
			'domain'     => 'https://account.two',
		],
	];
	protected const TEST_CLEANURL       = 'example.org';
	protected const TEST_OLD_URL        = 'https://oldsite.com';
	protected const TEST_OLD_CLEANURL   = 'oldsite.com';
	protected const TEST_ACCOUNT_DATA   = [ 'id' => self::TEST_ACCOUNT_ID ];

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->cleanup_synced    = $this->createMock( CleanupSyncedProducts::class );
		$this->merchant          = $this->createMock( Merchant::class );
		$this->mc_service        = $this->createMock( MerchantCenterService::class );
		$this->issue_table       = $this->createMock( MerchantIssueTable::class );
		$this->merchant_statuses = $this->createMock( MerchantStatuses::class );
		$this->middleware        = $this->createMock( Middleware::class );
		$this->site_verification = $this->createMock( SiteVerification::class );
		$this->rate_table        = $this->createMock( ShippingRateTable::class );
		$this->time_table        = $this->createMock( ShippingTimeTable::class );
		$this->state             = $this->createMock( MerchantAccountState::class );
		$this->options           = $this->createMock( OptionsInterface::class );

		$this->container = new Container();
		$this->container->share( CleanupSyncedProducts::class, $this->cleanup_synced );
		$this->container->share( Merchant::class, $this->merchant );
		$this->container->share( MerchantCenterService::class, $this->mc_service );
		$this->container->share( MerchantIssueTable::class, $this->issue_table );
		$this->container->share( MerchantStatuses::class, $this->merchant_statuses );
		$this->container->share( Middleware::class, $this->middleware );
		$this->container->share( SiteVerification::class, $this->site_verification );
		$this->container->share( ShippingRateTable::class, $this->rate_table );
		$this->container->share( ShippingTimeTable::class, $this->time_table );
		$this->container->share( MerchantAccountState::class, $this->state );

		$this->account = new AccountService( $this->container );
		$this->account->set_options_object( $this->options );
	}

	public function test_get_accounts() {
		$this->middleware->expects( $this->once() )
			->method( 'get_merchant_accounts' )
			->willReturn( self::TEST_ACCOUNTS );

		$this->assertEquals( self::TEST_ACCOUNTS, $this->account->get_accounts() );
	}

	public function test_get_accounts_with_api_exception() {
		$this->middleware->expects( $this->once() )
			->method( 'get_merchant_accounts' )
			->willThrowException( new Exception( 'error' ) );

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'error' );
		$this->account->get_accounts();
	}

	public function test_existing_account_already_connected() {
		$this->options->expects( $this->any() )
			->method( 'get_merchant_id' )
			->willReturn( self::TEST_OLD_ACCOUNT_ID );

		$this->mc_service->expects( $this->once() )
			->method( 'is_setup_complete' )
			->willReturn( true );

		$this->expectException( ExceptionWithResponseData::class );
		$this->expectExceptionMessage(
			sprintf(
				'Merchant Center account already connected: %d',
				self::TEST_OLD_ACCOUNT_ID
			)
		);
		$this->account->use_existing_account_id( self::TEST_ACCOUNT_ID );
	}

	public function test_existing_account_already_completed() {
		$this->options->expects( $this->once() )
			->method( 'get_merchant_id' )
			->willReturn( 0 );

		$this->state->expects( $this->any() )
			->method( 'get' )
			->willReturn(
				[
					'set_id' => [ 'status' => MerchantAccountState::STEP_DONE ],
				]
			);

		$this->middleware->expects( $this->never() )->method( 'link_merchant_account' );

		$this->account->use_existing_account_id( self::TEST_ACCOUNT_ID );
	}

	public function test_existing_account() {
		$this->options->expects( $this->once() )
			->method( 'get_merchant_id' )
			->willReturn( 0 );

		$this->state->expects( $this->any() )
			->method( 'get' )
			->willReturn(
				[
					'set_id' => [ 'status' => MerchantAccountState::STEP_PENDING ],
				]
			);

		$this->middleware->expects( $this->once() )
			->method( 'link_merchant_account' )
			->with( self::TEST_ACCOUNT_ID );

		$this->state->expects( $this->once() )
			->method( 'update' )
			->with(
				[
					'set_id'  => [
						'status' => MerchantAccountState::STEP_DONE,
						'data'   => [ 'from_mca' => false ],
					],
				]
			);

		$this->account->use_existing_account_id( self::TEST_ACCOUNT_ID );
	}

	public function test_existing_account_api_error() {
		$this->options->expects( $this->any() )
			->method( 'get_merchant_id' )
			->willReturn( 0 );

		$this->state->expects( $this->any() )
			->method( 'get' )
			->willReturn(
				[
					'set_id' => [ 'status' => MerchantAccountState::STEP_PENDING ],
				]
			);

		$this->middleware->expects( $this->once() )
			->method( 'get_merchant_accounts' )
			->willThrowException( new Exception( 'error' ) );

		$this->expectException( ExceptionWithResponseData::class );
		$this->expectExceptionMessage( 'error' );
		$this->expectExceptionCode( 400 );
		$this->account->use_existing_account_id( self::TEST_ACCOUNT_ID );
	}

	public function test_existing_account_invalid_url() {
		$this->options->expects( $this->any() )
			->method( 'get_merchant_id' )
			->willReturn( 0 );

		$this->state->expects( $this->any() )
			->method( 'get' )
			->willReturn(
				[
					'set_id' => [ 'status' => MerchantAccountState::STEP_PENDING ],
				]
			);

		add_filter(
			'woocommerce_gla_site_url',
			function () {
				return 'invalid://url';
			}
		);

		$this->expectException( ExceptionWithResponseData::class );
		$this->expectExceptionMessage( 'Invalid site URL.' );
		$this->account->use_existing_account_id( self::TEST_ACCOUNT_ID );
	}

	public function test_existing_account_update_url() {
		$this->options->expects( $this->any() )
			->method( 'get_merchant_id' )
			->willReturn( 0 );

		$this->state->expects( $this->any() )
			->method( 'get' )
			->willReturn(
				[
					'set_id' => [ 'status' => MerchantAccountState::STEP_PENDING ],
				]
			);

		$this->merchant->expects( $this->any() )
			->method( 'get_account' )
			->willReturn( $this->get_account_with_url( self::TEST_OLD_URL ) );

		$this->merchant->expects( $this->any() )
			->method( 'get_accountstatus' )
			->willReturn( $this->get_status_website_claimed() );

		try {
			$this->account->use_existing_account_id( self::TEST_ACCOUNT_ID );
		} catch ( Exception $e ) {
			$this->assertInstanceOf( ExceptionWithResponseData::class, $e );
			$this->assertEquals( 409, $e->getCode() );
			$this->assertEquals(
				[
					'id'          => self::TEST_ACCOUNT_ID,
					'claimed_url' => self::TEST_OLD_CLEANURL,
					'new_url'     => self::TEST_CLEANURL,
				],
				$e->get_response_data()
			);
		}
	}

	public function test_setup_account_step_set_id_already_created() {
		$this->options->expects( $this->any() )
			->method( 'get_merchant_id' )
			->willReturn( self::TEST_ACCOUNT_ID );

		$this->state->expects( $this->any() )
			->method( 'get' )
			->willReturn(
				[
					'set_id' => [ 'status' => MerchantAccountState::STEP_PENDING ],
				]
			);

		$this->middleware->expects( $this->never() )
			->method( 'create_merchant_account' );

		$this->assertEquals( self::TEST_ACCOUNT_DATA, $this->account->setup_account( 0 ) );
	}

	public function test_setup_account_step_set_id() {
		$this->options->expects( $this->any() )
			->method( 'get_merchant_id' )
			->willReturn( 0 );

		$this->state->expects( $this->any() )
			->method( 'get' )
			->willReturn(
				[
					'set_id' => [ 'status' => MerchantAccountState::STEP_PENDING ],
				]
			);

		$this->middleware->expects( $this->once() )
			->method( 'create_merchant_account' )
			->willReturn( self::TEST_ACCOUNT_ID );

		$this->assertEquals( self::TEST_ACCOUNT_DATA, $this->account->setup_account( 0 ) );
	}

	public function test_setup_account_step_verify() {
		$this->options->expects( $this->any() )
			->method( 'get_merchant_id' )
			->willReturn( self::TEST_ACCOUNT_ID );

		$this->state->expects( $this->any() )
			->method( 'get' )
			->willReturn(
				[
					'verify' => [ 'status' => MerchantAccountState::STEP_PENDING ],
				]
			);

		$this->site_verification->expects( $this->once() )
			->method( 'verify_site' )
			->with( get_home_url() );

		$this->assertEquals( self::TEST_ACCOUNT_DATA, $this->account->setup_account( self::TEST_ACCOUNT_ID ) );
	}

	public function test_setup_account_step_verify_already_verified() {
		$this->options->expects( $this->any() )
			->method( 'get_merchant_id' )
			->willReturn( self::TEST_ACCOUNT_ID );

		$this->state->expects( $this->any() )
			->method( 'get' )
			->willReturn(
				[
					'verify' => [ 'status' => MerchantAccountState::STEP_PENDING ],
				]
			);

		$this->state->expects( $this->any() )
			->method( 'is_site_verified' )
			->willReturn( true );

		$this->site_verification->expects( $this->never() )
			->method( 'verify_site' )
			->with( get_home_url() );

		$this->assertEquals( self::TEST_ACCOUNT_DATA, $this->account->setup_account( self::TEST_ACCOUNT_ID ) );
	}

	public function test_setup_account_step_link() {
		$this->options->expects( $this->any() )
			->method( 'get_merchant_id' )
			->willReturn( self::TEST_ACCOUNT_ID );

		$this->state->expects( $this->any() )
			->method( 'get' )
			->willReturn(
				[
					'link' => [ 'status' => MerchantAccountState::STEP_PENDING ],
				]
			);

		$this->middleware->expects( $this->once() )
			->method( 'link_merchant_to_mca' );

		$this->assertEquals( self::TEST_ACCOUNT_DATA, $this->account->setup_account( self::TEST_ACCOUNT_ID ) );
	}

	public function test_setup_account_step_link_retry_response() {
		$this->options->expects( $this->any() )
			->method( 'get_merchant_id' )
			->willReturn( self::TEST_ACCOUNT_ID );

		$this->state->expects( $this->any() )
			->method( 'get' )
			->willReturn(
				[
					'link' => [ 'status' => MerchantAccountState::STEP_PENDING ],
				]
			);

		$this->middleware->expects( $this->once() )
			->method( 'link_merchant_to_mca' )
			->willThrowException( new Exception( 'error', 401 ) );

		try {
			$this->account->setup_account( self::TEST_ACCOUNT_ID );
		} catch ( ApiNotReady $e ) {
			$this->assertInstanceOf( ApiNotReady::class, $e );
			$this->assertEquals( 503, $e->getCode() );
			$this->assertEquals(
				[
					'retry_after' => MerchantAccountState::MC_DELAY_AFTER_CREATE,
				],
				$e->get_response_data()
			);
		}
	}

	public function test_setup_account_step_claim() {
		$this->options->expects( $this->any() )
			->method( 'get_merchant_id' )
			->willReturn( self::TEST_ACCOUNT_ID );

		$this->state->expects( $this->any() )
			->method( 'get' )
			->willReturn(
				[
					'claim' => [ 'status' => MerchantAccountState::STEP_PENDING ],
				]
			);

		$this->merchant->expects( $this->once() )
			->method( 'claimwebsite' );

		$this->assertEquals( self::TEST_ACCOUNT_DATA, $this->account->setup_account( self::TEST_ACCOUNT_ID ) );
	}

	public function test_setup_account_step_claim_already_claimed() {
		$this->options->expects( $this->any() )
			->method( 'get_merchant_id' )
			->willReturn( self::TEST_ACCOUNT_ID );

		$this->state->expects( $this->any() )
			->method( 'get' )
			->willReturn(
				[
					'claim' => [ 'status' => MerchantAccountState::STEP_PENDING ],
				]
			);

		$this->merchant->expects( $this->any() )
			->method( 'get_accountstatus' )
			->willReturn( $this->get_status_website_claimed() );

		$this->merchant->expects( $this->never() )
			->method( 'claimwebsite' );

		$this->assertEquals( self::TEST_ACCOUNT_DATA, $this->account->setup_account( self::TEST_ACCOUNT_ID ) );
	}

	public function test_setup_account_step_claim_overwrite_required() {
		$this->options->expects( $this->any() )
			->method( 'get_merchant_id' )
			->willReturn( self::TEST_ACCOUNT_ID );

		$this->state->expects( $this->any() )
			->method( 'get' )
			->willReturn(
				[
					'claim' => [ 'status' => MerchantAccountState::STEP_PENDING ],
				]
			);

		$this->merchant->expects( $this->once() )
			->method( 'claimwebsite' )
			->willThrowException( new Exception( 'error', 403 ) );

		$this->state->expects( $this->once() )
			->method( 'update' )
			->with(
				[
					'claim'  => [
						'status'  => MerchantAccountState::STEP_ERROR,
						'message' => 'error',
						'data'    => [ 'overwrite_required' => true ],
					],
				]
			);

		$this->expectException( ExceptionWithResponseData::class );
		$this->expectExceptionMessage( 'error' );
		$this->expectExceptionCode( 403 );

		$this->account->setup_account( self::TEST_ACCOUNT_ID );
	}

	public function test_setup_account_step_claim_overwrite_not_possible() {
		$this->options->expects( $this->any() )
			->method( 'get_merchant_id' )
			->willReturn( self::TEST_ACCOUNT_ID );

		$this->state->expects( $this->any() )
			->method( 'get' )
			->willReturn(
				[
					'set_id' => [
						'status' => MerchantAccountState::STEP_DONE,
						'data'   => [ 'from_mca' => false ],
					],
					'claim' => [ 'status' => MerchantAccountState::STEP_PENDING ],
				]
			);

		$this->merchant->expects( $this->once() )
			->method( 'claimwebsite' )
			->willThrowException( new Exception( 'error', 403 ) );

		$this->expectException( ExceptionWithResponseData::class );
		$this->expectExceptionMessage( 'Unable to claim website URL with this Merchant Center Account.' );
		$this->expectExceptionCode( 406 );

		$this->account->setup_account( self::TEST_ACCOUNT_ID );
	}

	public function test_setup_account_step_invalid() {
		$this->state->expects( $this->once() )
			->method( 'get' )
			->willReturn(
				[
					'invalid' => [ 'status' => MerchantAccountState::STEP_PENDING ],
				]
			);

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Unknown merchant account creation step invalid' );

		$this->account->setup_account( self::TEST_ACCOUNT_ID );
	}

	public function test_switch_url() {
		$this->options->expects( $this->any() )
			->method( 'get_merchant_id' )
			->willReturn( self::TEST_ACCOUNT_ID );

		$this->state->expects( $this->any() )
			->method( 'get' )
			->willReturn(
				[
					'set_id' => [
						'status' => MerchantAccountState::STEP_PENDING,
						'data'   => [ 'old_url' => self::TEST_OLD_URL ],
					],
				]
			);

		$this->merchant->expects( $this->any() )
			->method( 'get_account' )
			->willReturn( $this->get_account_with_url( self::TEST_OLD_URL ) );

		$this->merchant->expects( $this->once() )
			->method( 'update_account' )
			->with( $this->get_account_with_url( get_home_url() ) );

		$this->assertEquals( self::TEST_ACCOUNT_DATA, $this->account->switch_url( self::TEST_ACCOUNT_ID ) );
	}

	public function test_switch_url_invalid() {
		$this->options->expects( $this->any() )
			->method( 'get_merchant_id' )
			->willReturn( self::TEST_ACCOUNT_ID );

		$this->state->expects( $this->any() )
			->method( 'get' )
			->willReturn(
				[
					'set_id' => [ 'status' => MerchantAccountState::STEP_PENDING ],
				]
			);

		$this->expectException( ExceptionWithResponseData::class );
		$this->expectExceptionMessage( 'Attempting invalid URL switch.' );

		$this->account->switch_url( self::TEST_ACCOUNT_ID );
	}

	public function test_overwrite_claim() {
		$this->options->expects( $this->any() )
			->method( 'get_merchant_id' )
			->willReturn( self::TEST_ACCOUNT_ID );

		$this->state->expects( $this->any() )
			->method( 'get' )
			->willReturn(
				[
					'claim' => [
						'status' => MerchantAccountState::STEP_PENDING,
						'data'   => [ 'overwrite_required' => true ],
					],
				]
			);

		$this->middleware->expects( $this->once() )
			->method( 'claim_merchant_website' )
			->with( true );

		$this->assertEquals( self::TEST_ACCOUNT_DATA, $this->account->overwrite_claim( self::TEST_ACCOUNT_ID ) );
	}

	public function test_overwrite_claim_invalid() {
		$this->options->expects( $this->any() )
			->method( 'get_merchant_id' )
			->willReturn( self::TEST_ACCOUNT_ID );

		$this->state->expects( $this->any() )
			->method( 'get' )
			->willReturn(
				[
					'claim' => [ 'status' => MerchantAccountState::STEP_PENDING ],
				]
			);

		$this->expectException( ExceptionWithResponseData::class );
		$this->expectExceptionMessage( 'Attempting invalid claim overwrite.' );

		$this->account->overwrite_claim( self::TEST_ACCOUNT_ID );
	}

	public function test_get_connected_status() {
		$this->options->expects( $this->once() )
			->method( 'get_merchant_id' )
			->willReturn( self::TEST_ACCOUNT_ID );

		$this->assertEquals(
			[
				'id'     => SELF::TEST_ACCOUNT_ID,
				'status' => 'connected',
			],
			$this->account->get_connected_status()
		);
	}

	public function test_get_connected_status_incomplete() {
		$this->options->expects( $this->once() )
			->method( 'get_merchant_id' )
			->willReturn( self::TEST_ACCOUNT_ID );

		$this->state->expects( $this->once() )
			->method( 'last_incomplete_step' )
			->willReturn( 'verify' );

		$this->assertEquals(
			[
				'id'     => SELF::TEST_ACCOUNT_ID,
				'status' => 'incomplete',
				'step'   => 'verify',
			],
			$this->account->get_connected_status()
		);
	}

	public function test_get_setup_status() {
		$this->mc_service->expects( $this->once() )
			->method( 'get_setup_status' )
			->willReturn( [ 'status' => 'complete' ] );

		$this->assertEquals( [ 'status' => 'complete' ], $this->account->get_setup_status() );
	}

	public function test_disconnect() {
		$this->options->expects( $this->exactly( 7 ) )
			->method( 'delete' )
			->withConsecutive(
				[ OptionsInterface::CONTACT_INFO_SETUP ],
				[ OptionsInterface::MC_SETUP_COMPLETED_AT ],
				[ OptionsInterface::MERCHANT_ACCOUNT_STATE ],
				[ OptionsInterface::MERCHANT_CENTER ],
				[ OptionsInterface::SITE_VERIFICATION ],
				[ OptionsInterface::TARGET_AUDIENCE ],
				[ OptionsInterface::MERCHANT_ID ]
			);

		$this->merchant_statuses->expects( $this->once() )->method( 'delete' );
		$this->issue_table->expects( $this->once() )->method( 'truncate' );
		$this->rate_table->expects( $this->once() )->method( 'truncate' );
		$this->time_table->expects( $this->once() )->method( 'truncate' );

		$this->cleanup_synced->expects( $this->once() )->method( 'schedule' );

		$this->account->disconnect();
	}

}
