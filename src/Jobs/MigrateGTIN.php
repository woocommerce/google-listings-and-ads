<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionSchedulerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\AttributeManager;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Class MigrateGTIN
 *
 * Schedules GTIN migration for all the products in the store.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Jobs
 * @since x.x.x
 */
class MigrateGTIN extends AbstractBatchedActionSchedulerJob implements OptionsAwareInterface {
	use OptionsAwareTrait;

	/**
	 * @var ProductRepository
	 */
	protected $product_repository;


	/**
	 * @var AttributeManager
	 */
	protected $attribute_manager;


	/**
	 * Job constructor.
	 *
	 * @param ActionSchedulerInterface  $action_scheduler
	 * @param ActionSchedulerJobMonitor $monitor
	 * @param ProductRepository         $product_repository
	 * @param AttributeManager          $attribute_manager
	 */
	public function __construct( ActionSchedulerInterface $action_scheduler, ActionSchedulerJobMonitor $monitor, ProductRepository $product_repository, AttributeManager $attribute_manager ) {
		parent::__construct( $action_scheduler, $monitor );
		$this->product_repository = $product_repository;
		$this->attribute_manager  = $attribute_manager;
	}

	/**
	 * Get the name of the job.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'migrate_gtin';
	}

	/**
	 * Can the job be scheduled.
	 *
	 * @param array|null $args
	 *
	 * @return bool Returns true if the job can be scheduled.
	 */
	public function can_schedule( $args = [] ): bool {
		return parent::can_schedule( $args ) && $this->is_gtin_available_in_core();
	}

	/**
	 * Process batch items.
	 *
	 * @param int[] $items A single batch of WooCommerce product IDs from the get_batch() method.
	 */
	protected function process_items( array $items ) {
		// todo: prevent execution if migration was completed?

		// update the product core GTIN using G4W GTIN
		$products = $this->product_repository->find_by_ids( $items );
		foreach ( $products as $product ) {
			// void if core GTIN is already set.
			if ( $product->get_global_unique_id() ) {
				continue;
			}

			// process variations
			if ( $product instanceof \WC_Product_Variable ) {
				$variations = $product->get_available_variations();
				$ids        = array_column( $variations, 'variation_id' );
				$this->process_items( $ids );
				continue;
			}

			$gtin = $this->attribute_manager->get_value( $product, 'gtin' );
			if ( $gtin ) {
				$product->set_global_unique_id( $gtin );
				$product->save();
			}
		}
	}

	/**
	 * Called when the job is completed.
	 *
	 * @param int $final_batch_number The final batch number when the job was completed.
	 *                                If equal to 1 then no items were processed by the job.
	 */
	protected function handle_complete( int $final_batch_number ) {
		// todo: set migration as completed?
	}

	/**
	 * Get a single batch of items.
	 *
	 * If no items are returned the job will stop.
	 *
	 * @param int $batch_number The batch number increments for each new batch in the job cycle.
	 *
	 * @return array
	 *
	 * @throws Exception If an error occurs. The exception will be logged by ActionScheduler.
	 */
	protected function get_batch( int $batch_number ): array {
		return $this->product_repository->find_all_product_ids( $this->get_batch_size(), $this->get_query_offset( $batch_number ) );
	}

	/**
	 * Verifies if GTIN logic is available in core.
	 *
	 * @return bool
	 */
	protected function is_gtin_available_in_core(): bool {
		return method_exists( \WC_Product::class, 'get_global_unique_id' );
	}
}
