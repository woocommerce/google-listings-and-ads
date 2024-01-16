<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs;

defined( 'ABSPATH' ) || exit;

/**
 * Class Notification
 *
 * Notifies Google about changes in the customer data.
 *
 * Note: The job will not start if it is already running.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Jobs
 */
class Notification extends AbstractProductSyncerBatchedJob {


	public const TOPIC_KEY = 'topic';
	public const ITEMS_KEY = 'items';

	/**
	 * Get the name of the job.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'notifications';
	}

	/**
	 * Schedules a notification action.
	 *
	 * @param array  $items The items to send notifications about.
	 * @param string $topic A topic to send notifications about.
	 */
	public function schedule_notifications( $items, $topic ) {
		$args = [
			1,
			[
				self::ITEMS_KEY => $items,
				self::TOPIC_KEY => $topic,
			],
		];
		if ( ! $this->is_running( $args ) ) {
			$this->action_scheduler->schedule_immediate( $this->get_create_batch_hook(), $args );
		}
	}


	/**
	 * Get the batch for the job.
	 *
	 * @param int   $batch_number
	 * @param array $args
	 *
	 * @return array
	 */
	protected function get_batch( int $batch_number, array $args = [] ): array {
		if ( isset( $args[ self::ITEMS_KEY ] ) ) {
			return array_slice( $args[ self::ITEMS_KEY ], $batch_number - 1, $this->get_batch_size() );
		}

		return [];
	}

	/**
	 * Get the batch size for this job
	 *
	 * @return int
	 */
	protected function get_batch_size(): int {
		return 1;
	}

	/**
	 * Process an item.
	 *
	 * @param array $ids an array of item ids.
	 * @param array $args The action arguments.
	 */
	public function process_items( array $ids, array $args = [] ) {
		if ( ! isset( $args[ self::TOPIC_KEY ] ) ) {
			return;
		}

		$topic = $args[ self::TOPIC_KEY ];
		if ( $this->notifications_service->is_product_topic( $topic ) ) {
			$item_ids = $this->product_repository->find_notification_ready_products( [ 'include' => $ids ] );
		} else {
			$item_ids = $ids;
		}

		foreach ( $item_ids as $id ) {
			$this->notifications_service->notify( $id, $topic );
		}
	}
}
