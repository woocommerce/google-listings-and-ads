<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Google;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * A destination country's delivery time, and the delivery date it gives for an order.
 *
 * Handling and transit times are counted in business days, as Merchant Center defines them.
 */
class EstimatedDeliveryTime {

	/** @var int[] ISO-8601 weekday numbers Merchant Center assumes when no business days are configured: Monday to Friday. */
	protected const DEFAULT_BUSINESS_DAYS = [ 1, 2, 3, 4, 5 ];

	/** @var int[] Merchant API Weekday enum values, mapped to ISO-8601 weekday numbers. */
	protected const WEEKDAYS = [
		'MONDAY'    => 1,
		'TUESDAY'   => 2,
		'WEDNESDAY' => 3,
		'THURSDAY'  => 4,
		'FRIDAY'    => 5,
		'SATURDAY'  => 6,
		'SUNDAY'    => 7,
	];

	/** @var int */
	private $max_handling_days;

	/** @var int */
	private $max_transit_days;

	/** @var int[] */
	private $handling_business_days;

	/** @var int[] */
	private $transit_business_days;

	/** @var array|null Cutoff with `hour`, `minute`, and a DateTimeZone `timezone`. Null means no cutoff. */
	private $cutoff;

	/**
	 * EstimatedDeliveryTime constructor.
	 *
	 * @param int        $max_handling_days      Maximum business days before an order ships.
	 * @param int        $max_transit_days       Maximum business days in transit.
	 * @param int[]      $handling_business_days ISO-8601 weekday numbers. Empty means Monday to Friday.
	 * @param int[]      $transit_business_days  ISO-8601 weekday numbers. Empty means Monday to Friday.
	 * @param array|null $cutoff_time            Merchant API CutoffTime (`hour`, `minute`, `timeZone`). Null or invalid means no cutoff.
	 */
	public function __construct(
		int $max_handling_days,
		int $max_transit_days,
		array $handling_business_days = [],
		array $transit_business_days = [],
		?array $cutoff_time = null
	) {
		$this->max_handling_days      = max( 0, $max_handling_days );
		$this->max_transit_days       = max( 0, $max_transit_days );
		$this->handling_business_days = self::normalize_business_days( $handling_business_days );
		$this->transit_business_days  = self::normalize_business_days( $transit_business_days );
		$this->cutoff                 = self::parse_cutoff_time( $cutoff_time );
	}

	/**
	 * Build from a Merchant API DeliveryTime.
	 *
	 * @param array $delivery_time Merchant API DeliveryTime.
	 *
	 * @return self|null Null when the delivery time has no maximum transit days.
	 */
	public static function from_merchant_center( array $delivery_time ): ?self {
		if ( ! isset( $delivery_time['maxTransitDays'] ) ) {
			return null;
		}

		$cutoff_time = $delivery_time['cutoffTime'] ?? null;

		return new self(
			(int) ( $delivery_time['maxHandlingDays'] ?? 0 ),
			(int) $delivery_time['maxTransitDays'],
			self::map_weekdays( $delivery_time['handlingBusinessDayConfig']['businessDays'] ?? [] ),
			self::map_weekdays( $delivery_time['transitBusinessDayConfig']['businessDays'] ?? [] ),
			is_array( $cutoff_time ) ? $cutoff_time : null
		);
	}

	/**
	 * @return int
	 */
	public function get_max_transit_days(): int {
		return $this->max_transit_days;
	}

