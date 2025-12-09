<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionSchedulerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Exports\RowBuilder\OrderItemRowBuilder;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Exports\Writer\CsvExportWriter;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\AbstractBatchedActionSchedulerJob;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\ActionSchedulerJobMonitor;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use WC_Order;

defined( 'ABSPATH' ) || exit;

/**
 * Class CreateMerchantReportedConversionReport
 *
 * Create a cache of Order IDs for a specific day that have a YouTube attribution source.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Jobs
 * @since 2.2.0
 */
class CreateMerchantReportedConversionReport extends AbstractBatchedActionSchedulerJob implements OptionsAwareInterface {
	use OptionsAwareTrait;

	/**
	 * @var OrderItemRowBuilder
	 */
	protected $row_builder;

	/**
	 * @var CsvExportWriter
	 */
	protected $writer;

	/**
	 * CreateMerchantReportedConversionReport constructor.
	 *
	 * @param ActionSchedulerInterface  $action_scheduler
	 * @param ActionSchedulerJobMonitor $monitor
	 * @param OrderItemRowBuilder       $row_builder
	 * @param CsvExportWriter           $writer
	 */
	public function __construct( ActionSchedulerInterface $action_scheduler, ActionSchedulerJobMonitor $monitor, OrderItemRowBuilder $row_builder, CsvExportWriter $writer ) {
		parent::__construct( $action_scheduler, $monitor );
		$this->row_builder = $row_builder;
		$this->writer      = $writer;
	}

	/**
	 * Get the name of the job.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'create_youtube_merchant_reported_conversions_report';
	}

	/**
	 * Get job batch size.
	 *
	 * @return int
	 */
	protected function get_batch_size(): int {
		/**
		 * Filters the batch size for the job.
		 *
		 * @param string Job's name
		 */
		return apply_filters( 'woocommerce_gla_batched_job_size', 100, $this->get_name() );
	}

	/**
	 * Get the date to create a report for.
	 *
	 * @return string
	 */
	protected function get_date(): string {
		/**
		 * Filters the YouTube orders query date value.
		 *
		 * @param string Date string formatted YYYY-MM-DD
		 */
		return apply_filters( 'woocommerce_gla_youtube_order_ids_job_date', gmdate( 'Y-m-d', strtotime( '-1 day' ) ) );
	}

	/**
	 * Get a single batch of items.
	 *
	 * If no items are returned the job will stop.
	 *
	 * @param int $batch_number The batch number increments for each new batch in the job cycle.
	 *
	 * @return int[]
	 */
	public function get_batch( int $batch_number ): array {
		// Get the order IDs from the Options.
		$youtube_cache = $this->options->get( OptionsInterface::YOUTUBE_ORDER_IDS_CACHE, [] );
		$date          = $this->get_date();

		// Return empty array if cache doesn't exist for this date.
		if ( ! isset( $youtube_cache[ $date ] ) || ! is_array( $youtube_cache[ $date ] ) ) {
			return [];
		}

		// Return the current batch to process.
		return array_slice( $youtube_cache[ $date ], $this->get_query_offset( $batch_number ), $this->get_batch_size() );
	}

	/**
	 * Process batch items.
	 *
	 * @param int[] $items A single batch of WooCommerce Order IDs from the get_batch() method.
	 */
	protected function process_items( array $items ) {
		$filename = 'youtube-merchant-conversion-report-' . $this->get_date();

		$file = $this->writer->create_file( $filename );

		foreach ( $items as $order_id ) {
			$order = wc_get_order( $order_id );

			if ( ! $order ) {
				continue;
			}

			// Get items from the order
			$line_items = $order->get_items();

			// If this is a refund with no items, create refund items from parent order's items
			if ( $order instanceof \WC_Order_Refund && empty( $line_items ) ) {
				$parent_id = $order->get_parent_id();
				if ( $parent_id ) {
					$parent_order = wc_get_order( $parent_id );
					if ( $parent_order ) {
						$parent_items = $parent_order->get_items();

						// Create temporary refund items from parent order items
						$line_items = [];
						foreach ( $parent_items as $parent_item ) {
							// Create a copy of the item and set it to belong to the refund
							$item_class = get_class( $parent_item );
							$refund_item = new $item_class();
							$refund_item->set_id( 0 );
							$refund_item->set_props( $parent_item->get_data() );
							// Set order_id AFTER set_props to ensure it points to the refund, not the parent
							$refund_item->set_order_id( $order->get_id() );
							// Set negative quantity for refund
							if ( method_exists( $refund_item, 'set_quantity' ) ) {
								$refund_item->set_quantity( -absint( $parent_item->get_quantity() ) );
							}
							// Set negative totals for refund
							if ( method_exists( $parent_item, 'get_total' ) && method_exists( $refund_item, 'set_total' ) ) {
								$parent_total = call_user_func( [ $parent_item, 'get_total' ] );
								$refund_item->set_total( -abs( (float) $parent_total ) );
							}
							if ( method_exists( $parent_item, 'get_subtotal' ) && method_exists( $refund_item, 'set_subtotal' ) ) {
								$parent_subtotal = call_user_func( [ $parent_item, 'get_subtotal' ] );
								$refund_item->set_subtotal( -abs( (float) $parent_subtotal ) );
							}
							$line_items[] = $refund_item;
						}
					}
				}
			}

			foreach ( $line_items as $line_item ) {
				$row = $this->row_builder->build_row( $line_item );

				if ( is_array( $row ) ) {
					$this->writer->append_row( $file, $row );
				}
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
		// @TODO: Send CSV to WCS and cleanup.
	}

	/**
	 * Get the name of an action hook to attach the job's start method to.
	 *
	 * @return StartHook
	 */
	public function get_start_hook(): StartHook {
		return new StartHook( "{$this->get_hook_base_name()}start" );
	}
}
