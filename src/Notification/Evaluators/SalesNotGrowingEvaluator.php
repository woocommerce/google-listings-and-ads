<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\CachedNotificationEvaluatorTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationEvaluatorInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationPriorities;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationSnoozeDurations;
use Automattic\WooCommerce\Utilities\OrderUtil;
use DateTime;
use DateTimeZone;

defined( 'ABSPATH' ) || exit;

/**
 * Class SalesNotGrowingEvaluator
 *
 * Fires when GMV for the current calendar month is less than the same month in the prior year.
 *
 * Only merchants with more than one year of sales data are considered; stores whose
 * earliest sale is less than a year old are excluded because there is no prior-year
 * period to compare against.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators
 */
class SalesNotGrowingEvaluator implements NotificationEvaluatorInterface, Service {

	use CachedNotificationEvaluatorTrait;

	/**
	 * Get the notification's unique ID.
	 *
	 * @return string
	 */
	public function get_id(): string {
		return 'sales-not-growing';
	}

	/**
	 * Evaluate whether the notification condition is met.
	 *
	 * @return bool
	 */
	protected function evaluate_condition(): bool {
		if ( ! $this->has_more_than_one_year_of_sales() ) {
			return false;
		}

		$timezone = wp_timezone();
		$now      = new DateTime( 'now', $timezone );

		$current_start = new DateTime( $now->format( 'Y-m-01 00:00:00' ), $timezone );
		$prior_start   = ( clone $current_start )->modify( '-1 year' );
		$prior_end     = ( clone $now )->modify( '-1 year' );

		$current_gmv = $this->get_gmv_for_period( $current_start, $now );
		$prior_gmv   = $this->get_gmv_for_period( $prior_start, $prior_end );

		return $current_gmv < $prior_gmv;
	}

	/**
	 * Get the notification's priority.
	 *
	 * @return int
	 */
	public function get_priority(): int {
		return NotificationPriorities::SALES_NOT_GROWING;
	}

	/**
	 * Get the snooze duration in seconds for temporary dismissals.
	 *
	 * @return int|null
	 */
	public function get_snooze_duration(): ?int {
		return NotificationSnoozeDurations::SALES_NOT_GROWING;
	}

	/**
	 * Whether the store has more than one year of sales data, i.e. its earliest
	 * paid order is more than a year old.
	 *
	 * @return bool
	 */
	protected function has_more_than_one_year_of_sales(): bool {
		$first_order_date = $this->get_first_order_date();

		if ( null === $first_order_date ) {
			return false;
		}

		$one_year_ago = ( new DateTime( 'now', new DateTimeZone( 'UTC' ) ) )->modify( '-1 year' );

		return $first_order_date < $one_year_ago;
	}

	/**
	 * Get the creation date of the store's earliest paid order, or null when there
	 * are no paid orders.
	 *
	 * @return DateTime|null
	 */
	protected function get_first_order_date(): ?DateTime {
		global $wpdb;

		$statuses = $this->paid_statuses();
		if ( empty( $statuses ) ) {
			return null;
		}
		$placeholders = implode( ', ', array_fill( 0, count( $statuses ), '%s' ) );

		if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
			$query = "SELECT MIN( date_created_gmt )
				FROM {$wpdb->prefix}wc_orders
				WHERE type = 'shop_order'
					AND status IN ( $placeholders )";
		} else {
			$query = "SELECT MIN( post_date_gmt )
				FROM {$wpdb->posts}
				WHERE post_type = 'shop_order'
					AND post_status IN ( $placeholders )";
		}

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared -- table names from $wpdb and built %s placeholders.
		$date = $wpdb->get_var( $wpdb->prepare( $query, $statuses ) );
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared

		if ( empty( $date ) ) {
			return null;
		}

		return new DateTime( $date, new DateTimeZone( 'UTC' ) );
	}

	/**
	 * Sum GMV for paid orders within a date range.
	 *
	 * @param DateTime $start
	 * @param DateTime $end
	 *
	 * @return float
	 */
	protected function get_gmv_for_period( DateTime $start, DateTime $end ): float {
		global $wpdb;

		$statuses = $this->paid_statuses();
		if ( empty( $statuses ) ) {
			return 0.0;
		}
		$placeholders = implode( ', ', array_fill( 0, count( $statuses ), '%s' ) );

		$start_sql = $this->format_datetime_as_gmt( $start );
		$end_sql   = $this->format_datetime_as_gmt( $end );

		if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
			$query = "SELECT COALESCE( SUM( total_amount ), 0 )
				FROM {$wpdb->prefix}wc_orders
				WHERE type = 'shop_order'
					AND status IN ( $placeholders )
					AND date_created_gmt >= %s
					AND date_created_gmt <= %s";
		} else {
			$query = "SELECT COALESCE( SUM( meta.meta_value + 0 ), 0 )
				FROM {$wpdb->posts} AS posts
				INNER JOIN {$wpdb->postmeta} AS meta
					ON posts.ID = meta.post_id
				WHERE posts.post_type = 'shop_order'
					AND posts.post_status IN ( $placeholders )
					AND meta.meta_key = '_order_total'
					AND posts.post_date_gmt >= %s
					AND posts.post_date_gmt <= %s";
		}

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared -- table names from $wpdb and built %s placeholders.
		$sum = $wpdb->get_var(
			$wpdb->prepare(
				$query,
				array_merge( $statuses, [ $start_sql, $end_sql ] )
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared

		return (float) $sum;
	}

	/**
	 * The WooCommerce paid order statuses, wc-prefixed for querying the status column.
	 *
	 * @return string[]
	 */
	private function paid_statuses(): array {
		return array_map(
			static function ( string $status ): string {
				return 'wc-' . $status;
			},
			wc_get_is_paid_statuses()
		);
	}

	/**
	 * Format a site-timezone datetime for comparison against GMT database columns.
	 *
	 * @param DateTime $date
	 *
	 * @return string
	 */
	private function format_datetime_as_gmt( DateTime $date ): string {
		$utc = clone $date;
		$utc->setTimezone( new DateTimeZone( 'UTC' ) );

		return $utc->format( 'Y-m-d H:i:s' );
	}
}
