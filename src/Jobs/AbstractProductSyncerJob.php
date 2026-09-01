<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionSchedulerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductSyncer;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductSyncerException;
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Class AbstractProductSyncerJob
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Jobs
 */
abstract class AbstractProductSyncerJob extends AbstractActionSchedulerJob {

	/**
	 * @var ProductSyncer
	 */
	protected $product_syncer;

	/**
	 * @var ProductRepository
	 */
	protected $product_repository;

	/**
	 * @var MerchantCenterService
	 */
	protected $merchant_center;

	/**
	 * AbstractProductSyncerJob constructor.
	 *
	 * @param ActionSchedulerInterface  $action_scheduler
	 * @param ActionSchedulerJobMonitor $monitor
	 * @param ProductSyncer             $product_syncer
	 * @param ProductRepository         $product_repository
	 * @param MerchantCenterService     $merchant_center
	 */
	public function __construct(
		ActionSchedulerInterface $action_scheduler,
		ActionSchedulerJobMonitor $monitor,
		ProductSyncer $product_syncer,
		ProductRepository $product_repository,
		MerchantCenterService $merchant_center
	) {
		$this->product_syncer     = $product_syncer;
		$this->product_repository = $product_repository;
		$this->merchant_center    = $merchant_center;
		parent::__construct( $action_scheduler, $monitor );
	}

	/**
	 * Can the job be scheduled.
	 *
	 * @param array|null $args
	 *
	 * @return bool Returns true if the job can be scheduled.
	 */
	public function can_schedule( $args = [] ): bool {
		return ! $this->is_running( $args ) && $this->merchant_center->is_ready_for_syncing();
	}

	/**
	 * Do not reschedule on an authentication failure, or once the account has already
	 * been marked as needing reauthentication - retrying immediately will not succeed
	 * either way, so let the failure-rate brake and error log stand instead of looping.
	 *
	 * @param Exception $exception The exception thrown by `process_items`.
	 *
	 * @return bool
	 */
	protected function should_reschedule_on_failure( Exception $exception ): bool {
		if ( $exception instanceof ProductSyncerException && $exception->is_authentication_failure() ) {
			$this->merchant_center->mark_product_sync_auth_failure();

			return false;
		}

		return $this->merchant_center->is_ready_for_syncing();
	}
}
