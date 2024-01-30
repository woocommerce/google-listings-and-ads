<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query;

defined( 'ABSPATH' ) || exit;

/**
 * Class MerchantProductViewReportQuery
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query
 */
class MerchantProductViewReportQuery extends MerchantQuery {

	/**
	 * Query constructor.
	 *
	 * @param array $args Query arguments.
	 */
	public function __construct( array $args ) {
		parent::__construct( 'ProductView' );
		$this->set_initial_columns();
		$this->filter( $args );
	}


	/**
	 * Filter the query by a list of ID's.
	 *
	 * @param array $ids list of ID's to filter by.
	 *
	 * @return $this
	 */
	public function filter( array $ids ): QueryInterface {
		if ( empty( $ids ) ) {
			return $this;
		}

		return $this->where( 'product_view.offer_id', $ids, 'IN' );
	}

	/**
	 * Set the initial columns for this query.
	 */
	protected function set_initial_columns() {
		$this->columns(
			[
				'id'              => 'product_view.id',
				'offer_id'        => 'product_view.offer_id',
				'experation_date' => 'product_view.expiration_date',
				'status'          => 'product_view.aggregated_destination_status',
			]
		);
	}
}
