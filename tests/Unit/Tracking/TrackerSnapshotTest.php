<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Tracking;

use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AdsService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Ads;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\MerchantMetrics;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Middleware;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MarketService;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\TargetAudience;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\Transients;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\TransientsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductImageSizeAudit;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Tracking\TrackerSnapshot;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\Container\Container;
use Error;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class TrackerSnapshotTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Tracking
 */
class TrackerSnapshotTest extends UnitTest {

	/** @var MockObject|AdsService $ads_service */
	protected $ads_service;

	/** @var MockObject|MerchantCenterService $mc_service */
	protected $mc_service;

	/** @var MockObject|MerchantMetrics $merchant_metrics */
	protected $merchant_metrics;

	/** @var MockObject|TargetAudience $target_audience */
	protected $target_audience;

	/** @var MockObject|MarketService $market_service */
	protected $market_service;

	/** @var MockObject|ProductImageSizeAudit $image_size_audit */
	protected $image_size_audit;

	/** @var MockObject|OptionsInterface $options */
	protected $options;

	/** @var MockObject|Ads $ads */
	protected $ads;

	/** @var MockObject|Middleware $middleware */
	protected $middleware;

	/** @var MockObject|TransientsInterface $transients */
	protected $transients;

	/** @var TrackerSnapshot $snapshot */
	protected $snapshot;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->ads_service      = $this->createMock( AdsService::class );
		$this->mc_service       = $this->createMock( MerchantCenterService::class );
		$this->merchant_metrics = $this->createMock( MerchantMetrics::class );
		$this->target_audience  = $this->createMock( TargetAudience::class );
		$this->market_service   = $this->createMock( MarketService::class );
		$this->image_size_audit = $this->createMock( ProductImageSizeAudit::class );
		$this->options          = $this->createMock( OptionsInterface::class );

		$this->target_audience->method( 'get_target_countries' )->willReturn( [] );
		$this->mc_service->method( 'is_connected' )->willReturn( false );
		$this->ads_service->method( 'is_setup_started' )->willReturn( false );
		$this->merchant_metrics->method( 'get_campaign_count' )->willReturn( 0 );
		$this->market_service->method( 'get_non_default_locale_market_counts' )->willReturn(
			[
				'multilingual'  => 0,
				'multicurrency' => 0,
			]
		);

		$this->ads        = $this->createMock( Ads::class );
		$this->middleware = $this->createMock( Middleware::class );
		$this->transients = $this->createMock( TransientsInterface::class );

