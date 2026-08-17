<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\MerchantApiException;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services\MapiAccountShippingSettingsService;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\ShippingTimeQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\EstimatedDeliveryTimeResolver;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Psr\Container\ContainerInterface;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class EstimatedDeliveryTimeResolverTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Google
 */
class EstimatedDeliveryTimeResolverTest extends UnitTest {

	protected const TEST_MERCHANT_ID = 12345;

	/** @var MockObject|OptionsInterface */
	protected $options;

	/** @var MockObject|ShippingTimeQuery */
	protected $shipping_time_query;

	/** @var MockObject|MapiAccountShippingSettingsService */
	protected $mapi_shipping_settings_service;

	/** @var MockObject|ContainerInterface */
	protected $container;

	/** @var EstimatedDeliveryTimeResolver */
	protected $resolver;

	public function setUp(): void {
		parent::setUp();

		$this->options                        = $this->createMock( OptionsInterface::class );
		$this->shipping_time_query            = $this->createMock( ShippingTimeQuery::class );
		$this->mapi_shipping_settings_service = $this->createMock( MapiAccountShippingSettingsService::class );

		// A stub (not an expectation) by default — resolves ShippingTimeQuery::class fresh each
		// call, same as the real container's `add()`-registered (non-shared) entry. Individual
		// tests that need to assert *how many times* it's fetched replace this with their own
		// stricter mock (see test_flat_mode_fetches_a_fresh_query_result_for_each_country).
		$this->container = $this->createMock( ContainerInterface::class );
		$this->container->method( 'get' )
			->with( ShippingTimeQuery::class )
			->willReturn( $this->shipping_time_query );

		$this->resolver = new EstimatedDeliveryTimeResolver( $this->mapi_shipping_settings_service );
		$this->resolver->set_options_object( $this->options );
		$this->resolver->set_container( $this->container );
	}

	/**
	 * @param string $shipping_time_mode
	 */
	protected function mock_shipping_time_mode( string $shipping_time_mode ): void {
		$this->options->method( 'get' )
			->with( OptionsInterface::MERCHANT_CENTER, [] )
			->willReturn( [ 'shipping_time' => $shipping_time_mode ] );
	}

	public function test_flat_mode_resolves_from_local_table() {
		$this->mock_shipping_time_mode( 'flat' );

		$this->shipping_time_query->method( 'where' )
			->with( 'country', 'US' )
			->willReturn( $this->shipping_time_query );
		$this->shipping_time_query->method( 'get_results' )
			->willReturn(
				[
					[
						'country'  => 'US',
						'time'     => '3',
						'max_time' => '5',
					],
				]
			);

		$this->mapi_shipping_settings_service->expects( $this->never() )->method( 'get_shipping_settings' );

		$this->assertSame( 5, $this->resolver->get_max_transit_days_for_country( 'US' ) );
	}

	public function test_flat_mode_returns_null_when_no_local_entry() {
		$this->mock_shipping_time_mode( 'flat' );

		$this->shipping_time_query->method( 'where' )->willReturn( $this->shipping_time_query );
		$this->shipping_time_query->method( 'get_results' )->willReturn( [] );

		$this->assertNull( $this->resolver->get_max_transit_days_for_country( 'US' ) );
	}

	public function test_flat_mode_resolves_a_zero_day_transit_time() {
		$this->mock_shipping_time_mode( 'flat' );

		$this->shipping_time_query->method( 'where' )->willReturn( $this->shipping_time_query );
		$this->shipping_time_query->method( 'get_results' )->willReturn(
			[
				[
					'country'  => 'US',
					'time'     => '0',
					'max_time' => '0',
				],
			]
		);

		// A legitimately-configured same-day (0-day) transit time must resolve as 0, not be
		// mistaken for "no entry" the way a truthiness check on the value would.
		$this->assertSame( 0, $this->resolver->get_max_transit_days_for_country( 'US' ) );
	}

	public function test_flat_mode_fetches_a_fresh_query_result_for_each_country() {
		$this->mock_shipping_time_mode( 'flat' );

		// Unlike the setUp() stub, this asserts ShippingTimeQuery::class is actually fetched
		// from the container once per resolver call — the guarantee the fix depends on. A
		// regression back to constructor-caching a single instance (calling container->get()
		// zero times per lookup) would fail this expectation, which a plain mock swap alone
		// wouldn't catch since PHPUnit mocks don't replicate the real Query class's stateful
		// where()/get_results() memoization that caused the original bug.
		$this->container = $this->createMock( ContainerInterface::class );
		$this->container->expects( $this->exactly( 2 ) )
			->method( 'get' )
			->with( ShippingTimeQuery::class )
			->willReturn( $this->shipping_time_query );
		$this->resolver->set_container( $this->container );

		$this->shipping_time_query->method( 'where' )->willReturnSelf();
		$this->shipping_time_query->method( 'get_results' )->willReturnOnConsecutiveCalls(
			[
				[
					'country'  => 'US',
					'time'     => '3',
					'max_time' => '5',
				],
			],
			[
				[
					'country'  => 'CA',
					'time'     => '4',
					'max_time' => '9',
				],
			]
		);

		// A second lookup for a different country in the same request must resolve that
		// country's own data, not a previous lookup's cached/stale result.
		$this->assertSame( 5, $this->resolver->get_max_transit_days_for_country( 'US' ) );
		$this->assertSame( 9, $this->resolver->get_max_transit_days_for_country( 'CA' ) );
	}

