<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionSchedulerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductSyncer;

defined( 'ABSPATH' ) || exit;

/**
 * Class AbstractProductSyncerJob
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Jobs
 */
abstract class AbstractProductSyncerJob extends AbstractActionSchedulerJob implements ProductSyncerJobInterface {

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
	 * SyncProducts constructor.
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
	 * Get whether Merchant Center is connected.
	 *
	 * @return bool
	 */
	public function is_mc_connected(): bool {
		return $this->merchant_center->is_connected();
	}

	/**
	 * Can the job be scheduled.
	 *
	 * @param array|null $args
	 *
	 * @return bool Returns true if the job can be scheduled.
	 */
	public function can_schedule( $args = [] ): bool {
		return ! $this->is_running( $args ) && $this->is_mc_connected();
	}
}
