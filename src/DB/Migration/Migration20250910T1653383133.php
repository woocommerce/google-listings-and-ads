<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\DB\Migration;

use Automattic\WooCommerce\GoogleListingsAndAds\DB\Table\AdsRecommendationsTable;

defined( 'ABSPATH' ) || exit;

/**
 * Class Migration20250910T1653383133
 *
 * Migration class to to remove the ads recommendation table.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\DB\Migration
 *
 * @since x.x.x
 */
class Migration20250910T1653383133 extends AbstractMigration {

	/**
	 * @var AdsRecommendationsTable
	 */
	protected $ads_recommendations_table;

	/**
	 * Migration constructor.
	 *
	 * @param \wpdb                   $wpdb
	 * @param AdsRecommendationsTable $ads_recommendations_table
	 */
	public function __construct( \wpdb $wpdb, AdsRecommendationsTable $ads_recommendations_table ) {
		parent::__construct( $wpdb );
		$this->ads_recommendations_table = $ads_recommendations_table;
	}


	/**
	 * Returns the version to apply this migration for.
	 *
	 * @return string A version number.
	 */
	public function get_applicable_version(): string {
		return 'x.x.x';
	}

	/**
	 * Apply the migrations.
	 *
	 * @return void
	 */
	public function apply(): void {
		if ( $this->ads_recommendations_table->exists() ) {
			$this->wpdb->query( "DROP TABLE IF EXISTS `{$this->wpdb->_escape( $this->ads_recommendations_table->get_name() )}`" ); // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}
	}
}
