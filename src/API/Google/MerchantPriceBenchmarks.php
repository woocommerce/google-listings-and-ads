<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query\MerchantPriceBenchmarksQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query\MerchantPriceSuggestionsQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent;
use Exception;

/**
 * Class MerchantPriceBenchmarks
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google
 */
class MerchantPriceBenchmarks implements OptionsAwareInterface {

	use OptionsAwareTrait;

	/**
	 * The shopping service.
	 *
	 * @var ShoppingContent
	 */
	protected $service;

	/**
	 * Merchant Report constructor.
	 *
	 * @param ShoppingContent $service
	 */
	public function __construct( ShoppingContent $service ) {
		$this->service = $service;
	}

	/**
	 * Get MerchantPriceBenchmarksQuery Query response.
	 *
	 * @param array $args Query arguments.
	 *
	 * @return array Associative array with price benchmarks data and the next page token.
	 *
	 * @throws Exception If the merchant price benchmarks data can't be retrieved.
	 */
	public function get_benchmark_data( array $args ): array {
		try {
			$response = ( new MerchantPriceBenchmarksQuery( $args ) )
			->set_client( $this->service, $this->options->get_merchant_id() )
			->get_results();

			$results = $response->getResults() ?? [];

			return $results;
		} catch ( GoogleException $e ) {
			do_action( 'woocommerce_gla_mc_client_exception', $e, __METHOD__ );
			throw new Exception( __( 'Unable to retrieve Merchant Price Benchmarks.', 'google-listings-and-ads' ) . $e->getMessage(), $e->getCode() );
		}
	}

	/**
	 * Get MerchantPriceSuggestions Query response.
	 *
	 * @param array $args Query arguments.
	 *
	 * @return array Associative array with price benchmarks data and the next page token.
	 *
	 * @throws Exception If the merchant price suggestions data can't be retrieved.
	 */
	public function get_price_insights( array $args ): array {
		try {
			$response = ( new MerchantPriceSuggestionsQuery( $args ) )
			->set_client( $this->service, $this->options->get_merchant_id() )
			->get_results();

			$results = $response->getResults() ?? [];

			return $results;
		} catch ( GoogleException $e ) {
			do_action( 'woocommerce_gla_mc_client_exception', $e, __METHOD__ );
			throw new Exception( __( 'Unable to retrieve Merchant Price Benchmarks.', 'google-listings-and-ads' ) . $e->getMessage(), $e->getCode() );
		}
	}
}
