<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

/**
 * Trait ReportTrait
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google
 */
trait ReportTrait {

	/** @var array $report_data */
	private $report_data = [];

	/**
	 * Increase report data by adding the subtotals.
	 *
	 * @param string $field Field to increase.
	 * @param string $index Unique index.
	 * @param array  $data  Report data.
	 */
	protected function increase_report_data( string $field, string $index, array $data ) {
		if ( ! isset( $this->report_data[ $field ][ $index ] ) ) {
			$this->report_data[ $field ][ $index ] = $data;
		} elseif ( ! empty( $data['subtotals'] ) ) {
			foreach ( $data['subtotals'] as $name => $subtotal ) {
				$this->report_data[ $field ][ $index ]['subtotals'][ $name ] += $subtotal;
			}
		}
	}

	/**
	 * Initialize report totals to 0 values.
	 *
	 * @param array $fields List of field names.
	 */
	protected function init_report_totals( array $fields ) {
		foreach ( $fields as $name ) {
			$this->report_data['totals'][ $name ] = 0;
		}
	}

	/**
	 * Increase report totals.
	 *
	 * @param array $data Totals data.
	 */
	protected function increase_report_totals( array $data ) {
		foreach ( $data as $name => $total ) {
			if ( ! isset( $this->report_data['totals'][ $name ] ) ) {
				$this->report_data['totals'][ $name ] = $total;
			} else {
				$this->report_data['totals'][ $name ] += $total;
			}
		}
	}

	/**
	 * Remove indexes from report data to conform to schema.
	 *
	 * @param array $fields Fields to reindex.
	 */
	protected function remove_report_indexes( array $fields ) {
		foreach ( $fields as $key ) {
			if ( isset( $this->report_data[ $key ] ) ) {
				$this->report_data[ $key ] = array_values( $this->report_data[ $key ] );
			}
		}
	}
}
