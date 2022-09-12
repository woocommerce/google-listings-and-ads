<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Merchant;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Settings;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\ShippingRateQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\ShippingTimeQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\GoogleHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\ContactInformation;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantStatuses;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\TargetAudience;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\MerchantAccountState;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\TransientsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\MerchantTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Utility\AddressUtility;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\Container\Container;
use DateTime;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class MerchantCenterServiceTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\MerchantCenter
 *
 * @property MockObject|AddressUtility       $address_utility
 * @property MockObject|ContactInformation   $contact_information
 * @property MockObject|GoogleHelper         $google_helper
 * @property MockObject|Merchant             $merchant
 * @property MockObject|MerchantAccountState $merchant_account_state
 * @property MockObject|MerchantStatuses     $merchant_statuses
 * @property MockObject|Settings             $settings
 * @property MockObject|ShippingRateQuery    $shipping_rate_query
 * @property MockObject|ShippingTimeQuery    $shipping_time_query
 * @property MockObject|TargetAudience       $target_audience
 * @property MockObject|TransientsInterface  $transients
 * @property MockObject|WC                   $wc
 * @property MockObject|WP                   $wp
 * @property MerchantCenterService           $mc_service
 * @property Container                       $container
 * @property OptionsInterface                $options
 */
class MerchantCenterServiceTest extends UnitTest {

	use MerchantTrait;

	protected const TEST_SETUP_COMPLETED = 1641038400;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->address_utility        = $this->createMock( AddressUtility::class );
		$this->contact_information    = $this->createMock( ContactInformation::class );
		$this->google_helper          = $this->createMock( GoogleHelper::class );
		$this->merchant               = $this->createMock( Merchant::class );
		$this->merchant_account_state = $this->createMock( MerchantAccountState::class );
		$this->merchant_statuses      = $this->createMock( MerchantStatuses::class );
		$this->settings               = $this->createMock( Settings::class );
		$this->shipping_rate_query    = $this->createMock( ShippingRateQuery::class );
		$this->shipping_time_query    = $this->createMock( ShippingTimeQuery::class );
		$this->target_audience        = $this->createMock( TargetAudience::class );
		$this->transients             = $this->createMock( TransientsInterface::class );
		$this->wc                     = $this->createMock( WC::class );
		$this->wp                     = $this->createMock( WP::class );
		$this->options                = $this->createMock( OptionsInterface::class );

		$this->container = new Container();
		$this->container->share( AddressUtility::class, $this->address_utility );
		$this->container->share( ContactInformation::class, $this->contact_information );
		$this->container->share( GoogleHelper::class, $this->google_helper );
		$this->container->share( Merchant::class, $this->merchant );
		$this->container->share( MerchantAccountState::class, $this->merchant_account_state );
		$this->container->share( MerchantStatuses::class, $this->merchant_statuses );
		$this->container->share( Settings::class, $this->settings );
		$this->container->share( ShippingRateQuery::class, $this->shipping_rate_query );
		$this->container->share( ShippingTimeQuery::class, $this->shipping_time_query );
		$this->container->share( TargetAudience::class, $this->target_audience );
		$this->container->share( TransientsInterface::class, $this->transients );
		$this->container->share( WC::class, $this->wc );
		$this->container->share( WP::class, $this->wp );

