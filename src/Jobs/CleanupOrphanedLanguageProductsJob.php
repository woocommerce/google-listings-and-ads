<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionSchedulerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\BatchProductIDRequestEntry;
use Automattic\WooCommerce\GoogleListingsAndAds\Integration\WPML;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductSyncer;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductSyncerException;

defined( 'ABSPATH' ) || exit;

/**
 * Class CleanupOrphanedLanguageProductsJob
 *
 * Deletes Merchant Center entries left orphaned when a language is removed from
 * a market's language set. Scheduled by MarketService when an update reduces
 * the set of accepted languages; carries the market's identifying keys
 * (feed label variants for a secondary market, target country codes for the
 * primary) and the language codes that were removed. The keys alone do not
 * identify a language — the job narrows the deletion to products whose own
 * post language is in the removed set.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Jobs
 */
class CleanupOrphanedLanguageProductsJob extends AbstractProductSyncerJob {

	/**
	 * @var ProductHelper
	 */
	protected $product_helper;

	/**
	 * @var WPML
	 */
	protected $wpml;

	/**
	 * CleanupOrphanedLanguageProductsJob constructor.
	 *
	 * @param ActionSchedulerInterface  $action_scheduler
	 * @param ActionSchedulerJobMonitor $monitor
	 * @param ProductSyncer             $product_syncer
	 * @param ProductRepository         $product_repository
	 * @param MerchantCenterService     $merchant_center
	 * @param ProductHelper             $product_helper
	 * @param WPML                      $wpml
	 */
	public function __construct(
		ActionSchedulerInterface $action_scheduler,
		ActionSchedulerJobMonitor $monitor,
		ProductSyncer $product_syncer,
		ProductRepository $product_repository,
		MerchantCenterService $merchant_center,
		ProductHelper $product_helper,
		WPML $wpml
	) {
		parent::__construct( $action_scheduler, $monitor, $product_syncer, $product_repository, $merchant_center );
		$this->product_helper = $product_helper;
		$this->wpml           = $wpml;
	}

	/**
	 * Get the name of the job.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'cleanup_orphaned_language_products_job';
	}

	/**
	 * Schedule the job.
	 *
	 * @param array $args Accepts `[ 'keys' => string[], 'removed_languages' => string[] ]`.
	 *                    `keys` is the set of `google_ids` keys to inspect (the feed
	 *                    label variants for a secondary market, target country codes
	 *                    for the primary). `removed_languages` is the set of language
	 *                    codes (short form, e.g. `fr`) that the market no longer accepts.
	 *
	 * @throws InvalidValue When either argument is missing or empty.
	 */
	public function schedule( array $args = [] ) {
		$keys              = $args['keys'] ?? null;
		$removed_languages = $args['removed_languages'] ?? null;

		if ( ! is_array( $keys ) || empty( $keys ) ) {
			throw InvalidValue::is_empty( 'keys' );
		}

		if ( ! is_array( $removed_languages ) || empty( $removed_languages ) ) {
			throw InvalidValue::is_empty( 'removed_languages' );
		}

		$process_args = [
			[
				'keys'              => array_values( $keys ),
				'removed_languages' => array_values( $removed_languages ),
			],
		];

		if ( $this->can_schedule( $process_args ) ) {
			$this->action_scheduler->schedule_immediate( $this->get_process_item_hook(), $process_args );
		}
	}

	/**
	 * Process orphaned entries for products whose language is in the removed set.
	 *
	 * @param array $items Single-element array containing the scheduling args.
	 *
	 * @throws ProductSyncerException If the Merchant Center delete call fails.
	 */
	protected function process_items( array $items ) {
		$keys    = is_array( $items['keys'] ?? null ) ? $items['keys'] : [];
		$removed = is_array( $items['removed_languages'] ?? null ) ? $items['removed_languages'] : [];

		if ( empty( $keys ) || empty( $removed ) ) {
			return;
		}

		$product_ids = $this->product_repository->find_synced_product_ids();
		if ( empty( $product_ids ) ) {
			return;
		}

		$products        = $this->product_repository->find_by_ids( $product_ids );
		$request_entries = [];

		foreach ( $products as $product ) {
			$product_language = $this->wpml->get_post_language( $product->get_id() );
			if ( '' === $product_language || ! in_array( $product_language, $removed, true ) ) {
				continue;
			}

			$google_ids = $this->product_helper->get_synced_google_product_ids( $product );
			if ( empty( $google_ids ) ) {
				continue;
			}

			foreach ( $keys as $key ) {
				if ( empty( $google_ids[ $key ] ) ) {
					continue;
				}

				$google_id                     = $google_ids[ $key ];
				$request_entries[ $google_id ] = new BatchProductIDRequestEntry( $product->get_id(), $google_id );
			}
		}

		if ( empty( $request_entries ) ) {
			return;
		}

		// The delete call also removes each deleted entry's Google ID from the
		// product's tracked IDs, leaving other markets' entries untouched.
		$this->product_syncer->delete_by_batch_requests( $request_entries );
	}
}
