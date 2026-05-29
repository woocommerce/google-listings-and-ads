<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\Google\GoogleDatafeedService;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\JobRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\UpdateShippingSettings;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MarketDatafeedManager;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MarketService;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\MerchantCenter
 */
class MarketDatafeedManagerTest extends UnitTest {

	/** @var MockObject|GoogleDatafeedService $datafeed_service */
	protected $datafeed_service;

	/** @var MockObject|MarketService $market_service */
	protected $market_service;

	/** @var MockObject|JobRepository $job_repository */
	protected $job_repository;

	/** @var MockObject|UpdateShippingSettings $update_shipping_settings */
	protected $update_shipping_settings;

	/** @var MarketDatafeedManager $manager */
	protected $manager;

	public function setUp(): void {
		parent::setUp();

		$this->datafeed_service         = $this->createMock( GoogleDatafeedService::class );
		$this->market_service           = $this->createMock( MarketService::class );
		$this->update_shipping_settings = $this->createMock( UpdateShippingSettings::class );
		$this->job_repository           = $this->createMock( JobRepository::class );
		$this->job_repository->method( 'get' )
			->with( UpdateShippingSettings::class )
			->willReturn( $this->update_shipping_settings );
		$this->manager = new MarketDatafeedManager( $this->datafeed_service, $this->market_service, $this->job_repository );
	}

	public function test_register_adds_action_hooks() {
		$this->manager->register();

		$this->assertNotFalse( has_action( 'woocommerce_gla_market_added', [ $this->manager, 'on_market_added' ] ) );
		$this->assertNotFalse( has_action( 'woocommerce_gla_market_updated', [ $this->manager, 'on_market_updated' ] ) );
		$this->assertNotFalse( has_action( 'woocommerce_gla_market_deleted', [ $this->manager, 'on_market_deleted' ] ) );
	}

	public function test_on_market_added_ensures_datafeed_for_each_pair() {
		$this->datafeed_service->expects( $this->once() )
								->method( 'ensure_for_feed_label' )
								->with( 'fr-EUR', 'fr', [ 'FR' ] );

		$this->manager->on_market_added(
			'fr-market',
			[
				'country'  => 'FR',
				'language' => [ 'fr' ],
				'currency' => [ 'EUR' ],
			]
		);
	}

	public function test_on_market_added_handles_multiple_pairs() {
		$calls = [];
		$this->datafeed_service->expects( $this->exactly( 2 ) )
								->method( 'ensure_for_feed_label' )
								->willReturnCallback(
									function ( $feed_label ) use ( &$calls ) {
										$calls[] = $feed_label;
									}
								);

		$this->manager->on_market_added(
			'mixed-market',
			[
				'country'  => 'BE',
				'language' => [ 'fr', 'nl' ],
				'currency' => [ 'EUR' ],
			]
		);

		$this->assertContains( 'fr-EUR', $calls );
		$this->assertContains( 'nl-EUR', $calls );
	}

	public function test_on_market_added_uses_countries_array_for_primary_market() {
		$this->datafeed_service->expects( $this->once() )
								->method( 'ensure_for_feed_label' )
								->with( 'en-USD', 'en', [ 'US', 'CA' ] );

		$this->manager->on_market_added(
			'primary',
			[
				'countries' => [ 'US', 'CA' ],
				'language'  => [ 'en' ],
				'currency'  => [ 'USD' ],
			]
		);
	}

	public function test_on_market_updated_ensures_datafeed() {
		$this->datafeed_service->expects( $this->once() )
								->method( 'ensure_for_feed_label' )
								->with( 'de-EUR', 'de', [ 'DE' ] );

		$this->manager->on_market_updated(
			'de-market',
			[
				'country'  => 'DE',
				'language' => [ 'de' ],
				'currency' => [ 'EUR' ],
			]
		);
	}

	public function test_on_market_deleted_deletes_unused_pairs() {
		// Remaining markets do not include fr-EUR.
		$this->market_service->method( 'get_markets' )->willReturn(
			[
				'primary' => [
					'countries' => [ 'US' ],
					'language'  => [ 'en' ],
					'currency'  => [ 'USD' ],
				],
			]
		);

		$this->datafeed_service->expects( $this->once() )
								->method( 'delete_by_feed_label' )
								->with( 'fr-EUR' );

		$this->manager->on_market_deleted(
			'fr-market',
			[
				'country'  => 'FR',
				'language' => [ 'fr' ],
				'currency' => [ 'EUR' ],
			]
		);
	}

