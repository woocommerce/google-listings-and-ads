<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Tracking;

use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AdsService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\MerchantMetrics;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\TargetAudience;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
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
		$this->options          = $this->createMock( OptionsInterface::class );

		$this->target_audience->method( 'get_target_countries' )->willReturn( [] );
		$this->mc_service->method( 'is_connected' )->willReturn( false );
		$this->ads_service->method( 'is_setup_started' )->willReturn( false );
		$this->merchant_metrics->method( 'get_campaign_count' )->willReturn( 0 );

		$container = new Container();
		$container->addShared( AdsService::class, $this->ads_service );
		$container->addShared( MerchantCenterService::class, $this->mc_service );
		$container->addShared( MerchantMetrics::class, $this->merchant_metrics );
		$container->addShared( TargetAudience::class, $this->target_audience );

		$this->snapshot = new TrackerSnapshot();
		$this->snapshot->set_container( $container );
		$this->snapshot->set_options_object( $this->options );
	}

	public function test_reviews_collection_is_yes_when_enabled() {
		$this->options->method( 'get' )->willReturnCallback(
			function ( $key ) {
				if ( OptionsInterface::MERCHANT_CENTER === $key ) {
					return [ 'collect_reviews_after_purchase' => true ];
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
				if ( OptionsInterface::MERCHANT_CENTER === $key ) {
					return [ 'collect_reviews_after_purchase' => false ];
				}
				return null;
			}
		);

		$settings = $this->invoke_get_settings();

		$this->assertSame( 'no', $settings['reviews_collection'] );
	}

	public function test_reviews_collection_is_no_when_key_missing_from_merchant_center_settings() {
		$this->options->method( 'get' )->willReturnCallback(
			function ( $key ) {
				if ( OptionsInterface::MERCHANT_CENTER === $key ) {
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
				if ( OptionsInterface::MERCHANT_CENTER === $key ) {
					return [ 'badge_widget_enabled' => true ];
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
				if ( OptionsInterface::MERCHANT_CENTER === $key ) {
					return [ 'badge_widget_enabled' => false ];
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
