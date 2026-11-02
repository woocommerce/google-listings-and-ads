<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\Google\EstimatedDeliveryTime;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use DateTimeImmutable;
use DateTimeZone;

defined( 'ABSPATH' ) || exit;

/**
 * Class EstimatedDeliveryTimeTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Google
 */
class EstimatedDeliveryTimeTest extends UnitTest {

	/**
	 * Merchant Center delivery time from the reported setup: handling 0–1 and transit 1–2
	 * business days, Monday to Friday, with a 14:00 Bucharest cutoff.
	 */
	protected const REPORTED_DELIVERY_TIME = [
		'minHandlingDays'           => 0,
		'maxHandlingDays'           => 1,
		'minTransitDays'            => 1,
		'maxTransitDays'            => 2,
		'cutoffTime'                => [
			'hour'     => 14,
			'minute'   => 0,
			'timeZone' => 'Europe/Bucharest',
		],
		'handlingBusinessDayConfig' => [ 'businessDays' => [ 'MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY' ] ],
		'transitBusinessDayConfig'  => [ 'businessDays' => [ 'MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY' ] ],
	];

	/**
	 * @param string $date_time
	 * @param string $timezone
	 *
	 * @return DateTimeImmutable
	 */
	protected function order_date( string $date_time, string $timezone = 'UTC' ): DateTimeImmutable {
		return new DateTimeImmutable( $date_time, new DateTimeZone( $timezone ) );
	}

	public function test_reported_setup_before_cutoff_counts_handling_and_transit_business_days() {
		$delivery_time = EstimatedDeliveryTime::from_merchant_center( self::REPORTED_DELIVERY_TIME );

		// Friday before the cutoff: handling ends Monday, transit ends Wednesday.
		$this->assertSame( '2026-10-28', $delivery_time->get_delivery_date( $this->order_date( '2026-10-23 10:00', 'Europe/Bucharest' ) ) );
	}

	public function test_order_after_cutoff_starts_handling_on_next_business_day() {
		$delivery_time = EstimatedDeliveryTime::from_merchant_center( self::REPORTED_DELIVERY_TIME );

		$this->assertSame( '2026-10-29', $delivery_time->get_delivery_date( $this->order_date( '2026-10-23 14:00', 'Europe/Bucharest' ) ) );
	}

	public function test_cutoff_is_compared_in_its_own_timezone() {
		$delivery_time = EstimatedDeliveryTime::from_merchant_center( self::REPORTED_DELIVERY_TIME );

		// 11:30 UTC is 14:30 in Bucharest, which is UTC+3 on that date: after the cutoff.
		$this->assertSame( '2026-10-29', $delivery_time->get_delivery_date( $this->order_date( '2026-10-23 11:30' ) ) );
		// 10:30 UTC is 13:30 in Bucharest: before the cutoff.
		$this->assertSame( '2026-10-28', $delivery_time->get_delivery_date( $this->order_date( '2026-10-23 10:30' ) ) );
	}

	public function test_order_day_is_read_in_cutoff_timezone() {
		$delivery_time = EstimatedDeliveryTime::from_merchant_center( self::REPORTED_DELIVERY_TIME );

		// 22:00 UTC on Thursday is already 01:00 on Friday in Bucharest, before the cutoff.
		$this->assertSame( '2026-10-28', $delivery_time->get_delivery_date( $this->order_date( '2026-10-22 22:00' ) ) );
	}

	public function test_matches_merchant_center_help_example() {
		$delivery_time = EstimatedDeliveryTime::from_merchant_center(
			[
				'maxHandlingDays'          => 1,
				'maxTransitDays'           => 5,
				'cutoffTime'               => [
					'hour'     => 15,
					'minute'   => 0,
					'timeZone' => 'America/New_York',
				],
				'transitBusinessDayConfig' => [ 'businessDays' => [ 'MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY', 'SATURDAY' ] ],
			]
		);

		// Google's example: a Wednesday 8 p.m. order after a 3 p.m. cutoff arrives by the following Thursday.
		$this->assertSame( '2026-10-29', $delivery_time->get_delivery_date( $this->order_date( '2026-10-21 20:00', 'America/New_York' ) ) );
	}

	public function test_missing_cutoff_defaults_to_8am_pacific_time() {
		$delivery_time = new EstimatedDeliveryTime( 0, 1 );

		// Friday 07:59 in Los Angeles is before the default cutoff, 08:00 is after it.
		$this->assertSame( '2026-10-26', $delivery_time->get_delivery_date( $this->order_date( '2026-10-23 07:59', 'America/Los_Angeles' ) ) );
		$this->assertSame( '2026-10-27', $delivery_time->get_delivery_date( $this->order_date( '2026-10-23 08:00', 'America/Los_Angeles' ) ) );
		// 15:30 UTC is 08:30 in Los Angeles, which is UTC-7 on that date.
		$this->assertSame( '2026-10-27', $delivery_time->get_delivery_date( $this->order_date( '2026-10-23 15:30' ) ) );
	}

