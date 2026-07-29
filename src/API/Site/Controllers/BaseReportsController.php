<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers;

use Automattic\WooCommerce\GoogleListingsAndAds\Internal\ContainerAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces\ContainerAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;
use DateTime;
use WP_Error;
use WP_REST_Request as Request;

defined( 'ABSPATH' ) || exit;

/**
 * Class BaseReportsController
 *
 * ContainerAware used for:
 * - WP
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers
 */
abstract class BaseReportsController extends BaseController implements ContainerAwareInterface {

	use ContainerAwareTrait;

	/**
	 * Get the query params for collections.
	 *
	 * @return array
	 */
	public function get_collection_params(): array {
		return [
			'context'   => $this->get_context_param( [ 'default' => 'view' ] ),
			'after'     => [
				'description'       => __( 'Limit response to data after a given ISO8601 compliant date.', 'google-listings-and-ads' ),
				'type'              => 'string',
				'format'            => 'date',
				'default'           => '-7 days',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'before'    => [
				'description'       => __( 'Limit response to data before a given ISO8601 compliant date.', 'google-listings-and-ads' ),
				'type'              => 'string',
				'format'            => 'date',
				'default'           => 'now',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'ids'       => [
				'description'       => __( 'Limit result to items with specified ids.', 'google-listings-and-ads' ),
				'type'              => 'array',
				'sanitize_callback' => 'wp_parse_slug_list',
				'validate_callback' => 'rest_validate_request_arg',
				'items'             => [
					'type' => 'string',
				],
			],
			'fields'    => [
				'description'       => __( 'Limit totals to a set of fields.', 'google-listings-and-ads' ),
				'type'              => 'array',
				'sanitize_callback' => 'wp_parse_slug_list',
				'validate_callback' => 'rest_validate_request_arg',
				'items'             => [
					'type' => 'string',
				],
			],
			'order'     => [
				'description'       => __( 'Order sort attribute ascending or descending.', 'google-listings-and-ads' ),
				'type'              => 'string',
				'default'           => 'desc',
				'enum'              => [ 'asc', 'desc' ],
				'validate_callback' => 'rest_validate_request_arg',
			],
			'orderby'   => [
				'description'       => __( 'Sort collection by attribute.', 'google-listings-and-ads' ),
				'type'              => 'string',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'per_page'  => [
				'description'       => __( 'Maximum number of rows to be returned in result data.', 'google-listings-and-ads' ),
				'type'              => 'integer',
				'default'           => 200,
				'minimum'           => 1,
				'maximum'           => 1000,
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'next_page' => [
				'description'       => __( 'Token to retrieve the next page.', 'google-listings-and-ads' ),
				'type'              => 'string',
				'validate_callback' => 'rest_validate_request_arg',
			],
		];
	}

	/**
	 * Maps query arguments from the REST request.
	 *
	 * @param Request $request REST Request.
	 * @return array
	 */
	protected function prepare_query_arguments( Request $request ): array {
		$args = wp_parse_args(
			array_intersect_key(
				$request->get_query_params(),
				$this->get_collection_params()
			),
			$request->get_default_params()
		);

		$this->normalize_timezones( $args );
		return $args;
	}

	/**
	 * Maximum span, in days, allowed for a per-product report segmented by day.
	 *
	 * A product×day report's row volume scales with catalog size × this span, so an
	 * unbounded range is expensive to fetch and produces sparse, truncated data once the
	 * per_page row cap is applied. Requests beyond this are rejected. Tune as needed.
	 */
	protected const MAX_PRODUCT_DAY_INTERVAL_DAYS = 366;

	/**
	 * Reject a per-product report segmented by day whose date range is too wide.
	 *
	 * Only applies to the day interval — coarser intervals (week/month/quarter/year) keep
	 * row volume low regardless of span.
	 *
	 * @param array $args Prepared query arguments (after/before as DateTime, interval).
	 *
	 * @return WP_Error|null WP_Error when the range exceeds the allowed span, otherwise null.
	 */
	protected function validate_product_day_interval_range( array $args ): ?WP_Error {
		if ( 'day' !== ( $args['interval'] ?? '' ) ) {
			return null;
		}

		$after  = $args['after'] ?? null;
		$before = $args['before'] ?? null;

		if ( ! $after instanceof DateTime || ! $before instanceof DateTime ) {
			return null;
		}

		$days = (int) $after->diff( $before )->format( '%a' );

		if ( $days > self::MAX_PRODUCT_DAY_INTERVAL_DAYS ) {
			return new WP_Error(
				'gla_report_date_range_too_large',
				sprintf(
					/* translators: %d number of days */
					__( 'The date range for a daily product report cannot exceed %d days. Use a shorter range or a coarser interval.', 'google-listings-and-ads' ),
					self::MAX_PRODUCT_DAY_INTERVAL_DAYS
				),
				[ 'status' => 400 ]
			);
		}

		return null;
	}

	/**
	 * Converts input datetime parameters to local timezone.
	 *
	 * @param array $query_args Array of query arguments.
	 */
	protected function normalize_timezones( &$query_args ) {
		/** @var WP $wp */
		$wp       = $this->container->get( WP::class );
		$local_tz = $wp->wp_timezone();

		foreach ( [ 'before', 'after' ] as $query_arg_key ) {
			if ( isset( $query_args[ $query_arg_key ] ) && is_string( $query_args[ $query_arg_key ] ) ) {

				// Assume that unspecified timezone is a local timezone.
				$datetime = new DateTime( $query_args[ $query_arg_key ], $local_tz );

				// In case timezone was forced by using +HH:MM, convert to local timezone.
				$datetime->setTimezone( $local_tz );
				$query_args[ $query_arg_key ] = $datetime;

			} elseif ( isset( $query_args[ $query_arg_key ] ) && $query_args[ $query_arg_key ] instanceof DateTime ) {

				// In case timezone is in other timezone, convert to local timezone.
				$query_args[ $query_arg_key ]->setTimezone( $local_tz );
			}
		}
	}
}
