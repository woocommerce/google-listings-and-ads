<?php
// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception messages are context-neutral data; escape only at the eventual output boundary.
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services\MapiAccountIssuesService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Models\Product;
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
use Automattic\WooCommerce\GoogleListingsAndAds\Product\BatchProductHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductMetaHandler;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\ChannelVisibility;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\MCStatus;
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
	 * Stored issue severities shown as warnings. Every other severity is shown as an error.
	 *
	 * @since x.x.x
	 */
	public const WARNING_SEVERITIES = [
		'warning',
		'suggestion',
		'DEMOTED',
		'NOT_IMPACTED',
	];

	/**
	 * Merchant issue table source of the pre-sync product validation issues, which
	 * copy the products' local sync errors.
	 *
	 * @since x.x.x
	 */
	public const ISSUE_SOURCE_PRESYNC = 'pre-sync';

	/**
	 * Merchant issue table source of the issues added through the
	 * `woocommerce_gla_custom_merchant_issues` filter, by the plugin and by default.
	 *
	 * @since x.x.x
	 */
	public const ISSUE_SOURCE_CUSTOM = 'filter';

	/**
	 * Help link given with pre-sync product validation issues.
	 *
	 * @since x.x.x
	 */
	public const PRESYNC_ISSUE_HELP_URL = 'https://support.google.com/merchants/answer/10538362?hl=en&ref_topic=6098333';

	/**
	 * @var DateTime $cache_created_time For cache age operations.
	 */
	protected $cache_created_time;

	/**
	 * Start time (Unix) of the status refresh run this request is processing, or null
	 * outside a run. Set by UpdateMerchantProductStatuses through start_status_refresh()
	 * and continue_status_refresh().
	 *
	 * While it is set, the previous run's statuses and issues stay readable until this
	 * run replaces them: each page replaces the issues of its own products, and only a
	 * completed run removes what it did not refresh.
	 *
	 * @var int|null
	 */
	protected $refresh_started_at = null;

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
	protected $initial_intermediate_data = null;

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
	 * Start a status refresh run without clearing the previous results.
	 *
	 * Only the intermediate status counts restart. The previous run's statuses and
	 * issues stay readable until this run replaces them, so the Product Feed does not
	 * read as empty while a refresh is in progress.
	 *
	 * @since x.x.x
	 *
	 * @return int The run's start time, to pass to continue_status_refresh() on later pages.
	 */
	public function start_status_refresh(): int {
		$this->delete_product_statuses_count_intermediate_data();

		// Strictly increasing, and independent of when this PHP process started: every
		// row this run writes is stamped with it, and anything older is the previous
		// run's. Long-lived or overlapping queue runners would otherwise disagree.
		$previous   = (int) $this->get_option_uncached( OptionsInterface::MC_STATUSES_RUN_STARTED_AT );
		$started_at = max( time(), $previous + 1 );
		$this->options->update( OptionsInterface::MC_STATUSES_RUN_STARTED_AT, $started_at );

		$this->begin_status_refresh_page( $started_at );
		$this->cache_created_time = $this->get_refresh_started_at();

		return $started_at;
	}

	/**
	 * Continue a status refresh run started by start_status_refresh(), possibly in
	 * an earlier request.
	 *
	 * @since x.x.x
	 *
	 * @param int $started_at The value returned by start_status_refresh().
	 */
	public function continue_status_refresh( int $started_at ): void {
		$this->begin_status_refresh_page( $started_at );
	}

	/**
	 * Whether a run is the latest one started. A newer run supersedes older ones, whose
	 * remaining pages should stop instead of writing over its results.
	 *
	 * @since x.x.x
	 *
	 * @param int $started_at The value returned by start_status_refresh().
	 *
	 * @return bool
	 */
	public function is_current_status_refresh( int $started_at ): bool {
		return (int) $this->get_option_uncached( OptionsInterface::MC_STATUSES_RUN_STARTED_AT ) === $started_at;
	}

	/**
	 * Read one of the plugin's options from the database, bypassing every cache.
	 *
	 * The run state is shared between queue runners. OptionsInterface::get() keeps a
	 * copy per process and WordPress caches options too (persistently, with an object
	 * cache), so a long-lived runner would otherwise never see that a newer run started.
	 *
	 * @param string $name Unprefixed option name.
	 *
	 * @return mixed|null Null when the option does not exist.
	 */
	protected function get_option_uncached( string $name ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Bypassing the caches is the point: the value is shared between processes.
		$value = $wpdb->get_var(
			$wpdb->prepare( 'SELECT option_value FROM %i WHERE option_name = %s LIMIT 1', $wpdb->options, "{$this->get_slug()}_{$name}" )
		);

		return null === $value ? null : maybe_unserialize( $value );
	}

	/**
	 * Whether this request is processing a run that a newer one has superseded.
	 *
	 * @return bool
	 */
	protected function is_superseded_status_refresh(): bool {
		return null !== $this->refresh_started_at && ! $this->is_current_status_refresh( $this->refresh_started_at );
	}

	/**
	 * Set up this request for one page of a run. A shared instance can process several
	 * pages in one request, so nothing from an earlier page carries over.
	 *
	 * @param int $started_at
	 */
	protected function begin_status_refresh_page( int $started_at ): void {
		$this->refresh_started_at        = $started_at;
		$this->product_data_lookup       = [];
		$this->initial_intermediate_data = null;
	}

	/**
	 * The current run's start time as a UTC DateTime, or null outside a run.
	 *
	 * @return DateTime|null
	 */
	protected function get_refresh_started_at(): ?DateTime {
		return null === $this->refresh_started_at ? null : new DateTime( '@' . $this->refresh_started_at );
	}

	/**
	 * The `created_at` of the issue rows written now: the run's start within a run, so
	 * every row of a run carries the same stamp whichever request or process wrote it.
	 *
	 * @return string
	 */
	protected function get_issue_created_at(): string {
		return ( $this->get_refresh_started_at() ?? $this->cache_created_time )->format( 'Y-m-d H:i:s' );
	}

	/**
	 * Clear the product statuses cache and delete stale issues.
	 *
	 * The status refresh job no longer calls this at the start of a run (see
	 * start_status_refresh()); it keeps its behavior for any other caller.
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
		$this->options->delete( OptionsInterface::MC_STATUSES_LAST_ATTEMPT );
		$this->options->delete( OptionsInterface::MC_STATUSES_LAST_SUCCESS );
		$this->options->delete( OptionsInterface::MC_STATUSES_RUN_STARTED_AT );
		$this->options->delete( OptionsInterface::MC_STATUSES_RUN_STAMP_FAILED );
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
	 * @param array $products_by_google_id Merchant API products keyed by their Merchant API product id (the resource name's last segment, e.g. en~US~gla_123); issues are read from these, so no request is made here.
	 * @throws NotFoundExceptionInterface  If the class is not found in the container.
	 * @throws ContainerExceptionInterface If the container throws an exception.
	 *
	 * @return array The list of product issues.
	 */
	protected function get_product_issues( array $statuses, array $products_by_google_id = [] ): array {
		/** @var ProductHelper $product_helper */
		$product_helper = $this->container->get( ProductHelper::class );
		/** @var BatchProductHelper $batch_product_helper */
		$batch_product_helper = $this->container->get( BatchProductHelper::class );
		$visibility_meta_key  = $this->prefix_meta_key( ProductMetaHandler::KEY_VISIBILITY );

		// Map each synced Merchant API product ID back to its WooCommerce product.
		$google_id_to_wc_id = [];
		foreach ( $statuses as $status ) {
			$wc_product_id = $status['product_id'];
			$wc_product    = $this->product_data_lookup[ $wc_product_id ] ?? null;

			if ( ! $wc_product ) {
				do_action(
					'woocommerce_gla_debug_message',
					sprintf( 'Merchant Center product %s not found in this WooCommerce store.', $status['mc_id'] ?? $wc_product_id ),
					__METHOD__
				);
				continue;
			}
			// Unsynced issues shouldn't be shown.
			if ( ChannelVisibility::DONT_SYNC_AND_SHOW === $wc_product->get_meta( $visibility_meta_key ) ) {
				continue;
			}

			foreach ( $product_helper->get_synced_google_product_ids( $wc_product ) ?? [] as $google_id ) {
				// Skip legacy (pre-migration) colon-format ids; the Merchant API rejects them as invalid names.
				if ( null === $batch_product_helper->parse_mapi_identity( (string) $google_id ) ) {
					continue;
				}
				$google_id_to_wc_id[ $google_id ] = $wc_product_id;
			}
		}

		if ( empty( $google_id_to_wc_id ) ) {
			return [];
		}

		$created_at = $this->get_issue_created_at();
		// Issues come only from the provided page. A product synced to several feeds
		// (markets, and languages with WPML) has one Merchant Center entry per feed,
		// and those entries can land on different list pages; the countries seen by
		// the pages processed before this one are folded back in by
		// merge_issue_countries_from_earlier_pages().
		$products       = array_intersect_key( $products_by_google_id, $google_id_to_wc_id );
		$product_issues = [];

		foreach ( $products as $google_id => $product ) {
			$product_status = $product->get_product_status();

			// Confirm there are issues for this product.
			if ( ! $product_status || empty( $product_status->get_item_level_issues() ) ) {
				continue;
			}

			$wc_product_id = $google_id_to_wc_id[ $google_id ];
			$wc_product    = $this->product_data_lookup[ $wc_product_id ];

			$product_issue_template = [
				'product'              => html_entity_decode( $wc_product->get_name(), ENT_QUOTES ),
				'product_id'           => $wc_product_id,
				'created_at'           => $created_at,
				'applicable_countries' => [],
				'source'               => 'mc',
			];
			foreach ( $product_status->get_item_level_issues() as $item_level_issue ) {
				if ( 'merchant_action' !== $item_level_issue->get_resolution() ) {
					continue;
				}
				$hash_key = $wc_product_id . '__' . md5( $item_level_issue->get_description() );

				$this->product_issue_countries[ $hash_key ] = array_merge(
					$this->product_issue_countries[ $hash_key ] ?? [],
					$item_level_issue->get_applicable_countries()
				);

				$product_issues[ $hash_key ] = $product_issue_template + [
					'code'       => $item_level_issue->get_code(),
					'issue'      => $item_level_issue->get_description(),
					'action'     => $item_level_issue->get_detail(),
					'action_url' => $item_level_issue->get_documentation(),
					'severity'   => $item_level_issue->get_severity(),
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

		// Within a run, the rows above replace the previous run's account, pre-sync and
		// custom issues now that they are written. Merchant Center product issues are
		// replaced page by page instead (process_product_statuses()).
		$started_at = $this->get_refresh_started_at();
		if ( $started_at ) {
			$this->container->get( MerchantIssueTable::class )->delete_stale_except_product_issues( $started_at );
		}
	}

	/**
	 * Retrieve all account-level issues and store them in the database.
	 *
	 * @throws Exception If the account state can't be retrieved from Google.
	 */
	protected function refresh_account_issues(): void {
		$account_issues = [];
		$created_at     = $this->get_issue_created_at();

		/** @var MapiAccountIssuesService $account_issues_service */
		$account_issues_service = $this->container->get( MapiAccountIssuesService::class );
		foreach ( $account_issues_service->get_account_issues() as $issue ) {
			$title        = $issue['title'] ?? '';
			$key          = md5( $title );
			$region_codes = $this->account_issue_region_codes( $issue );

			if ( isset( $account_issues[ $key ] ) ) {
				$account_issues[ $key ]['applicable_countries'] = array_merge(
					$account_issues[ $key ]['applicable_countries'],
					$region_codes
				);
			} else {
				$account_issues[ $key ] = [
					'product_id'           => 0,
					'product'              => __( 'All products', 'google-listings-and-ads' ),
					'code'                 => $this->account_issue_code( $issue['name'] ?? '' ),
					'issue'                => $title,
					'action'               => $issue['detail'] ?? '',
					'action_url'           => $issue['documentationUri'] ?? '',
					'created_at'           => $created_at,
					'type'                 => self::TYPE_ACCOUNT,
					'severity'             => $this->account_issue_severity( $issue['severity'] ?? '' ),
					'source'               => 'mc',
					'applicable_countries' => $region_codes,
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
	 * Extract the region codes a Merchant API account issue impacts.
	 *
	 * @param array $issue AccountIssue resource, decoded as an array.
	 *
	 * @return string[]
	 */
	protected function account_issue_region_codes( array $issue ): array {
		$codes = [];
		foreach ( $issue['impactedDestinations'] ?? [] as $destination ) {
			foreach ( $destination['impacts'] ?? [] as $impact ) {
				if ( ! empty( $impact['regionCode'] ) ) {
					$codes[] = $impact['regionCode'];
				}
			}
		}

		return $codes;
	}

	/**
	 * Derive the issue code from a Merchant API account issue resource name
	 * (accounts/{account}/issues/{issue}).
	 *
	 * @param string $name
	 *
	 * @return string
	 */
	protected function account_issue_code( string $name ): string {
		if ( '' === $name ) {
			return '';
		}

		$parts = explode( '/', $name );

		// The Merchant API uses kebab-case issue ids, while the Content API codes
		// (and the maybe_override_issue_values matches) are snake_case. Normalize so
		// the override matching and the stored code keep the pre-migration convention.
		return str_replace( '-', '_', (string) end( $parts ) );
	}

	/**
	 * Map a Merchant API account issue severity.
	 *
	 * CRITICAL, ERROR and SUGGESTION keep the pre-migration lower-case values.
	 * SEVERITY_UNSPECIFIED (and any unrecognized value) folds into 'error' so the
	 * issue stays in the error bucket for both get_issue_severity() and the
	 * only-errors filter.
	 *
	 * @param string $severity
	 *
	 * @return string
	 */
	protected function account_issue_severity( string $severity ): string {
		$severity = strtolower( $severity );

		return in_array( $severity, [ 'critical', self::SEVERITY_ERROR, 'suggestion' ], true ) ? $severity : self::SEVERITY_ERROR;
	}

	/**
	 * Custom issues can be added to the merchant issues table.
	 *
	 * @since 1.2.0
	 */
	protected function refresh_custom_merchant_issues() {
		$custom_issues = apply_filters(
			'woocommerce_gla_custom_merchant_issues',
			[],
			$this->get_refresh_started_at() ?? $this->cache_created_time
		);

		$rows    = [];
		$dropped = 0;
		foreach ( is_array( $custom_issues ) ? $custom_issues : [] as $issue ) {
			$row = $this->sanitize_custom_merchant_issue( $issue );
			if ( null === $row ) {
				++$dropped;
			} else {
				$rows[] = $row;
			}
		}

		if ( $dropped > 0 ) {
			do_action(
				'woocommerce_gla_debug_message',
				sprintf( '%d custom merchant issue(s) were ignored because they have no issue text.', $dropped ),
				__METHOD__
			);
		}

		if ( empty( $rows ) ) {
			return;
		}

		/** @var MerchantIssueQuery $issue_query */
		$issue_query = $this->container->get( MerchantIssueQuery::class );
		$issue_query->update_or_insert( $rows );
	}

	/**
	 * Validate one row returned by the `woocommerce_gla_custom_merchant_issues` filter
	 * and give it every column, so rows from different callbacks can be written together.
	 *
	 * The row is stamped with this run's `created_at`, whatever date the callback set,
	 * so the run does not treat it as a previous run's row and delete it.
	 *
	 * @param mixed $issue A row returned by the filter.
	 *
	 * @return array|null The row to write, or null when it lacks an issue text.
	 */
	protected function sanitize_custom_merchant_issue( $issue ): ?array {
		if ( ! is_array( $issue ) || ! isset( $issue['issue'] ) || ! is_scalar( $issue['issue'] ) || '' === (string) $issue['issue'] ) {
			return null;
		}

		$string = function ( string $key, string $fallback ) use ( $issue ): string {
			return isset( $issue[ $key ] ) && is_scalar( $issue[ $key ] ) ? (string) $issue[ $key ] : $fallback;
		};

		$countries = $issue['applicable_countries'] ?? [];
		if ( is_array( $countries ) ) {
			$countries = wp_json_encode( array_values( $countries ) );
		}

		// Defaults match the table's column defaults.
		return [
			'product_id'           => isset( $issue['product_id'] ) && is_numeric( $issue['product_id'] ) ? (int) $issue['product_id'] : 0,
			'product'              => $string( 'product', '' ),
			'code'                 => $string( 'code', '' ),
			'issue'                => (string) $issue['issue'],
			'action'               => $string( 'action', '' ),
			'action_url'           => $string( 'action_url', '' ),
			'applicable_countries' => is_string( $countries ) ? $countries : '[]',
			'created_at'           => $this->get_issue_created_at(),
			'type'                 => in_array( $issue['type'] ?? null, $this->get_valid_issue_types(), true ) ? $issue['type'] : self::TYPE_PRODUCT,
			'severity'             => $string( 'severity', self::SEVERITY_WARNING ),
			// Custom rows are refreshed whole on the first page, like the plugin's own
			// custom issues (source `filter`), so a missing source must not make them
			// look like Merchant Center product issues, which are replaced page by page.
			'source'               => $string( 'source', self::ISSUE_SOURCE_CUSTOM ),
		];
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
		$stale_row_ids = $this->merge_issue_countries_from_earlier_pages( $product_issues );

		// Alphabetize all product/issue country lists.
		array_walk(
			$this->product_issue_countries,
			function ( &$countries ) {
				sort( $countries );
			}
		);

		// Product issue cleanup: sort by unique key for a deterministic write order
		// and encode applicable countries. The countries are de-duplicated: the source
		// repeats them once per reporting context, and merging earlier pages in would
		// compound that repetition.
		ksort( $product_issues );
		$product_issues = array_map(
			function ( $unique_key, $issue ) {
				$issue['applicable_countries'] = wp_json_encode(
					array_values( array_unique( $this->product_issue_countries[ $unique_key ] ) )
				);
				return $issue;
			},
			array_keys( $product_issues ),
			$product_issues
		);

		/** @var MerchantIssueQuery $issue_query */
		$issue_query = $this->container->get( MerchantIssueQuery::class );
		$issue_query->update_or_insert( array_values( $product_issues ) );

		// Deleted only after the merged rows are written: a page interrupted in
		// between leaves duplicate rows, which the next page or cycle absorbs,
		// instead of losing the countries collected by earlier pages.
		if ( ! empty( $stale_row_ids ) ) {
			$this->container->get( MerchantIssueTable::class )->delete_by_ids( $stale_row_ids );
		}
	}

	/**
	 * Fold in the product issue rows written by the pages processed before this one.
	 *
	 * One status refresh spans several list pages, each processed in its own
	 * scheduled action, and a product synced to several feeds (markets, and
	 * languages with WPML) can have its Merchant Center entries spread over
	 * several of those pages. Each page only sees the item-level issues of its
	 * own entries, so the rows written so far carry the applicable countries of
	 * earlier pages. Merging them into this page's countries, and replacing them
	 * because the table has no unique key an upsert could update through, leaves
	 * one row per product and issue whose country list is the union across all
	 * of the product's entries, the same result a single-page refresh produces.
	 *
	 * @since 3.9.3
	 *
	 * @param array $product_issues Product issue rows about to be written, keyed by product ID and hashed issue text.
	 *
	 * @return int[] IDs of the folded-in rows, for deletion once the merged rows are written.
	 * @throws NotFoundExceptionInterface  If the class is not found in the container.
	 * @throws ContainerExceptionInterface If the container throws an exception.
	 */
	protected function merge_issue_countries_from_earlier_pages( array $product_issues ): array {
		if ( empty( $product_issues ) ) {
			return [];
		}

		/** @var MerchantIssueQuery $issue_query */
		$issue_query = $this->container->get( MerchantIssueQuery::class );
		$issue_query->where( 'product_id', array_unique( array_column( $product_issues, 'product_id' ) ), 'IN' );
		$issue_query->where( 'source', 'mc' );
		$issue_query->where( 'type', self::TYPE_PRODUCT );

		// Only the pages of the current run: the previous run's rows are replaced, not merged.
		if ( null !== $this->refresh_started_at ) {
			$issue_query->where( 'created_at', $this->get_issue_created_at() );
		}

		$stale_row_ids = [];
		foreach ( $issue_query->get_results() as $row ) {
			$unique_key = $row['product_id'] . '__' . md5( $row['issue'] );
			if ( ! isset( $product_issues[ $unique_key ] ) ) {
				continue;
			}

			$stale_row_ids[] = (int) $row['id'];

			$stored_countries = json_decode( $row['applicable_countries'], true );

			$this->product_issue_countries[ $unique_key ] = array_merge(
				$this->product_issue_countries[ $unique_key ] ?? [],
				is_array( $stored_countries ) ? $stored_countries : []
			);
		}

		return $stale_row_ids;
	}

	/**
	 * Include local presync product validation issues in the merchant issues table.
	 */
	protected function refresh_presync_product_issues(): void {
		/** @var MerchantIssueQuery $issue_query */
		$issue_query = $this->container->get( MerchantIssueQuery::class );
		$created_at  = $this->get_issue_created_at();

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
				$issue            = $this->get_presync_issue( $text );
				$product_issues[] = [
					'product'              => $product->post_title,
					'product_id'           => $product_id,
					'code'                 => $issue['code'],
					'severity'             => self::SEVERITY_ERROR,
					'issue'                => $issue['issue'],
					'action'               => $issue['action'],
					'action_url'           => $issue['action_url'],
					'applicable_countries' => '["all"]',
					'source'               => self::ISSUE_SOURCE_PRESYNC,
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
	 * Convert one page of Merchant API products into product view statuses and process them.
	 *
	 * Replaces the product_view report as the driver of the status refresh: the aggregated
	 * status is derived from each product's destination statuses, and the same page of
	 * products doubles as the item-level issue source, so no further request is needed.
	 *
	 * @since 3.9.3
	 *
	 * @param Product[] $products One page of Merchant API products.
	 */
	public function process_mapi_products( array $products ): void {
		$product_helper        = $this->container->get( ProductHelper::class );
		$statuses              = [];
		$products_by_google_id = [];

		foreach ( $products as $product ) {
			$product_status = $product->get_product_status();
			$aggregated     = $product_status ? $product_status->get_aggregated_reporting_context_status() : '';

			// No destination statuses yet: the product is still processing, and the
			// product_view report would not return it either. Skip it for parity.
			if ( '' === $aggregated ) {
				continue;
			}

			$wc_product_id = $product_helper->get_wc_product_id( $product->get_id() );
			if ( ! $wc_product_id ) {
				continue;
			}

			$expiration_date = null;
			if ( $product_status->get_google_expiration_date() ) {
				try {
					$expiration_date = new DateTime( $product_status->get_google_expiration_date() );
				} catch ( Exception $e ) {
					// An unparsable timestamp only loses the expiring hint; it must not
					// fail the page and, through the retry chain, kill the refresh.
					$expiration_date = null;
				}
			}

			$statuses[ $wc_product_id ] = [
				'mc_id'           => $product->get_id(),
				'product_id'      => $wc_product_id,
				'status'          => MCStatus::from_aggregated_reporting_context_status( $aggregated ),
				'expiration_date' => $expiration_date,
			];

			$products_by_google_id[ $product->get_id() ] = $product;
		}

		$this->process_product_statuses( $statuses, $products_by_google_id );
	}

	/**
	 * Process product status statistics.
	 *
	 * @param array $product_view_statuses Product View statuses.
	 * @param array $products_by_google_id Merchant API products keyed by their Merchant API product id (the resource name's last segment, e.g. en~US~gla_123), the source of item-level issues. When omitted, no product issues are written.
	 * @see UpdateMerchantProductStatuses::process_items
	 *
	 * @throws NotFoundExceptionInterface  If the class is not found in the container.
	 * @throws ContainerExceptionInterface If the container throws an exception.
	 */
	public function process_product_statuses( array $product_view_statuses, array $products_by_google_id = [] ): void {
		// A newer run may have started while this page was being fetched: write nothing.
		if ( $this->is_superseded_status_refresh() ) {
			return;
		}

		$this->mc_statuses = [];
		// This service is a shared singleton, so in a long-lived process the map
		// still holds the countries of previously processed pages, or of an earlier
		// refresh cycle entirely. Earlier pages of the current cycle are re-supplied
		// from their rows by merge_issue_countries_from_earlier_pages(); anything
		// older would pollute the new rows, so each page starts from an empty map.
		$this->product_issue_countries = [];

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

		$product_issues = $this->get_product_issues( $product_view_statuses, $products_by_google_id );
		$this->refresh_product_issues( $product_issues );

		$this->replace_previous_run_data( $product_view_statuses );
	}

	/**
	 * Within a run, finish replacing the previous run's data for the products on this
	 * page: drop their older Merchant Center issues (now that the new ones are written,
	 * including for products whose issues were resolved), and stamp the run on the
	 * products whose status this page wrote, so the completed run can remove the
	 * statuses it did not refresh.
	 *
	 * @param array $product_view_statuses The page's product statuses.
	 */
	protected function replace_previous_run_data( array $product_view_statuses ): void {
		$started_at = $this->get_refresh_started_at();
		if ( ! $started_at ) {
			return;
		}

		$page_product_ids = array_map( 'intval', array_column( $product_view_statuses, 'product_id' ) );
		$this->container->get( MerchantIssueTable::class )->delete_stale_product_issues( $page_product_ids, $started_at );

		// A newer run's stamps on these products must not be replaced by older ones.
		if ( $this->is_superseded_status_refresh() ) {
			return;
		}

		$written_ids = array_merge( array_keys( $this->product_statuses['products'] ), array_keys( $this->product_statuses['parents'] ) );
		$stamped     = $this->container->get( ProductMetaQueryHelper::class )->set_value_for_posts(
			ProductMetaHandler::KEY_MC_STATUS_RUN,
			$written_ids,
			(string) $this->refresh_started_at
		);

		// Without the stamps, the run cannot tell which statuses it refreshed, so it must
		// not remove any when it completes.
		if ( ! $stamped && ! $this->is_superseded_status_refresh() ) {
			$this->options->update( OptionsInterface::MC_STATUSES_RUN_STAMP_FAILED, $this->refresh_started_at );
		}
	}

	/**
	 * When a run completes, remove what it did not refresh: issues written before the
	 * run started (products no longer in Merchant Center) and the statuses of products
	 * no page wrote.
	 */
	protected function remove_data_not_refreshed_by_run(): void {
		$started_at = $this->get_refresh_started_at();
		if ( ! $started_at ) {
			return;
		}

		if ( (int) $this->get_option_uncached( OptionsInterface::MC_STATUSES_RUN_STAMP_FAILED ) === $this->refresh_started_at ) {
			// Without the stamps the run cannot tell which products it refreshed, so it
			// removes nothing: neither statuses nor the issues that explain them.
			do_action(
				'woocommerce_gla_debug_message',
				'Some product statuses could not be stamped during the status refresh, so the statuses and issues it did not refresh are kept.',
				__METHOD__
			);
		} else {
			// Only rows older than this run: a newer run's rows are never touched.
			$this->container->get( MerchantIssueTable::class )->delete_stale( $started_at );

			/** @var ProductMetaQueryHelper $meta_query_helper */
			$meta_query_helper = $this->container->get( ProductMetaQueryHelper::class );
			$meta_query_helper->delete_values_marked_before( ProductMetaHandler::KEY_MC_STATUS, ProductMetaHandler::KEY_MC_STATUS_RUN, $this->refresh_started_at );
			// Stamps of earlier runs mark nothing any more.
			$meta_query_helper->delete_values_marked_before( ProductMetaHandler::KEY_MC_STATUS_RUN, ProductMetaHandler::KEY_MC_STATUS_RUN, $this->refresh_started_at );
		}
		// Clear this run's flag, or one left by an older run that never completed, but
		// never a newer run's.
		$stamp_failed = (int) $this->get_option_uncached( OptionsInterface::MC_STATUSES_RUN_STAMP_FAILED );
		if ( $stamp_failed > 0 && $stamp_failed <= $this->refresh_started_at ) {
			$this->options->delete( OptionsInterface::MC_STATUSES_RUN_STAMP_FAILED );
		}

		$this->refresh_started_at = null;
	}

	/**
	 * Whether a product is expiring.
	 *
	 * @param DateTime|null $expiration_date
	 *
	 * @return bool Whether the product is expiring.
	 */
	protected function product_is_expiring( ?DateTime $expiration_date ): bool {
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
		$product_statistics_intermediate_data = $this->get_intermediate_data();
		if ( $product_statistics_intermediate_data ) {
			$product_statistics = $product_statistics_intermediate_data;
		}
		// What a failure of this page restores: the counts from before it.
		$this->initial_intermediate_data = $product_statistics;

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
		// Undo the counts of the page that failed, if it got as far as counting. A page
		// that failed earlier (e.g. while fetching) leaves the earlier pages' counts alone.
		if ( null !== $this->initial_intermediate_data ) {
			$this->options->update( OptionsInterface::PRODUCT_STATUSES_COUNT_INTERMEDIATE_DATA, $this->initial_intermediate_data );
		}
		// Outside a run, remove any issue created during the failed fetch. Within a run the
		// rows written so far replace the previous run's, and the next completed run
		// cleans up, so nothing is deleted on failure.
		if ( null === $this->refresh_started_at ) {
			$this->container->get( MerchantIssueTable::class )->delete_specific_product_issues( array_keys( $this->product_data_lookup ) );
		}

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

		// Kept outside the transient, which every new fetch clears: a failed fetch
		// still counts toward the hand-started refresh cooldown.
		$this->options->update( OptionsInterface::MC_STATUSES_LAST_ATTEMPT, time() );
	}


	/**
	 * Handle the completion of the Merchant Center statuses fetching.
	 *
	 * @since 2.6.4
	 */
	public function handle_complete_mc_statuses_fetching() {
		$started_at        = $this->refresh_started_at;
		$intermediate_data = $this->get_intermediate_data() ?: self::DEFAULT_PRODUCT_STATS;

		$this->remove_data_not_refreshed_by_run();

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

		// Kept outside the transient, which every new fetch clears, so the age of the
		// review data stays known while the next fetch runs.
		$now = time();
		$this->options->update( OptionsInterface::MC_STATUSES_LAST_SUCCESS, $now );
		$this->options->update( OptionsInterface::MC_STATUSES_LAST_ATTEMPT, $now );

		// Checked last, right before deleting: a newer run started meanwhile already
		// stores its own counts in the same option.
		if ( null === $started_at || $this->is_current_status_refresh( $started_at ) ) {
			$this->delete_product_statuses_count_intermediate_data();
		}
	}

	/**
	 * The intermediate status counts. Within a run they are read past every cache,
	 * because the run's pages may each be processed by a different process.
	 *
	 * @return array|null
	 */
	protected function get_intermediate_data(): ?array {
		$data = null === $this->refresh_started_at
			? $this->options->get( OptionsInterface::PRODUCT_STATUSES_COUNT_INTERMEDIATE_DATA )
			: $this->get_option_uncached( OptionsInterface::PRODUCT_STATUSES_COUNT_INTERMEDIATE_DATA );

		return is_array( $data ) ? $data : null;
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
	 * Describe a product's local (pre-sync) validation error the way the issues table
	 * stores it: code, formatted issue text, suggested action and help link.
	 *
	 * @since x.x.x
	 *
	 * @param string $text Error text as stored in the product's errors meta.
	 *
	 * @return string[] With indexes `code`, `issue`, `action` and `action_url`.
	 */
	public function get_presync_issue( string $text ): array {
		return $this->parse_presync_issue_text( $text ) + [
			'action'     => __( 'Update this attribute in your product data', 'google-listings-and-ads' ),
			'action_url' => self::PRESYNC_ISSUE_HELP_URL,
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
		return self::standardize_issue_severity( (string) $row['severity'] );
	}

	/**
	 * Map a stored issue severity to `warning` or `error`, the two severities the
	 * issues lists show.
	 *
	 * @since x.x.x
	 *
	 * @param string $severity Severity as stored in the merchant issue table.
	 *
	 * @return string self::SEVERITY_WARNING or self::SEVERITY_ERROR.
	 */
	public static function standardize_issue_severity( string $severity ): string {
		return in_array( $severity, self::WARNING_SEVERITIES, true ) ? self::SEVERITY_WARNING : self::SEVERITY_ERROR;
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
