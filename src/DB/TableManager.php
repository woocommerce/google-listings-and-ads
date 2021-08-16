<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\DB;

use Automattic\WooCommerce\GoogleListingsAndAds\DB\Table\BudgetRecommendationTable;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Table\MerchantIssueTable;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Table\ShippingRateTable;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Table\ShippingTimeTable;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\ValidateInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Class TableManager
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\DB
 *
 * @since 1.3.0
 */
class TableManager {

	use ValidateInterface;

	protected const VALID_TABLES = [
		BudgetRecommendationTable::class => true,
		MerchantIssueTable::class        => true,
		ShippingRateTable::class         => true,
		ShippingTimeTable::class         => true,
	];

	/**
	 * @var Table[]
	 */
	protected $tables;

	/**
	 * TableManager constructor.
	 *
	 * @param Table[] $tables
	 */
	public function __construct( array $tables ) {
		$this->setup_tables( $tables );
	}

	/**
	 * @return Table[]
	 *
	 * @see \Automattic\WooCommerce\GoogleListingsAndAds\DB\Installer::install for installing these tables.
	 */
	public function get_tables(): array {
		return $this->tables;
	}

	/**
	 * Returns a list of table names to be installed.
	 *
	 * @return string[] Table names
	 *
	 * @see TableManager::VALID_TABLES for the list of valid table classes.
	 */
	public static function get_all_table_names(): array {
		$tables = [];
		foreach ( array_keys( self::VALID_TABLES ) as $table_class ) {
			$table_name = call_user_func( [ $table_class, 'get_raw_name' ] );

			$tables[ $table_name ] = $table_name;
		}

		return $tables;
	}

	/**
	 * Set up each of the table controllers.
	 *
	 * @param Table[] $tables
	 */
	protected function setup_tables( array $tables ) {
		foreach ( $tables as $table ) {
			$this->validate_instanceof( $table, Table::class );

			// only include tables from the installable tables list.
			if ( isset( self::VALID_TABLES[ get_class( $table ) ] ) ) {
				$this->tables[] = $table;
			}
		}
	}
}
