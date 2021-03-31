<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionSchedulerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\HelperTraits\MerchantCenterTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductSyncer;

defined( 'ABSPATH' ) || exit;

/**
 * Class AbstractProductSyncerJob
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Jobs
 */
abstract class AbstractProductSyncerJob extends AbstractActionSchedulerJob implements ProductSyncerJobInterface {

	use MerchantCenterTrait;

	/**
	 * @var ProductSyncer
	 */
	protected $product_syncer;

	/**
	 * @var ProductRepository
	 */
	protected $product_repository;

	/**
	 * SyncProducts constructor.
	 *
	 * @param ActionSchedulerInterface  $action_scheduler
	 * @param ActionSchedulerJobMonitor $monitor
	 * @param ProductSyncer             $product_syncer
	 * @param ProductRepository         $product_repository
	 */
	public function __construct(
		ActionSchedulerInterface $action_scheduler,
		ActionSchedulerJobMonitor $monitor,
		ProductSyncer $product_syncer,
		ProductRepository $product_repository
	) {
		$this->product_syncer     = $product_syncer;
		$this->product_repository = $product_repository;
		parent::__construct( $action_scheduler, $monitor );
	}

	/**
	 * Get whether Merchant Center setup is completed.
	 *
	 * @return bool
	 */
	public function is_mc_setup(): bool {
		return $this->setup_complete();
	}

	/**
	 * Can the job start.
	 *
	 * @param array $args
	 *
	 * @return bool Returns true if the job can start.
	 */
	public function can_start( array $args = [] ): bool {
		return ! $this->is_running( $args ) && $this->is_mc_setup();
	}
}
