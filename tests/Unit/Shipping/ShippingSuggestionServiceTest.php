<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Shipping;

use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\Location;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\LocationRate;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\ShippingRate;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\ShippingSuggestionService;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\ShippingZone;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class ShippingSuggestionServiceTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Shipping
 *
 * @property MockObject|ShippingZone   $shipping_zone
 * @property MockObject|WC             $wc
 * @property ShippingSuggestionService $suggestion_service
 */
class ShippingSuggestionServiceTest extends UnitTest {
	public function test_get_suggestions_returns_correct_data() {
		$location = new Location( 'US', 'CA' );

		$location_rates = [
			new LocationRate( $location, new ShippingRate( 200 ) ),
		];

		$this->shipping_zone->expects( $this->any() )
							->method( 'get_shipping_rates_grouped_by_country' )
							->willReturn( $location_rates );

		$suggestions = $this->suggestion_service->get_suggestions( 'US' );
		$this->assertCount( 1, $suggestions );

		$this->assertEqualSets(
			[
				'country'  => 'US',
				'currency' => 'USD',
				'rate'     => 200.0,
			],
			$suggestions[0]
		);
	}

	public function test_get_suggestions_skips_conditional_free_rate() {
		$location = new Location( 'US', 'CA' );

		$free_rate_1 = new ShippingRate( 0 );
		$free_rate_1->set_min_order_amount( 50 );

		$location_rates = [
			new LocationRate( $location, $free_rate_1 ),
			new LocationRate( $location, new ShippingRate( 200 ) ),
		];

		$this->shipping_zone->expects( $this->any() )
							->method( 'get_shipping_rates_grouped_by_country' )
							->willReturn( $location_rates );

		$suggestions = $this->suggestion_service->get_suggestions( 'US' );
		$this->assertCount( 1, $suggestions );
		$this->assertEquals( 200.0, $suggestions[0]['rate'] );
	}

	public function test_get_suggestions_sets_conditional_free_rate_threshold_on_other_rates() {
		$location = new Location( 'US', 'CA' );

		$free_rate_1 = new ShippingRate( 0 );
		$free_rate_1->set_min_order_amount( 50 );

		$location_rates = [
			new LocationRate( $location, $free_rate_1 ),
			new LocationRate( $location, new ShippingRate( 200 ) ),
		];

		$this->shipping_zone->expects( $this->any() )
							->method( 'get_shipping_rates_grouped_by_country' )
							->willReturn( $location_rates );

		$suggestions = $this->suggestion_service->get_suggestions( 'US' );
		$this->assertCount( 1, $suggestions );
		$this->assertEquals( [ 'free_shipping_threshold' => 50 ], $suggestions[0]['options'] );
	}

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->wc = $this->createMock( WC::class );
		$this->wc->expects( $this->any() )
				 ->method( 'get_woocommerce_currency' )
				 ->willReturn( 'USD' );

		$this->shipping_zone = $this->createMock( ShippingZone::class );

		$this->suggestion_service = new ShippingSuggestionService( $this->shipping_zone, $this->wc );
	}
}