	public function test_manual_mode_resolves_from_merchant_center() {
		$this->mock_shipping_time_mode( 'manual' );
		$this->options->method( 'get_merchant_id' )->willReturn( self::TEST_MERCHANT_ID );

		$this->shipping_time_query->expects( $this->never() )->method( 'where' );

		$this->mapi_shipping_settings_service->expects( $this->once() )
			->method( 'get_shipping_settings' )
			->willReturn(
				[
					'services' => [
						[
							'deliveryCountries' => [ 'US' ],
							'deliveryTime'      => [
								'minTransitDays' => 3,
								'maxTransitDays' => 7,
							],
						],
					],
				]
			);

		$this->assertSame( 7, $this->resolver->get_max_transit_days_for_country( 'US' ) );
	}

	public function test_manual_mode_returns_null_when_country_not_in_any_service() {
		$this->mock_shipping_time_mode( 'manual' );
		$this->options->method( 'get_merchant_id' )->willReturn( self::TEST_MERCHANT_ID );

		$this->mapi_shipping_settings_service->method( 'get_shipping_settings' )
			->willReturn(
				[
					'services' => [
						[
							'deliveryCountries' => [ 'CA' ],
							'deliveryTime'      => [
								'minTransitDays' => 3,
								'maxTransitDays' => 7,
							],
						],
					],
				]
			);

		$this->assertNull( $this->resolver->get_max_transit_days_for_country( 'US' ) );
	}

	public function test_manual_mode_returns_null_when_no_shipping_policy_configured() {
		$this->mock_shipping_time_mode( 'manual' );
		$this->options->method( 'get_merchant_id' )->willReturn( self::TEST_MERCHANT_ID );

		$this->mapi_shipping_settings_service->method( 'get_shipping_settings' )->willReturn( [] );

		$this->assertNull( $this->resolver->get_max_transit_days_for_country( 'US' ) );
	}

	public function test_manual_mode_returns_null_when_merchant_center_not_connected() {
		$this->mock_shipping_time_mode( 'manual' );
		$this->options->method( 'get_merchant_id' )->willReturn( 0 );

		$this->mapi_shipping_settings_service->expects( $this->never() )->method( 'get_shipping_settings' );

		$this->assertNull( $this->resolver->get_max_transit_days_for_country( 'US' ) );
	}

	public function test_manual_mode_returns_null_when_merchant_api_call_fails() {
		$this->mock_shipping_time_mode( 'manual' );
		$this->options->method( 'get_merchant_id' )->willReturn( self::TEST_MERCHANT_ID );

		$this->mapi_shipping_settings_service->method( 'get_shipping_settings' )
			->willThrowException( new MerchantApiException( 500, [], __METHOD__ ) );

		$this->assertNull( $this->resolver->get_max_transit_days_for_country( 'US' ) );
	}

	public function test_manual_mode_caches_shipping_settings_across_calls() {
		$this->mock_shipping_time_mode( 'manual' );
		$this->options->method( 'get_merchant_id' )->willReturn( self::TEST_MERCHANT_ID );

		$this->mapi_shipping_settings_service->expects( $this->once() )
			->method( 'get_shipping_settings' )
			->willReturn(
				[
					'services' => [
						[
							'deliveryCountries' => [ 'US' ],
							'deliveryTime'      => [
								'minTransitDays' => 3,
								'maxTransitDays' => 7,
							],
						],
					],
				]
			);

		$this->assertSame( 7, $this->resolver->get_max_transit_days_for_country( 'US' ) );
		// A second lookup within the cache window must not re-trigger a live API call.
		$this->assertSame( 7, $this->resolver->get_max_transit_days_for_country( 'US' ) );
	}

	public function test_switching_mode_uses_newly_declared_value_immediately() {
		$this->shipping_time_query->method( 'where' )->willReturn( $this->shipping_time_query );
		$this->shipping_time_query->method( 'get_results' )->willReturn(
			[
				[
					'country'  => 'US',
					'time'     => '3',
					'max_time' => '5',
				],
			]
		);

		$this->mapi_shipping_settings_service->method( 'get_shipping_settings' )
			->willReturn(
				[
					'services' => [
						[
							'deliveryCountries' => [ 'US' ],
							'deliveryTime'      => [
								'minTransitDays' => 3,
								'maxTransitDays' => 7,
							],
						],
					],
				]
			);
		$this->options->method( 'get_merchant_id' )->willReturn( self::TEST_MERCHANT_ID );

		$this->mock_shipping_time_mode( 'flat' );
		$this->assertSame( 5, $this->resolver->get_max_transit_days_for_country( 'US' ) );

		// Merchant switches to manual — the previously-used local-table source must never be
		// consulted again once the newly declared mode takes over.
		$this->options = $this->createMock( OptionsInterface::class );
		$this->resolver->set_options_object( $this->options );
		$this->mock_shipping_time_mode( 'manual' );
		$this->options->method( 'get_merchant_id' )->willReturn( self::TEST_MERCHANT_ID );

		$this->assertSame( 7, $this->resolver->get_max_transit_days_for_country( 'US' ) );
	}
}
