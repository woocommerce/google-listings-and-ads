<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Ads;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\BillingSetupStatus;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\ExceptionWithResponseData;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\GoogleAdsClientTrait;
use Exception;
use Google\Ads\GoogleAds\V16\Enums\BillingSetupStatusEnum\BillingSetupStatus as AdsBillingSetupStatus;
use Google\Ads\GoogleAds\V16\Enums\ProductLinkInvitationStatusEnum\ProductLinkInvitationStatus;
use Google\Ads\GoogleAds\V16\Resources\MerchantCenterLinkInvitationIdentifier;
use Google\Ads\GoogleAds\V16\Resources\ProductLinkInvitation;
use Google\ApiCore\ApiException;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class AdsTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google
 */
class AdsTest extends UnitTest {

	use GoogleAdsClientTrait;

	/** @var MockObject|OptionsInterface $options */
	protected $options;

	/** @var Ads $ads */
	protected $ads;

	protected const TEST_ADS_ID      = 1234567890;
	protected const TEST_MERCHANT_ID = 2345678901;
	protected const TEST_ADS_NAME    = 'Ads Account';
	protected const TEST_EMAIL       = 'john@doe.email';
	protected const TEST_CURRENCY    = 'EUR';
	protected const TEST_BILLING_URL = 'https://domain.test/billing/setup/';

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->ads_client_setup();

		$this->options = $this->createMock( OptionsInterface::class );

