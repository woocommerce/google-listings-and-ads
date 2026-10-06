<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\SyncHealth;

use ActionScheduler_DBStore;
use ActionScheduler_Store;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\JetpackAuthCircuitBreaker;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\GoogleProductService;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\JobRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\UpdateMerchantProductStatuses;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MarketService;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantStatuses;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\TransientsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductMetaHandler;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductSyncer;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\MCStatus;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\SyncStatus;
use Exception;
use Throwable;
use wpdb;

defined( 'ABSPATH' ) || exit;

/**
 * Computes read-only health signals for product and shipping sync.
 *
 * Every signal is derived from data the plugin already stores (options,
 * transients, product meta and the Action Scheduler store). Nothing here
 * schedules a job or calls Google, so the summary is safe to poll.
 *
 * @since x.x.x
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\SyncHealth
 */
class SyncHealthService implements OptionsAwareInterface {

	use OptionsAwareTrait;
	use PluginHelper;

	/** Overall states, worst last. */
	public const STATE_HEALTHY      = 'healthy';
	public const STATE_SYNCING      = 'syncing';
	public const STATE_ATTENTION    = 'attention';
	public const STATE_PAUSED       = 'paused';
	public const STATE_DISCONNECTED = 'disconnected';

	/** Pillar statuses. */
	public const STATUS_OK           = 'ok';
	public const STATUS_UNKNOWN      = 'unknown';
	public const STATUS_SYNCING      = 'syncing';
	public const STATUS_WARNING      = 'warning';
	public const STATUS_ERROR        = 'error';
	public const STATUS_PAUSED       = 'paused';
	public const STATUS_DISCONNECTED = 'disconnected';

	/** Job states, as shown in the background jobs table. */
	public const JOB_OK          = 'ok';
	public const JOB_SCHEDULED   = 'scheduled';
	public const JOB_QUEUED      = 'queued';
	public const JOB_RUNNING     = 'running';
	public const JOB_DELAYED     = 'delayed';
	public const JOB_ERRORS      = 'errors';
	public const JOB_STUCK       = 'stuck';
	public const JOB_STALLED     = 'stalled';
	public const JOB_PAUSED      = 'paused';
	public const JOB_INTERRUPTED = 'interrupted';

	/** An in-progress action older than this is considered stuck. */
	protected const STUCK_RUNNING_AFTER = 5 * MINUTE_IN_SECONDS;

	/** A past-due pending action older than this means the queue is slow. */
	protected const DELAYED_PENDING_AFTER = 15 * MINUTE_IN_SECONDS;

	/** A past-due pending action older than this means the runner has stalled. */
	protected const STALLED_PENDING_AFTER = HOUR_IN_SECONDS;

	/** Review data older than this is flagged as stale. */
	protected const STALE_REVIEW_AFTER = DAY_IN_SECONDS;

	/** How long a computed summary is reused. */
	protected const CACHE_LIFETIME = 30;

	/**
	 * Least time between review fetches a merchant can start by hand. Each fetch
	 * pages through the whole catalog on the Merchant API.
	 */
	protected const REVIEW_REFRESH_COOLDOWN = 5 * MINUTE_IN_SECONDS;

	/** Jobs that push or remove product data. */
	protected const PRODUCT_JOBS = [
		'update_products',
		'delete_products',
		'update_all_products',
		'resubmit_expiring_products',
		'cleanup_products_job',
		'cleanup_synced_products',
		'cleanup_orphaned_market_products_job',
		'cleanup_orphaned_language_products_job',
		'cleanup_product_target_countries',
		'delete_all_products',
	];

	/** Jobs whose pending actions can clear a product's `pending` sync status. */
	protected const PRODUCT_PUSH_JOBS = [
		'update_products',
		'update_all_products',
		'resubmit_expiring_products',
	];

	protected const SHIPPING_JOB       = 'update_shipping_settings';
	protected const STATUS_REFRESH_JOB = 'update_merchant_product_statuses';
	protected const FULL_SYNC_JOB      = 'update_all_products';

	/**
	 * @var MerchantCenterService
	 */
	protected $merchant_center;

	/**
	 * @var JetpackAuthCircuitBreaker
	 */
	protected $circuit_breaker;

	/**
	 * @var MarketService
	 */
	protected $market_service;

	/**
	 * @var TransientsInterface
	 */
	protected $transients;

	/**
	 * @var wpdb
	 */
	protected $wpdb;

	/**
	 * @var MerchantStatuses
	 */
	protected $merchant_statuses;

	/**
	 * @var JobRepository
	 */
	protected $job_repository;

	/**
	 * Per-request memo of the job rows, so the summary and the jobs list share one query.
	 *
	 * @var array|null|false False when not computed yet.
	 */
	protected $jobs_memo = false;

	/**
	 * SyncHealthService constructor.
	 *
	 * @param MerchantCenterService     $merchant_center
	 * @param JetpackAuthCircuitBreaker $circuit_breaker
	 * @param MarketService             $market_service
	 * @param TransientsInterface       $transients
	 * @param wpdb                      $wpdb
	 * @param MerchantStatuses          $merchant_statuses
	 * @param JobRepository             $job_repository
	 */
	public function __construct(
		MerchantCenterService $merchant_center,
		JetpackAuthCircuitBreaker $circuit_breaker,
		MarketService $market_service,
		TransientsInterface $transients,
		wpdb $wpdb,
		MerchantStatuses $merchant_statuses,
		JobRepository $job_repository
	) {
		$this->merchant_center   = $merchant_center;
		$this->circuit_breaker   = $circuit_breaker;
		$this->market_service    = $market_service;
		$this->transients        = $transients;
		$this->wpdb              = $wpdb;
		$this->merchant_statuses = $merchant_statuses;
		$this->job_repository    = $job_repository;
	}

