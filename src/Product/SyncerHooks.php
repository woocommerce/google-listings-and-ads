<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Product;

use Automattic\WooCommerce\GoogleListingsAndAds\Google\BatchProductRequestEntry;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\DeleteProducts;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\UpdateProducts;
use WC_Product;

defined( 'ABSPATH' ) || exit;

/**
 * Class SyncerHooks
 *
 * Hooks to various WooCommerce and WordPress actions to provide automatic product sync functionality.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Product
 */
class SyncerHooks implements Service, Registerable {

	/**
	 * Array of booleans mapped to product IDs indicating that they have been already
	 * scheduled for update or delete during current request. Used to avoid scheduling
	 * duplicate jobs.
	 *
	 * @var bool[]
	 */
	protected $already_scheduled = [];

	/**
	 * @var BatchProductRequestEntry[][]
	 */
	protected $delete_requests_map;

	/**
	 * @var BatchProductHelper
	 */
	protected $batch_helper;

	/**
	 * @var ProductHelper
	 */
	protected $product_helper;

	/**
	 * @var UpdateProducts
	 */
	protected $update_products_job;

	/**
	 * @var DeleteProducts
	 */
	protected $delete_products_job;

	/**
	 * SyncerHooks constructor.
	 *
	 * @param BatchProductHelper $batch_helper
	 * @param ProductHelper      $product_helper
	 * @param UpdateProducts     $update_products_job
	 * @param DeleteProducts     $delete_products_job
	 */
	public function __construct(
		BatchProductHelper $batch_helper,
		ProductHelper $product_helper,
		UpdateProducts $update_products_job,
		DeleteProducts $delete_products_job
	) {
		$this->batch_helper        = $batch_helper;
		$this->product_helper      = $product_helper;
		$this->update_products_job = $update_products_job;
		$this->delete_products_job = $delete_products_job;
	}

	/**
	 * Register a service.
	 */
	public function register(): void {
		$update = function( int $product_id ) {
			$this->handle_update_product( $product_id );
		};

		$pre_delete = function( int $product_id ) {
			$this->handle_pre_delete_product( $product_id );
		};

		$delete = function( int $product_id ) {
			$this->handle_delete_product( $product_id );
		};

		// when a product is added / updated, schedule a update job.
		add_action( 'woocommerce_new_product', $update, 90 );
		add_action( 'woocommerce_update_product', $update, 90 );

		// if we don't attach to these we miss product gallery updates.
		add_action( 'woocommerce_process_product_meta', $update, 90, 2 );

		// when a product is trashed or removed, schedule a delete job.
		add_action( 'wp_trash_post', $pre_delete, 90 );
		add_action( 'before_delete_post', $pre_delete, 90 );
		add_action( 'trashed_post', $delete, 90 );
		add_action( 'deleted_post', $delete, 90 );

		// when a product is restored from the trash, schedule a update job.
		add_action( 'untrashed_post', $update, 90 );
	}

	/**
	 * Handle updating of a product.
	 *
	 * @param int $product_id The product ID of the product being saved.
	 *
	 * @return void
	 */
	protected function handle_update_product( int $product_id ) {
		$product = wc_get_product( $product_id );
		if ( ! $product instanceof WC_Product || $this->already_scheduled( $product_id ) ) {
			// bail if it's not a WooCommerce product.
			return;
		}

		if ( $this->product_helper->is_sync_enabled( $product ) ) {
			// schedule an update job if product sync is enabled.
			$this->update_products_job->start( [ $product_id ] );
			$this->set_already_scheduled( $product_id );
		} elseif ( $this->product_helper->is_product_synced( $product ) ) {
			// delete the product from Google Merchant Center if it's already synced AND sync has been disabled.
			$request_entries = $this->batch_helper->generate_delete_request_entries( [ $product ] );
			$this->delete_products_job->start( $this->batch_helper->request_entries_to_id_map( $request_entries ) );
			$this->set_already_scheduled( $product_id );
		}
	}

	/**
	 * Handle deleting of a product.
	 *
	 * @param int $product_id
	 */
	protected function handle_delete_product( int $product_id ) {
		if ( isset( $this->delete_requests_map[ $product_id ] ) ) {
			$this->delete_products_job->start( $this->batch_helper->request_entries_to_id_map( $this->delete_requests_map[ $product_id ] ) );
			$this->set_already_scheduled( $product_id );
		}
	}

	/**
	 * Create request entries for the product (containing its Google ID) so that we can schedule a delete job when the product is actually trashed / deleted.
	 *
	 * @param int $product_id
	 */
	protected function handle_pre_delete_product( int $product_id ) {
		$product = wc_get_product( $product_id );
		if ( $product instanceof WC_Product && $this->product_helper->is_product_synced( $product ) ) {
			$this->delete_requests_map[ $product_id ] = $this->batch_helper->generate_delete_request_entries( [ $product ] );
		}
	}

	/**
	 * @param int $product_id
	 *
	 * @return bool
	 */
	protected function already_scheduled( int $product_id ): bool {
		return isset( $this->already_scheduled[ $product_id ] );
	}

	/**
	 * @param int $product_id
	 *
	 * @return void
	 */
	protected function set_already_scheduled( int $product_id ): void {
		$this->already_scheduled[ $product_id ] = true;
	}
}
