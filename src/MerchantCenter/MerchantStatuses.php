<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Merchant;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\ProductMetaQueryHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\MerchantIssueQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Table\MerchantIssueTable;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\ContainerAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces\ContainerAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\Transients;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\TransientsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductMetaHandler;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\ChannelVisibility;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\MCStatus;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\ProductStatus as GoogleProductStatus;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\DeleteAllProducts;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\JobRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\UpdateAllProducts;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\UpdateMerchantProductStatuses;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use DateTime;
use Exception;
use WC_Product;

/**
 * Class MerchantStatuses.
 * Note: this class uses vanilla WP methods get_post, get_post_meta, update_post_meta
 *
 * ContainerAware used to retrieve
 * - JobRepository
 * - Merchant
 * - MerchantCenterService
 * - MerchantIssueQuery
 * - MerchantIssueTable
 * - ProductHelper
 * - ProductRepository
 * - TransientsInterface
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter
 */
class MerchantStatuses implements Service, ContainerAwareInterface, OptionsAwareInterface {

	use OptionsAwareTrait;
	use ContainerAwareTrait;
	use PluginHelper;


	/**
	 * The lifetime of the status-related data.
	 */
	public const STATUS_LIFETIME = 30 * MINUTE_IN_SECONDS;

	/**
	 * The types of issues.
	 */
	public const TYPE_ACCOUNT = 'account';
	public const TYPE_PRODUCT = 'product';

	/**
	 * Issue severity levels.
	 */
	public const SEVERITY_WARNING = 'warning';
	public const SEVERITY_ERROR   = 'error';

	/**
	 * @var DateTime $cache_created_time For cache age operations.
	 */
	protected $cache_created_time;

	/**
	 * @var array Reference array of countries associated to each product+issue combo.
	 */
	protected $product_issue_countries = [];

	/**
	 * @var array Transient with timestamp and product statuses as reported by Merchant Center.
	 */
	protected $mc_statuses;

	/**
	 * @var array Statuses for each product id and parent id.
	 */
	protected $product_statuses = [
		'products' => [],
		'parents'  => [],
	];

	/**
	 * @var array Default product stats.
	 */
	public const DEFAULT_PRODUCT_STATS = [
		MCStatus::APPROVED           => 0,
		MCStatus::PARTIALLY_APPROVED => 0,
		MCStatus::EXPIRING           => 0,
		MCStatus::PENDING            => 0,
		MCStatus::DISAPPROVED        => 0,
		MCStatus::NOT_SYNCED         => 0,
		'parents'                    => [],
	];

	/**
	 * @var array Initial intermediate data for product status counts.
	 */
	protected $initial_intermediate_data = self::DEFAULT_PRODUCT_STATS;

	/**
	 * @var WC_Product[] Lookup of WooCommerce Product Objects.
	 */
	protected $product_data_lookup = [];

	/**
	 * MerchantStatuses constructor.
	 */
	public function __construct() {
		$this->cache_created_time = new DateTime();
	}