	/**
	 * Start fetching review results from Google, if allowed.
	 *
	 * Skipped while a fetch is already queued or running, and within
	 * REVIEW_REFRESH_COOLDOWN of the last completed fetch. Only ever called from an
	 * explicit merchant action, never from polling.
	 *
	 * @return array `started` (bool), `reason` (`started`, `running` or `recent`),
	 *               and `available_at` (Unix time a fetch can next start, or null).
	 *
	 * @throws Exception If Merchant Center is not connected.
	 */
	public function request_review_refresh(): array {
		if ( $this->job_repository->get( UpdateMerchantProductStatuses::class )->is_scheduled() ) {
			return [
				'started'      => false,
				'reason'       => 'running',
				'available_at' => null,
			];
		}

		$available_at = $this->get_review_refresh_available_at();
		if ( $available_at && $available_at > time() ) {
			return [
				'started'      => false,
				'reason'       => 'recent',
				'available_at' => $available_at,
			];
		}

		$this->merchant_statuses->maybe_refresh_status_data( true );

		// The cached summary still says "not loading"; drop it so the next read shows the fetch.
		$this->transients->delete( TransientsInterface::SYNC_HEALTH );

		return [
			'started'      => true,
			'reason'       => 'started',
			'available_at' => null,
		];
	}

	/**
	 * When the next hand-started review fetch is allowed, based on the last completed one.
	 *
	 * @return int|null Null when there is no cached result to measure from.
	 */
	protected function get_review_refresh_available_at(): ?int {
		$cached  = $this->transients->get( TransientsInterface::MC_STATUSES );
		$data_at = is_array( $cached ) && ! empty( $cached['timestamp'] ) && empty( $cached['error'] ) ? (int) $cached['timestamp'] : null;

		return $data_at ? $data_at + self::REVIEW_REFRESH_COOLDOWN : null;
	}

	/**
	 * Get the overall state, the four pillars, and the queue summary.
	 *
	 * @param bool $force_refresh Skip the short-lived cache.
	 *
	 * @return array
	 */
	public function get_summary( bool $force_refresh = false ): array {
		if ( ! $force_refresh ) {
			$cached = $this->transients->get( TransientsInterface::SYNC_HEALTH );
			if ( is_array( $cached ) && isset( $cached['state'] ) ) {
				return $cached;
			}
		}

		$jobs = $this->get_jobs_by_name();

		$pillars = [
			'connection' => $this->get_connection_pillar(),
			'products'   => $this->get_products_pillar( $jobs ),
			'shipping'   => $this->get_shipping_pillar( $jobs ),
			'review'     => $this->get_review_pillar( $jobs ),
		];

		$queue   = $this->get_queue_summary( $jobs );
		$reasons = $this->collect_reasons( $pillars, $queue );

		$summary = [
			'generated_at' => time(),
			'state'        => $this->get_overall_state( $pillars, $queue ),
			'reasons'      => $reasons,
			'pillars'      => $pillars,
			'queue'        => $queue,
		];

		$this->transients->set( TransientsInterface::SYNC_HEALTH, $summary, self::CACHE_LIFETIME );

		return $summary;
	}

	/**
	 * Get per-job counts and states for the technical view.
	 *
	 * @return array
	 */
	public function get_jobs(): array {
		$jobs = $this->get_jobs_by_name();

		return [
			'available'         => null !== $jobs,
			'failure_threshold' => $this->get_failure_rate_threshold(),
			'jobs'              => null === $jobs ? [] : array_values( $jobs ),
		];
	}

	/**
	 * Connection to Google, WordPress.com, and the claimed URL.
	 *
	 * @return array
	 */
	protected function get_connection_pillar(): array {
		$google_connected  = $this->merchant_center->is_google_connected();
		$setup_complete    = $this->merchant_center->is_setup_complete();
		$jetpack_connected = $this->is_jetpack_connected();
		$breaker_open      = $this->circuit_breaker->is_open();

		// Read only; the transient is recomputed with a remote call elsewhere.
		$url_matches = $this->transients->get( TransientsInterface::URL_MATCHES );
		$url_matches = in_array( $url_matches, [ 'yes', 'no' ], true ) ? $url_matches : null;

		if ( ! $google_connected || ! $setup_complete || ! $jetpack_connected ) {
			$status = self::STATUS_DISCONNECTED;
		} elseif ( $breaker_open || 'no' === $url_matches ) {
			$status = self::STATUS_PAUSED;
		} else {
			$status = self::STATUS_OK;
		}

		$merchant_id = $this->options->get( OptionsInterface::MERCHANT_ID );

		return [
			'status'                   => $status,
			'google_connected'         => $google_connected,
			'mc_setup_complete'        => $setup_complete,
			'jetpack_connected'        => $jetpack_connected,
			'merchant_id'              => $merchant_id ? (int) $merchant_id : null,
			'circuit_breaker_open'     => $breaker_open,
			'circuit_breaker_retry_at' => $breaker_open ? $this->circuit_breaker->get_retry_time() : null,
			'url_matches'              => $url_matches,
		];
	}

