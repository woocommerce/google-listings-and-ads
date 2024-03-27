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

	use ReportQueryTrait;

	/**
	 * Query constructor.
	 *
	 * @param array $args Query arguments.
	 */
	public function __construct( array $args ) {
		parent::__construct( 'ProductView' );
		$this->set_initial_columns();
		$this->handle_query_args( $args );
	}


	/**
	 * Filter the query by a list of ID's.
	 *
	 * @param array $ids list of ID's to filter by.
	 *
	 * @return $this
	 */
	public function filter( array $ids ): QueryInterface {
		// No filtering used for product view report.
		return $this;
	}

	/**
	 * Set the initial columns for this query.
	 */
	protected function set_initial_columns() {
		$this->columns(
			[
				'id'              => 'product_view.id',
				'expiration_date' => 'product_view.expiration_date',
				'status'          => 'product_view.aggregated_destination_status',
			]
		);
	}
}
