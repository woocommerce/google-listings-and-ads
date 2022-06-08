<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Ads;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Merchant;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Middleware;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidDomainName;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\GoogleHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\TransientsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\GuzzleClientTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Utility\DateTimeUtility;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\Container\Container;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class MiddlewareTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google
 * @group Middleware
 *
 * @property MockObject|Ads                  $ads
 * @property MockObject|DateTimeUtility      $date_utility
 * @property MockObject|GoogleHelper         $google_helper
 * @property MockObject|Merchant             $merchant
 * @property MockObject|WP                   $wp
 * @property MockObject|OptionsInterface     $options
 * @property MockObject|TransientsInterface  $transients
 * @property Middleware                      $middleware
 * @property Container                       $container
 */
class MiddlewareTest extends UnitTest {

	use GuzzleClientTrait;

	protected const TEST_ADS_ID      = 12345678;
	protected const TEST_MERCHANT_ID = 23456781;
	protected const TEST_BILLING_URL = 'https://domain.test/billing/setup/';

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->container = new Container();
		$this->guzzle_client_setup();

		$this->ads           = $this->createMock( Ads::class );
		$this->date_utility  = $this->createMock( DateTimeUtility::class );
		$this->google_helper = $this->createMock( GoogleHelper::class );
		$this->merchant      = $this->createMock( Merchant::class );
		$this->options       = $this->createMock( OptionsInterface::class );
		$this->transients    = $this->createMock( TransientsInterface::class );
		$this->wp            = $this->createMock( WP::class );

		$this->container->share( Ads::class, $this->ads );
		$this->container->share( DateTimeUtility::class, $this->date_utility );
		$this->container->share( GoogleHelper::class, $this->google_helper );
		$this->container->share( Merchant::class, $this->merchant );
		$this->container->share( TransientsInterface::class, $this->transients );
		$this->container->share( WP::class, $this->wp );

		$this->middleware = new Middleware( $this->container );
		$this->middleware->set_options_object( $this->options );