		$this->mc_service = new MerchantCenterService();
		$this->mc_service->set_container( $this->container );
		$this->mc_service->set_options_object( $this->options );
	}

	public function test_is_setup_complete() {
		$this->options->method( 'get' )
			->with( OptionsInterface::MC_SETUP_COMPLETED_AT, false )
			->willReturn( self::TEST_SETUP_COMPLETED );

		$this->assertTrue( $this->mc_service->is_setup_complete() );
	}

	public function test_is_not_setup_complete() {
		$this->options->method( 'get' )
			->with( OptionsInterface::MC_SETUP_COMPLETED_AT, false )
			->willReturn( false );

		$this->assertFalse( $this->mc_service->is_setup_complete() );
	}

	public function test_is_connected() {
		$this->options->method( 'get' )
			->withConsecutive(
				[ OptionsInterface::GOOGLE_CONNECTED, false ],
				[ OptionsInterface::MC_SETUP_COMPLETED_AT, false ]
			)
			->willReturnOnConsecutiveCalls(
				true,
				self::TEST_SETUP_COMPLETED
			);

		$this->assertTrue( $this->mc_service->is_connected() );
	}

	public function test_is_not_connected() {
		$this->options->method( 'get' )
		->withConsecutive(
			[ OptionsInterface::GOOGLE_CONNECTED, false ],
			[ OptionsInterface::MC_SETUP_COMPLETED_AT, false ]
		)
		->willReturnOnConsecutiveCalls(
			true,
			false
		);

		$this->assertFalse( $this->mc_service->is_connected() );
	}

	public function test_is_google_connected() {
		$this->options->method( 'get' )
			->with( OptionsInterface::GOOGLE_CONNECTED, false )
			->willReturn( true );

		$this->assertTrue( $this->mc_service->is_google_connected() );
	}

	public function test_is_not_google_connected() {
		$this->options->method( 'get' )
			->with( OptionsInterface::GOOGLE_CONNECTED, false )
			->willReturn( false );

		$this->assertFalse( $this->mc_service->is_google_connected() );
	}

	public function test_is_ready_for_syncing() {
		$hash = md5( site_url() );
		$this->merchant->method( 'get_claimed_url_hash' )->willReturn( $hash );
		$this->options->method( 'get' )
			->withConsecutive(
				[ OptionsInterface::GOOGLE_CONNECTED, false ],
				[ OptionsInterface::MC_SETUP_COMPLETED_AT, false ]
			)
			->willReturnOnConsecutiveCalls(
				true,
				self::TEST_SETUP_COMPLETED
			);

		$this->assertTrue( $this->mc_service->is_ready_for_syncing() );
	}

	public function test_is_ready_for_syncing_not_setup() {
		$hash = md5( site_url() );
		$this->merchant->method( 'get_claimed_url_hash' )->willReturn( $hash );
		$this->options->method( 'get' )
			->withConsecutive(
				[ OptionsInterface::GOOGLE_CONNECTED, false ],
				[ OptionsInterface::MC_SETUP_COMPLETED_AT, false ]
			)
			->willReturnOnConsecutiveCalls(
				true,
				false
			);

		$this->transients->expects( $this->never() )
			->method( 'get' )
			->with( TransientsInterface::URL_MATCHES );

		$this->assertFalse( $this->mc_service->is_ready_for_syncing() );
	}

	public function test_is_ready_for_syncing_filter_override() {
		$hash = md5( 'https://staging-site.test' );
		$this->merchant->method( 'get_claimed_url_hash' )->willReturn( $hash );
		$this->options->method( 'get' )
			->withConsecutive(
				[ OptionsInterface::GOOGLE_CONNECTED, false ],
				[ OptionsInterface::MC_SETUP_COMPLETED_AT, false ]
			)
			->willReturnOnConsecutiveCalls(
				true,
				self::TEST_SETUP_COMPLETED
			);

		add_filter( 'woocommerce_gla_ready_for_syncing', '__return_true' );

		$this->assertTrue( $this->mc_service->is_ready_for_syncing() );
	}

	public function test_is_ready_for_syncing_fetch_from_transient() {
		$hash = md5( site_url() );
		$this->merchant->expects( $this->once() )
			->method( 'get_claimed_url_hash' )
			->willReturn( $hash );

		$this->options->expects( $this->exactly( 4 ) )
			->method( 'get' )
			->withConsecutive(
				[ OptionsInterface::GOOGLE_CONNECTED, false ],
				[ OptionsInterface::MC_SETUP_COMPLETED_AT, false ],
				[ OptionsInterface::GOOGLE_CONNECTED, false ],
				[ OptionsInterface::MC_SETUP_COMPLETED_AT, false ]
			)
			->willReturnOnConsecutiveCalls(
				true,
				self::TEST_SETUP_COMPLETED,
				true,
				self::TEST_SETUP_COMPLETED
			);

		$this->transients->expects( $this->exactly( 2 ) )
			->method( 'get' )
			->with( TransientsInterface::URL_MATCHES )
			->willReturnOnConsecutiveCalls(
				null,
				'yes'
			);

		$this->transients->expects( $this->once() )
			->method( 'set' )
			->with( TransientsInterface::URL_MATCHES, 'yes', HOUR_IN_SECONDS * 12 );

		// Call twice to ensure we are getting the value from the transient the second time.
		$this->assertTrue( $this->mc_service->is_ready_for_syncing() );
		$this->assertTrue( $this->mc_service->is_ready_for_syncing() );
	}

	public function test_is_store_country_supported() {
		$this->wc->method( 'get_base_country' )->willReturn( 'US' );
		$this->google_helper->method( 'is_country_supported' )->with( 'US' )->willReturn( true );
		$this->assertTrue( $this->mc_service->is_store_country_supported() );
	}

	public function test_is_not_store_country_supported() {
		$this->wc->method( 'get_base_country' )->willReturn( 'XX' );
		$this->google_helper->method( 'is_country_supported' )->with( 'XX' )->willReturn( false );
		$this->assertFalse( $this->mc_service->is_store_country_supported() );
	}

	public function test_is_language_supported() {
		$this->wp->method( 'get_locale' )->willReturn( 'en_US' );
		$this->google_helper->method( 'get_mc_supported_languages' )->willReturn( [ 'en' => 'en' ] );
		$this->assertTrue( $this->mc_service->is_language_supported() );
		$this->assertTrue( $this->mc_service->is_language_supported( 'en' ) );
		$this->assertFalse( $this->mc_service->is_language_supported( 'xx' ) );
	}

	public function test_is_contact_information_setup() {
		$this->options->method( 'get' )
			->with( OptionsInterface::CONTACT_INFO_SETUP, false )
			->willReturn( true );

		$this->assertTrue( $this->mc_service->is_contact_information_setup() );
	}

	public function test_is_not_contact_information_setup() {
		$this->options->method( 'get' )
			->withConsecutive(
				[ OptionsInterface::CONTACT_INFO_SETUP, false ],
				[ OptionsInterface::MC_SETUP_COMPLETED_AT, false ]
			)
			->willReturnOnConsecutiveCalls(
				false,
				false
			);

		$this->assertFalse( $this->mc_service->is_contact_information_setup() );
	}

	public function test_is_contact_information_setup_already_completed_onboarding() {
		$this->options->method( 'get' )
			->withConsecutive(
				[ OptionsInterface::CONTACT_INFO_SETUP, false ],
				[ OptionsInterface::MC_SETUP_COMPLETED_AT, false ]
			)
			->willReturnOnConsecutiveCalls(
				false,
				self::TEST_SETUP_COMPLETED
			);

		$this->contact_information->method( 'get_contact_information' )
			->willReturn( $this->get_valid_business_info() );
		$this->settings->method( 'get_store_address' )
			->willReturn( $this->get_sample_address() );
		$this->address_utility->method( 'compare_addresses' )
			->willReturn( true );

		$this->assertTrue( $this->mc_service->is_contact_information_setup() );
	}

	public function test_is_not_contact_information_setup_already_completed_onboarding() {
		$this->options->method( 'get' )
			->withConsecutive(
				[ OptionsInterface::CONTACT_INFO_SETUP, false ],
				[ OptionsInterface::MC_SETUP_COMPLETED_AT, false ]
			)
			->willReturnOnConsecutiveCalls(
				false,
				self::TEST_SETUP_COMPLETED
			);

		$this->assertFalse( $this->mc_service->is_contact_information_setup() );
	}

	public function test_is_promotion_supported_country() {
		$this->wc->method( 'get_base_country' )->willReturn( 'US' );
		$this->google_helper->method( 'get_mc_promotion_supported_countries' )
			->willReturn(
				[
					'AU',
					'CA',
					'DE',
					'FR',
					'GB',
					'IN',
					'US',
				]
			);

		$this->assertTrue( $this->mc_service->is_promotion_supported_country() );
		$this->assertTrue( $this->mc_service->is_promotion_supported_country( 'AU' ) );
		$this->assertTrue( $this->mc_service->is_promotion_supported_country( 'CA' ) );
		$this->assertTrue( $this->mc_service->is_promotion_supported_country( 'DE' ) );
		$this->assertTrue( $this->mc_service->is_promotion_supported_country( 'FR' ) );
		$this->assertTrue( $this->mc_service->is_promotion_supported_country( 'GB' ) );
		$this->assertTrue( $this->mc_service->is_promotion_supported_country( 'IN' ) );
		$this->assertTrue( $this->mc_service->is_promotion_supported_country( 'US' ) );
		$this->assertFalse( $this->mc_service->is_promotion_supported_country( 'XX' ) );
	}

	public function test_get_setup_status_complete() {
		$this->options->method( 'get' )
			->with( OptionsInterface::MC_SETUP_COMPLETED_AT, false )
			->willReturn( self::TEST_SETUP_COMPLETED );

		$this->assertEquals(
			[ 'status' => 'complete' ],
			$this->mc_service->get_setup_status()
		);
	}

	public function test_get_setup_status_step_accounts() {
		$this->assertEquals(
			[
				'status' => 'incomplete',
				'step'   => 'accounts',
			],
			$this->mc_service->get_setup_status()
		);
	}

	public function test_get_setup_status_step_product_listings() {
		$this->options->method( 'get_merchant_id' )->willReturn( 1234 );
		$this->merchant_account_state->method( 'last_incomplete_step' )->willReturn( '' );
		$this->options->method( 'get' )
			->withConsecutive(
				[ OptionsInterface::MC_SETUP_COMPLETED_AT, false ],
				[ OptionsInterface::TARGET_AUDIENCE ]
			)->willReturnOnConsecutiveCalls(
				false,
				[
					'location'  => 'selected',
					'countries' => [ 'US' ],
				]
			);
		$this->target_audience->method( 'get_target_countries' )->willReturn( [ 'US' ] );

		$this->assertEquals(
			[
				'status' => 'incomplete',
				'step'   => 'product_listings',
			],
			$this->mc_service->get_setup_status()
		);
	}

	public function test_get_setup_status_step_store_requirements() {
		$this->options->method( 'get_merchant_id' )->willReturn( 1234 );
		$this->merchant_account_state->method( 'last_incomplete_step' )->willReturn( '' );
		$this->options->method( 'get' )
			->withConsecutive(
				[ OptionsInterface::MC_SETUP_COMPLETED_AT, false ],
				[ OptionsInterface::TARGET_AUDIENCE ],
				[ OptionsInterface::MERCHANT_CENTER, [] ]
			)->willReturnOnConsecutiveCalls(
				false,
				[
					'location'  => 'selected',
					'countries' => [ 'US' ],
				],
				[
					'shipping_rate' => 'automatic',
					'shipping_time' => 'manual',
				]
			);

		$this->assertEquals(
			[
				'status' => 'incomplete',
				'step'   => 'store_requirements',
			],
			$this->mc_service->get_setup_status()
		);
	}

	public function test_get_setup_status_shipping_selected_rates() {
		$this->options->method( 'get_merchant_id' )->willReturn( 1234 );
		$this->merchant_account_state->method( 'last_incomplete_step' )->willReturn( '' );
		$this->options->method( 'get' )
			->withConsecutive(
				[ OptionsInterface::MC_SETUP_COMPLETED_AT, false ],
				[ OptionsInterface::TARGET_AUDIENCE ]
			)->willReturnOnConsecutiveCalls(
				false,
				[
					'location'  => 'selected',
					'countries' => [ 'US' ],
				]
			);
		$this->shipping_time_query->method( 'get_results' )
			->willReturn(
				[
					[
						'time'    => '1',
						'country' => 'GB',
					],
				]
			);
		$this->shipping_rate_query->method( 'get_results' )
			->willReturn(
				[
					[
						'rate'    => '10',
						'country' => 'GB',
					],
				]
			);
		$this->target_audience->method( 'get_target_countries' )->willReturn( [ 'GB' ] );

		$this->assertEquals(
			[
				'status' => 'incomplete',
				'step'   => 'store_requirements',
			],
			$this->mc_service->get_setup_status()
		);
	}

	public function test_get_setup_status_step_paid_ads() {
		$this->options->method( 'get_merchant_id' )->willReturn( 1234 );
		$this->merchant_account_state->method( 'last_incomplete_step' )->willReturn( '' );
		$this->options->method( 'get' )
			->withConsecutive(
				[ OptionsInterface::MC_SETUP_COMPLETED_AT, false ],
				[ OptionsInterface::TARGET_AUDIENCE ],
				[ OptionsInterface::MERCHANT_CENTER, [] ],
				[ OptionsInterface::MERCHANT_CENTER, [] ]
			)->willReturnOnConsecutiveCalls(
				false,
				[
					'location'  => 'selected',
					'countries' => [ 'GB' ],
				],
				[],
				[
					'website_live'            => true,
					'checkout_process_secure' => true,
					'payment_methods_visible' => true,
					'refund_tos_visible'      => true,
					'contact_info_visible'    => true,
				]
			);
		$this->shipping_time_query->method( 'get_results' )
			->willReturn(
				[
					[
						'time'    => '1',
						'country' => 'GB',
					],
				]
			);
		$this->shipping_rate_query->method( 'get_results' )
			->willReturn(
				[
					[
						'rate'    => '10',
						'country' => 'GB',
					],
				]
			);
		$this->target_audience->method( 'get_target_countries' )->willReturn( [ 'GB' ] );
		$this->contact_information->method( 'get_contact_information' )
			->willReturn( $this->get_valid_business_info() );
		$this->settings->method( 'get_store_address' )
			->willReturn( $this->get_sample_address() );
		$this->address_utility->method( 'compare_addresses' )
			->willReturn( true );

		$this->assertEquals(
			[
				'status' => 'incomplete',
				'step'   => 'paid_ads',
			],
			$this->mc_service->get_setup_status()
		);
	}

	public function test_filter_contact_info_issue() {
		$this->options->method( 'get' )
			->withConsecutive(
				[ OptionsInterface::MC_SETUP_COMPLETED_AT, false ],
				[ OptionsInterface::CONTACT_INFO_SETUP, false ],
				[ OptionsInterface::MC_SETUP_COMPLETED_AT, false ]
			)
			->willReturnOnConsecutiveCalls(
				self::TEST_SETUP_COMPLETED,
				false,
				false
			);

		$issues = apply_filters( 'woocommerce_gla_custom_merchant_issues', [], new DateTime() );
		$this->assertEquals( 'missing_contact_information', $issues[0]['code'] );
		$this->assertEquals( 'account', $issues[0]['type'] );
		$this->assertEquals( 'error', $issues[0]['severity'] );
	}

	public function test_filter_contact_info_already_added() {
		$this->options->method( 'get' )
			->withConsecutive(
				[ OptionsInterface::MC_SETUP_COMPLETED_AT, false ],
				[ OptionsInterface::CONTACT_INFO_SETUP, false ]
			)
			->willReturnOnConsecutiveCalls(
				self::TEST_SETUP_COMPLETED,
				true
			);

		$this->assertTrue( has_filter( 'woocommerce_gla_custom_merchant_issues' ) );
		$this->assertEquals(
			[],
			apply_filters( 'woocommerce_gla_custom_merchant_issues', [], new DateTime() )
		);
	}

	public function test_has_account_issues() {
		$this->merchant_statuses->method( 'get_issues' )
			->with( MerchantStatuses::TYPE_ACCOUNT )
			->willReturn(
				[
					'issues' => [
						'code'     => 'issue_code',
						'type'     => 'account',
						'severity' => 'error',
					],
				]
			);

		$this->assertTrue( $this->mc_service->has_account_issues() );
	}

	public function test_has_at_least_one_synced_product() {
		$this->merchant_statuses->method( 'get_product_statistics' )
			->willReturn(
				[
					'statistics' => [
						'active'     => 1,
						'pending'    => 4,
						'not_synced' => 5,
					],
				]
			);

		$this->assertTrue( $this->mc_service->has_at_least_one_synced_product() );
	}

}