	/**
	 * Local product sync state plus the product job queue.
	 *
	 * @param array|null $jobs Jobs keyed by name, or null when the queue can't be read.
	 *
	 * @return array
	 */
	protected function get_products_pillar( ?array $jobs ): array {
		$counts = $this->get_sync_status_counts();
		$errors = $this->get_error_split();

		$queued = 0;
		$worst  = self::JOB_OK;
		foreach ( self::PRODUCT_JOBS as $name ) {
			if ( isset( $jobs[ $name ] ) ) {
				$queued += $this->active_count( $jobs[ $name ] );
				$worst   = $this->worse_job_state( $worst, $jobs[ $name ]['state'] );
			}
		}

		$push_queued = 0;
		foreach ( self::PRODUCT_PUSH_JOBS as $name ) {
			if ( isset( $jobs[ $name ] ) ) {
				$push_queued += $jobs[ $name ]['pending'] + $jobs[ $name ]['running'];
			}
		}

		// Pending products with nothing queued that could send them are stuck.
		$stuck_pending = ( null !== $jobs && 0 === $push_queued ) ? $counts[ SyncStatus::PENDING ] : 0;

		$full_sync      = $jobs[ self::FULL_SYNC_JOB ] ?? null;
		$last_full_sync = $this->options->get( OptionsInterface::UPDATE_ALL_PRODUCTS_LAST_SYNC );
		$last_push      = $jobs['update_products']['last_completed_at'] ?? null;

		if ( in_array( $worst, [ self::JOB_PAUSED, self::JOB_STUCK, self::JOB_STALLED, self::JOB_INTERRUPTED ], true ) ) {
			$status = self::STATUS_ERROR;
		} elseif ( $stuck_pending > 0 || in_array( $worst, [ self::JOB_ERRORS, self::JOB_DELAYED ], true ) ) {
			$status = self::STATUS_WARNING;
		} elseif ( $queued > 0 ) {
			$status = self::STATUS_SYNCING;
		} elseif ( null === $jobs ) {
			$status = self::STATUS_UNKNOWN;
		} else {
			$status = self::STATUS_OK;
		}

		return [
			'status'           => $status,
			'job_state'        => $worst,
			'counts'           => [
				'synced'     => $counts[ SyncStatus::SYNCED ],
				'not_synced' => $counts[ SyncStatus::NOT_SYNCED ],
				'has_errors' => $counts[ SyncStatus::HAS_ERRORS ],
				'pending'    => $counts[ SyncStatus::PENDING ],
			],
			'syncable'         => $this->get_syncable_count(),
			'needs_fix'        => $errors['invalid'],
			'retrying'         => $errors['transient'],
			'backoff'          => $this->get_backoff_count(),
			'stuck_pending'    => $stuck_pending,
			'overdue_resubmit' => $this->get_overdue_resubmit_count(),
			'queued_actions'   => $queued,
			'last_push_at'     => $last_push,
			'full_sync'        => [
				'in_progress'       => $full_sync ? $this->active_count( $full_sync ) > 0 : false,
				'interrupted'       => $full_sync ? self::JOB_INTERRUPTED === $full_sync['state'] : false,
				'last_completed_at' => $last_full_sync ? (int) $last_full_sync : null,
			],
		];
	}

	/**
	 * Shipping settings sync.
	 *
	 * @param array|null $jobs Jobs keyed by name, or null when the queue can't be read.
	 *
	 * @return array
	 */
	protected function get_shipping_pillar( ?array $jobs ): array {
		$settings  = $this->options->get( OptionsInterface::MERCHANT_CENTER );
		$rate_mode = is_array( $settings ) && isset( $settings['shipping_rate'] ) ? (string) $settings['shipping_rate'] : null;

		$syncable = false;
		try {
			$syncable = $this->market_service->has_syncable_markets();
		} catch ( Throwable $e ) {
			do_action( 'woocommerce_gla_exception', $e, __METHOD__ );
		}

		$failure    = $this->options->get( OptionsInterface::SHIPPING_SYNC_FAILURE );
		$failed_at  = is_array( $failure ) && ! empty( $failure['failed_at'] ) ? strtotime( $failure['failed_at'] . ' UTC' ) : null;
		$last_ok_at = $this->options->get( OptionsInterface::SHIPPING_SYNC_LAST_SUCCESS );
		$last_ok_at = $last_ok_at ? (int) $last_ok_at : null;
		$job        = $jobs[ self::SHIPPING_JOB ] ?? null;
		$job_state  = $job['state'] ?? self::JOB_OK;

		if ( ! $syncable ) {
			// Nothing to push: rates are managed in Merchant Center.
			$status = self::STATUS_OK;
		} elseif ( self::JOB_PAUSED === $job_state ) {
			$status = self::STATUS_PAUSED;
		} elseif ( $failed_at && ( ! $last_ok_at || $failed_at >= $last_ok_at ) ) {
			$status = self::STATUS_ERROR;
		} elseif ( $job && $this->active_count( $job ) > 0 ) {
			$status = self::STATUS_SYNCING;
		} elseif ( ! $last_ok_at ) {
			$status = self::STATUS_UNKNOWN;
		} else {
			$status = self::STATUS_OK;
		}

		return [
			'status'          => $status,
			'rate_mode'       => $rate_mode,
			'syncable'        => $syncable,
			'last_success_at' => $last_ok_at,
			'last_failure'    => $failed_at ? [
				'message'   => wp_trim_words( (string) ( $failure['message'] ?? '' ), 40 ),
				'failed_at' => $failed_at,
			] : null,
			'job_state'       => $job_state,
		];
	}

