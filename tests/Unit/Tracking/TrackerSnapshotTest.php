<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Tracking;

use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AdsService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\MerchantMetrics;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MarketService;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\TargetAudience;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductImageSizeAudit;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Tracking\TrackerSnapshot;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\Container\Container;
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

		$container = new Container();
		$container->addShared( AdsService::class, $this->ads_service );
		$container->addShared( MerchantCenterService::class, $this->mc_service );
		$container->addShared( MerchantMetrics::class, $this->merchant_metrics );
		$container->addShared( TargetAudience::class, $this->target_audience );
		$container->addShared( MarketService::class, $this->market_service );
		$container->addShared( ProductImageSizeAudit::class, $this->image_size_audit );

		$this->snapshot = new TrackerSnapshot();
		$this->snapshot->set_container( $container );
		$this->snapshot->set_options_object( $this->options );
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

	/**
	 * Rebuild the snapshot with a connected Merchant Center account.
	 *
	 * @param bool $has_account_issues
	 */
	private function use_connected_merchant_center( bool $has_account_issues = false ): void {
		$mc_service = $this->createMock( MerchantCenterService::class );
		$mc_service->method( 'is_connected' )->willReturn( true );
		$mc_service->method( 'has_account_issues' )->willReturn( $has_account_issues );

		$container = new Container();
		$container->addShared( AdsService::class, $this->ads_service );
		$container->addShared( MerchantCenterService::class, $mc_service );
		$container->addShared( MerchantMetrics::class, $this->merchant_metrics );
		$container->addShared( TargetAudience::class, $this->target_audience );
		$container->addShared( MarketService::class, $this->market_service );
		$container->addShared( ProductImageSizeAudit::class, $this->image_size_audit );

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
		$this->options->method( 'get' )->willReturnCallback(
			function ( $key ) use ( $summary ) {
				return OptionsInterface::FEED_ISSUE_SUMMARY === $key ? $summary : null;
			}
		);
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
