<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Merchant;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\MerchantIssueQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\ContainerAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces\ContainerAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\Transients;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\TransientsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductRepository;
use Exception;
use Google_Service_ShoppingContent_ProductStatus as MC_Product_Status;
use DateTime;

/**
 * Class MerchantProductStatuses
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Options
 */
class MerchantProductStatuses implements Service, ContainerAwareInterface {

	use ContainerAwareTrait;

	/**
	 * The lifetime of the product feed status and issues data.
	 */
	public const STATUS_LIFETIME = HOUR_IN_SECONDS;

	/**
	 * @var Merchant $merchant
	 */
	protected $merchant;

	/**
	 * @var MerchantIssueQuery $issue_query
	 */
	protected $issue_query;

	/**
	 * @var DateTime $current_time For cache age operations.
	 */
	protected $current_time;

	/**
	 * @var array Reference array of countries by product/issue combo.
	 */
	protected $product_issue_countries = [];

	/**
	 * @var array Product statuses as reported by Merchant Center.
	 */
	protected $product_statistics = [];

	/**
	 * MerchantProductStatuses constructor.
	 *
	 * @param Merchant           $merchant
	 * @param MerchantIssueQuery $issue_query
	 */
	public function __construct( Merchant $merchant, MerchantIssueQuery $issue_query ) {
		$this->merchant     = $merchant;
		$this->issue_query  = $issue_query;
		$this->current_time = new DateTime();
	}

	/**
	 * Get the Product Statistics (updating if necessary).
	 *
	 * @param bool $force_refresh Force refresh of all product status data.
	 *
	 * @return array The product status statistics.
	 * @throws Exception If the merchant center can't be polled for the statuses.
	 */
	public function get_statistics( bool $force_refresh = false ): array {
		$this->product_statistics = $this->container->get( TransientsInterface::class )->get( Transients::MC_PRODUCT_STATISTICS, null );

		if ( $force_refresh || is_null( $this->product_statistics ) ) {
			$this->refresh_stats_and_issues();
		}

		return $this->product_statistics;
	}


	/**
	 * Update Merchant Center product stats and issues, retrieving MC product
	 * statuses page by page.
	 *
	 * @throws Exception If the merchant center isn't connected (can't be polled for the statuses).
	 */
	public function refresh_stats_and_issues(): void {
		// Save a request if no MC account connected.
		if ( ! $this->container->get( OptionsInterface::class )->get_merchant_id() ) {
			throw new Exception( __( 'No Merchant Center account connected.', 'google-listings-and-ads' ) );
		}

		$page_token = null;
		do {
			$response = $this->merchant->get_productstatuses( $page_token );
			$statuses = $response->getResources();
			$this->refresh_product_issues( $statuses );
			$this->tabulate_statistics( $statuses );

			$page_token = $response->getNextPageToken();
		} while ( $page_token );

		$this->save_product_statistics();
	}

	/**
	 * Update the product status statistics transient.
	 */
	protected function save_product_statistics() {
		$this->product_statistics = array_merge(
			[
				'active'      => 0,
				'expiring'    => 0,
				'pending'     => 0,
				'disapproved' => 0,
				'not_synced'  => 0,
			],
			$this->product_statistics
		);

		/** @var ProductRepository $product_repository */
		$product_repository                     = $this->container->get( ProductRepository::class );
		$this->product_statistics['not_synced'] = count( $product_repository->find_sync_pending_product_ids() );

		$this->product_statistics = [
			'timestamp'  => $this->current_time->getTimestamp(),
			'statistics' => $this->product_statistics,
		];

		// Update the cached values
		$this->container->get( TransientsInterface::class )->set(
			Transients::MC_PRODUCT_STATISTICS,
			$this->product_statistics,
			$this->get_status_lifetime()
		);
	}

	/**
	 * Add the status from the current page of products to the overall numbers.
	 *
	 * @param MC_Product_Status[] $mc_statuses
	 */
	protected function tabulate_statistics( array $mc_statuses ): void {
		/** @var ProductHelper $product_helper */
		$product_helper = $this->container->get( ProductHelper::class );

		foreach ( $mc_statuses as $product ) {

			// Skip products not synced by this extension.
			if ( ! $product_helper->get_wc_product_id( $product->getProductId() ) ) {
				continue;
			}

			$status = $this->get_product_status( $product );
			if ( ! is_null( $status ) ) {
				$this->product_statistics[ $status ] = 1 + ( $this->product_statistics[ $status ] ?? 0 );
			}
		}
	}

	/**
	 * Retrieve all product-level issues and store them in the database.
	 *
	 * @param MC_Product_Status[] $mc_statuses
	 */
	protected function refresh_product_issues( array $mc_statuses ): void {
		/** @var ProductHelper $product_helper */
		$product_helper = $this->container->get( ProductHelper::class );

		$product_issues = [];
		$created_at     = $this->current_time->format( 'Y-m-d H:i:s' );
		foreach ( $mc_statuses as $product ) {
			$wc_product_id = $product_helper->get_wc_product_id( $product->getProductId() );

			// Skip products not synced by this extension.
			if ( ! $wc_product_id ) {
				continue;
			}

			$product_issue_template = [
				'product'    => $product->getTitle(),
				'product_id' => $wc_product_id,
			];
			foreach ( $product->getItemLevelIssues() as $item_level_issue ) {
				if ( 'merchant_action' !== $item_level_issue->getResolution() ) {
					continue;
				}
				$code = $wc_product_id . '__' . md5( $item_level_issue->getDescription() );

				$this->product_issue_countries[ $code ] = array_merge(
					$this->product_issue_countries[ $code ] ?? [],
					$item_level_issue->getApplicableCountries()
				);

				$product_issues[ $code ] = $product_issue_template + [
					'code'                 => $item_level_issue->getCode(),
					'issue'                => $item_level_issue->getDescription(),
					'action'               => $item_level_issue->getDetail(),
					'action_url'           => $item_level_issue->getDocumentation(),
					'applicable_countries' => [],
					'created_at'           => $created_at,
				];
			}
		}

		// Alphabetize all product/issue country lists.
		array_walk(
			$this->product_issue_countries,
			function( &$countries ) {
				sort( $countries );
			}
		);

		// Product issue cleanup: sorting (by product ID) and sort applicable countries.
		ksort( $product_issues );
		$product_issues = array_map(
			function( $code, $issue ) {
				$issue['applicable_countries'] = json_encode( $this->product_issue_countries[ $code ] );
				return $issue;
			},
			array_keys( $product_issues ),
			$product_issues
		);

		$this->issue_query->update_or_insert( array_values( $product_issues ) );
	}


	/**
	 * Return the current product status in the Google Merchant Center.
	 * Active, Pending, Disapproved, Expiring.
	 *
	 * @param MC_Product_Status $product_status
	 *
	 * @return string|null
	 */
	protected function get_product_status( MC_Product_Status $product_status ): ?string {
		foreach ( $product_status->getDestinationStatuses() as $d ) {
			if ( $d->getDestination() === 'Shopping' ) {
				return $d->getStatus();
			}
		}
		return null;
	}



	/**
	 * Allows a hook to modify the statistics lifetime.
	 *
	 * @return int
	 */
	private function get_status_lifetime(): int {
		return apply_filters( 'woocommerce_gla_product_status_lifetime', self::STATUS_LIFETIME );
	}
}
