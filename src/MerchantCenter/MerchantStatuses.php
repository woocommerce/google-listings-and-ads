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
use Automattic\WooCommerce\GoogleListingsAndAds\Options\Transients;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\TransientsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductMetaHandler;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\MCStatus;
use Exception;
use Google_Service_ShoppingContent_ProductStatus as Shopping_Product_Status;
use DateTime;

/**
 * Class MerchantStatuses
 *
 * ContainerAware used to retrieve
 * - Merchant
 * - MerchantIssueQuery
 * - MerchantIssueTable
 * - MerchantCenterService
 * - ProductHelper
 * - ProductMetaHandler
 * - ProductRepository
 * - TransientInterface
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter
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
	 * @var array Transient with timestamp and product statuses as reported by Merchant Center.
	 */
	protected $mc_statuses;

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
	 * Get the Product Statistics (updating caches if necessary). This is the
	 * number of product IDs with each status (active and partially active are combined).
	 *
	 * @param bool $force_refresh Force refresh of all product status data.
	 *
	 * @return array The product status statistics.
	 * @throws Exception If the Merchant Center can't be polled for the statuses.
	 */
	public function get_product_statistics( bool $force_refresh = false ): array {
		$this->maybe_refresh_status_data( $force_refresh );

		$counting_stats = $this->mc_statuses['statistics'];
		$counting_stats = array_merge(
			[ 'active' => $counting_stats[ MCStatus::PARTIALLY_APPROVED ] + $counting_stats[ MCStatus::APPROVED ] ],
			$counting_stats
		);
		unset( $counting_stats[ MCStatus::PARTIALLY_APPROVED ], $counting_stats[ MCStatus::APPROVED ] );

		return array_merge(
			$this->mc_statuses,
			[ 'statistics' => $counting_stats ]
		);
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
		return $this->fetch_issues( $type, $per_page, $page );
	}

	/**
	 * Update stale status-related data - account issues, product issues, products status stats.
	 *
	 * @param bool $force_refresh Force refresh of all status-related data.
	 *
	 * @throws Exception If no Merchant Center account is connected, or account status is not retrievable.
	 */
	public function maybe_refresh_status_data( bool $force_refresh = false ): void {
		// Only refresh if the current data has expired.
		$this->mc_statuses = $this->container->get( TransientsInterface::class )->get( Transients::MC_STATUSES );
		if ( ! $force_refresh && null !== $this->mc_statuses ) {
			return;
		}

		// Save a request if no MC account connected.
		if ( ! $this->container->get( MerchantCenterService::class )->is_connected() ) {
			throw new Exception( __( 'No Merchant Center account connected.', 'google-listings-and-ads' ) );
		}

		// Update product stats and issues page by page.
		$this->mc_statuses = [];
		$page_token        = null;
		do {
			$response = $this->container->get( Merchant::class )->get_productstatuses( $page_token );
			$statuses = $response->getResources();
			$this->refresh_product_issues( $statuses );
			$this->sum_status_counts( $statuses );

			$page_token = $response->getNextPageToken();
		} while ( $page_token );

		$this->update_mc_statuses();
		$this->update_product_mc_statuses();

		// Update account issues.
		$this->refresh_account_issues();

		// Delete stale issues.
		$delete_before = clone $this->current_time;
		$delete_before->modify( '-' . $this->get_status_lifetime() . ' seconds' );
		$this->container->get( MerchantIssueTable::class )->delete_stale( $delete_before );
	}

	/**
	 * Delete the cached statistics and issues.
	 */
	public function delete(): void {
		$this->container->get( TransientsInterface::class )->delete( Transients::MC_STATUSES );
		$this->container->get( MerchantIssueTable::class )->truncate();
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
	protected function fetch_issues( string $type = null, int $per_page = 0, int $page = 1 ): array {
		/** @var MerchantIssueQuery $issue_query */
		$issue_query = $this->container->get( MerchantIssueQuery::class );

		// Ensure account issues are shown first.
		$issue_query->set_order( 'product_id' );
		$issue_query->set_order( 'issue' );

		// Filter by type if valid.
		if ( in_array( $type, $this->get_valid_issue_types(), true ) ) {
			$compare = self::TYPE_ACCOUNT === $type ? '=' : '>';
			$issue_query->where( 'product_id', 0, $compare );
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
			'issues' => $issues,
			'total'  => $issue_query->get_count(),
		];
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
	 * Retrieve all product-level issues and store them in the database.
	 *
	 * @param Shopping_Product_Status[] $mc_statuses
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
				'product'    => wc_get_product( $wc_product_id )->get_name(),
				'product_id' => $wc_product_id,
			];
			foreach ( $product->getItemLevelIssues() as $item_level_issue ) {
				if ( 'merchant_action' !== $item_level_issue->getResolution() ) {
					continue;
				}
				$hash_key = $wc_product_id . '__' . md5( $item_level_issue->getDescription() );

				$this->product_issue_countries[ $hash_key ] = array_merge(
					$this->product_issue_countries[ $hash_key ] ?? [],
					$item_level_issue->getApplicableCountries()
				);

				$product_issues[ $hash_key ] = $product_issue_template + [
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
			function ( $unique_key, $issue ) {
				$issue['applicable_countries'] = json_encode( $this->product_issue_countries[ $unique_key ] );
				return $issue;
			},
			array_keys( $product_issues ),
			$product_issues
		);

		$this->container->get( MerchantIssueQuery::class )->update_or_insert( array_values( $product_issues ) );
	}

	/**
	 * Add the provided status counts to the overall totals.
	 *
	 * @param Shopping_Product_Status[] $mc_statuses
	 */
	protected function sum_status_counts( array $mc_statuses ): void {
		/** @var ProductHelper $product_helper */
		$product_helper = $this->container->get( ProductHelper::class );

		foreach ( $mc_statuses as $product ) {
			$wc_product_id = $product_helper->get_wc_product_id( $product->getProductId() );

			// Skip products not synced by this extension.
			if ( ! $wc_product_id ) {
				continue;
			}

			$status = $this->get_product_shopping_status( $product );
			if ( is_null( $status ) ) {
				continue;
			}
			// Simple is used later for global product status statistics.
			$this->product_statuses['simple'][ $wc_product_id ][ $status ] = 1 + ( $this->product_statuses['simple'][ $wc_product_id ][ $status ] ?? 0 );

			// Aggregate parent statuses for mc_status postmeta.
			$wc_parent_id = $product_helper->maybe_get_parent_id( $wc_product_id );
			if ( is_null( $wc_parent_id ) ) {
				continue;
			}
			$this->product_statuses['parent'][ $wc_parent_id ][ $status ] = 1 + ( $this->product_statuses['parent'][ $wc_parent_id ][ $status ] ?? 0 );
		}
	}

	/**
	 * Calculate the product status statistics and update the transient.
	 */
	protected function update_mc_statuses() {
		$product_statistics = [
			MCStatus::APPROVED           => 0,
			MCStatus::PARTIALLY_APPROVED => 0,
			MCStatus::EXPIRING           => 0,
			MCStatus::PENDING            => 0,
			MCStatus::DISAPPROVED        => 0,
			MCSTATUS::NOT_SYNCED         => 0,
		];

		foreach ( $this->product_statuses['simple'] as $statuses ) {
			foreach ( $statuses as $status => $num_products ) {
				$product_statistics[ $status ] += $num_products;
			}
		}

		/** @var ProductRepository $product_repository */
		$product_repository                         = $this->container->get( ProductRepository::class );
		$product_statistics[ MCStatus::NOT_SYNCED ] = count( $product_repository->find_sync_pending_product_ids() );

		$this->mc_statuses = [
			'timestamp'  => $this->current_time->getTimestamp(),
			'statistics' => $product_statistics,
		];

		// Update the cached values
		$this->container->get( TransientsInterface::class )->set(
			Transients::MC_STATUSES,
			$this->mc_statuses,
			$this->get_status_lifetime()
		);
	}

	/**
	 * Update the Merchant Center status for each product.
	 */
	protected function update_product_mc_statuses() {
		// Generate a product_id=>mc_status array.
		$product_statuses = [];
		foreach ( $this->product_statuses as $products ) {
			foreach ( $products as $product_id => $statuses ) {
				if ( isset( $statuses[ MCStatus::PENDING ] ) ) {
					$product_statuses[ $product_id ] = MCStatus::PENDING;
				} elseif ( isset( $statuses[ MCStatus::EXPIRING ] ) ) {
					$product_statuses[ $product_id ] = MCStatus::EXPIRING;
				} elseif ( isset( $statuses[ MCStatus::APPROVED ] ) ) {
					if ( count( $statuses ) > 1 ) {
						$product_statuses[ $product_id ] = MCStatus::PARTIALLY_APPROVED;
					} else {
						$product_statuses[ $product_id ] = MCStatus::APPROVED;
					}
				} else {
					$product_statuses[ $product_id ] = array_key_first( $statuses );
				}
			}
		}
		ksort( $product_statuses );

		/** @var ProductMetaHandler $product_meta */
		$product_meta = $this->container->get( ProductMetaHandler::class );
		foreach ( $this->container->get( ProductRepository::class )->find_ids() as $product_id ) {
			$product_meta->update_mc_status( $product_id, $product_statuses[ $product_id ] ?? MCStatus::NOT_SYNCED );
		}
	}

	/**
	 * Return the product's shopping status in the Google Merchant Center.
	 * Active, Pending, Disapproved, Expiring.
	 *
	 * @param Shopping_Product_Status $product_status
	 *
	 * @return string|null
	 */
	protected function get_product_shopping_status( Shopping_Product_Status $product_status ): ?string {
		foreach ( $product_status->getDestinationStatuses() as $d ) {
			if ( $d->getDestination() === 'Shopping' ) {
				return $d->getStatus();
			}
		}
		return null;
	}

	/**
	 * Allows a hook to modify the lifetime of the statuses data.
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
}
