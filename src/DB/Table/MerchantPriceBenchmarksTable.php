<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\DB\Table;

use Automattic\WooCommerce\GoogleListingsAndAds\DB\Table;

defined( 'ABSPATH' ) || exit;

/**
 * Class MerchantPriceBenchmarksTable
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\DB\Tables
 */
class MerchantPriceBenchmarksTable extends Table {

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
	product_id bigint(20) NOT NULL,
	mc_product_id varchar(255) NOT NULL,
	mc_product_offer_id varchar(255) NOT NULL,
	mc_product_price_micros varchar(64) NOT NULL,
	mc_product_currency_code varchar(3) NOT NULL,
	mc_price_country_code varchar(2) NOT NULL,
	mc_price_benchmark_price_micros varchar(64) NOT NULL,
	mc_price_benchmark_price_currency_code varchar(3) NOT NULL,
	mc_insights_suggested_price_micros varchar(64) NOT NULL,
	mc_insights_suggested_price_currency_code varchar(3) NOT NULL,
	mc_insights_predicted_impressions_change_fraction decimal(10,6) NOT NULL,
	mc_insights_predicted_clicks_change_fraction decimal(10,6) NOT NULL,
	mc_insights_predicted_conversions_change_fraction decimal(10,6) NOT NULL,
	mc_insights_effectiveness tinyint(1) NOT NULL,
	mc_metrics_clicks varchar(64) NOT NULL,
	mc_metrics_impressions varchar(64) NOT NULL,
	mc_metrics_ctr int(20) NOT NULL,
	mc_metrics_conversions int(20) NOT NULL,
	price_compared_with_benchmark tinyint(1) NOT NULL DEFAULT 0,
	PRIMARY KEY (product_id),
	UNIQUE KEY mc_product_id (mc_product_id),
	KEY mc_insights_effectiveness (mc_insights_effectiveness),
	KEY price_compared_with_benchmark (price_compared_with_benchmark)
) {$this->get_collation()};
";
	}

	/**
	 * Get the un-prefixed (raw) table name.
	 *
	 * @return string
	 */
	public static function get_raw_name(): string {
		return 'merchant_price_benchmarks';
	}

	/**
	 * Get the columns for the table.
	 *
	 * @return array
	 */
	public function get_columns(): array {
		return [
			'product_id'                                        => true,
			'mc_product_id'                                     => true,
			'mc_product_offer_id'                               => true,
			'mc_product_price_micros'                           => true,
			'mc_product_currency_code'                          => true,
			'mc_price_country_code'                             => true,
			'mc_price_benchmark_price_micros'                   => true,
			'mc_price_benchmark_price_currency_code'            => true,
			'mc_insights_suggested_price_micros'                => true,
			'mc_insights_suggested_price_currency_code'         => true,
			'mc_insights_predicted_impressions_change_fraction' => true,
			'mc_insights_predicted_clicks_change_fraction'      => true,
			'mc_insights_predicted_conversions_change_fraction' => true,
			'mc_insights_effectiveness'                         => true,
			'mc_metrics_clicks'                                 => true,
			'mc_metrics_impressions'                            => true,
			'mc_metrics_ctr'                                    => true,
			'mc_metrics_conversions'                            => true,
			'price_compared_with_benchmark'                     => true,
		];
	}
}