	public function test_defaults_to_monday_to_friday_without_handling_time() {
		$delivery_time = new EstimatedDeliveryTime( 0, 2 );

		// Friday plus 2 transit business days is Tuesday, not Sunday.
		$this->assertSame( '2026-10-27', $delivery_time->get_delivery_date( $this->order_date( '2026-10-23 12:00' ) ) );
	}

	public function test_weekend_order_starts_handling_on_monday() {
		$delivery_time = new EstimatedDeliveryTime( 1, 2 );

		$this->assertSame( '2026-10-29', $delivery_time->get_delivery_date( $this->order_date( '2026-10-24 09:00' ) ) );
		$this->assertSame( '2026-10-29', $delivery_time->get_delivery_date( $this->order_date( '2026-10-25 09:00' ) ) );
	}

	public function test_zero_handling_and_transit_days() {
		$delivery_time = new EstimatedDeliveryTime( 0, 0 );

		$this->assertSame( '2026-10-23', $delivery_time->get_delivery_date( $this->order_date( '2026-10-23 12:00' ) ) );
		$this->assertSame( '2026-10-26', $delivery_time->get_delivery_date( $this->order_date( '2026-10-24 12:00' ) ) );
	}

	public function test_transit_business_days_can_include_saturday() {
		$delivery_time = EstimatedDeliveryTime::from_merchant_center(
			[
				'maxHandlingDays'          => 0,
				'maxTransitDays'           => 1,
				'transitBusinessDayConfig' => [ 'businessDays' => [ 'MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY', 'SATURDAY' ] ],
			]
		);

		$this->assertSame( '2026-10-24', $delivery_time->get_delivery_date( $this->order_date( '2026-10-23 12:00' ) ) );
	}

	public function test_handling_business_days_are_used_for_handling_only() {
		$delivery_time = EstimatedDeliveryTime::from_merchant_center(
			[
				'maxHandlingDays'           => 1,
				'maxTransitDays'            => 1,
				'handlingBusinessDayConfig' => [ 'businessDays' => [ 'MONDAY', 'WEDNESDAY' ] ],
			]
		);

		// Tuesday order: handling starts Wednesday, ships the next Monday, arrives Tuesday.
		$this->assertSame( '2026-10-27', $delivery_time->get_delivery_date( $this->order_date( '2026-10-20 12:00' ) ) );
	}

	public function test_from_merchant_center_defaults_missing_fields() {
		$delivery_time = EstimatedDeliveryTime::from_merchant_center( [ 'maxTransitDays' => 2 ] );

		$this->assertSame( 2, $delivery_time->get_max_transit_days() );
		$this->assertSame( '2026-10-27', $delivery_time->get_delivery_date( $this->order_date( '2026-10-23 07:00', 'America/Los_Angeles' ) ) );
	}

	public function test_from_merchant_center_returns_null_without_max_transit_days() {
		$this->assertNull( EstimatedDeliveryTime::from_merchant_center( [ 'maxHandlingDays' => 1 ] ) );
		$this->assertNull( EstimatedDeliveryTime::from_merchant_center( [ 'transitTimeTable' => [] ] ) );
	}

	public function test_unknown_weekdays_fall_back_to_monday_to_friday() {
		$delivery_time = EstimatedDeliveryTime::from_merchant_center(
			[
				'maxTransitDays'           => 2,
				'transitBusinessDayConfig' => [ 'businessDays' => [ 'WEEKDAY_UNSPECIFIED' ] ],
			]
		);

		$this->assertSame( '2026-10-27', $delivery_time->get_delivery_date( $this->order_date( '2026-10-23 12:00' ) ) );
	}

	public function test_invalid_cutoff_falls_back_to_default() {
		$delivery_time = new EstimatedDeliveryTime(
			0,
			1,
			[],
			[],
			[
				'hour'     => 9,
				'minute'   => 0,
				'timeZone' => 'Not/A_Zone',
			]
		);

		// 09:00 in Los Angeles is after the default cutoff, so handling starts on Monday.
		$this->assertSame( '2026-10-27', $delivery_time->get_delivery_date( $this->order_date( '2026-10-23 09:00', 'America/Los_Angeles' ) ) );
	}

	public function test_out_of_range_cutoff_falls_back_to_default() {
		$delivery_time = new EstimatedDeliveryTime(
			0,
			1,
			[],
			[],
			[
				'hour'     => 24,
				'minute'   => 0,
				'timeZone' => 'Europe/Bucharest',
			]
		);

		$this->assertSame( '2026-10-27', $delivery_time->get_delivery_date( $this->order_date( '2026-10-23 09:00', 'America/Los_Angeles' ) ) );
	}
}