		$this->ads = new Ads( $this->client );
		$this->ads->set_options_object( $this->options );
	}

	public function test_get_ads_accounts_empty_list() {
		$this->generate_customer_list_mock( [] );
		$this->assertEquals( [], $this->ads->get_ads_accounts() );
	}

	public function test_get_ads_accounts() {
		$this->generate_customer_list_mock(
			[
				'customers/' . self::TEST_ADS_ID,
				'customers/2345',
				'customers/3456',
			]
		);

		$this->generate_customers_mock(
			[
				[
					'id'   => self::TEST_ADS_ID,
					'name' => self::TEST_ADS_NAME,
				],
				[
					'id'      => 2345,
					'name'    => 'Manager Account',
					'manager' => true,
				],
				[
					'id'           => 4567,
					'name'         => 'Test Account',
					'test_account' => true,
				],
			]
		);

		$this->assertEquals(
			[
				[
					'id'   => self::TEST_ADS_ID,
					'name' => self::TEST_ADS_NAME,
				],
			],
			$this->ads->get_ads_accounts()
		);
	}

	public function test_get_ads_accounts_not_signed_up() {
		$errors = [
			'errors' => [
				[
					'errorCode' => [
						'AuthenticationError' => 'NOT_ADS_USER',
					],
					'message'   => 'Not associated with any Google Ads account.',
				],
			],
		];

		$this->generate_customer_list_mock_exception(
			new ApiException( 'denied', 7, 'PERMISSION_DENIED', [ 'metadata' => [ $errors ] ] )
		);

		$this->assertEquals( [], $this->ads->get_ads_accounts() );
	}

	public function test_get_ads_accounts_exception() {
		$this->generate_customer_list_mock_exception(
			new ApiException( 'unavailable', 14, 'UNAVAILABLE' )
		);

		try {
			$this->ads->get_ads_accounts();
		} catch ( ExceptionWithResponseData $e ) {
			$this->assertEquals(
				[
					'message' => 'Error retrieving accounts: unavailable',
					'errors'  => [ 'UNAVAILABLE' => 'unavailable' ],
				],
				$e->get_response_data( true )
			);
			$this->assertEquals( 503, $e->getCode() );
		}
	}

	public function test_get_billing_status_no_ads_id() {
		$this->options->expects( $this->once() )
			->method( 'get_ads_id' )
			->willReturn( 0 );

		$this->assertEquals( BillingSetupStatus::UNKNOWN, $this->ads->get_billing_status() );
	}

	public function test_get_billing_status() {
		$this->options->method( 'get_ads_id' )->willReturn( self::TEST_ADS_ID );
		$this->generate_ads_billing_status_query_mock( AdsBillingSetupStatus::APPROVED );
		$this->assertEquals( BillingSetupStatus::APPROVED, $this->ads->get_billing_status() );
	}

	public function test_get_billing_status_exception() {
		$this->options->method( 'get_ads_id' )->willReturn( self::TEST_ADS_ID );
		$this->generate_ads_query_mock_exception( new ApiException( 'not found', 5, 'NOT_FOUND' ) );
		$this->assertEquals( BillingSetupStatus::UNKNOWN, $this->ads->get_billing_status() );
	}

	public function test_accept_merchant_link_not_available() {
		$this->options->method( 'get_ads_id' )->willReturn( self::TEST_ADS_ID );
		$this->generate_mc_link_mock( [] );
		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Merchant link is not available to accept' );

		$this->ads->accept_merchant_link( self::TEST_MERCHANT_ID );
	}

	public function test_accept_merchant_link_already_accepted() {
		$this->options->method( 'get_ads_id' )->willReturn( self::TEST_ADS_ID );
		$link = new ProductLinkInvitation();
		$mc   = new MerchantCenterLinkInvitationIdentifier();
		$link->setStatus( ProductLinkInvitationStatus::ACCEPTED );
		$mc->setMerchantCenterId( self::TEST_MERCHANT_ID );
		$link->setMerchantCenter( $mc );
		$service = $this->generate_mc_link_mock( [ $link ] );
		$service->expects( $this->never() )->method( 'updateProductLinkInvitation' );

		$this->ads->accept_merchant_link( self::TEST_MERCHANT_ID );
	}

	public function test_accept_merchant_link_with_retry_get_merchant_link() {
		$this->options->method( 'get_ads_id' )->willReturn( self::TEST_ADS_ID );
		$link = new ProductLinkInvitation();
		$mc   = $this->createMock( MerchantCenterLinkInvitationIdentifier::class );

		$mc
			->expects( $this->exactly( 4 ) )
			->method( 'getMerchantCenterId' )
			->willReturnOnConsecutiveCalls(
				self::TEST_MERCHANT_ID + 3,
				self::TEST_MERCHANT_ID + 2,
				self::TEST_MERCHANT_ID + 1,
				self::TEST_MERCHANT_ID
			);

		$link->setStatus( ProductLinkInvitationStatus::PENDING_APPROVAL );
		$link->setMerchantCenter( $mc );

		$service = $this->generate_mc_link_mock( [ $link ] );
		$service->expects( $this->once() )->method( 'updateProductLinkInvitation' );

		$this->ads->accept_merchant_link( self::TEST_MERCHANT_ID );
	}

	public function test_accept_merchant_link() {
		$this->options->method( 'get_ads_id' )->willReturn( self::TEST_ADS_ID );
		$link = new ProductLinkInvitation();
		$mc   = new MerchantCenterLinkInvitationIdentifier();
		$link->setStatus( ProductLinkInvitationStatus::PENDING_APPROVAL );
		$mc->setMerchantCenterId( self::TEST_MERCHANT_ID );
		$link->setMerchantCenter( $mc );

		$service = $this->generate_mc_link_mock( [ $link ] );
		$service->expects( $this->once() )->method( 'updateProductLinkInvitation' );

		$this->ads->accept_merchant_link( self::TEST_MERCHANT_ID );
	}

	public function test_has_no_access() {
		$this->options->method( 'get_ads_id' )->willReturn( self::TEST_ADS_ID );
		$this->generate_ads_access_query_mock( false );
		$this->assertFalse( $this->ads->has_access( self::TEST_EMAIL ) );
	}

	public function test_has_access() {
		$this->options->method( 'get_ads_id' )->willReturn( self::TEST_ADS_ID );
		$this->generate_ads_access_query_mock( true );
		$this->assertTrue( $this->ads->has_access( self::TEST_EMAIL ) );
	}

	public function test_get_has_access_exception() {
		$this->options->method( 'get_ads_id' )->willReturn( self::TEST_ADS_ID );
		$this->generate_ads_query_mock_exception( new ApiException( 'not found', 5, 'NOT_FOUND' ) );
		$this->assertFalse( $this->ads->has_access( self::TEST_EMAIL ) );
	}

	public function test_get_default_currency() {
		$this->assertEquals( 'USD', $this->ads->get_ads_currency() );
	}

	public function test_get_ads_currency() {
		$this->options->expects( $this->once() )
			->method( 'get' )
			->with( OptionsInterface::ADS_ACCOUNT_CURRENCY )
			->willReturn( self::TEST_CURRENCY );

		$this->assertEquals( self::TEST_CURRENCY, $this->ads->get_ads_currency() );
	}

	public function test_request_ads_currency() {
		$this->options->method( 'get_ads_id' )->willReturn( self::TEST_ADS_ID );
		$this->generate_customers_mock(
			[
				[ 'currency' => self::TEST_CURRENCY ],
			]
		);

		$this->options->expects( $this->once() )
			->method( 'update' )
			->with( OptionsInterface::ADS_ACCOUNT_CURRENCY, self::TEST_CURRENCY )
			->willReturn( true );

		$this->assertTrue( $this->ads->request_ads_currency() );
	}

	public function test_request_ads_currency_unavailable() {
		$this->options->method( 'get_ads_id' )->willReturn( self::TEST_ADS_ID );
		$this->generate_ads_query_mock_exception(
			new ApiException( 'unavailable', 14, 'UNAVAILABLE' )
		);

		$this->options->expects( $this->once() )
			->method( 'update' )
			->with( OptionsInterface::ADS_ACCOUNT_CURRENCY, null )
			->willReturn( true );

		$this->assertTrue( $this->ads->request_ads_currency() );
	}

	public function test_use_store_currency() {
		$this->options->expects( $this->once() )
			->method( 'update' )
			->with( OptionsInterface::ADS_ACCOUNT_CURRENCY, 'USD' )
			->willReturn( true );

		$this->assertTrue( $this->ads->use_store_currency() );
	}

	public function test_parse_ads_id() {
		$resource_name = 'customers/1234';
		$this->assertEquals( 1234, $this->ads->parse_ads_id( $resource_name ) );
	}

	public function test_parse_ads_id_invalid() {
		$resource_name = 'foobar/1234';
		$this->assertEquals( 0, $this->ads->parse_ads_id( $resource_name ) );
	}

	public function test_update_ads_id() {
		$this->options->expects( $this->once() )
			->method( 'update' )
			->with( OptionsInterface::ADS_ID, self::TEST_ADS_ID )
			->willReturn( true );
		$this->assertTrue( $this->ads->update_ads_id( self::TEST_ADS_ID ) );
	}

	public function test_update_billing_url() {
		$this->options->expects( $this->once() )
			->method( 'update' )
			->with( OptionsInterface::ADS_BILLING_URL, self::TEST_BILLING_URL )
			->willReturn( true );
		$this->assertTrue( $this->ads->update_billing_url( self::TEST_BILLING_URL ) );
	}
}
