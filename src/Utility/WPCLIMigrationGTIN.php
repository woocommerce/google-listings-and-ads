<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Utility;

use Automattic\WooCommerce\GoogleListingsAndAds\HelperTraits\GTINMigrationUtilities;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Conditional;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\AttributeManager;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductRepository;
use Exception;
use WP_CLI;

defined( 'ABSPATH' ) || exit;

/**
 * Class WP_CLI_GTIN_Migration
 * Creates a set of utility commands in WP CLI for GTIN Migration
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Utility
 *
 * @since x.x.x
 */
class WPCLIMigrationGTIN implements Service, Registerable, Conditional {
	use GTINMigrationUtilities;

	/** @var AttributeManager  */
	public AttributeManager $attribute_manager;

	/** @var ProductRepository  */
	public ProductRepository $product_repository;

	/**
	 * Constructor
	 *
	 * @param ProductRepository $product_repository
	 * @param AttributeManager  $attribute_manager
	 */
	public function __construct( ProductRepository $product_repository, AttributeManager $attribute_manager ) {
		$this->product_repository = $product_repository;
		$this->attribute_manager  = $attribute_manager;
	}

	/**
	 * Register service and initialize hooks.
	 */
	public function register(): void {
		WP_CLI::add_hook( 'after_wp_load', [ $this, 'register_commands' ] );
	}

	/**
	 * Register the commands
	 */
	public function register_commands(): void {
		WP_CLI::add_command( 'wc g4wc gtin-migration start', [ $this, 'gtin_migration_start' ] );
	}

	/**
	 * Starts the GTIN migration in batches
	 */
	public function gtin_migration_start(): void {
		$batch_size   = $this->get_batch_size();
		$num_products = $this->get_total_products_count();
		WP_CLI::log( sprintf( 'Starting GTIN migration for %s products in the store.', $num_products ) );
		$progress     = WP_CLI\Utils\make_progress_bar( 'GTIN Migration', $num_products / $batch_size );
		$processed    = 0;
		$batch_number = 1;
		$start_time   = microtime( true );

		// First batch
		$items      = $this->get_items( $batch_number );
		$processed += $this->process_items( $items );
		$progress->tick();

		// Next batches
		while ( ! empty( $items ) ) {
			++$batch_number;
			$items      = $this->get_items( $batch_number );
			$processed += $this->process_items( $items );
			$progress->tick();
		}

		$progress->finish();
		$total_time = microtime( true ) - $start_time;

		// Issue a warning if nothing is migrated.
		if ( ! $processed ) {
			WP_CLI::warning( __( 'No GTIN were migrated.', 'google-listings-and-ads' ) );
			return;
		}

		WP_CLI::success(
			sprintf(
			/* Translators: %1$d is the number of migrated GTINS and %2$d is the execution time in seconds. */
				_n(
					'%1$d GTIN was migrated in %2$d seconds.',
					'%1$d GTIN were migrated in %2$d seconds.',
					$processed,
					'google-listings-and-ads'
				),
				$processed,
				$total_time
			)
		);
	}

	/**
	 * Get total of products in the store to be migrated.
	 *
	 * @return int The total number of products.
	 */
	private function get_total_products_count(): int {
		$args = [
			'status' => 'publish',
			'return' => 'ids',
			'type'   => [ 'simple', 'variation' ],
		];

		return count( $this->product_repository->find_ids( $args ) );
	}


	/**
	 * Get the items for the current batch
	 *
	 * @param int $batch_number
	 * @return int[] Array of WooCommerce product IDs
	 */
	private function get_items( int $batch_number ): array {
		return $this->product_repository->find_all_product_ids( $this->get_batch_size(), $this->get_query_offset( $batch_number ) );
	}

	/**
	 * Get the query offset based on a given batch number and the specified batch size.
	 *
	 * @param int $batch_number
	 *
	 * @return int
	 */
	protected function get_query_offset( int $batch_number ): int {
		return $this->get_batch_size() * ( $batch_number - 1 );
	}

	/**
	 * Get the batch size. By default, 100.
	 *
	 * @return int The batch size.
	 */
	private function get_batch_size(): int {
		return apply_filters( 'woocommerce_gla_batched_cli_size', 100 );
	}

	/**
	 * Process batch items.
	 *
	 * @param int[] $items A single batch of WooCommerce product IDs from the get_batch() method.
	 * @return int The number of items processed.
	 */
	protected function process_items( array $items ): int {
		// update the product core GTIN using G4W GTIN
		$products  = $this->product_repository->find_by_ids( $items );
		$processed = 0;

		foreach ( $products as $product ) {
			// process variations
			if ( $product instanceof \WC_Product_Variable ) {
				$variations = $product->get_children();
				$processed += $this->process_items( $variations );
				continue;
			}

			// void if core GTIN is already set.
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
				++$processed;
				$this->debug( $this->successful_migrated_gtin( $product, $gtin ) );
			} catch ( Exception $e ) {
				$this->error( $this->error_gtin_not_saved( $product, $gtin, $e ) );
			}
		}

		return $processed;
	}

	/**
	 * Check if this Service is needed.
	 *
	 * @see https://make.wordpress.org/cli/handbook/guides/commands-cookbook/#include-in-a-plugin-or-theme
	 * @return bool
	 */
	public static function is_needed(): bool {
		return defined( 'WP_CLI' ) && WP_CLI;
	}

	/**
	 * Add some info in the debug console.
	 * Add --debug to see these logs in WP CLI
	 *
	 * @param string $message
	 * @return void
	 */
	protected function debug( string $message ): void {
		WP_CLI::debug( $message );
	}

	/**
	 * Add some info in the error console.
	 *
	 * @param string $message
	 * @return void
	 */
	protected function error( string $message ): void {
		WP_CLI::error( $message, false );
	}
}
