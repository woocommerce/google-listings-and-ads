<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query\MerchantFreeListingReportQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use DateTime;
use Google\Service\ShoppingContent;
use Google\Service\ShoppingContent\SearchResponse;

/**
 * Class MerchantMetrics
 *
 * @since x.x.x
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google
 */
class MerchantMetrics implements OptionsAwareInterface {

	use OptionsAwareTrait;

	/**
	 * The shopping service.
	 *
	 * @var ShoppingContent
	 */
	protected $service;

	protected const MAX_QUERY_START_DATE = '2020-01-01';

	/**
	 * Merchant Report constructor.
	 *
	 * @param ShoppingContent $service
	 */
	public function __construct( ShoppingContent $service ) {
		$this->service = $service;
	}

	/**
	 * Get number of free listing clicks.
	 *
	 * @return int
	 */
	public function get_free_listing_clicks(): int {
		// Google API requires a date clause to be set but there doesn't seem to be any limits on how wide the range
		$query = new MerchantFreeListingReportQuery(
			[
				'after'  => self::MAX_QUERY_START_DATE,
				'before' => $this->get_today(),
				'fields' => [ 'clicks' ],
			]
		);

		/** @var SearchResponse $results */
		$results = $query
			->set_client( $this->service, $this->options->get_merchant_id() )
			->get_results();

		if ( empty( $results->getResults() ) ) {
			return 0;
		}

		$result = $results->getResults()[0];
		return (int) $result->getMetrics()->getClicks();
	}

	/**
	 * Get today's date.
	 *
	 * @return string
	 */
	protected function get_today(): string {
		return ( new DateTime( 'now', wp_timezone() ) )->format( 'Y-m-d' );
	}

}