	/**
	 * Google's review results, read from the cached product statuses.
	 *
	 * Reads the transient directly instead of MerchantStatuses::get_product_statistics(),
	 * which would schedule a refresh as a side effect.
	 *
	 * @param array|null $jobs Jobs keyed by name, or null when the queue can't be read.
	 *
	 * @return array
	 */
	protected function get_review_pillar( ?array $jobs ): array {
		$cached    = $this->transients->get( TransientsInterface::MC_STATUSES );
		$job       = $jobs[ self::STATUS_REFRESH_JOB ] ?? null;
		$job_state = $job['state'] ?? self::JOB_OK;
		$loading   = $job && $this->active_count( $job ) > 0;

		$data_at    = is_array( $cached ) && ! empty( $cached['timestamp'] ) ? (int) $cached['timestamp'] : null;
		$error      = is_array( $cached ) && ! empty( $cached['error'] ) ? (string) $cached['error'] : null;
		$statistics = null;

		if ( is_array( $cached ) && is_array( $cached['statistics'] ?? null ) ) {
			$stats      = $cached['statistics'];
			$statistics = [
				'active'      => (int) ( $stats[ MCStatus::APPROVED ] ?? 0 ) + (int) ( $stats[ MCStatus::PARTIALLY_APPROVED ] ?? 0 ),
				'expiring'    => (int) ( $stats[ MCStatus::EXPIRING ] ?? 0 ),
				'pending'     => (int) ( $stats[ MCStatus::PENDING ] ?? 0 ),
				'disapproved' => (int) ( $stats[ MCStatus::DISAPPROVED ] ?? 0 ),
				'not_synced'  => (int) ( $stats[ MCStatus::NOT_SYNCED ] ?? 0 ),
			];
		}

		// Reading results back is not sending anything, so a refresh in progress never
		// makes this pillar "syncing"; the `loading` flag tells the UI instead.
		if ( in_array( $job_state, [ self::JOB_PAUSED, self::JOB_STUCK, self::JOB_STALLED ], true ) || $error ) {
			$status = self::STATUS_ERROR;
		} elseif ( null === $data_at ) {
			$status = self::STATUS_UNKNOWN;
		} elseif ( time() - $data_at > self::STALE_REVIEW_AFTER ) {
			$status = self::STATUS_WARNING;
		} else {
			$status = self::STATUS_OK;
		}

		return [
			'status'     => $status,
			'data_at'    => $data_at,
			'loading'    => $loading,
			'refresh_at' => $loading ? null : $this->get_review_refresh_available_at(),
			'error'      => $error,
			'statistics' => $statistics,
			'job_state'  => $job_state,
		];
	}

	/**
	 * Totals across every gla job, plus runner-level signals.
	 *
	 * @param array|null $jobs Jobs keyed by name, or null when the queue can't be read.
	 *
	 * @return array
	 */
	protected function get_queue_summary( ?array $jobs ): array {
		if ( null === $jobs ) {
			return [
				'available'          => false,
				'pending'            => 0,
				'running'            => 0,
				'failed_24h'         => 0,
				'oldest_past_due_at' => null,
				'runner_stalled'     => false,
				'stuck_jobs'         => 0,
				'last_completed_at'  => null,
			];
		}

		$summary = [
			'available'          => true,
			'pending'            => 0,
			'running'            => 0,
			'failed_24h'         => 0,
			'oldest_past_due_at' => null,
			'runner_stalled'     => false,
			'stuck_jobs'         => 0,
			'last_completed_at'  => null,
		];

		foreach ( $jobs as $job ) {
			$summary['pending']    += $job['pending'];
			$summary['running']    += $job['running'];
			$summary['failed_24h'] += $job['failed_24h'];

			if ( self::JOB_STUCK === $job['state'] ) {
				++$summary['stuck_jobs'];
			}

			if ( $job['oldest_past_due_at'] && ( ! $summary['oldest_past_due_at'] || $job['oldest_past_due_at'] < $summary['oldest_past_due_at'] ) ) {
				$summary['oldest_past_due_at'] = $job['oldest_past_due_at'];
			}

			if ( $job['last_completed_at'] && $job['last_completed_at'] > (int) $summary['last_completed_at'] ) {
				$summary['last_completed_at'] = $job['last_completed_at'];
			}
		}

		$summary['runner_stalled'] = $summary['oldest_past_due_at'] && ( time() - $summary['oldest_past_due_at'] ) > self::STALLED_PENDING_AFTER;

		return $summary;
	}

