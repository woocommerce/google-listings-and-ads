<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query;

use DateTime;

defined( 'ABSPATH' ) || exit;

/**
 * Trait ReportQueryTrait
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query
 */
trait ReportQueryTrait {

	/**
	 * Handle the common arguments for this query.
	 *
	 * @param array $args List of arguments which were passed to the query.
	 */
	protected function handle_query_args( array $args ) {
		if ( ! empty( $args['fields'] ) ) {
			$this->fields( $args['fields'] );
		}

		if ( ! empty( $args['interval'] ) ) {
			$this->segment_interval( $args['interval'] );
		}

		if ( ! empty( $args['after'] ) && ! empty( $args['before'] ) ) {
			$after  = $args['after'];
			$before = $args['before'];

			$this->where_date_between(
				$after instanceof DateTime ? $after->format( 'Y-m-d' ) : $after,
				$before instanceof DateTime ? $before->format( 'Y-m-d' ) : $before
			);
		}

		if ( ! empty( $args['ids'] ) ) {
			$this->filter( $args['ids'] );
		}

		if ( ! empty( $args['orderby'] ) ) {
			$this->set_order( $args['orderby'], $args['order'] );
		}

		if ( ! empty( $args['per_page'] ) ) {
			$this->search_args['pageSize'] = $args['per_page'];
		}

		if ( ! empty( $args['next_page'] ) ) {
			$this->search_args['pageToken'] = $args['next_page'];
		}
	}
}