		$this->login_as_administrator();
	}

	public function test_get_merchant_accounts_empty_list() {
		$this->generate_request_mock( [] );
		$this->assertEquals( [], $this->middleware->get_merchant_accounts() );
	}

	public function test_get_merchant_accounts() {
		$accounts = [
			[
				'id'         => self::TEST_MERCHANT_ID,
				'subaccount' => true,
				'name'       => 'One',
				'domain'     => 'https://account.one',
			],
			[
				'id'         => 34567812,
				'subaccount' => true,
				'name'       => 'Two',
				'domain'     => 'https://account.two',
			]
		];

		$this->generate_request_mock( $accounts );
		$this->assertEquals( $accounts, $this->middleware->get_merchant_accounts() );
	}

	public function test_get_merchant_accounts_exception() {
		$this->generate_request_mock_exception( 'error' );

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Error retrieving accounts' );
		$this->expectExceptionCode( 400 );

		$this->middleware->get_merchant_accounts();
	}

	public function test_create_merchant_account_failed_tos() {
		$this->generate_tos_failed_mock();

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Unable to log accepted TOS' );
		$this->middleware->create_merchant_account();
	}

	public function test_create_merchant_account_invalid_url() {
		$this->generate_tos_accepted_mock();

		add_filter(
			'woocommerce_gla_site_url',
			function () {
				return 'invalid://url';
			}
		);

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Invalid site URL.' );
		$this->middleware->create_merchant_account();
	}

	// Invalid term is caught and repeats the request with a default account name.
	public function test_create_merchant_account_invalid_term() {
		$this->generate_create_account_exception_mock(
			'The term foobar is not allowed',
			[ 'id' => self::TEST_MERCHANT_ID ]
		);

		$this->assertEquals( self::TEST_MERCHANT_ID, $this->middleware->create_merchant_account() );
	}

	public function test_create_merchant_account_invalid_top_level_domain() {
		$this->generate_create_account_exception_mock(
			'URL ends with an invalid top-level domain name'
		);

		$this->expectException( InvalidDomainName::class );
		$this->expectExceptionMessage( 'must end with a valid top-level domain name' );
		$this->middleware->create_merchant_account();
	}

	public function test_create_merchant_account_exception() {
		$this->generate_create_account_exception_mock( 'error' );

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Error creating account: error' );
		$this->middleware->create_merchant_account();
	}

	public function test_create_merchant_account_invalid_response() {
		$this->generate_create_account_mock(
			[ 'status' => 'failed' ]
		);

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Invalid response when creating account' );
		$this->middleware->create_merchant_account();
	}

	public function test_create_merchant_account() {
		$this->generate_create_account_mock(
			[ 'id' => self::TEST_MERCHANT_ID ]
		);

		$this->assertEquals( self::TEST_MERCHANT_ID, $this->middleware->create_merchant_account() );
	}

	public function test_link_merchant_account() {
		$this->merchant->expects( $this->once() )
			->method( 'update_merchant_id' )
			->with( self::TEST_MERCHANT_ID );

		$this->assertEquals(
			self::TEST_MERCHANT_ID,
			$this->middleware->link_merchant_account( self::TEST_MERCHANT_ID )
		);
	}

	public function test_link_merchant_to_mca_exception() {
		$this->generate_request_mock_exception( 'error', 'post' );

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Error linking merchant to MCA' );
		$this->expectExceptionCode( 400 );
		$this->middleware->link_merchant_to_mca();
	}

	public function test_link_merchant_to_mca_invalid_response() {
		$this->generate_request_mock(
			[ 'status' => 'failed' ],
			'post'
		);

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Invalid response when linking merchant to MCA' );
		$this->middleware->link_merchant_to_mca();
	}

	public function test_link_merchant_to_mca() {
		$this->generate_request_mock(
			[ 'status' => 'success' ],
			'post'
		);

		$this->assertTrue( $this->middleware->link_merchant_to_mca() );
	}

	public function test_claim_merchant_website_exception() {
		$this->generate_request_mock_exception( 'error', 'post' );

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Error claiming website' );
		$this->expectExceptionCode( 400 );
		$this->middleware->claim_merchant_website( true );
	}

	public function test_claim_merchant_website_invalid_response() {
		$this->generate_request_mock(
			[ 'status' => 'failed' ],
			'post'
		);

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Invalid response when claiming website' );
		$this->middleware->claim_merchant_website( true );
	}

	public function test_claim_merchant_website() {
		$this->generate_request_mock(
			[ 'status' => 'success' ],
			'post'
		);

		$this->assertTrue( $this->middleware->claim_merchant_website( true ) );
		$this->assertEquals( 1, did_action( 'woocommerce_gla_site_claim_success' ) );
	}

	public function test_create_ads_account_unsupported_country() {
		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Store country is not supported' );
		$this->middleware->create_ads_account();
	}

	public function test_create_ads_account_failed_tos() {
		$this->google_helper->method( 'is_country_supported' )->willReturn( true );
		$this->generate_tos_failed_mock();

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Unable to log accepted TOS' );
		$this->middleware->create_ads_account();
	}

	public function test_create_ads_account_exception() {
		$this->google_helper->method( 'is_country_supported' )->willReturn( true );
		$this->generate_create_account_exception_mock( 'error' );

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Error creating account: error' );
		$this->middleware->create_ads_account();
	}

	public function test_create_ads_account_invalid_response() {
		$this->google_helper->method( 'is_country_supported' )->willReturn( true );
		$this->generate_create_account_mock(
			[ 'status' => 'failed' ]
		);

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Invalid response when creating account' );
		$this->middleware->create_ads_account();
	}

	public function test_create_ads_account() {
		$this->google_helper->method( 'is_country_supported' )->willReturn( true );

		$this->ads->expects( $this->once() )->method( 'parse_ads_id' )->willReturn( self::TEST_ADS_ID );
		$this->ads->expects( $this->once() )->method( 'update_ads_id' )->with( self::TEST_ADS_ID );
		$this->ads->expects( $this->once() )->method( 'use_store_currency' );
		$this->ads->expects( $this->once() )->method( 'update_billing_url' )->with( self::TEST_BILLING_URL );

		$this->generate_create_account_mock(
			[
				'resourceName'   => 'customers/' . self::TEST_ADS_ID,
				'invitationLink' => self::TEST_BILLING_URL,
			]
		);

		$this->assertEquals(
			[
				'id'          => self::TEST_ADS_ID,
				'billing_url' => self::TEST_BILLING_URL,
			],
			$this->middleware->create_ads_account()
		);
	}

	public function test_link_ads_account_exception() {
		$this->generate_request_mock_exception( 'error', 'post' );

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Error linking account' );
		$this->expectExceptionCode( 400 );
		$this->middleware->link_ads_account( self::TEST_ADS_ID );
	}

	public function test_link_ads_account_invalid_response() {
		$this->generate_request_mock(
			[ 'status' => 'failed' ],
			'post'
		);

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Invalid response when linking account' );
		$this->middleware->link_ads_account( self::TEST_ADS_ID );
	}

	public function test_link_ads_account() {
		$this->generate_request_mock(
			[
				'resourceName' => 'customers/' . self::TEST_ADS_ID,
			],
			'post'
		);

		$this->ads->expects( $this->once() )->method( 'update_ads_id' )->with( self::TEST_ADS_ID );
		$this->ads->expects( $this->once() )->method( 'request_ads_currency' );

		$this->assertEquals(
			[
				'id' => self::TEST_ADS_ID,
			],
			$this->middleware->link_ads_account( self::TEST_ADS_ID )
		);
	}

	public function test_check_tos_accepted() {
		$this->generate_request_mock( [ 'status' => 'accepted' ] );
		$this->assertTrue( $this->middleware->check_tos_accepted( 'google-mc' )->accepted() );
	}

	public function test_check_tos_accepted_exception() {
		$this->generate_request_mock_exception( 'error' );
		$tos = $this->middleware->check_tos_accepted( 'google-mc' );
		$this->assertFalse( $tos->accepted() );
		$this->assertEquals( 'error', $tos->message() );
	}

	public function test_get_account_review_status() {

		$this->options->expects( $this->exactly( 2 ) )->method('get_merchant_id')->willReturn(self::TEST_MERCHANT_ID);

		$accounts = [
			[
				'id'         => self::TEST_MERCHANT_ID,
				'subaccount' => true,
			],
			[
				'id'         => 34567812,
				'subaccount' => false,
			]
		];

		$review_status = [ 'freeListingsProgram' => 'freeListingsProgram', 'shoppingAdsProgram' => 'shoppingAdsProgram'];

		$this->generate_account_review_mock( $accounts,  $review_status);


		$this->assertEquals( $this->middleware->get_account_review_status(), $review_status);
	}

	public function test_get_account_review_status_standalone() {
		$this->options->expects( $this->once() )->method('get_merchant_id')->willReturn(self::TEST_MERCHANT_ID);

		$accounts = [
			[
				'id'         => self::TEST_MERCHANT_ID,
				'subaccount' => false,
			],
			[
				'id'         => 34567812,
				'subaccount' => true,
			]
		];

		$this->generate_request_mock( $accounts );
		$this->assertEquals( $this->middleware->get_account_review_status(), [] );
	}


	public function test_get_account_review_status_exception() {
		$this->options->expects( $this->once() )->method('get_merchant_id')->willReturn(self::TEST_MERCHANT_ID);

		$this->generate_request_mock_exception( 'Some exception' );

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Error retrieving accounts' );
		$this->expectExceptionCode( 400 );

		$this->middleware->get_account_review_status();
	}


	public function test_get_account_review_status_error() {
		$this->options->expects( $this->exactly( 2 ) )->method('get_merchant_id')->willReturn(self::TEST_MERCHANT_ID);

		$accounts = [
			[
				'id'         => self::TEST_MERCHANT_ID,
				'subaccount' => true,
			]
		];

		$review_status = [];

		$this->generate_account_review_mock( $accounts,  $review_status);

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Invalid response getting account review status' );

		$this->middleware->get_account_review_status();
	}

}