	/**
	 * Machine-readable reasons behind the overall state, most severe first.
	 * The UI turns these into copy.
	 *
	 * @param array $pillars
	 * @param array $queue
	 *
	 * @return array[]
	 */
	protected function collect_reasons( array $pillars, array $queue ): array {
		$reasons    = [];
		$connection = $pillars['connection'];
		$products   = $pillars['products'];
		$shipping   = $pillars['shipping'];
		$review     = $pillars['review'];

		if ( ! $connection['google_connected'] || ! $connection['mc_setup_complete'] ) {
			$reasons[] = $this->reason( 'google_disconnected', 'connection', self::STATUS_DISCONNECTED );
		}
		if ( ! $connection['jetpack_connected'] ) {
			$reasons[] = $this->reason( 'jetpack_disconnected', 'connection', self::STATUS_DISCONNECTED );
		}
		if ( $connection['circuit_breaker_open'] ) {
			$reasons[] = $this->reason( 'auth_paused', 'connection', self::STATUS_PAUSED, [ 'retry_at' => $connection['circuit_breaker_retry_at'] ] );
		}
		if ( 'no' === $connection['url_matches'] ) {
			$reasons[] = $this->reason( 'url_mismatch', 'connection', self::STATUS_PAUSED );
		}
		if ( $queue['runner_stalled'] ) {
			$reasons[] = $this->reason( 'runner_stalled', 'queue', self::STATUS_ERROR, [ 'since' => $queue['oldest_past_due_at'] ] );
		}
		if ( self::JOB_PAUSED === $products['job_state'] ) {
			$reasons[] = $this->reason( 'product_job_paused', 'products', self::STATUS_ERROR );
		}
		if ( $products['full_sync']['interrupted'] ) {
			$reasons[] = $this->reason( 'full_sync_interrupted', 'products', self::STATUS_ERROR );
		}
		if ( in_array( $products['job_state'], [ self::JOB_STUCK, self::JOB_STALLED ], true ) ) {
			$reasons[] = $this->reason( 'product_job_stuck', 'products', self::STATUS_ERROR );
		}
		if ( $products['stuck_pending'] > 0 ) {
			$reasons[] = $this->reason( 'products_stuck_pending', 'products', self::STATUS_WARNING, [ 'count' => $products['stuck_pending'] ] );
		}
		if ( self::STATUS_PAUSED === $shipping['status'] ) {
			$reasons[] = $this->reason( 'shipping_job_paused', 'shipping', self::STATUS_ERROR );
		} elseif ( self::STATUS_ERROR === $shipping['status'] ) {
			$reasons[] = $this->reason( 'shipping_failed', 'shipping', self::STATUS_ERROR, [ 'failed_at' => $shipping['last_failure']['failed_at'] ?? null ] );
		}
		if ( self::STATUS_ERROR === $review['status'] ) {
			$reasons[] = $this->reason( 'review_refresh_failed', 'review', self::STATUS_ERROR );
		} elseif ( self::STATUS_WARNING === $review['status'] ) {
			$reasons[] = $this->reason( 'review_data_stale', 'review', self::STATUS_WARNING, [ 'data_at' => $review['data_at'] ] );
		}
		if ( $products['backoff'] > 0 ) {
			$reasons[] = $this->reason( 'products_backoff', 'products', self::STATUS_WARNING, [ 'count' => $products['backoff'] ] );
		}

		return $reasons;
	}

	/**
	 * The worst pillar decides the overall state.
	 *
	 * @param array $pillars
	 * @param array $queue
	 *
	 * @return string
	 */
	protected function get_overall_state( array $pillars, array $queue ): string {
		$statuses = array_column( $pillars, 'status' );

		if ( in_array( self::STATUS_DISCONNECTED, $statuses, true ) ) {
			return self::STATE_DISCONNECTED;
		}
		if ( in_array( self::STATUS_PAUSED, $statuses, true ) || self::JOB_PAUSED === $pillars['products']['job_state'] ) {
			return self::STATE_PAUSED;
		}
		if ( $queue['runner_stalled'] || array_intersect( [ self::STATUS_ERROR, self::STATUS_WARNING ], $statuses ) ) {
			return self::STATE_ATTENTION;
		}
		if ( in_array( self::STATUS_SYNCING, $statuses, true ) ) {
			return self::STATE_SYNCING;
		}

		return self::STATE_HEALTHY;
	}

	/**
	 * Build a reason entry.
	 *
	 * @param string $code
	 * @param string $pillar
	 * @param string $severity
	 * @param array  $data
	 *
	 * @return array
	 */
	protected function reason( string $code, string $pillar, string $severity, array $data = [] ): array {
		return [
			'code'     => $code,
			'pillar'   => $pillar,
			'severity' => $severity,
			'data'     => (object) $data,
		];
	}

	/**
	 * Whether the WordPress.com connection has an owner. Guarded because it
	 * reaches into the Jetpack connection package.
	 *
	 * @return bool
	 */
	protected function is_jetpack_connected(): bool {
		try {
			return $this->merchant_center->is_jetpack_owner_connected();
		} catch ( Throwable $e ) {
			do_action( 'woocommerce_gla_exception', $e, __METHOD__ );
			return false;
		}
	}

	/**
	 * Load gla job rows once per request, keyed by job name.
	 *
	 * @return array|null Null when the Action Scheduler store isn't the DB store.
	 */
	protected function get_jobs_by_name(): ?array {
		if ( false === $this->jobs_memo ) {
			$this->jobs_memo = $this->query_jobs();
		}

		return $this->jobs_memo;
	}

