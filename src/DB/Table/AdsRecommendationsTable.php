<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\DB\Table;

use Automattic\WooCommerce\GoogleListingsAndAds\DB\Table;

defined( 'ABSPATH' ) || exit;

/**
 * Class AdsRecommendationsTable
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\DB\Tables
 */
class AdsRecommendationsTable extends Table {

	/**
	 * Get the schema for the DB.
	 *
	 * This should be a SQL string for creating the DB table.
	 *
	 * @return string
	 */
	protected function get_install_query(): string {
		return "
CREATE TABLE `{$this->get_sql_safe_name()}` (
	recommendation_id bigint(20) NOT NULL,
	recommendation_type varchar(64) NOT NULL,
	recommendation_resource_name varchar(255) NOT NULL,
	recommendation_campaign_id bigint(20) NOT NULL,
	recommendation_campaign_name varchar(255) NOT NULL,
	recommendation_campaign_status varchar(64) NOT NULL,
	recommendation_customer_id bigint(20) NOT NULL,
	recommendation_last_synced datetime NOT NULL,
	PRIMARY KEY (recommendation_id),
	KEY recommendation_type (recommendation_type),
	KEY recommendation_campaign_id (recommendation_campaign_id),
	KEY recommendation_customer_id (recommendation_customer_id)
) {$this->get_collation()};
";
	}

	/**
	 * Get the un-prefixed (raw) table name.
	 *
	 * @return string
	 */
	public static function get_raw_name(): string {
		return 'ads_recommendations';
	}

	/**
	 * Get the columns for the table.
	 *
	 * @return array
	 */
	public function get_columns(): array {
		return [
			'recommendation_id'              => true,
			'recommendation_type'            => true,
			'recommendation_resource_name'   => true,
			'recommendation_campaign_id'     => true,
			'recommendation_campaign_name'   => true,
			'recommendation_campaign_status' => true,
			'recommendation_customer_id'     => true,
			'recommendation_last_synced'     => true,
		];
	}
}
