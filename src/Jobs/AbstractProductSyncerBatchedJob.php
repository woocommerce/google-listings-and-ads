<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionSchedulerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\BatchProductHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductSyncer;

defined( 'ABSPATH' ) || exit;

/**
 * Class AbstractGoogleBatchedJob
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Jobs
 */
abstract class AbstractProductSyncerBatchedJob extends AbstractBatchedActionSchedulerJob implements ProductSyncerJobInterface {

	/**
	 * @var ProductSyncer
	 */
	protected $product_syncer;

	/**
	 * @var ProductRepository
	 */
	protected $product_repository;

	/**
	 * @var BatchProductHelper
	 */
	protected $batch_product_helper;

	/**
	 * @var MerchantCenterService
	 */
	protected $merchant_center;

	/**
	 * SyncProducts constructor.
	 *
	 * @param ActionSchedulerInterface  $action_scheduler
	 * @param ActionSchedulerJobMonitor $monitor
	 * @param ProductSyncer             $product_syncer
	 * @param ProductRepository         $product_repository
	 * @param BatchProductHelper        $batch_product_helper
	 * @param MerchantCenterService     $merchant_center
	 */
	public function __construct(
		ActionSchedulerInterface $action_scheduler,
		ActionSchedulerJobMonitor $monitor,
		ProductSyncer $product_syncer,
		ProductRepository $product_repository,
		BatchProductHelper $batch_product_helper,
		MerchantCenterService $merchant_center
	) {
		$this->batch_product_helper = $batch_product_helper;
		$this->product_syncer       = $product_syncer;
		$this->product_repository   = $product_repository;
		$this->merchant_center      = $merchant_center;
		parent::__construct( $action_scheduler, $monitor );
	}

	/**
	 * Get whether Merchant Center is connected and ready for syncing data.
	 *
	 * @return bool
	 */
	public function is_mc_ready_for_syncing(): bool {
		return $this->merchant_center->is_ready_for_syncing();
	}

	/**
	 * Can the job be scheduled.
	 *
	 * @param array|null $args
	 *
	 * @return bool Returns true if the job can be scheduled.
	 */
	public function can_schedule( $args = [] ): bool {
		return ! $this->is_running( $args ) && $this->is_mc_ready_for_syncing();
	}
}