	/**
	 * Aggregate Action Scheduler counts per gla hook in a single query, then
	 * group the hooks by job name.
	 *
	 * Counts come straight from the AS tables so a large queue costs one
	 * GROUP BY rather than loading every action (see ProductSyncStats::get_count()).
	 *
	 * @return array|null Null when the Action Scheduler store isn't the DB store.
	 */
	protected function query_jobs(): ?array {
		if ( ! class_exists( ActionScheduler_Store::class ) || ! class_exists( ActionScheduler_DBStore::class ) ) {
			return null;
		}

		if ( ! ActionScheduler_Store::instance() instanceof ActionScheduler_DBStore ) {
			return null;
		}

		$now     = time();
		$actions = $this->wpdb->prefix . 'actionscheduler_actions';
		$groups  = $this->wpdb->prefix . 'actionscheduler_groups';
		$now_gmt = gmdate( 'Y-m-d H:i:s', $now );
		$day_ago = gmdate( 'Y-m-d H:i:s', $now - DAY_IN_SECONDS );

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $this->wpdb is the injected wpdb instance, the query is prepared inline, and table names come from its prefix.
		$rows = $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT a.hook,
					SUM( a.status = 'pending' ) AS pending,
					SUM( a.status = 'pending' AND a.scheduled_date_gmt <= %s ) AS past_due,
					MIN( CASE WHEN a.status = 'pending' AND a.scheduled_date_gmt <= %s THEN a.scheduled_date_gmt END ) AS oldest_past_due,
					SUM( a.status = 'in-progress' ) AS running,
					MIN( CASE WHEN a.status = 'in-progress' THEN a.last_attempt_gmt END ) AS oldest_running,
					SUM( a.status = 'failed' AND a.last_attempt_gmt >= %s ) AS failed_24h,
					MAX( CASE WHEN a.status = 'failed' THEN a.last_attempt_gmt END ) AS last_failed,
					MAX( CASE WHEN a.status = 'complete' THEN a.last_attempt_gmt END ) AS last_completed
				FROM {$actions} a
				INNER JOIN {$groups} g ON g.group_id = a.group_id
				WHERE g.slug = %s
					AND a.hook LIKE %s
					AND ( a.status IN ( 'pending', 'in-progress' ) OR a.last_attempt_gmt >= %s )
				GROUP BY a.hook",
				$now_gmt,
				$now_gmt,
				$day_ago,
				$this->get_slug(),
				$this->wpdb->esc_like( $this->get_slug() . '/jobs/' ) . '%',
				$day_ago
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( ! is_array( $rows ) ) {
			return null;
		}

		$paused_hooks = $this->query_paused_hooks( $actions, $groups, $now );

		$jobs = [];
		foreach ( $rows as $row ) {
			$parts = explode( '/', (string) $row['hook'] );
			if ( count( $parts ) < 4 ) {
				continue;
			}

			$name  = $parts[2];
			$stage = $parts[3];

			$hook = [
				'hook'               => (string) $row['hook'],
				'stage'              => $stage,
				'pending'            => (int) $row['pending'],
				'past_due'           => (int) $row['past_due'],
				'oldest_past_due_at' => $this->to_timestamp( $row['oldest_past_due'] ),
				'running'            => (int) $row['running'],
				'oldest_running_at'  => $this->to_timestamp( $row['oldest_running'] ),
				'failed_24h'         => (int) $row['failed_24h'],
				'paused'             => isset( $paused_hooks[ $row['hook'] ] ) && 0 === (int) $row['pending'] + (int) $row['running'],
				'last_failed_at'     => $this->to_timestamp( $row['last_failed'] ),
				'last_completed_at'  => $this->to_timestamp( $row['last_completed'] ),
			];

			if ( ! isset( $jobs[ $name ] ) ) {
				$jobs[ $name ] = [
					'name'               => $name,
					'state'              => self::JOB_OK,
					'pending'            => 0,
					'past_due'           => 0,
					'running'            => 0,
					'failed_24h'         => 0,
					'oldest_past_due_at' => null,
					'oldest_running_at'  => null,
					'last_failed_at'     => null,
					'last_completed_at'  => null,
					'hooks'              => [],
				];
			}

			$job =& $jobs[ $name ];

			$job['pending']           += $hook['pending'];
			$job['past_due']          += $hook['past_due'];
			$job['running']           += $hook['running'];
			$job['failed_24h']        += $hook['failed_24h'];
			$job['oldest_past_due_at'] = $this->min_timestamp( $job['oldest_past_due_at'], $hook['oldest_past_due_at'] );
			$job['oldest_running_at']  = $this->min_timestamp( $job['oldest_running_at'], $hook['oldest_running_at'] );
			$job['last_failed_at']     = max( (int) $job['last_failed_at'], (int) $hook['last_failed_at'] ) ?: null;
			$job['last_completed_at']  = max( (int) $job['last_completed_at'], (int) $hook['last_completed_at'] ) ?: null;
			$job['hooks'][]            = $hook;

			unset( $job );
		}

		foreach ( $jobs as $name => $job ) {
			$jobs[ $name ]['state'] = $this->get_job_state( $job, $now );
		}

		ksort( $jobs );

		return $jobs;
	}