	/**
	 * Get the Product Statistics (updating caches if necessary). This is the
	 * number of product IDs with each status (approved and partially approved are combined as active).
	 *
	 * @param bool $force_refresh Force refresh of all product status data.
	 *
	 * @return array The product status statistics.
	 * @throws Exception If no Merchant Center account is connected, or account status is not retrievable.
	 */
	public function get_product_statistics( bool $force_refresh = false ): array {
		$job               = $this->maybe_refresh_status_data( $force_refresh );
		$failure_rate_msg  = $job->get_failure_rate_message();
		$this->mc_statuses = $this->container->get( TransientsInterface::class )->get( Transients::MC_STATUSES );

		// If the failure rate is too high, return an error message so the UI can stop polling.
		if ( $failure_rate_msg && null === $this->mc_statuses ) {
			return [
				'timestamp'  => $this->cache_created_time->getTimestamp(),
				'statistics' => null,
				'loading'    => false,
				'error'      => __( 'The scheduled job has been paused due to a high failure rate.', 'google-listings-and-ads' ),
			];
		}

		if ( $job->is_scheduled() || null === $this->mc_statuses ) {
			return [
				'timestamp'  => $this->cache_created_time->getTimestamp(),
				'statistics' => null,
				'loading'    => true,
				'error'      => null,
			];
		}

		if ( ! empty( $this->mc_statuses['error'] ) ) {
			return $this->mc_statuses;
		}

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
	 * Issues can be filtered by type, severity and searched by name or ID (if product type) and paginated.
	 * Count takes into account the type filter, but not the pagination.
	 *
	 * In case there are issues with severity Error we hide the other issues with lower severity.
	 *
	 * @param string|null $type To filter by issue type if desired.
	 * @param int         $per_page The number of issues to return (0 for no limit).
	 * @param int         $page The page to start on (1-indexed).
	 * @param bool        $force_refresh Force refresh of all product status data.
	 *
	 * @return array With two indices, results (may be paged), count (considers type) and loading (indicating whether the data is loading).
	 * @throws Exception If the account state can't be retrieved from Google.
	 */
	public function get_issues( ?string $type = null, int $per_page = 0, int $page = 1, bool $force_refresh = false ): array {
		$job = $this->maybe_refresh_status_data( $force_refresh );

		// Get only error issues
		$severity_error_issues = $this->fetch_issues( $type, $per_page, $page, true );

		// In case there are error issues we show only those, otherwise we show all the issues.
		$issues            = $severity_error_issues['total'] > 0 ? $severity_error_issues : $this->fetch_issues( $type, $per_page, $page );
		$issues['loading'] = $job->is_scheduled();

		return $issues;
	}

	/**
	 * Clears the status cache data.
	 *
	 * @since 1.1.0
	 */
	public function clear_cache(): void {
		$job_repository          = $this->container->get( JobRepository::class );
		$update_all_products_job = $job_repository->get( UpdateAllProducts::class );
		$delete_all_products_job = $job_repository->get( DeleteAllProducts::class );

		// Clear the cache if we are not in the middle of updating/deleting all products. Otherwise, we might update the product stats for each individual batch.
		// See: ClearProductStatsCache::register
		if ( $update_all_products_job->can_schedule( null ) && $delete_all_products_job->can_schedule( null ) ) {
			$this->container->get( TransientsInterface::class )->delete( TransientsInterface::MC_STATUSES );
		}
	}

	/**
	 * Delete the intermediate product status count data.
	 *
	 * @since 2.6.4
	 */
	protected function delete_product_statuses_count_intermediate_data(): void {
		$this->options->delete( OptionsInterface::PRODUCT_STATUSES_COUNT_INTERMEDIATE_DATA );
	}

	/**
	 * Delete the stale issues from the database.
	 *
	 * @since 2.6.4
	 */
	protected function delete_stale_issues(): void {
		$this->container->get( MerchantIssueTable::class )->delete_stale( $this->cache_created_time );
	}

	/**
	 * Delete the stale mc statuses from the database.
	 *
	 * @since 2.6.4
	 */
	protected function delete_stale_mc_statuses(): void {
		$product_meta_query_helper = $this->container->get( ProductMetaQueryHelper::class );
		$product_meta_query_helper->delete_all_values( ProductMetaHandler::KEY_MC_STATUS );
	}

	/**
	 * Clear the product statuses cache and delete stale issues.
	 *
	 * @since 2.6.4
	 */
	public function clear_product_statuses_cache_and_issues(): void {
		$this->delete_stale_issues();
		$this->delete_stale_mc_statuses();
		$this->delete_product_statuses_count_intermediate_data();
	}

	/**
	 * Check if the Merchant Center account is connected and throw an exception if it's not.
	 *
	 * @since 2.6.4
	 *
	 * @throws Exception If the Merchant Center account is not connected.
	 */
	protected function check_mc_is_connected() {
		$mc_service = $this->container->get( MerchantCenterService::class );

		if ( ! $mc_service->is_connected() ) {

			// Return a 401 to redirect to reconnect flow if the Google account is not connected.
			if ( ! $mc_service->is_google_connected() ) {
				throw new Exception( __( 'Google account is not connected.', 'google-listings-and-ads' ), 401 );
			}

			throw new Exception( __( 'Merchant Center account is not set up.', 'google-listings-and-ads' ) );
		}
	}

	/**
	 * Maybe start the job to refresh the status and issues data.
	 *
	 * @param bool $force_refresh Force refresh of all status-related data.
	 *
	 * @return UpdateMerchantProductStatuses The job to update the statuses.
	 *
	 * @throws Exception If no Merchant Center account is connected, or account status is not retrievable.
	 * @throws NotFoundExceptionInterface  If the class is not found in the container.
	 * @throws ContainerExceptionInterface If the container throws an exception.
	 */
	public function maybe_refresh_status_data( bool $force_refresh = false ): UpdateMerchantProductStatuses {
		$this->check_mc_is_connected();

		// Only refresh if the current data has expired.
		$this->mc_statuses = $this->container->get( TransientsInterface::class )->get( Transients::MC_STATUSES );
		$job               = $this->container->get( JobRepository::class )->get( UpdateMerchantProductStatuses::class );

		// If force_refresh is true or if not transient, return empty array and scheduled the job to update the statuses.
		if ( ! $job->is_scheduled() && ( $force_refresh || ( ! $force_refresh && null === $this->mc_statuses ) ) ) {
			// Delete the transient before scheduling the job because some errors, like the failure rate message, can occur before the job is executed.
			$this->clear_cache();
			// Schedule job to update the statuses. If the failure rate is too high, the job will not be scheduled.
			$job->schedule();
		}

		return $job;
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
	 * @param bool        $only_errors Filters only the issues with error and critical severity.
	 *
	 * @return array The requested issues and the total count of issues.
	 * @throws InvalidValue If the type filter is invalid.
	 */
	protected function fetch_issues( ?string $type = null, int $per_page = 0, int $page = 1, bool $only_errors = false ): array {
		/** @var MerchantIssueQuery $issue_query */
		$issue_query = $this->container->get( MerchantIssueQuery::class );

		// Ensure account issues are shown first.
		$issue_query->set_order( 'type' );
		$issue_query->set_order( 'product' );
		$issue_query->set_order( 'issue' );

		// Filter by type if valid.
		if ( in_array( $type, $this->get_valid_issue_types(), true ) ) {
			$issue_query->where( 'type', $type );
		} elseif ( null !== $type ) {
			throw InvalidValue::not_in_allowed_list( 'type filter', $this->get_valid_issue_types() );
		}

		// Result pagination.
		if ( $per_page > 0 ) {
			$issue_query->set_limit( $per_page );
			$issue_query->set_offset( $per_page * ( $page - 1 ) );
		}

		if ( $only_errors ) {
			$issue_query->where( 'severity', [ 'error', 'critical' ], 'IN' );
		}

		$issues = [];
		foreach ( $issue_query->get_results() as $row ) {
			$issue = [
				'type'       => $row['type'],
				'product_id' => intval( $row['product_id'] ),
				'product'    => $row['product'],
				'issue'      => $row['issue'],
				'code'       => $row['code'],
				'action'     => $row['action'],
				'action_url' => $row['action_url'],
				'severity'   => $this->get_issue_severity( $row ),
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
	 * Get MC product issues from a list of Product View statuses.
	 *
	 * @param array $statuses The list of Product View statuses.
	 * @throws NotFoundExceptionInterface  If the class is not found in the container.
	 * @throws ContainerExceptionInterface If the container throws an exception.
	 *
	 * @return array The list of product issues.
	 */
	protected function get_product_issues( array $statuses ): array {
		/** @var Merchant $merchant */
		$merchant = $this->container->get( Merchant::class );
		/** @var ProductHelper $product_helper */
		$product_helper      = $this->container->get( ProductHelper::class );
		$visibility_meta_key = $this->prefix_meta_key( ProductMetaHandler::KEY_VISIBILITY );

		$google_ids     = array_column( $statuses, 'mc_id' );
		$product_issues = [];
		$created_at     = $this->cache_created_time->format( 'Y-m-d H:i:s' );
		$entries        = $merchant->get_productstatuses_batch( $google_ids )->getEntries() ?? [];
		foreach ( $entries as $response_entry ) {
			/** @var GoogleProductStatus $mc_product_status */
			$mc_product_status = $response_entry->getProductStatus();
			$mc_product_id     = $mc_product_status->getProductId();
			$wc_product_id     = $product_helper->get_wc_product_id( $mc_product_id );
			$wc_product        = $this->product_data_lookup[ $wc_product_id ] ?? null;

			// Skip products not synced by this extension.
			if ( ! $wc_product ) {
				do_action(
					'woocommerce_gla_debug_message',
					sprintf( 'Merchant Center product %s not found in this WooCommerce store.', $mc_product_id ),
					__METHOD__ . ' in remove_invalid_statuses()',
				);
				continue;
			}
			// Unsynced issues shouldn't be shown.
			if ( ChannelVisibility::DONT_SYNC_AND_SHOW === $wc_product->get_meta( $visibility_meta_key ) ) {
				continue;
			}

			// Confirm there are issues for this product.
			if ( empty( $mc_product_status->getItemLevelIssues() ) ) {
				continue;
			}

			$product_issue_template = [
				'product'              => html_entity_decode( $wc_product->get_name(), ENT_QUOTES ),
				'product_id'           => $wc_product_id,
				'created_at'           => $created_at,
				'applicable_countries' => [],
				'source'               => 'mc',
			];
			foreach ( $mc_product_status->getItemLevelIssues() as $item_level_issue ) {
				if ( 'merchant_action' !== $item_level_issue->getResolution() ) {
					continue;
				}
				$hash_key = $wc_product_id . '__' . md5( $item_level_issue->getDescription() );

				$this->product_issue_countries[ $hash_key ] = array_merge(
					$this->product_issue_countries[ $hash_key ] ?? [],
					$item_level_issue->getApplicableCountries()
				);

				$product_issues[ $hash_key ] = $product_issue_template + [
					'code'       => $item_level_issue->getCode(),
					'issue'      => $item_level_issue->getDescription(),
					'action'     => $item_level_issue->getDetail(),
					'action_url' => $item_level_issue->getDocumentation(),
					'severity'   => $item_level_issue->getServability(),
				];
			}
		}

		return $product_issues;
	}

	/**
	 * Refresh the account , pre-sync product validation and custom merchant issues.
	 *
	 * @since 2.6.4
	 *
	 * @throws Exception  If the account state can't be retrieved from Google.
	 */
	public function refresh_account_and_presync_issues(): void {
		// Update account-level issues.
		$this->refresh_account_issues();

		// Update pre-sync product validation issues.
		$this->refresh_presync_product_issues();

		// Include any custom merchant issues.
		$this->refresh_custom_merchant_issues();
	}

	/**
	 * Retrieve all account-level issues and store them in the database.
	 *
	 * @throws Exception If the account state can't be retrieved from Google.
	 */
	protected function refresh_account_issues(): void {
		/** @var Merchant $merchant */
		$merchant       = $this->container->get( Merchant::class );
		$account_issues = [];
		$created_at     = $this->cache_created_time->format( 'Y-m-d H:i:s' );
		$issues         = $merchant->get_accountstatus()->getAccountLevelIssues() ?? [];
		foreach ( $issues as $issue ) {
			$key = md5( $issue->getTitle() );

			if ( isset( $account_issues[ $key ] ) ) {
				$account_issues[ $key ]['applicable_countries'][] = $issue->getCountry();
			} else {
				$account_issues[ $key ] = [
					'product_id'           => 0,
					'product'              => __( 'All products', 'google-listings-and-ads' ),
					'code'                 => $issue->getId(),
					'issue'                => $issue->getTitle(),
					'action'               => $issue->getDetail(),
					'action_url'           => $issue->getDocumentation(),
					'created_at'           => $created_at,
					'type'                 => self::TYPE_ACCOUNT,
					'severity'             => $issue->getSeverity(),
					'source'               => 'mc',
					'applicable_countries' => [ $issue->getCountry() ],
				];

				$account_issues[ $key ] = $this->maybe_override_issue_values( $account_issues[ $key ] );
			}
		}

		// Sort and encode countries
		$account_issues = array_map(
			function ( $issue ) {
				sort( $issue['applicable_countries'] );
				$issue['applicable_countries'] = wp_json_encode(
					array_unique(
						$issue['applicable_countries']
					)
				);
				return $issue;
			},
			$account_issues
		);

		/** @var MerchantIssueQuery $issue_query */
		$issue_query = $this->container->get( MerchantIssueQuery::class );
		$issue_query->update_or_insert( $account_issues );
	}

	/**
	 * Custom issues can be added to the merchant issues table.
	 *
	 * @since 1.2.0
	 */
	protected function refresh_custom_merchant_issues() {
		$custom_issues = apply_filters( 'woocommerce_gla_custom_merchant_issues', [], $this->cache_created_time );

		if ( empty( $custom_issues ) ) {
			return;
		}

		/** @var MerchantIssueQuery $issue_query */
		$issue_query = $this->container->get( MerchantIssueQuery::class );
		$issue_query->update_or_insert( $custom_issues );
	}

	/**
	 * Refresh product issues in the merchant issues table.
	 *
	 * @param array $product_issues Array of product issues.
	 * @throws InvalidQuery If an invalid column name is provided.
	 * @throws NotFoundExceptionInterface  If the class is not found in the container.
	 * @throws ContainerExceptionInterface If the container throws an exception.
	 */
	protected function refresh_product_issues( array $product_issues ): void {
		// Alphabetize all product/issue country lists.
		array_walk(
			$this->product_issue_countries,
			function ( &$countries ) {
				sort( $countries );
			}
		);

		// Product issue cleanup: sorting (by product ID) and encode applicable countries.
		ksort( $product_issues );
		$product_issues = array_map(
			function ( $unique_key, $issue ) {
				$issue['applicable_countries'] = wp_json_encode( $this->product_issue_countries[ $unique_key ] );
				return $issue;
			},
			array_keys( $product_issues ),
			$product_issues
		);

		/** @var MerchantIssueQuery $issue_query */
		$issue_query = $this->container->get( MerchantIssueQuery::class );
		$issue_query->update_or_insert( array_values( $product_issues ) );
	}

	/**
	 * Include local presync product validation issues in the merchant issues table.
	 */
	protected function refresh_presync_product_issues(): void {
		/** @var MerchantIssueQuery $issue_query */
		$issue_query  = $this->container->get( MerchantIssueQuery::class );
		$created_at   = $this->cache_created_time->format( 'Y-m-d H:i:s' );
		$issue_action = __( 'Update this attribute in your product data', 'google-listings-and-ads' );

		/** @var ProductMetaQueryHelper $product_meta_query_helper */
		$product_meta_query_helper = $this->container->get( ProductMetaQueryHelper::class );
		// Get all MC statuses.
		$all_errors = $product_meta_query_helper->get_all_values( ProductMetaHandler::KEY_ERRORS );

		$chunk_size     = apply_filters( 'woocommerce_gla_merchant_status_presync_issues_chunk', 500 );
		$product_issues = [];
		foreach ( $all_errors as $product_id => $presync_errors ) {
			// Don't create issues with empty descriptions
			// or for variable parents (they contain issues of all children).
			$error = $presync_errors[ array_key_first( $presync_errors ) ];
			if ( empty( $error ) || ! is_string( $error ) ) {
				continue;
			}

			$product = get_post( $product_id );
			// Don't store pre-sync errors for unpublished (draft, trashed) products.
			if ( 'publish' !== get_post_status( $product ) ) {
				continue;
			}

			foreach ( $presync_errors as $text ) {
				$issue_parts      = $this->parse_presync_issue_text( $text );
				$product_issues[] = [
					'product'              => $product->post_title,
					'product_id'           => $product_id,
					'code'                 => $issue_parts['code'],
					'severity'             => self::SEVERITY_ERROR,
					'issue'                => $issue_parts['issue'],
					'action'               => $issue_action,
					'action_url'           => 'https://support.google.com/merchants/answer/10538362?hl=en&ref_topic=6098333',
					'applicable_countries' => '["all"]',
					'source'               => 'pre-sync',
					'created_at'           => $created_at,
				];
			}

			// Do update-or-insert in chunks.
			if ( count( $product_issues ) >= $chunk_size ) {
				$issue_query->update_or_insert( $product_issues );
				$product_issues = [];
			}
		}

		// Handle any leftover issues.
		$issue_query->update_or_insert( $product_issues );
	}

	/**
	 * Process product status statistics.
	 *
	 * @param array $product_view_statuses Product View statuses.
	 * @see MerchantReport::get_product_view_report
	 *
	 * @throws NotFoundExceptionInterface  If the class is not found in the container.
	 * @throws ContainerExceptionInterface If the container throws an exception.
	 */
	public function process_product_statuses( array $product_view_statuses ): void {
		$this->mc_statuses         = [];
		$product_repository        = $this->container->get( ProductRepository::class );
		$this->product_data_lookup = $product_repository->find_by_ids_as_associative_array( array_column( $product_view_statuses, 'product_id' ) );

		$this->product_statuses = [
			'products' => [],
			'parents'  => [],
		];

		foreach ( $product_view_statuses as $product_status ) {

			$wc_product_id     = $product_status['product_id'];
			$mc_product_status = $product_status['status'];
			$wc_product        = $this->product_data_lookup[ $wc_product_id ] ?? null;

			if ( ! $wc_product || ! $wc_product_id ) {
				// Skip if the product does not exist in WooCommerce.
				do_action(
					'woocommerce_gla_debug_message',
					sprintf( 'Merchant Center product %s not found in this WooCommerce store.', $wc_product_id ),
					__METHOD__,
				);
				continue;
			}

			if ( $this->product_is_expiring( $product_status['expiration_date'] ) ) {
				$mc_product_status = MCStatus::EXPIRING;
			}

			// Products is used later for global product status statistics.
			$this->product_statuses['products'][ $wc_product_id ][ $mc_product_status ] = 1 + ( $this->product_statuses['products'][ $wc_product_id ][ $mc_product_status ] ?? 0 );

			// Aggregate parent statuses for mc_status postmeta.
			$wc_parent_id = $wc_product->get_parent_id();
			if ( ! $wc_parent_id ) {
				continue;
			}
			$this->product_statuses['parents'][ $wc_parent_id ][ $mc_product_status ] = 1 + ( $this->product_statuses['parents'][ $wc_parent_id ][ $mc_product_status ] ?? 0 );

		}

		$parent_keys               = array_values( array_keys( $this->product_statuses['parents'] ) );
		$parent_products           = $product_repository->find_by_ids_as_associative_array( $parent_keys );
		$this->product_data_lookup = $this->product_data_lookup + $parent_products;

		// Update each product's mc_status and then update the global statistics.
		$this->update_products_meta_with_mc_status();
		$this->update_intermediate_product_statistics();

		$product_issues = $this->get_product_issues( $product_view_statuses );
		$this->refresh_product_issues( $product_issues );
	}

	/**
	 * Whether a product is expiring.
	 *
	 * @param DateTime $expiration_date
	 *
	 * @return bool Whether the product is expiring.
	 */
	protected function product_is_expiring( DateTime $expiration_date ): bool {
		if ( ! $expiration_date ) {
			return false;
		}

		// Products are considered expiring if they will expire within 3 days.
		return time() + 3 * DAY_IN_SECONDS > $expiration_date->getTimestamp();
	}

	/**
	 * Sum and update the intermediate product status statistics. It will group
	 * the variations for the same parent.
	 *
	 * For the case that one variation is approved and the other disapproved:
	 * 1. Give each status a priority.
	 * 2. Store the last highest priority status in `$parent_statuses`.
	 * 3. Compare if a higher priority status is found for that variable product.
	 * 4. Loop through the `$parent_statuses` array at the end to add the final status counts.
	 *
	 * @return array Product status statistics.
	 */
	protected function update_intermediate_product_statistics(): array {
		$product_statistics = self::DEFAULT_PRODUCT_STATS;

		// If the option is set, use it to sum the total quantity.
		$product_statistics_intermediate_data = $this->options->get( OptionsInterface::PRODUCT_STATUSES_COUNT_INTERMEDIATE_DATA );
		if ( $product_statistics_intermediate_data ) {
			$product_statistics              = $product_statistics_intermediate_data;
			$this->initial_intermediate_data = $product_statistics;
		}

		$product_statistics_priority = [
			MCStatus::APPROVED           => 6,
			MCStatus::PARTIALLY_APPROVED => 5,
			MCStatus::EXPIRING           => 4,
			MCStatus::PENDING            => 3,
			MCStatus::DISAPPROVED        => 2,
			MCStatus::NOT_SYNCED         => 1,
		];

		$parent_statuses = [];

		foreach ( $this->product_statuses['products'] as $product_id => $statuses ) {
			foreach ( $statuses as $status => $num_products ) {
				$product = $this->product_data_lookup[ $product_id ] ?? null;

				if ( ! $product ) {
					continue;
				}

				$parent_id = $product->get_parent_id();

				if ( ! $parent_id ) {
					$product_statistics[ $status ] += $num_products;
				} elseif ( ! isset( $parent_statuses[ $parent_id ] ) ) {
					$parent_statuses[ $parent_id ] = $status;
				} else {
					$current_parent_status = $parent_statuses[ $parent_id ];
					if ( $product_statistics_priority[ $status ] < $product_statistics_priority[ $current_parent_status ] ) {
						$parent_statuses[ $parent_id ] = $status;
					}
				}
			}
		}

		foreach ( $parent_statuses as $parent_id => $new_parent_status ) {
			$current_parent_intermediate_data_status = $product_statistics_intermediate_data['parents'][ $parent_id ] ?? null;

			if ( $current_parent_intermediate_data_status === $new_parent_status ) {
				continue;
			}

			if ( ! $current_parent_intermediate_data_status ) {
				$product_statistics[ $new_parent_status ]   += 1;
				$product_statistics['parents'][ $parent_id ] = $new_parent_status;
				continue;
			}

			// Check if the new parent status has higher priority than the previous one.
			if ( $product_statistics_priority[ $new_parent_status ] < $product_statistics_priority[ $current_parent_intermediate_data_status ] ) {
				$product_statistics[ $current_parent_intermediate_data_status ] -= 1;
				$product_statistics[ $new_parent_status ]                       += 1;
				$product_statistics['parents'][ $parent_id ]                     = $new_parent_status;
			} else {
				$product_statistics['parents'][ $parent_id ] = $current_parent_intermediate_data_status;
			}
		}

		$this->options->update( OptionsInterface::PRODUCT_STATUSES_COUNT_INTERMEDIATE_DATA, $product_statistics );

		return $product_statistics;
	}

	/**
	 * Calculate the total count of products in the MC using the statistics.
	 *
	 * @since 2.6.4
	 *
	 * @param array $statistics
	 *
	 * @return int
	 */
	protected function calculate_total_synced_product_statistics( array $statistics ): int {
		if ( ! count( $statistics ) ) {
			return 0;
		}

		$synced_status_values = array_values( array_diff( $statistics, [ $statistics[ MCStatus::NOT_SYNCED ] ] ) );
		return array_sum( $synced_status_values );
	}

	/**
	 * Handle the failure of the Merchant Center statuses fetching.
	 *
	 * @since 2.6.4
	 *
	 * @param string $error_message The error message.
	 *
	 * @throws NotFoundExceptionInterface  If the class is not found in the container.
	 * @throws ContainerExceptionInterface If the container throws an exception.
	 */
	public function handle_failed_mc_statuses_fetching( string $error_message = '' ): void {
		// Reset the intermediate data to the initial state when starting the job.
		$this->options->update( OptionsInterface::PRODUCT_STATUSES_COUNT_INTERMEDIATE_DATA, $this->initial_intermediate_data );
		// Let's remove any issue created during the failed fetch.
		$this->container->get( MerchantIssueTable::class )->delete_specific_product_issues( array_keys( $this->product_data_lookup ) );

		$mc_statuses = [
			'timestamp'  => $this->cache_created_time->getTimestamp(),
			'statistics' => null,
			'loading'    => false,
			'error'      => $error_message,
		];

		$this->container->get( TransientsInterface::class )->set(
			Transients::MC_STATUSES,
			$mc_statuses,
			$this->get_status_lifetime()
		);
	}


	/**
	 * Handle the completion of the Merchant Center statuses fetching.
	 *
	 * @since 2.6.4
	 */
	public function handle_complete_mc_statuses_fetching() {
		$intermediate_data = $this->options->get( OptionsInterface::PRODUCT_STATUSES_COUNT_INTERMEDIATE_DATA, self::DEFAULT_PRODUCT_STATS );

		unset( $intermediate_data['parents'] );

		$total_synced_products = $this->calculate_total_synced_product_statistics( $intermediate_data );

		/** @var ProductRepository $product_repository */
		$product_repository                        = $this->container->get( ProductRepository::class );
		$intermediate_data[ MCStatus::NOT_SYNCED ] = count(
			$product_repository->find_all_product_ids()
		) - $total_synced_products;

		$mc_statuses = [
			'timestamp'  => $this->cache_created_time->getTimestamp(),
			'statistics' => $intermediate_data,
			'loading'    => false,
			'error'      => null,
		];

		$this->container->get( TransientsInterface::class )->set(
			Transients::MC_STATUSES,
			$mc_statuses,
			$this->get_status_lifetime()
		);

		$this->delete_product_statuses_count_intermediate_data();
	}


	/**
	 * Update the Merchant Center status for each product.
	 */
	protected function update_products_meta_with_mc_status() {
		// Generate a product_id=>mc_status array.
		$new_product_statuses = [];
		foreach ( $this->product_statuses as $types ) {
			foreach ( $types as $product_id => $statuses ) {
				if ( isset( $statuses[ MCStatus::PENDING ] ) ) {
					$new_product_statuses[ $product_id ] = MCStatus::PENDING;
				} elseif ( isset( $statuses[ MCStatus::EXPIRING ] ) ) {
					$new_product_statuses[ $product_id ] = MCStatus::EXPIRING;
				} elseif ( isset( $statuses[ MCStatus::APPROVED ] ) ) {
					if ( count( $statuses ) > 1 ) {
						$new_product_statuses[ $product_id ] = MCStatus::PARTIALLY_APPROVED;
					} else {
						$new_product_statuses[ $product_id ] = MCStatus::APPROVED;
					}
				} else {
					$new_product_statuses[ $product_id ] = array_key_first( $statuses );
				}
			}
		}

		foreach ( $new_product_statuses as $product_id => $new_status ) {
			$product = $this->product_data_lookup[ $product_id ] ?? null;

			// At this point, the product should exist in WooCommerce but in the case that product is not found, log it.
			if ( ! $product ) {
				do_action(
					'woocommerce_gla_debug_message',
					sprintf( 'Merchant Center product with WooCommerce ID %d is not found in this store.', $product_id ),
					__METHOD__,
				);
				continue;
			}

			$product->add_meta_data( $this->prefix_meta_key( ProductMetaHandler::KEY_MC_STATUS ), $new_status, true );
			// We use save_meta_data so we don't trigger the woocommerce_update_product hook and the Syncer Hooks.
			$product->save_meta_data();
		}
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

	/**
	 * Parse the code and formatted issue text out of the presync validation error text.
	 *
	 * Converts the error strings:
	 * "[attribute] Error message." > "Error message [attribute]"
	 *
	 * Note:
	 * If attribute is an array the name can be "[attribute[0]]".
	 * So we need to match the additional set of square brackets.
	 *
	 * @param string $text
	 *
	 * @return string[] With indexes `code` and `issue`
	 */
	protected function parse_presync_issue_text( string $text ): array {
		$matches = [];
		preg_match( '/^\[([^\]]+\]?)\]\s*(.+)$/', $text, $matches );
		if ( count( $matches ) !== 3 ) {
			return [
				'code'  => 'presync_error_attrib_' . md5( $text ),
				'issue' => $text,
			];
		}

		// Convert attribute name "imageLink" to "image".
		if ( 'imageLink' === $matches[1] ) {
			$matches[1] = 'image';
		}

		// Convert attribute name "additionalImageLinks[]" to "galleryImage".
		if ( str_starts_with( $matches[1], 'additionalImageLinks' ) ) {
			$matches[1] = 'galleryImage';
		}

		$matches[2] = trim( $matches[2], ' .' );
		return [
			'code'  => 'presync_error_' . $matches[1],
			'issue' => "{$matches[2]} [{$matches[1]}]",
		];
	}

	/**
	 * Return a standardized Merchant Issue severity value.
	 *
	 * @param array $row
	 *
	 * @return string
	 */
	protected function get_issue_severity( array $row ): string {
		$is_warning = in_array(
			$row['severity'],
			[
				'warning',
				'suggestion',
				'demoted',
				'unaffected',
			],
			true
		);

		return $is_warning ? self::SEVERITY_WARNING : self::SEVERITY_ERROR;
	}

	/**
	 * In very rare instances, issue values need to be overridden manually.
	 *
	 * @param array $issue
	 *
	 * @return array The original issue with any possibly overridden values.
	 */
	private function maybe_override_issue_values( array $issue ): array {
		/**
		 * Code 'merchant_quality_low' for matching the original issue.
		 * Ref: https://developers.google.com/shopping-content/guides/account-issues#merchant_quality_low
		 *
		 * Issue string "Account isn't eligible for free listings" for matching
		 * the updated copy after Free and Enhanced Listings merge.
		 *
		 * TODO: Remove the condition of matching the $issue['issue']
		 *       if its issue code is the same as 'merchant_quality_low'
		 *       after Google replaces the issue title on their side.
		 */
		if ( 'merchant_quality_low' === $issue['code'] || "Account isn't eligible for free listings" === $issue['issue'] ) {
			$issue['issue']      = 'Show products on additional surfaces across Google through product feed';
			$issue['severity']   = self::SEVERITY_WARNING;
			$issue['action_url'] = 'https://support.google.com/merchants/answer/9199328?hl=en';
		}

		/**
		 * Reference: https://github.com/woocommerce/google-listings-and-ads/issues/1688
		 */
		if ( 'home_page_issue' === $issue['code'] ) {
			$issue['issue']      = 'Website claim is lost, need to re verify and claim your website. Please reference the support link';
			$issue['action_url'] = 'https://woocommerce.com/document/google-for-woocommerce/faq/#reverify-website';
		}

		return $issue;
	}

	/**
	 * Getter for get_cache_created_time
	 *
	 * @return DateTime The DateTime stored in cache_created_time
	 */
	public function get_cache_created_time(): DateTime {
		return $this->cache_created_time;
	}
}
