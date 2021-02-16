<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Exception;

use InvalidArgumentException;

defined( 'ABSPATH' ) || exit;

/**
 * Class InvalidColumn
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Exception
 */
class InvalidQuery extends InvalidArgumentException implements GoogleListingsAndAdsException {

	/**
	 * Create a new instance of the exception when a column is not valid for a given table.
	 *
	 * @param string $name        The column name.
	 * @param string $table_class The table class name.
	 *
	 * @return static
	 */
	public static function from_column( string $name, string $table_class ): InvalidQuery {
		return new static( sprintf( 'The column "%s" is not valid for table class "%s".', $name, $table_class ) );
	}

	/**
	 * @param string $compare
	 *
	 * @return static
	 */
	public static function from_compare( string $compare ): InvalidQuery {
		return new static( sprintf( 'The compare value "%s" is not valid.', $compare ) );
	}

	/**
	 * Create a new instance of the exception when there is an error inserting data into the DB.
	 *
	 * @param string $error
	 *
	 * @return InvalidQuery
	 */
	public static function from_insert( string $error ): InvalidQuery {
		return new static( sprintf( 'Error inserting data into the database: "%s"', $error ) );
	}

	/**
	 * Create a new instance of the exception when trying to set an auto increment ID.
	 *
	 * @param string $table_class
	 * @param string $column_name
	 *
	 * @return InvalidQuery
	 */
	public static function cant_set_id( string $table_class, string $column_name = 'id' ): InvalidQuery {
		return new static( sprintf( 'Cannot set column "%s" for table class "%s".', $column_name, $table_class ) );
	}
}