	/**
	 * The latest estimated delivery date for an order placed at the given time.
	 *
	 * Handling starts on the order day, or on the next handling business day when the shopper
	 * orders on a non-business day or after the cutoff. The date then moves forward by the maximum
	 * handling days, then by the maximum transit days, each counting only its own business days.
	 *
	 * Without a cutoff, the order date's own timezone decides the order day, and the time of day
	 * does not matter.
	 *
	 * @param DateTimeInterface $order_date When the order was placed.
	 *
	 * @return string Delivery date in YYYY-MM-DD format.
	 */
	public function get_delivery_date( DateTimeInterface $order_date ): string {
		// Merchant Center defines a cutoff in its own timezone, so read the order day in that timezone too.
		$timezone = $this->cutoff ? $this->cutoff['timezone'] : ( $order_date->getTimezone() ?: new DateTimeZone( 'UTC' ) );
		$date     = ( new DateTimeImmutable( '@' . $order_date->getTimestamp() ) )->setTimezone( $timezone );

		$is_after_cutoff = $this->cutoff && (int) $date->format( 'Gi' ) >= $this->cutoff['hour'] * 100 + $this->cutoff['minute'];

		$date = $date->setTime( 0, 0 );
		if ( $is_after_cutoff ) {
			$date = $date->modify( '+1 day' );
		}

		while ( ! in_array( (int) $date->format( 'N' ), $this->handling_business_days, true ) ) {
			$date = $date->modify( '+1 day' );
		}

		$date = self::add_business_days( $date, $this->max_handling_days, $this->handling_business_days );
		$date = self::add_business_days( $date, $this->max_transit_days, $this->transit_business_days );

		return $date->format( 'Y-m-d' );
	}

	/**
	 * @param DateTimeImmutable $date
	 * @param int               $days
	 * @param int[]             $business_days ISO-8601 weekday numbers, never empty.
	 *
	 * @return DateTimeImmutable
	 */
	private static function add_business_days( DateTimeImmutable $date, int $days, array $business_days ): DateTimeImmutable {
		while ( $days > 0 ) {
			$date = $date->modify( '+1 day' );
			if ( in_array( (int) $date->format( 'N' ), $business_days, true ) ) {
				--$days;
			}
		}

		return $date;
	}

	/**
	 * @param mixed $weekdays Merchant API Weekday enum values.
	 *
	 * @return int[] ISO-8601 weekday numbers. Drops unknown values, such as WEEKDAY_UNSPECIFIED, and returns none for a non-array.
	 */
	private static function map_weekdays( $weekdays ): array {
		if ( ! is_array( $weekdays ) ) {
			return [];
		}

		$days = [];
		foreach ( $weekdays as $weekday ) {
			if ( is_string( $weekday ) && isset( self::WEEKDAYS[ $weekday ] ) ) {
				$days[] = self::WEEKDAYS[ $weekday ];
			}
		}

		return $days;
	}

	/**
	 * @param array $days ISO-8601 weekday numbers.
	 *
	 * @return int[] Valid, unique weekday numbers, or Monday to Friday when none are valid.
	 */
	private static function normalize_business_days( array $days ): array {
		$days = array_values(
			array_unique(
				array_filter(
					array_map( 'intval', $days ),
					function ( int $day ): bool {
						return $day >= 1 && $day <= 7;
					}
				)
			)
		);

		return $days ?: self::DEFAULT_BUSINESS_DAYS;
	}

	/**
	 * @param array|null $cutoff_time Merchant API CutoffTime.
	 *
	 * @return array|null The cutoff. Null when the cutoff is missing, incomplete, or invalid.
	 */
	private static function parse_cutoff_time( ?array $cutoff_time ): ?array {
		if ( ! isset( $cutoff_time['hour'], $cutoff_time['minute'], $cutoff_time['timeZone'] ) || ! is_string( $cutoff_time['timeZone'] ) ) {
			return null;
		}

		$hour   = (int) $cutoff_time['hour'];
		$minute = (int) $cutoff_time['minute'];

		try {
			$timezone = new DateTimeZone( $cutoff_time['timeZone'] );
		} catch ( Exception $e ) {
			return null;
		}

		if ( $hour < 0 || $hour > 23 || $minute < 0 || $minute > 59 ) {
			return null;
		}

		return [
			'hour'     => $hour,
			'minute'   => $minute,
			'timezone' => $timezone,
		];
	}
}
