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
	 * @var BatchProductRequestEntry[]
	 */
	protected $delete_requests;

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
		// when a product is added / updated, schedule a update job.
		add_action( 'woocommerce_new_product', [ $this, 'update_product' ], 90 );
		add_action( 'woocommerce_update_product', [ $this, 'update_product' ], 90 );

		// if we don't attach to these we miss product gallery updates.
		add_action( 'woocommerce_process_product_meta', [ $this, 'update_product' ], 90, 2 );

		// when a product is trashed or removed, schedule a delete job.
		add_action( 'wp_trash_post', [ $this, 'pre_delete_product' ], 90 );
		add_action( 'before_delete_post', [ $this, 'pre_delete_product' ], 90 );
		add_action( 'trashed_post', [ $this, 'delete_product' ], 90 );
		add_action( 'deleted_post', [ $this, 'delete_product' ], 90 );

		// when a product is restored from the trash, schedule a update job.
		add_action( 'untrashed_post', [ $this, 'update_product' ], 90 );
	}

	/**
	 * Handle updating of a product.
	 *
	 * @param int $product_id The product ID of the product being saved.
	 *
	 * @return void
	 */
	public function update_product( int $product_id ) {
		// bail if it's not a WooCommerce product.
		$product = wc_get_product( $product_id );
		if ( $product instanceof WC_Product ) {
			$this->update_products_job->start( [ $product_id ] );
		}
	}

	/**
	 * Handle deleting of a product.
	 *
	 * @param int $product_id
	 */
	public function delete_product( int $product_id ) {
		if ( isset( $this->delete_requests[ $product_id ] ) ) {
			$google_product_id = $this->delete_requests[ $product_id ]->get_product();
			$this->update_products_job->start( [ $product_id => $google_product_id ] );
		}
	}

	/**
	 * Create request entries for the product (containing its Google ID) so that we can schedule a delete job when the product is actually trashed / deleted.
	 *
	 * @param int $product_id
	 */
	public function pre_delete_product( int $product_id ) {
		$product = wc_get_product( $product_id );
		if ( $product instanceof WC_Product && $this->product_helper->is_product_synced( $product ) ) {
			$this->delete_requests[ $product_id ] = $this->batch_helper->generate_delete_request_entries( [ $product ] )[0];
		}
	}
}
