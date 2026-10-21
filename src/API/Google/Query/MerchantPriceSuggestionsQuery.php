<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query;

defined( 'ABSPATH' ) || exit;

/**
 * Class MerchantPriceSuggestionsQuery
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query
 */
class MerchantPriceSuggestionsQuery extends MapiReportQuery {

	use ReportQueryTrait;

	/**
	 * MerchantPriceSuggestionsQuery constructor.
	 *
	 * @param array $args Query arguments.
	 */
	public function __construct( array $args ) {
		parent::__construct( 'price_insights_product_view' );

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
			$this->where( 'price_insights_product_view.id', $ids, 'IN' );
		}
		return $this;
	}

	/**
	 * Set the initial columns for this query.
	 */
	protected function set_initial_columns() {
		$this->columns(
			[
				'id'                                    => 'price_insights_product_view.id',
				'offer_id'                              => 'price_insights_product_view.offer_id',
				'title'                                 => 'price_insights_product_view.title',
				'price'                                 => 'price_insights_product_view.price',
				'suggested_price'                       => 'price_insights_product_view.suggested_price',
				'predicted_impressions_change_fraction' => 'price_insights_product_view.predicted_impressions_change_fraction',
				'predicted_clicks_change_fraction'      => 'price_insights_product_view.predicted_clicks_change_fraction',
				'predicted_conversions_change_fraction' => 'price_insights_product_view.predicted_conversions_change_fraction',
				'effectiveness'                         => 'price_insights_product_view.effectiveness',
			]
		);
	}
}
