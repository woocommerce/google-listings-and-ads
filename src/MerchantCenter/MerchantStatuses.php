<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Merchant;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\MerchantIssueQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Table\MerchantIssueTable;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
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
 * Class MerchantStatuses
 *
 * ContainerAware used to retrieve
 * - Merchant
 * - MerchantIssueQuery
 * - MerchantIssueTable
 * - OptionInterface
 * - ProductHelper
 * - ProductRepository
 * - TransientInterface
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Options
 */
class MerchantStatuses implements Service, ContainerAwareInterface {

	use ContainerAwareTrait;

	/**
	 * The lifetime of the status-related data.
	 */
	public const STATUS_LIFETIME = HOUR_IN_SECONDS;

	/**
	 * The types of issues.
	 */
	public const TYPE_ACCOUNT = 'account';
	public const TYPE_PRODUCT = 'product';

	/**
	 * @var DateTime $current_time For cache age operations.
	 */
	protected $current_time;

	/**
	 * @var array Reference array of countries associated to each product+issue combo.
	 */
	protected $product_issue_countries = [];

	/**
	 * @var array Product statuses as reported by Merchant Center.
	 */
	protected $product_statistics;

	/**
	 * @var array Statuses for each product ID.
	 */
	protected $product_statuses;

	/**
	 * MerchantStatuses constructor.
	 */
	public function __construct() {
		$this->current_time = new DateTime();
	}

	/**
	 * Get the Product Statistics (updating caches if necessary).
	 *
	 * @param bool $force_refresh Force refresh of all product status data.
	 *
	 * @return array The product status statistics.
	 * @throws Exception If the Merchant Center can't be polled for the statuses.
	 */
	public function get_product_statistics( bool $force_refresh = false ): array {
		$this->maybe_refresh_status_data( $force_refresh );

		return $this->get_counting_statistics();
	}

	/**
	 * Retrieve the Merchant Center issues and total count. Refresh if the cache issues have gone stale.
	 * Issue details are reduced, and for products, grouped by type.
	 * Issues can be filtered by type, searched by name or ID (if product type) and paginated.
	 * Count takes into account the type filter, but not the pagination.
	 *
	 * @param string|null $type To filter by issue type if desired.
	 * @param int         $per_page The number of issues to return (0 for no limit).
	 * @param int         $page The page to start on (1-indexed).
	 * @param bool        $force_refresh Force refresh of all product status data.
	 *
	 * @return array With two indices, results (may be paged) and count (considers type).
	 * @throws Exception If the account state can't be retrieved from Google.
	 */
	public function get_issues( string $type = null, int $per_page = 0, int $page = 1, bool $force_refresh = false ): array {
		$this->maybe_refresh_status_data( $force_refresh );
		return $this->fetch_issue_data( $type, $per_page, $page );
	}

	/**
	 * Fetch the cached issues from the database.
	 *
	 * @param string|null $type To filter by issue type if desired.
	 * @param int         $per_page The number of issues to return (0 for no limit).
	 * @param int         $page The page to start on (1-indexed).
	 *
	 * @return array The requested issues and the total count of issues.
	 * @throws InvalidValue If the type filter is invalid.
	 */
	protected function fetch_issue_data( string $type = null, int $per_page = 0, int $page = 1 ): array {
		/** @var MerchantIssueQuery $issue_query */
		$issue_query = $this->container->get( MerchantIssueQuery::class );

		// Ensure account issues are shown first.
		$issue_query->set_order( 'product_id' );
		$issue_query->set_order( 'issue' );

		// Filter by type.
		if ( in_array( $type, $this->get_valid_issue_types(), true ) ) {
			$issue_query->where(
				'product_id',
				0,
				self::TYPE_ACCOUNT === $type ? '=' : '>'
			);
		} elseif ( null !== $type ) {
			throw InvalidValue::not_in_allowed_list( 'type filter', $this->get_valid_issue_types() );
		}

		// Result pagination.
		if ( $per_page > 0 ) {
			$issue_query->set_limit( $per_page );
			$issue_query->set_offset( $per_page * ( $page - 1 ) );
		}

		$issues = [];
		foreach ( $issue_query->get_results() as $row ) {
			$issue = [
				'type'       => $row['product_id'] ? self::TYPE_PRODUCT : self::TYPE_ACCOUNT,
				'product_id' => intval( $row['product_id'] ),
				'product'    => $row['product'],
				'issue'      => $row['issue'],
				'code'       => $row['code'],
				'action'     => $row['action'],
				'action_url' => $row['action_url'],
			];
			if ( $issue['product_id'] ) {
				$issue['applicable_countries'] = json_decode( $row['applicable_countries'], true );
			} else {
				unset( $issue['product_id'] );
			}
			$issues[] = $issue;
		}

		return [
			'results' => array_values( $issues ),
			'count'   => $issue_query->get_count(),
		];
	}