		$this->build_snapshot();
	}

	public function test_reviews_collection_is_yes_when_enabled() {
		$this->options->method( 'get' )->willReturnCallback(
			function ( $key ) {
				if ( OptionsInterface::GOOGLE_CUSTOMER_REVIEWS === $key ) {
					return [ 'gcr_collect_reviews_after_purchase' => true ];
				}
				return null;
			}
		);

		$settings = $this->invoke_get_settings();

		$this->assertSame( 'yes', $settings['reviews_collection'] );
	}

	public function test_reviews_collection_is_no_when_disabled() {
		$this->options->method( 'get' )->willReturnCallback(
			function ( $key ) {
				if ( OptionsInterface::GOOGLE_CUSTOMER_REVIEWS === $key ) {
					return [ 'gcr_collect_reviews_after_purchase' => false ];
				}
				return null;
			}
		);

		$settings = $this->invoke_get_settings();

		$this->assertSame( 'no', $settings['reviews_collection'] );
	}

	public function test_reviews_collection_is_no_when_key_missing_from_google_customer_reviews_settings() {
		$this->options->method( 'get' )->willReturnCallback(
			function ( $key ) {
				if ( OptionsInterface::GOOGLE_CUSTOMER_REVIEWS === $key ) {
					return [];
				}
				return null;
			}
		);

		$settings = $this->invoke_get_settings();

		$this->assertSame( 'no', $settings['reviews_collection'] );
	}

	public function test_reviews_badge_widget_is_yes_when_enabled() {
		$this->options->method( 'get' )->willReturnCallback(
			function ( $key ) {
				if ( OptionsInterface::GOOGLE_CUSTOMER_REVIEWS === $key ) {
					return [ 'gcr_badge_widget_enabled' => true ];
				}
				return null;
			}
		);

		$settings = $this->invoke_get_settings();

		$this->assertSame( 'yes', $settings['reviews_badge_widget'] );
	}

	public function test_reviews_badge_widget_is_no_when_disabled() {
		$this->options->method( 'get' )->willReturnCallback(
			function ( $key ) {
				if ( OptionsInterface::GOOGLE_CUSTOMER_REVIEWS === $key ) {
					return [ 'gcr_badge_widget_enabled' => false ];
				}
				return null;
			}
		);

		$settings = $this->invoke_get_settings();

		$this->assertSame( 'no', $settings['reviews_badge_widget'] );
	}

	public function test_reviews_badge_widget_is_no_when_key_missing_from_merchant_center_settings() {
		$this->options->method( 'get' )->willReturnCallback(
			function ( $key ) {
				if ( OptionsInterface::MERCHANT_CENTER === $key ) {
					return [];
				}
				return null;
			}
		);

		$settings = $this->invoke_get_settings();

		$this->assertSame( 'no', $settings['reviews_badge_widget'] );
	}

	public function test_feed_issue_counts_are_sent_from_the_saved_summary() {
		$this->use_connected_merchant_center();
		$this->set_feed_issue_summary( $this->get_feed_issue_summary() );

		$settings = $this->invoke_get_settings();

		foreach ( $this->get_feed_issue_summary() as $field => $count ) {
			$this->assertSame( $count, $settings[ $field ] );
		}
	}

	public function test_feed_issue_counts_are_null_when_merchant_center_is_not_connected() {
		$this->set_feed_issue_summary( $this->get_feed_issue_summary() );

		$settings = $this->invoke_get_settings();

		foreach ( array_keys( $this->get_feed_issue_summary() ) as $field ) {
			$this->assertArrayHasKey( $field, $settings );
			$this->assertNull( $settings[ $field ] );
		}
	}

	/**
	 * @dataProvider invalid_feed_issue_summary_provider
	 *
	 * @param mixed $summary
	 */
	public function test_feed_issue_counts_are_all_null_when_the_saved_summary_is_invalid( $summary ) {
		$this->use_connected_merchant_center();
		$this->set_feed_issue_summary( $summary );

		$settings = $this->invoke_get_settings();

		foreach ( array_keys( $this->get_feed_issue_summary() ) as $field ) {
			$this->assertNull( $settings[ $field ] );
		}
	}

	public function invalid_feed_issue_summary_provider(): array {
		$summary = $this->get_feed_issue_summary();

		$missing_key = $summary;
		unset( $missing_key['synced_product_count'] );

		return [
			'no saved summary' => [ null ],
			'not an array'     => [ 'summary' ],
			'missing key'      => [ $missing_key ],
			'negative count'   => [ array_merge( $summary, [ 'disapproved_product_count' => -1 ] ) ],
			'string count'     => [ array_merge( $summary, [ 'synced_product_count' => '10' ] ) ],
		];
	}

	public function test_has_account_issue_is_unchanged_by_the_blocking_account_issue_count() {
		$this->use_connected_merchant_center( true );
		$this->set_feed_issue_summary( array_merge( $this->get_feed_issue_summary(), [ 'blocking_account_issue_count' => 0 ] ) );

		$settings = $this->invoke_get_settings();

		$this->assertSame( 'yes', $settings['has_account_issue'] );
		$this->assertSame( 0, $settings['blocking_account_issue_count'] );
	}

	public function test_feed_issue_details_are_null_for_a_summary_saved_without_them() {
		$this->use_connected_merchant_center();
		$this->set_feed_issue_summary( $this->get_feed_issue_summary() );

		$settings = $this->invoke_get_settings();

		foreach ( $this->get_feed_issue_summary() as $field => $count ) {
			$this->assertSame( $count, $settings[ $field ] );
		}
		foreach ( array_keys( $this->get_feed_issue_details() ) as $field ) {
			$this->assertArrayHasKey( $field, $settings );
			$this->assertNull( $settings[ $field ] );
		}
	}

	public function test_feed_issue_details_are_sent_from_the_saved_summary() {
		$this->use_connected_merchant_center();
		$this->set_feed_issue_summary( array_merge( $this->get_feed_issue_summary(), $this->get_feed_issue_details() ) );

		$settings = $this->invoke_get_settings();

		foreach ( $this->get_feed_issue_details() as $field => $value ) {
			$this->assertSame( $value, $settings[ $field ] );
		}
	}

	public function test_feed_issue_details_send_checked_with_none_found_as_empty_arrays() {
		$empty_codes = [
			'account_issue_codes'     => [],
			'plugin_issue_codes'      => [],
			'top_product_issue_codes' => [],
			'top_presync_issue_codes' => [],
		];
		$this->use_connected_merchant_center();
		$this->set_feed_issue_summary( array_merge( $this->get_feed_issue_summary(), $this->get_feed_issue_details(), $empty_codes ) );

		$settings = $this->invoke_get_settings();

		foreach ( array_keys( $empty_codes ) as $field ) {
			$this->assertSame( [], $settings[ $field ] );
		}
	}

	public function test_feed_issue_details_accept_a_numeric_issue_code() {
		$this->use_connected_merchant_center();
		$this->set_feed_issue_summary(
			array_merge(
				$this->get_feed_issue_summary(),
				$this->get_feed_issue_details(),
				[ 'top_presync_issue_codes' => [ '404' => 2 ] ]
			)
		);

		$settings = $this->invoke_get_settings();

		$this->assertSame( [ 404 => 2 ], $settings['top_presync_issue_codes'] );
	}

	/**
	 * @dataProvider invalid_feed_issue_detail_provider
	 *
	 * @param string $invalid_field
	 * @param mixed  $invalid_value
	 */
	public function test_an_invalid_feed_issue_detail_is_null_on_its_own( string $invalid_field, $invalid_value ) {
		$this->use_connected_merchant_center();
		$this->set_feed_issue_summary(
			array_merge(
				$this->get_feed_issue_summary(),
				$this->get_feed_issue_details(),
				[ $invalid_field => $invalid_value ]
			)
		);

		$settings = $this->invoke_get_settings();

		$this->assertNull( $settings[ $invalid_field ] );
		foreach ( $this->get_feed_issue_details() as $field => $value ) {
			if ( $field !== $invalid_field ) {
				$this->assertSame( $value, $settings[ $field ] );
			}
		}
		foreach ( $this->get_feed_issue_summary() as $field => $count ) {
			$this->assertSame( $count, $settings[ $field ] );
		}
	}

	public function invalid_feed_issue_detail_provider(): array {
		return [
			'negative timestamp'            => [ 'feed_issue_summary_checked_at', -1 ],
			'string approved count'         => [ 'approved_product_count', '40' ],
			'negative critical count'       => [ 'critical_account_issue_count', -1 ],
			'unknown coverage'              => [ 'critical_covers_all_targets', 'maybe' ],
			'account codes not an array'    => [ 'account_issue_codes', 'homepage_not_claimed' ],
			'account code unknown severity' => [ 'account_issue_codes', [ 'homepage_not_claimed' => 'warning' ] ],
			'account code with a hyphen'    => [ 'account_issue_codes', [ 'homepage-not-claimed' => 'critical' ] ],
			'account code with a newline'   => [ 'account_issue_codes', [ "homepage_not_claimed\n" => 'critical' ] ],
			'account code over 64 chars'    => [ 'account_issue_codes', [ str_repeat( 'a', 65 ) => 'error' ] ],
			'plugin codes not an array'     => [ 'plugin_issue_codes', 'missing_contact_information' ],
			'plugin codes not a list'       => [ 'plugin_issue_codes', [ 'code' => 'missing_contact_information' ] ],
			'plugin code outside allowlist' => [ 'plugin_issue_codes', [ 'missing_contact_information', 'other_code' ] ],
			'product codes not an array'    => [ 'top_product_issue_codes', 'missing_gtin' ],
			'product code negative count'   => [ 'top_product_issue_codes', [ 'missing_gtin' => -1 ] ],
			'presync code string count'     => [ 'top_presync_issue_codes', [ 'presync_error_other' => '2' ] ],
			'presync code with a space'     => [ 'top_presync_issue_codes', [ 'presync error' => 2 ] ],
		];
	}

	public function test_feed_issue_details_are_sent_when_the_feed_issue_counts_are_invalid() {
		$this->use_connected_merchant_center();
		$this->set_feed_issue_summary(
			array_merge(
				$this->get_feed_issue_summary(),
				$this->get_feed_issue_details(),
				[ 'synced_product_count' => -1 ]
			)
		);

		$settings = $this->invoke_get_settings();

		$this->assertNull( $settings['synced_product_count'] );
		foreach ( $this->get_feed_issue_details() as $field => $value ) {
			$this->assertSame( $value, $settings[ $field ] );
		}
	}

	/**
	 * @dataProvider not_an_array_summary_provider
	 *
	 * @param mixed $summary
	 */
	public function test_feed_issue_details_are_null_when_the_saved_summary_is_not_an_array( $summary ) {
		$this->use_connected_merchant_center();
		$this->set_feed_issue_summary( $summary );

		$settings = $this->invoke_get_settings();

		foreach ( array_keys( $this->get_feed_issue_details() ) as $field ) {
			$this->assertArrayHasKey( $field, $settings );
			$this->assertNull( $settings[ $field ] );
		}
	}

	public function not_an_array_summary_provider(): array {
		return [
			'no saved summary' => [ null ],
			'not an array'     => [ 'summary' ],
		];
	}

	public function test_feed_issue_details_and_subaccount_are_null_when_merchant_center_is_not_connected() {
		$this->set_feed_issue_summary( array_merge( $this->get_feed_issue_summary(), $this->get_feed_issue_details() ) );
		$this->middleware->expects( $this->never() )->method( 'is_subaccount' );

		$settings = $this->invoke_get_settings();

		foreach ( array_keys( $this->get_feed_issue_details() ) as $field ) {
			$this->assertArrayHasKey( $field, $settings );
			$this->assertNull( $settings[ $field ] );
		}
		$this->assertArrayHasKey( 'mc_is_subaccount', $settings );
		$this->assertNull( $settings['mc_is_subaccount'] );
	}

	/**
	 * @dataProvider serving_campaign_count_provider
	 *
	 * @param int|null $count
	 */
	public function test_ads_serving_campaign_count_is_sent_from_merchant_metrics( ?int $count ) {
		$this->merchant_metrics->method( 'get_serving_campaign_count' )->willReturn( $count );

		$settings = $this->invoke_get_settings();

		$this->assertArrayHasKey( 'ads_serving_campaign_count', $settings );
		$this->assertSame( $count, $settings['ads_serving_campaign_count'] );
	}

	public function serving_campaign_count_provider(): array {
		return [
			'serving campaigns' => [ 3 ],
			'none serving'      => [ 0 ],
			'query failed'      => [ null ],
		];
	}

	public function test_ads_serving_campaign_count_is_null_when_reading_it_throws() {
		$this->merchant_metrics->method( 'get_serving_campaign_count' )->willThrowException( new Error( 'Ads client failed' ) );

		$settings = $this->invoke_get_settings();

		$this->assertNull( $settings['ads_serving_campaign_count'] );
		$this->assertSame( 0, $settings['ads_campaign_count'] );
	}

	/**
	 * @dataProvider valid_billing_status_provider
	 *
	 * @param string $status
	 */
	public function test_ads_billing_status_is_sent_from_the_cache_without_calling_ads( string $status ) {
		$this->use_connected_ads_account();
		$this->transients->method( 'get' )->with( TransientsInterface::ADS_BILLING_STATUS )->willReturn( $status );
		$this->transients->expects( $this->never() )->method( 'set' );
		$this->ads->expects( $this->never() )->method( 'get_billing_status' );

		$settings = $this->invoke_get_settings();

		$this->assertSame( $status, $settings['ads_billing_status'] );
	}

	public function valid_billing_status_provider(): array {
		return [
			'approved' => [ 'approved' ],
			'pending'  => [ 'pending' ],
			'unknown'  => [ 'unknown' ],
		];
	}

	/**
	 * @dataProvider billing_status_cache_miss_provider
	 *
	 * @param mixed $cached
	 */
	public function test_ads_billing_status_is_fetched_and_cached_for_12_hours_on_a_cache_miss( $cached ) {
		$this->use_connected_ads_account();
		$this->transients->method( 'get' )->willReturn( $cached );
		$this->ads->expects( $this->once() )->method( 'get_billing_status' )->willReturn( 'cancelled' );
		$this->transients->expects( $this->once() )
			->method( 'set' )
			->with( TransientsInterface::ADS_BILLING_STATUS, 'cancelled', 12 * HOUR_IN_SECONDS );

		$settings = $this->invoke_get_settings();

		$this->assertSame( 'cancelled', $settings['ads_billing_status'] );
	}

	public function billing_status_cache_miss_provider(): array {
		return [
			'nothing cached' => [ null ],
			'invalid cached' => [ 'bogus' ],
		];
	}

	public function test_ads_billing_status_is_cached_in_a_registered_transient() {
		$this->use_connected_ads_account();
		$this->transients = new Transients();
		$this->build_snapshot();
		$this->ads->expects( $this->once() )->method( 'get_billing_status' )->willReturn( 'approved' );

		$settings = $this->invoke_get_settings();

		$this->assertSame( 'approved', $settings['ads_billing_status'] );
		$this->assertSame( 'approved', ( new Transients() )->get( TransientsInterface::ADS_BILLING_STATUS ) );
	}

	/**
	 * @dataProvider invalid_billing_status_provider
	 *
	 * @param string $status
	 */
	public function test_ads_billing_status_is_sent_and_cached_as_unknown_when_the_filtered_status_is_invalid( string $status ) {
		$this->use_connected_ads_account();
		$this->ads->method( 'get_billing_status' )->willReturn( $status );
		$this->transients->expects( $this->once() )
			->method( 'set' )
			->with( TransientsInterface::ADS_BILLING_STATUS, 'unknown', 12 * HOUR_IN_SECONDS );

		$settings = $this->invoke_get_settings();

		$this->assertSame( 'unknown', $settings['ads_billing_status'] );
	}

	public function invalid_billing_status_provider(): array {
		return [
			'unrecognized label' => [ 'bogus' ],
			'unmapped status'    => [ '' ],
		];
	}

	public function test_ads_billing_status_is_unknown_and_not_cached_when_fetching_throws() {
		$this->use_connected_ads_account();
		$this->ads->method( 'get_billing_status' )->willThrowException( new Error( 'Filter returned an array' ) );
		$this->transients->expects( $this->never() )->method( 'set' );

		$settings = $this->invoke_get_settings();

		$this->assertSame( 'unknown', $settings['ads_billing_status'] );
		$this->assertSame( 1234567890, $settings['ads_customer_id'] );
	}

	/**
	 * @dataProvider billing_status_not_read_provider
	 *
	 * @param int  $ads_id
	 * @param bool $google_connected
	 */
	public function test_ads_billing_status_is_null_without_calling_ads( int $ads_id, bool $google_connected ) {
		$this->options->method( 'get_ads_id' )->willReturn( $ads_id );
		$this->set_option_values( [ OptionsInterface::GOOGLE_CONNECTED => $google_connected ] );
		$this->ads->expects( $this->never() )->method( 'get_billing_status' );
		$this->transients->expects( $this->never() )->method( 'get' );

		$settings = $this->invoke_get_settings();

		$this->assertArrayHasKey( 'ads_billing_status', $settings );
		$this->assertNull( $settings['ads_billing_status'] );
	}

	public function billing_status_not_read_provider(): array {
		return [
			'no Ads account'         => [ 0, true ],
			'Google not connected'   => [ 1234567890, false ],
			'neither Ads nor Google' => [ 0, false ],
		];
	}

	/**
	 * @dataProvider subaccount_provider
	 *
	 * @param bool   $is_subaccount
	 * @param string $expected
	 */
	public function test_mc_is_subaccount_is_sent_as_yes_or_no( bool $is_subaccount, string $expected ) {
		$this->use_connected_merchant_center();
		$this->middleware->method( 'is_subaccount' )->willReturn( $is_subaccount );

		$settings = $this->invoke_get_settings();

		$this->assertSame( $expected, $settings['mc_is_subaccount'] );
	}

	public function subaccount_provider(): array {
		return [
			'sub-account'        => [ true, 'yes' ],
			'standalone account' => [ false, 'no' ],
		];
	}

	public function test_mc_is_subaccount_is_null_when_the_lookup_throws_and_the_snapshot_still_builds() {
		$this->use_connected_merchant_center();
		$this->set_feed_issue_summary( array_merge( $this->get_feed_issue_summary(), $this->get_feed_issue_details() ) );
		$this->middleware->method( 'is_subaccount' )->willThrowException( new Error( 'Connect Server failed' ) );

		$settings = $this->invoke_get_settings();

		$this->assertNull( $settings['mc_is_subaccount'] );
		foreach ( array_merge( $this->get_feed_issue_summary(), $this->get_feed_issue_details() ) as $field => $value ) {
			$this->assertSame( $value, $settings[ $field ] );
		}
	}

	/**
	 * Rebuild the snapshot with a connected Merchant Center account.
	 *
	 * @param bool $has_account_issues
	 */
	private function use_connected_merchant_center( bool $has_account_issues = false ): void {
		$this->mc_service = $this->createMock( MerchantCenterService::class );
		$this->mc_service->method( 'is_connected' )->willReturn( true );
		$this->mc_service->method( 'has_account_issues' )->willReturn( $has_account_issues );

		$this->build_snapshot();
	}

	/**
	 * Build the snapshot with a container holding the current service mocks.
	 */
	private function build_snapshot(): void {
		$container = new Container();
		$container->addShared( Ads::class, $this->ads );
		$container->addShared( AdsService::class, $this->ads_service );
		$container->addShared( MerchantCenterService::class, $this->mc_service );
		$container->addShared( MerchantMetrics::class, $this->merchant_metrics );
		$container->addShared( Middleware::class, $this->middleware );
		$container->addShared( TargetAudience::class, $this->target_audience );
		$container->addShared( MarketService::class, $this->market_service );
		$container->addShared( ProductImageSizeAudit::class, $this->image_size_audit );
		$container->addShared( TransientsInterface::class, $this->transients );

		$this->snapshot = new TrackerSnapshot();
		$this->snapshot->set_container( $container );
		$this->snapshot->set_options_object( $this->options );
	}

	/**
	 * Return the given value for the saved feed issue summary option.
	 *
	 * @param mixed $summary
	 */
	private function set_feed_issue_summary( $summary ): void {
		$this->set_option_values( [ OptionsInterface::FEED_ISSUE_SUMMARY => $summary ] );
	}

	/**
	 * Return the given values for their option keys, and null for any other option.
	 *
	 * @param array $values Option values keyed by option name.
	 */
	private function set_option_values( array $values ): void {
		$this->options->method( 'get' )->willReturnCallback(
			function ( $key ) use ( $values ) {
				return $values[ $key ] ?? null;
			}
		);
	}

	/**
	 * Connect Google and an Ads account, so the snapshot reads the Ads billing status.
	 */
	private function use_connected_ads_account(): void {
		$this->options->method( 'get_ads_id' )->willReturn( 1234567890 );
		$this->set_option_values( [ OptionsInterface::GOOGLE_CONNECTED => true ] );
	}

	/**
	 * A valid saved feed issue summary.
	 *
	 * @return array
	 */
	private function get_feed_issue_summary(): array {
		return [
			'blocking_account_issue_count' => 1,
			'disapproved_product_count'    => 2,
			'error_issue_product_count'    => 3,
			'presync_error_product_count'  => 4,
			'synced_product_count'         => 50,
		];
	}

	/**
	 * The issue codes and counts a current version saves beside the feed issue counts.
	 *
	 * @return array
	 */
	private function get_feed_issue_details(): array {
		return [
			'feed_issue_summary_checked_at' => 1795000000,
			'approved_product_count'        => 40,
			'critical_account_issue_count'  => 1,
			'account_issue_codes'           => [
				'homepage_not_claimed' => 'critical',
				'missing_shipping'     => 'error',
			],
			'critical_covers_all_targets'   => 'partial',
			'plugin_issue_codes'            => [ 'incompatible_google_product_feed', 'missing_contact_information' ],
			'top_product_issue_codes'       => [
				'image_link_broken' => 7,
				'missing_gtin'      => 3,
			],
			'top_presync_issue_codes'       => [ 'presync_error_other' => 2 ],
		];
	}

	/**
	 * Invoke the protected get_settings() method.
	 *
	 * @return array
	 */
	private function invoke_get_settings(): array {
		$method = new \ReflectionMethod( $this->snapshot, 'get_settings' );
		$method->setAccessible( true );

		return $method->invoke( $this->snapshot );
	}
}
