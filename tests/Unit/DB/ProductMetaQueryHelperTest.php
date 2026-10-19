<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\DB;

use Automattic\WooCommerce\GoogleListingsAndAds\DB\ProductMetaQueryHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductMetaHandler;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use WC_Helper_Product;

/**
 * Class ProductMetaQueryHelperTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\DB
 */
class ProductMetaQueryHelperTest extends UnitTest {

	use PluginHelper;

	public function test_delete_values_marked_before_never_touches_a_later_runs_values() {
		global $wpdb;
		$helper = new ProductMetaQueryHelper( $wpdb );

		// Statuses with no stamp, a stamp of an earlier run, of this run, and of a later run.
		$ids = [];
		foreach ( [ null, 100, 200, 300 ] as $stamp ) {
			$id    = WC_Helper_Product::create_simple_product()->get_id();
			$ids[] = $id;
			update_post_meta( $id, $this->prefix_meta_key( ProductMetaHandler::KEY_MC_STATUS ), 'approved' );
			if ( null !== $stamp ) {
				update_post_meta( $id, $this->prefix_meta_key( ProductMetaHandler::KEY_MC_STATUS_RUN ), (string) $stamp );
			}
		}

		$helper->delete_values_marked_before( ProductMetaHandler::KEY_MC_STATUS, ProductMetaHandler::KEY_MC_STATUS_RUN, 200 );
		$helper->delete_values_marked_before( ProductMetaHandler::KEY_MC_STATUS_RUN, ProductMetaHandler::KEY_MC_STATUS_RUN, 200 );

		$read = function ( string $key ) use ( $ids ): array {
			return array_map(
				function ( $id ) use ( $key ) {
					return get_post_meta( $id, $this->prefix_meta_key( $key ), true );
				},
				$ids
			);
		};
		$this->assertSame( [ '', '', 'approved', 'approved' ], $read( ProductMetaHandler::KEY_MC_STATUS ) );
		$this->assertSame( [ '', '', '200', '300' ], $read( ProductMetaHandler::KEY_MC_STATUS_RUN ) );
	}

	public function test_delete_values_marked_before_spares_values_a_later_run_stamps_meanwhile() {
		global $wpdb;
		$helper = new ProductMetaQueryHelper( $wpdb );
		$id     = WC_Helper_Product::create_simple_product()->get_id();
		$status = $this->prefix_meta_key( ProductMetaHandler::KEY_MC_STATUS );
		$stamp  = $this->prefix_meta_key( ProductMetaHandler::KEY_MC_STATUS_RUN );
		update_post_meta( $id, $status, 'approved' );
		update_post_meta( $id, $stamp, '100' );

		// Between finding the stale values and deleting them, run 300 stamps the product.
		$restamp = function ( $query ) use ( $wpdb, $id, $stamp ) {
			if ( 0 === strpos( ltrim( $query ), 'DELETE' ) ) {
				$wpdb->update(
					$wpdb->postmeta,
					[ 'meta_value' => '300' ],
					[
						'post_id'  => $id,
						'meta_key' => $stamp,
					]
				);
			}
			return $query;
		};
		add_filter( 'query', $restamp );
		try {
			$helper->delete_values_marked_before( ProductMetaHandler::KEY_MC_STATUS, ProductMetaHandler::KEY_MC_STATUS_RUN, 200 );
			$helper->delete_values_marked_before( ProductMetaHandler::KEY_MC_STATUS_RUN, ProductMetaHandler::KEY_MC_STATUS_RUN, 200 );
		} finally {
			remove_filter( 'query', $restamp );
		}

		$this->assertSame( 'approved', get_post_meta( $id, $status, true ) );
		$this->assertSame( '300', get_post_meta( $id, $stamp, true ) );
	}
}