	/**
	 * Update stale status-related data - account issues, product issues, products status stats.
	 *
	 * @param bool $force_refresh Force refresh of all status-related data.
	 *
	 * @throws Exception If no Merchant Center account is connected, or account status is not retrievable.
	 */
	protected function maybe_refresh_status_data( bool $force_refresh = false ): void {
		// Only refresh if the current data has expired.
		$this->product_statistics = $this->container->get( TransientsInterface::class )->get( Transients::MC_STATUSES );
		if ( ! $force_refresh && null !== $this->product_statistics ) {
			return;
		}

		// Save a request if no MC account connected.
		if ( ! $this->container->get( OptionsInterface::class )->get_merchant_id() ) {
			throw new Exception( __( 'No Merchant Center account connected.', 'google-listings-and-ads' ) );
		}

		// Update product stats and issues.
		$this->product_statistics = [];
		$page_token               = null;
		do {
			$response = $this->container->get( Merchant::class )->get_productstatuses( $page_token );
			$statuses = $response->getResources();
			$this->refresh_product_issues( $statuses );
			$this->include_page_statuses( $statuses );

			$page_token = $response->getNextPageToken();
		} while ( $page_token );

		$this->save_product_statistics();

		// Update account issues.
		$this->refresh_account_issues();

		// Delete stale issues.
		$delete_before = clone $this->current_time;
		$delete_before->modify( '-' . $this->get_status_lifetime() . ' seconds' );
		$this->container->get( MerchantIssueTable::class )->delete_stale( $delete_before );
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
			function ( &$countries ) {
				sort( $countries );
			}
		);

		// Product issue cleanup: sorting (by product ID) and sort applicable countries.
		ksort( $product_issues );
		$product_issues = array_map(
			function ( $code, $issue ) {
				$issue['applicable_countries'] = json_encode( $this->product_issue_countries[ $code ] );

				return $issue;
			},
			array_keys( $product_issues ),
			$product_issues
		);

