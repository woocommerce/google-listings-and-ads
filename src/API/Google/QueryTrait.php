<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidArray;
use DateTime;

defined( 'ABSPATH' ) || exit;

/**
 * Trait QueryTrait
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google
 */
trait QueryTrait {

	/**
	 * Build a Google Query string.
	 *
	 * @param array  $fields     List of fields to return.
	 * @param string $resource   Resource name to query from.
	 * @param array  $conditions Query conditions.
	 *
	 * @return string
	 */
	protected function build_query( array $fields, string $resource, array $conditions = [] ): string {
		$joined = join( ',', $fields );
		$query  = "SELECT {$joined} FROM {$resource}";

		if ( empty( $conditions ) ) {
			return $query;
		}

		$relation = 'AND';
		$where    = [];

		foreach ( $conditions as $key => $condition ) {
			if ( 'relation' === $key ) {
				$relation = $condition;
				continue;
			}

			$where[] = $this->build_condition( $condition );
		}

		return $query . ' WHERE ' . join( ' ' . $relation . ' ', $where );
	}

	/**
	 * Build a condition to use in a Google Query.
	 *
	 * @param array $condition Defined condition.
	 *
	 * @throws InvalidArray When condition doesn't have a key.
	 */
	protected function build_condition( array $condition ): string {
		if ( empty( $condition['key'] ) ) {
			throw InvalidArray::missing_keys( static::class, [ 'key' ] );
		}

		$key      = $condition['key'];
		$value    = $condition['value'] ?? '';
		$operator = strtoupper( $condition['operator'] ) ?? '=';

		switch ( $operator ) {
			case 'BETWEEN':
				$value = $this->convert_value( $value[0] ) . ' AND ' . $this->convert_value( $value[1] );
				break;

			default:
				$value = $this->convert_value( $value );
				break;
		}

		return "{$key} {$operator} {$value}";
	}

	/**
	 * Convert the value to a string which can be used in a query.
	 *
	 * @param mixed $value Original value to convert.
	 *
	 * @return string
	 */
	protected function convert_value( $value ): string {
		if ( $value instanceof DateTime ) {
			return "'" . $value->format( 'Y-m-d' ) . "'";
		}

		if ( ! is_numeric( $value ) ) {
			return "'$value'";
		}

		return (string) $value;
	}
}