	/**
	 * Hooks with at least one set of arguments that ActionSchedulerJobMonitor has
	 * stopped rescheduling.
	 *
	 * Mirrors ActionSchedulerJobMonitor::is_failure_rate_above_threshold(): the same
	 * hook and arguments failing `threshold` times among actions scheduled within
	 * the timeframe.
	 *
	 * @param string $actions Actions table name.
	 * @param string $groups  Groups table name.
	 * @param int    $now     Current timestamp.
	 *
	 * @return true[] Keyed by hook.
	 */
	protected function query_paused_hooks( string $actions, string $groups, int $now ): array {
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $this->wpdb is the injected wpdb instance, the query is prepared inline, and table names come from its prefix.
		$hooks = $this->wpdb->get_col(
			$this->wpdb->prepare(
				"SELECT DISTINCT failures.hook FROM (
					SELECT a.hook
					FROM {$actions} a
					INNER JOIN {$groups} g ON g.group_id = a.group_id
					WHERE g.slug = %s
						AND a.hook LIKE %s
						AND a.status = 'failed'
						AND a.scheduled_date_gmt > %s
					GROUP BY a.hook, a.args
					HAVING COUNT( * ) >= %d
				) failures",
				$this->get_slug(),
				$this->wpdb->esc_like( $this->get_slug() . '/jobs/' ) . '%',
				gmdate( 'Y-m-d H:i:s', $now - $this->get_failure_timeframe() ),
				$this->get_failure_rate_threshold()
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return array_fill_keys( (array) $hooks, true );
	}

	/**
	 * Decide a job's state from its hook counts.
	 *
	 * @param array $job
	 * @param int   $now
	 *
	 * @return string
	 */
	protected function get_job_state( array $job, int $now ): string {
		$queued = $job['pending'] + $job['running'];

		foreach ( $job['hooks'] as $hook ) {
			if ( $hook['paused'] ) {
				return self::JOB_PAUSED;
			}
		}

		// A batched job whose chain died: a create_batch failed, is not retried, and
		// no later create_batch completed, with nothing left queued.
		foreach ( $job['hooks'] as $hook ) {
			if ( 'create_batch' === $hook['stage'] && $hook['last_failed_at'] && 0 === $queued
				&& (int) $hook['last_completed_at'] <= $hook['last_failed_at'] ) {
				return self::JOB_INTERRUPTED;
			}
		}

		if ( $job['oldest_running_at'] && $now - $job['oldest_running_at'] > self::STUCK_RUNNING_AFTER ) {
			return self::JOB_STUCK;
		}

		if ( $job['oldest_past_due_at'] ) {
			$late = $now - $job['oldest_past_due_at'];
			if ( $late > self::STALLED_PENDING_AFTER ) {
				return self::JOB_STALLED;
			}
			if ( $late > self::DELAYED_PENDING_AFTER ) {
				return self::JOB_DELAYED;
			}
		}

		if ( $job['running'] > 0 ) {
			return self::JOB_RUNNING;
		}

		if ( $job['past_due'] > 0 ) {
			return self::JOB_QUEUED;
		}

		if ( $job['pending'] > 0 ) {
			return self::JOB_SCHEDULED;
		}

		if ( $job['failed_24h'] > 0 ) {
			return self::JOB_ERRORS;
		}

		return self::JOB_OK;
	}

	/**
	 * Actions that are running or due now. Pending actions scheduled for later, such
	 * as the daily resubmit at 03:00, are not work in progress.
	 *
	 * @param array $job
	 *
	 * @return int
	 */
	protected function active_count( array $job ): int {
		return (int) $job['past_due'] + (int) $job['running'];
	}

	/**
	 * Pick the more severe of two job states.
	 *
	 * @param string $a
	 * @param string $b
	 *
	 * @return string
	 */
	protected function worse_job_state( string $a, string $b ): string {
		$rank = [
			self::JOB_OK          => 0,
			self::JOB_SCHEDULED   => 0,
			self::JOB_QUEUED      => 1,
			self::JOB_RUNNING     => 2,
			self::JOB_ERRORS      => 3,
			self::JOB_DELAYED     => 4,
			self::JOB_STUCK       => 5,
			self::JOB_STALLED     => 6,
			self::JOB_INTERRUPTED => 7,
			self::JOB_PAUSED      => 8,
		];

		return ( $rank[ $b ] ?? 0 ) > ( $rank[ $a ] ?? 0 ) ? $b : $a;
	}

	/**
	 * Count products and variations per `_wc_gla_sync_status`.
	 *
	 * @return int[] Keyed by SyncStatus value.
	 */
	protected function get_sync_status_counts(): array {
		$counts = array_fill_keys( SyncStatus::ALLOWED_VALUES, 0 );

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $this->wpdb is the injected wpdb instance and the query is prepared inline.
		$rows = $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT pm.meta_value AS status, COUNT( * ) AS total
				FROM {$this->wpdb->postmeta} pm
				INNER JOIN {$this->wpdb->posts} p ON p.ID = pm.post_id
				WHERE pm.meta_key = %s
					AND p.post_type IN ( 'product', 'product_variation' )
					AND p.post_status NOT IN ( 'trash', 'auto-draft' )
				GROUP BY pm.meta_value",
				$this->prefix_meta_key( ProductMetaHandler::KEY_SYNC_STATUS )
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		foreach ( (array) $rows as $row ) {
			if ( isset( $counts[ $row['status'] ] ) ) {
				$counts[ $row['status'] ] = (int) $row['total'];
			}
		}

		return $counts;
	}

	/**
	 * Split products with errors into "Google rejected the data" and "temporary error, will retry".
	 *
	 * @return int[] With keys `invalid` and `transient`.
	 */
	protected function get_error_split(): array {
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $this->wpdb is the injected wpdb instance and the query is prepared inline.
		$row = $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT
					SUM( pm.meta_value LIKE %s ) AS transient_errors,
					SUM( pm.meta_value NOT LIKE %s ) AS invalid_errors
				FROM {$this->wpdb->postmeta} pm
				INNER JOIN {$this->wpdb->postmeta} st ON st.post_id = pm.post_id AND st.meta_key = %s AND st.meta_value = %s
				WHERE pm.meta_key = %s",
				'%' . $this->wpdb->esc_like( GoogleProductService::INTERNAL_ERROR_REASON ) . '%',
				'%' . $this->wpdb->esc_like( GoogleProductService::INTERNAL_ERROR_REASON ) . '%',
				$this->prefix_meta_key( ProductMetaHandler::KEY_SYNC_STATUS ),
				SyncStatus::HAS_ERRORS,
				$this->prefix_meta_key( ProductMetaHandler::KEY_ERRORS )
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return [
			'invalid'   => (int) ( $row['invalid_errors'] ?? 0 ),
			'transient' => (int) ( $row['transient_errors'] ?? 0 ),
		];
	}

	/**
	 * Products excluded from sync after repeated transient failures
	 * (see ProductHelper::is_sync_failed_recently()).
	 *
	 * @return int
	 */
	protected function get_backoff_count(): int {
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $this->wpdb is the injected wpdb instance and the query is prepared inline.
		return (int) $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT COUNT( * )
				FROM {$this->wpdb->postmeta} attempts
				INNER JOIN {$this->wpdb->postmeta} failed_at ON failed_at.post_id = attempts.post_id AND failed_at.meta_key = %s
				WHERE attempts.meta_key = %s
					AND CAST( attempts.meta_value AS UNSIGNED ) > %d
					AND CAST( failed_at.meta_value AS UNSIGNED ) > %d",
				$this->prefix_meta_key( ProductMetaHandler::KEY_SYNC_FAILED_AT ),
				$this->prefix_meta_key( ProductMetaHandler::KEY_FAILED_SYNC_ATTEMPTS ),
				ProductSyncer::FAILURE_THRESHOLD,
				strtotime( '-' . ProductSyncer::FAILURE_THRESHOLD_WINDOW )
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * Synced products that should have been resubmitted by now.
	 *
	 * @return int
	 */
	protected function get_overdue_resubmit_count(): int {
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $this->wpdb is the injected wpdb instance and the query is prepared inline.
		return (int) $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT COUNT( * )
				FROM {$this->wpdb->postmeta} synced_at
				INNER JOIN {$this->wpdb->postmeta} st ON st.post_id = synced_at.post_id AND st.meta_key = %s AND st.meta_value = %s
				WHERE synced_at.meta_key = %s
					AND CAST( synced_at.meta_value AS UNSIGNED ) < %d",
				$this->prefix_meta_key( ProductMetaHandler::KEY_SYNC_STATUS ),
				SyncStatus::SYNCED,
				$this->prefix_meta_key( ProductMetaHandler::KEY_SYNCED_AT ),
				strtotime( '-' . ProductRepository::RESUBMIT_EXPIRY_DAYS . ' days' )
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * Number of products ready to sync, as last computed by UpdateSyncableProductsCount.
	 *
	 * @return int|null
	 */
	protected function get_syncable_count(): ?int {
		$count = $this->options->get( OptionsInterface::SYNCABLE_PRODUCTS_COUNT );

		return null === $count ? null : (int) $count;
	}

	/**
	 * Mirrors ActionSchedulerJobMonitor::get_failure_rate_threshold().
	 *
	 * @return int
	 */
	protected function get_failure_rate_threshold(): int {
		return max( 1, absint( apply_filters( 'woocommerce_gla_job_failure_rate_threshold', 3 ) ) );
	}

	/**
	 * Mirrors ActionSchedulerJobMonitor::get_failure_timeframe().
	 *
	 * @return int
	 */
	protected function get_failure_timeframe(): int {
		return max( 1, absint( apply_filters( 'woocommerce_gla_job_failure_timeframe', 2 * HOUR_IN_SECONDS ) ) );
	}

	/**
	 * Convert a GMT datetime string from the AS tables to a Unix timestamp.
	 *
	 * @param string|null $gmt
	 *
	 * @return int|null
	 */
	protected function to_timestamp( ?string $gmt ): ?int {
		if ( empty( $gmt ) || '0000-00-00 00:00:00' === $gmt ) {
			return null;
		}

		$timestamp = strtotime( $gmt . ' UTC' );

		return false === $timestamp ? null : $timestamp;
	}

	/**
	 * Smaller of two nullable timestamps.
	 *
	 * @param int|null $a
	 * @param int|null $b
	 *
	 * @return int|null
	 */
	protected function min_timestamp( ?int $a, ?int $b ): ?int {
		if ( null === $a ) {
			return $b;
		}
		if ( null === $b ) {
			return $a;
		}

		return min( $a, $b );
	}
}
