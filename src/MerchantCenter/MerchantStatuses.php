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
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductSyncer;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\ChannelVisibility;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\MCStatus;
use Google\Service\ShoppingContent\ProductStatus as GoogleProductStatus;
use DateTime;
use Exception;

/**
 * Class MerchantStatuses.
 * Note: this class uses vanilla WP methods get_post, get_post_meta, update_post_meta
 *
 * ContainerAware used to retrieve
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
class MerchantStatuses implements Service, ContainerAwareInterface {

	use ContainerAwareTrait;
	use PluginHelper;

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
	 * @var string[] Lookup of WooCommerce Product Names.
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
	 * Clears the status cache data.
	 *
	 * @since 1.1.0
	 */
	public function clear_cache(): void {
		$this->container->get( TransientsInterface::class )->delete( TransientsInterface::MC_STATUSES );
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

		// Save a request if accounts are not connected.
		$mc_service = $this->container->get( MerchantCenterService::class );
		if ( ! $mc_service->is_connected() ) {

			// Return a 401 to redirect to reconnect flow if the Google account is not connected.
			if ( ! $mc_service->is_google_connected() ) {
				throw new Exception( __( 'Google account is not connected.', 'google-listings-and-ads' ), 401 );
			}

			throw new Exception( __( 'Merchant Center account is not set up.', 'google-listings-and-ads' ) );
		}

		$this->mc_statuses = [];

		// Update account-level issues.
		 $this->refresh_account_issues();

		// Update MC product issues and tabulate statistics in batches.
		$chunk_size = apply_filters( 'woocommerce_gla_merchant_status_google_ids_chunk', 1000 );
		foreach ( array_chunk( $this->get_synced_google_ids(), $chunk_size ) as $google_ids ) {
			$mc_product_statuses = $this->filter_valid_statuses( $google_ids );
			$this->refresh_product_issues( $mc_product_statuses );
			$this->sum_status_counts( $mc_product_statuses );
		}

		// Update each product's mc_status and then update the global statistics.
		$this->update_product_mc_statuses();
		$this->update_mc_statuses();

		// Update pre-sync product validation issues.
		$this->refresh_presync_product_issues();

		// Include any custom merchant issues.
		$this->refresh_custom_merchant_issues();

		// Delete stale issues.
		$this->container->get( MerchantIssueTable::class )->delete_stale( $this->cache_created_time );
	}

	/**
	 * Delete the cached statistics and issues.
	 */
	public function delete(): void {
		$this->container->get( TransientsInterface::class )->delete( Transients::MC_STATUSES );
		$this->container->get( MerchantIssueTable::class )->truncate();
	}

	/**
	 * Get the associated Google offer IDs for all synced simple products and product variations.
	 *
	 * @since 1.1.0
	 *
	 * @return array Google offer IDs.
	 */
	protected function get_synced_google_ids(): array {
		/** @var ProductRepository $product_repository */
		$product_repository = $this->container->get( ProductRepository::class );
		/** @var ProductMetaQueryHelper $product_meta_query_helper */
		$product_meta_query_helper = $this->container->get( ProductMetaQueryHelper::class );

		// Get only synced simple and variations
		$args['type']         = array_diff( ProductSyncer::get_supported_product_types(), [ 'variable' ] );
		$filtered_product_ids = array_flip( $product_repository->find_synced_product_ids( $args ) );
		$all_google_ids       = $product_meta_query_helper->get_all_values( ProductMetaHandler::KEY_GOOGLE_IDS );
		$filtered_google_ids  = [];
		foreach ( array_intersect_key( $all_google_ids, $filtered_product_ids ) as $product_ids ) {
			if ( empty( $product_ids ) || ! is_array( $product_ids ) ) {
				// Skip if empty or not an array
				continue;
			}

			$filtered_google_ids = array_merge( $filtered_google_ids, array_values( $product_ids ) );
		}
		return $filtered_google_ids;
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
	 * Return only the valid statuses from a provided array of Google IDs. Invalid statuses:
	 * - Aren't synced by this extension (invalid ID format), or
	 * - Map to products no longer in WooCommerce (deleted or uploaded by a previous connection).
	 * Also populates the product_data_lookup used in refresh_product_issues()
	 *
	 * @param string[] $google_ids
	 *
	 * @return GoogleProductStatus[] Statuses found to be valid.
	 */
	protected function filter_valid_statuses( array $google_ids ): array {
		/** @var Merchant $merchant */
		$merchant = $this->container->get( Merchant::class );
		/** @var ProductHelper $product_helper */
		$product_helper      = $this->container->get( ProductHelper::class );
		$visibility_meta_key = $this->prefix_meta_key( ProductMetaHandler::KEY_VISIBILITY );

		$valid_statuses = [];
		foreach ( $merchant->get_productstatuses_batch( $google_ids )->getEntries() as $response_entry ) {
			$mc_product_status = $response_entry->getProductStatus();
			if ( ! $mc_product_status ) {
				do_action(
					'woocommerce_gla_debug_message',
					'A Google ID was not found in Merchant Center.',
					__METHOD__
				);
				continue;
			}
			$mc_product_id = $mc_product_status->getProductId();
			$wc_product_id = $product_helper->get_wc_product_id( $mc_product_id );
			// Skip products not synced by this extension.
			if ( ! $wc_product_id ) {
				continue;
			}

			// Product previously found/validated.
			if ( ! empty( $this->product_data_lookup[ $wc_product_id ] ) ) {
				$valid_statuses[] = $response_entry->getProductStatus();
				continue;
			}

			$wc_product = get_post( $wc_product_id );
			if ( ! $wc_product || 'product' !== substr( $wc_product->post_type, 0, 7 ) ) {
				// Should never reach here since the products IDS are retrieved from postmeta.
				do_action(
					'woocommerce_gla_debug_message',
					sprintf( 'Merchant Center product %s not found in this WooCommerce store.', $mc_product_id ),
					__METHOD__ . ' in remove_invalid_statuses()',
				);
				continue;
			}

			$this->product_data_lookup[ $wc_product_id ] = [
				'name'       => get_the_title( $wc_product ),
				'visibility' => get_post_meta( $wc_product_id, $visibility_meta_key ),
				'parent_id'  => $wc_product->post_parent,
			];

			$valid_statuses[] = $response_entry->getProductStatus();
		}

		return $valid_statuses;
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
		foreach ( $merchant->get_accountstatus()->getAccountLevelIssues() as $issue ) {
			$account_issues[] = [
				'product_id' => 0,
				'product'    => __( 'All products', 'google-listings-and-ads' ),
				'code'       => $issue->getId(),
				'issue'      => $issue->getTitle(),
				'action'     => __( 'Read more about this account issue', 'google-listings-and-ads' ),
				'action_url' => $issue->getDocumentation(),
				'created_at' => $created_at,
				'type'       => self::TYPE_ACCOUNT,
				'severity'   => $issue->getSeverity(),
				'source'     => 'mc',
			];
		}

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
	 * Retrieve all product-level issues and store them in the database.
	 *
	 * @param GoogleProductStatus[] $validated_mc_statuses Product statuses of validated products.
	 */
	protected function refresh_product_issues( array $validated_mc_statuses ): void {
		/** @var ProductHelper $product_helper */
		$product_helper = $this->container->get( ProductHelper::class );

		$product_issues = [];
		$created_at     = $this->cache_created_time->format( 'Y-m-d H:i:s' );
		foreach ( $validated_mc_statuses as $product ) {
			$wc_product_id = $product_helper->get_wc_product_id( $product->getProductId() );

			// Unsynced issues shouldn't be shown.
			if ( ChannelVisibility::DONT_SYNC_AND_SHOW === $this->product_data_lookup[ $wc_product_id ]['visibility'] ) {
				continue;
			}

			$product_issue_template = [
				'product'              => $this->product_data_lookup[ $wc_product_id ]['name'],
				'product_id'           => $wc_product_id,
				'created_at'           => $created_at,
				'applicable_countries' => [],
				'source'               => 'mc',
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
					'code'       => $item_level_issue->getCode(),
					'issue'      => $item_level_issue->getDescription(),
					'action'     => $item_level_issue->getDetail(),
					'action_url' => $item_level_issue->getDocumentation(),
					'severity'   => $item_level_issue->getServability(),
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

		// Product issue cleanup: sorting (by product ID) and encode applicable countries.
		ksort( $product_issues );
		$product_issues = array_map(
			function ( $unique_key, $issue ) {
				$issue['applicable_countries'] = json_encode( $this->product_issue_countries[ $unique_key ] );
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
			if ( empty( $presync_errors[0] ) ) {
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
	 * Add the provided status counts to the overall totals.
	 *
	 * @param GoogleProductStatus[] $validated_mc_statuses Product statuses of validated products.
	 */
	protected function sum_status_counts( array $validated_mc_statuses ): void {
		/** @var ProductHelper $product_helper */
		$product_helper = $this->container->get( ProductHelper::class );

		foreach ( $validated_mc_statuses as $product ) {
			$wc_product_id = $product_helper->get_wc_product_id( $product->getProductId() );
			$status        = $this->get_product_shopping_status( $product );
			if ( is_null( $status ) ) {
				continue;
			}
			// Products is used later for global product status statistics.
			$this->product_statuses['products'][ $wc_product_id ][ $status ] = 1 + ( $this->product_statuses['products'][ $wc_product_id ][ $status ] ?? 0 );

			// Aggregate parent statuses for mc_status postmeta.
			$wc_parent_id = intval( $this->product_data_lookup[ $wc_product_id ]['parent_id'] );
			if ( ! $wc_parent_id ) {
				continue;
			}
			$this->product_statuses['parents'][ $wc_parent_id ][ $status ] = 1 + ( $this->product_statuses['parents'][ $wc_parent_id ][ $status ] ?? 0 );
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

		foreach ( $this->product_statuses['products'] as $statuses ) {
			foreach ( $statuses as $status => $num_products ) {
				$product_statistics[ $status ] += $num_products;
			}
		}

		/** @var ProductRepository $product_repository */
		$product_repository                         = $this->container->get( ProductRepository::class );
		$product_statistics[ MCStatus::NOT_SYNCED ] = count( $product_repository->find_mc_not_synced_product_ids() );

		$this->mc_statuses = [
			'timestamp'  => $this->cache_created_time->getTimestamp(),
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
		ksort( $new_product_statuses );

		/** @var ProductRepository $product_repository */
		$product_repository = $this->container->get( ProductRepository::class );

		/** @var ProductMetaQueryHelper $product_meta_query_helper */
		$product_meta_query_helper = $this->container->get( ProductMetaQueryHelper::class );

		// Get all MC statuses.
		$current_product_statuses = $product_meta_query_helper->get_all_values( ProductMetaHandler::KEY_MC_STATUS );
		// Get all product IDs.
		$all_product_ids = array_flip( $product_repository->find_ids() );

		// Format: product_id=>status.
		$to_insert = [];
		// Format: status=>[product_ids].
		$to_update = [];

		foreach ( $new_product_statuses as $product_id => $new_status ) {
			if ( ! isset( $current_product_statuses[ $product_id ] ) ) {
				// MC status not in WC, insert.
				$to_insert[ $product_id ] = $new_status;
			} elseif ( $current_product_statuses[ $product_id ] !== $new_status ) {
				// MC status not same as WC, update.
				$to_update[ $new_status ][] = intval( $product_id );
			}
			// Unset all found statuses from WC products array.
			unset( $all_product_ids[ $product_id ] );
		}

		// Set products NOT FOUND in MC to NOT_SYNCED.
		foreach ( array_keys( $all_product_ids ) as $product_id ) {
			if ( empty( $current_product_statuses[ $product_id ] ) ) {
				$to_insert[ $product_id ] = MCStatus::NOT_SYNCED;
			} elseif ( $current_product_statuses[ $product_id ] !== MCStatus::NOT_SYNCED ) {
				$to_update[ MCStatus::NOT_SYNCED ][] = $product_id;
			}
		}

		unset( $all_product_ids, $current_product_statuses, $new_product_statuses );

		// Insert and update changed MC Status postmeta.
		$product_meta_query_helper->batch_insert_values( ProductMetaHandler::KEY_MC_STATUS, $to_insert );
		foreach ( $to_update as $status => $product_ids ) {
			$product_meta_query_helper->batch_update_values( ProductMetaHandler::KEY_MC_STATUS, $status, $product_ids );
		}
	}

	/**
	 * Return the product's shopping status in the Google Merchant Center.
	 * Active, Pending, Disapproved, Expiring.
	 *
	 * @param GoogleProductStatus $product_status
	 *
	 * @return string|null
	 */
	protected function get_product_shopping_status( GoogleProductStatus $product_status ): ?string {
		$status = null;
		foreach ( $product_status->getDestinationStatuses() as $d ) {
			if ( 'SurfacesAcrossGoogle' === $d->getDestination() ) {
				$status = $d->getStatus();
			} elseif ( 'Shopping' === $d->getDestination() ) {
				$status = $d->getStatus();
				break;
			}
		}
		return $status;
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
	 * @param string $text
	 *
	 * @return string[] With indexes `code` and `issue`
	 */
	protected function parse_presync_issue_text( string $text ): array {
		$matches = [];
		preg_match( '/^\[([^\]]+)\]\s*(.+)$/', $text, $matches );
		if ( count( $matches ) !== 3 ) {
			return [
				'code'  => 'presync_error_attrib_' . md5( $text ),
				'issue' => $text,
			];
		}

		// Convert imageLink to image
		if ( 'imageLink' === $matches[1] ) {
			$matches[1] = 'image';
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
}