	public function test_on_market_deleted_keeps_pairs_still_used_by_other_markets() {
		// en-USD is still used by the primary market.
		$this->market_service->method( 'get_markets' )->willReturn(
			[
				'primary' => [
					'countries' => [ 'US' ],
					'language'  => [ 'en' ],
					'currency'  => [ 'USD' ],
				],
			]
		);

		$this->datafeed_service->expects( $this->never() )
								->method( 'delete_by_feed_label' );

		// Delete a secondary market that also had en-USD (shared with primary).
		$this->manager->on_market_deleted(
			'secondary',
			[
				'country'  => 'CA',
				'language' => [ 'en' ],
				'currency' => [ 'USD' ],
			]
		);
	}

	public function test_on_market_deleted_skips_when_no_target_countries() {
		$this->datafeed_service->expects( $this->never() )
								->method( 'ensure_for_feed_label' );

		$this->manager->on_market_added( 'empty', [] );
	}

	public function test_ensure_all_market_datafeeds_syncs_every_market() {
		$this->market_service->method( 'get_markets' )->willReturn(
			[
				'primary' => [
					'countries' => [ 'US' ],
					'language'  => [ 'en' ],
					'currency'  => [ 'USD' ],
				],
				'fr-FR'   => [
					'country'  => 'FR',
					'language' => [ 'fr' ],
					'currency' => [ 'EUR' ],
				],
			]
		);

		$calls = [];
		$this->datafeed_service->expects( $this->exactly( 2 ) )
								->method( 'ensure_for_feed_label' )
								->willReturnCallback(
									function ( $feed_label ) use ( &$calls ) {
										$calls[] = $feed_label;
									}
								);

		$this->manager->ensure_all_market_datafeeds();

		$this->assertContains( 'en-USD', $calls );
		$this->assertContains( 'fr-EUR', $calls );
	}

	public function test_on_market_added_schedules_shipping_sync() {
		$this->update_shipping_settings->expects( $this->once() )
										->method( 'schedule' );

		$this->manager->on_market_added(
			'fr-market',
			[
				'country'  => 'FR',
				'language' => [ 'fr' ],
				'currency' => [ 'EUR' ],
			]
		);
	}

	public function test_on_market_updated_schedules_shipping_sync() {
		$this->update_shipping_settings->expects( $this->once() )
										->method( 'schedule' );

		$this->manager->on_market_updated(
			'de-market',
			[
				'country'  => 'DE',
				'language' => [ 'de' ],
				'currency' => [ 'EUR' ],
			]
		);
	}

	public function test_on_market_deleted_schedules_shipping_sync() {
		$this->market_service->method( 'get_markets' )->willReturn( [] );

		$this->update_shipping_settings->expects( $this->once() )
										->method( 'schedule' );

		$this->manager->on_market_deleted(
			'fr-market',
			[
				'country'  => 'FR',
				'language' => [ 'fr' ],
				'currency' => [ 'EUR' ],
			]
		);
	}

	public function test_shipping_sync_not_double_scheduled_in_same_request() {
		$this->update_shipping_settings->expects( $this->once() )
										->method( 'schedule' );

		$config = [
			'country'  => 'FR',
			'language' => [ 'fr' ],
			'currency' => [ 'EUR' ],
		];

		$this->manager->on_market_added( 'fr-market', $config );
		$this->manager->on_market_added( 'fr-market-2', $config );
	}

	public function test_shipping_sync_not_double_scheduled_across_different_handlers() {
		$this->market_service->method( 'get_markets' )->willReturn( [] );

		$this->update_shipping_settings->expects( $this->once() )
										->method( 'schedule' );

		$this->manager->on_market_added(
			'fr-market',
			[
				'country'  => 'FR',
				'language' => [ 'fr' ],
				'currency' => [ 'EUR' ],
			]
		);
		$this->manager->on_market_deleted(
			'de-market',
			[
				'country'  => 'DE',
				'language' => [ 'de' ],
				'currency' => [ 'EUR' ],
			]
		);
	}
}
