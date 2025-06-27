<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query\MerchantQuery;

/**
 * Class MerchantPriceBenchmarksQuery
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query
 */
class MerchantPriceBenchmarksQuery extends MerchantQuery {

	use ReportQueryTrait;

	/**
	 * MerchantPriceBenchmarksQuery constructor.
	 *
	 * @param array $args Query arguments.
	 */
	public function __construct( array $args ) {
		parent::__construct( 'PriceCompetitivenessProductView' );

		$this->set_initial_columns();
		$this->handle_query_args( $args );
	}

	/**
	 * Filter the query by a list of product IDs.
	 *
	 * @param array $ids List of product IDs to filter by.
	 *
	 * @return $this
	 */
	public function filter( array $ids ): QueryInterface {
		if ( ! empty( $ids ) ) {
			$this->where( 'product_view.id', $ids, 'IN' );
		}
		return $this;
	}

	/**
	 * Set the initial columns for this query.
	 */
	protected function set_initial_columns() {
		$this->columns(
			[
				'id'                            => 'product_view.id',
				'offer_id'                      => 'product_view.offer_id',
				'title'                         => 'product_view.title',
				'price_micros'                  => 'product_view.price_micros',
				'currency_code'                 => 'product_view.currency_code',
				'country_code'                  => 'price_competitiveness.country_code',
				'benchmark_price_micros'        => 'price_competitiveness.benchmark_price_micros',
				'benchmark_price_currency_code' => 'price_competitiveness.benchmark_price_currency_code',
			]
		);
	}
}
