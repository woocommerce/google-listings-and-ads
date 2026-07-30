<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionSchedulerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\HelperTraits\GTINMigrationUtilities;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\AttributeManager;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductRepository;
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Class MigrateGTIN
 *
 * Schedules GTIN migration for all the products in the store.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Jobs
 * @since 2.9.0
 */
class MigrateGTIN extends AbstractBatchedActionSchedulerJob implements OptionsAwareInterface {
	use OptionsAwareTrait;
	use GTINMigrationUtilities;

	public const GTIN_MIGRATION_COMPLETED   = 'completed';
	public const GTIN_MIGRATION_STARTED     = 'started';
	public const GTIN_MIGRATION_READY       = 'ready';
	public const GTIN_MIGRATION_UNAVAILABLE = 'unavailable';

	/**
	 * @var ProductRepository
	 */
	protected $product_repository;


	/**
	 * @var AttributeManager
	 */
	protected $attribute_manager;


	/**
	 * MigrateGTIN constructor.
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
		return ! parent::is_running( $args ) && $this->is_gtin_available_in_core();
	}

	/**
	 * Process batch items.
	 *
	 * @param int[] $items   A single batch of WooCommerce product IDs from the get_batch() method.
	 * @param array $context Unused.
	 */
	protected function process_items( array $items, array $context = [] ) {
		// update the product core GTIN using G4W GTIN
		$products = $this->product_repository->find_by_ids( $items );
		foreach ( $products as $product ) {
			// process variations
			if ( $product instanceof \WC_Product_Variable ) {
				$variations = $product->get_children();
				$this->process_items( $variations );
				continue;
			}

			if ( $product->get_global_unique_id() ) {
				$this->debug( $this->error_gtin_already_set( $product ) );
				continue;
			}

			$gtin = $this->get_gtin( $product );

			if ( ! $gtin ) {
				$this->debug( $this->error_gtin_not_found( $product ) );
				continue;
			}

			$gtin = $this->prepare_gtin( $gtin );
			if ( ! is_numeric( $gtin ) ) {
				$this->debug( $this->error_gtin_invalid( $product, $gtin ) );
				continue;
			}

			try {
				$product->set_global_unique_id( $gtin );
				$product->save();
				$this->debug( $this->successful_migrated_gtin( $product, $gtin ) );
			} catch ( Exception $e ) {
				$this->debug( $this->error_gtin_not_saved( $product, $gtin, $e ) );
			}
		}
	}

	/**
	 * Tweak schedule function for adding a start flag.
	 *
	 * @param array $args
	 */
	public function schedule( array $args = [] ) {
		$this->options->update( OptionsInterface::GTIN_MIGRATION_STATUS, self::GTIN_MIGRATION_STARTED );
		parent::schedule( $args );
	}

	/**
	 *
	 * To run when the job is completed.
	 *
	 * @param int $final_batch_number
	 */
	public function handle_complete( int $final_batch_number ) {
		$this->options->update( OptionsInterface::GTIN_MIGRATION_STATUS, self::GTIN_MIGRATION_COMPLETED );
	}


	/**
	 * Get a single batch of items.
	 *
	 * If no items are returned the job will stop.
	 *
	 * @param int   $batch_number The batch number increments for each new batch in the job cycle.
	 * @param array $context      Unused.
	 *
	 * @return array
	 *
	 * @throws Exception If an error occurs. The exception will be logged by ActionScheduler.
	 */
	protected function get_batch( int $batch_number, array $context = [] ): array {
		return $this->product_repository->find_all_product_ids( $this->get_batch_size(), $this->get_query_offset( $batch_number ) );
	}

	/**
	 * Debug info in the logs.
	 *
	 * @param string $message
	 *
	 * @return void
	 */
	protected function debug( string $message ): void {
		do_action(
			'woocommerce_gla_debug_message',
			$message,
			__METHOD__
		);
	}
}