		$this->container->get( MerchantIssueQuery::class )->update_or_insert( array_values( $product_issues ) );
	}

	/**
	 * Add the status from the current page of products to the overall numbers.
	 *
	 * @param MC_Product_Status[] $mc_statuses
	 */
	protected function include_page_statuses( array $mc_statuses ): void {
		/** @var ProductHelper $product_helper */
		$product_helper = $this->container->get( ProductHelper::class );

		foreach ( $mc_statuses as $product ) {
			$wc_product_id = $product_helper->get_wc_product_id( $product->getProductId() );

			// Skip products not synced by this extension.
			if ( ! $wc_product_id ) {
				continue;
			}

			$status = $this->get_product_shopping_status( $product );
			if ( ! is_null( $status ) ) {
				$this->product_statuses[ $wc_product_id ][ $status ] = 1 + ( $this->product_statuses[ $wc_product_id ][ $status ] ?? 0 );
			}
		}
	}

	/**
	 * Update the product status statistics transient.
	 */
	protected function save_product_statistics() {
		$this->product_statistics =
			[
				'active'           => [],
				'partially_active' => [],
				'expiring'         => [],
				'pending'          => [],
				'disapproved'      => [],
				'not_synced'       => [],
			];

		foreach ( $this->product_statuses as $product_id => $statuses ) {
			if ( isset( $statuses['pending'] ) ) {
				$this->product_statistics['pending'][] = $product_id;
			} elseif ( isset( $statuses['expiring'] ) ) {
				$this->product_statistics['expiring'][] = $product_id;
			} elseif ( isset( $statuses['active'] ) ) {
				if ( count( $statuses ) > 1 ) {
					$this->product_statistics['partially_active'][] = $product_id;
				} else {
					$this->product_statistics['active'][] = $product_id;
				}
			} else {
				$this->product_statistics[ array_key_first( $statuses ) ][] = $product_id;
			}
		}

		/** @var ProductRepository $product_repository */
		$product_repository                     = $this->container->get( ProductRepository::class );
		$this->product_statistics['not_synced'] = $product_repository->find_sync_pending_product_ids();

		// Sort the product IDs.
		array_walk(
			$this->product_statistics,
			function( array &$el ): void {
				sort( $el );
			}
		);

		$this->product_statistics = [
			'timestamp'  => $this->current_time->getTimestamp(),
			'statistics' => $this->product_statistics,
		];

		// Update the cached values
		$this->container->get( TransientsInterface::class )->set(
			Transients::MC_STATUSES,
			$this->product_statistics,
			$this->get_status_lifetime()
		);
	}

	/**
	 * Retrieve all account-level issues and store them in the database.
	 *
	 * @throws Exception If the account state can't be retrieved from Google.
	 */
	protected function refresh_account_issues(): void {
		$account_issues = [];
		foreach ( $this->container->get( Merchant::class )->get_accountstatus()->getAccountLevelIssues() as $issue ) {
			$account_issues[] = [
				'product_id' => 0,
				'product'    => __( 'All products', 'google-listings-and-ads' ),
				'code'       => $issue->getId(),
				'issue'      => $issue->getTitle(),
				'action'     => __( 'Read more about this account issue', 'google-listings-and-ads' ),
				'action_url' => $issue->getDocumentation(),
				'created_at' => $this->current_time->format( 'Y-m-d H:i:s' ),
			];
		}

		$this->container->get( MerchantIssueQuery::class )->update_or_insert( $account_issues );
	}


	/**
	 * Delete the cached statistics and issues.
	 */
	public function delete(): void {
		$this->container->get( TransientsInterface::class )->delete( Transients::MC_STATUSES );
		$this->container->get( MerchantIssueTable::class )->truncate();
	}

	/**
	 * Return the product's shopping status in the Google Merchant Center.
	 * Active, Pending, Disapproved, Expiring.
	 *
	 * @param MC_Product_Status $product_status
	 *
	 * @return string|null
	 */
	protected function get_product_shopping_status( MC_Product_Status $product_status ): ?string {
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
	protected function get_status_lifetime(): int {
		return apply_filters( 'woocommerce_gla_mc_status_lifetime', self::STATUS_LIFETIME );
	}

	/**
	 * Valid issues types for issue type filter.
	 *
	 * @return string[]
	 */
	protected function get_valid_issue_types(): array {
		return [
			self::TYPE_ACCOUNT,
			self::TYPE_PRODUCT,
		];
	}

	/**
	 * Substitute the list of product IDs for the product count in the status transient.
	 *
	 * @return array
	 */
	protected function get_counting_statistics(): array {
		$counting_stats            = array_map( 'count', $this->product_statistics['statistics'] );
		$counting_stats['active'] += $counting_stats['partially_active'];
		unset( $counting_stats['partially_active'] );

		return array_merge(
			$this->product_statistics,
			[ 'statistics' => $counting_stats ]
		);
	}
}
