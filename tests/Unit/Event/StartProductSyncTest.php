<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Event;

use Automattic\WooCommerce\GoogleListingsAndAds\Event\StartProductSync;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\JobRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\UpdateAllProducts;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;

/**
 * Class StartProductSyncTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Event
 */
class StartProductSyncTest extends UnitTest {

	/** @var StartProductSync start_product_sync */
	protected $start_product_sync;

	/** @var Stub|JobRepository $job_repository */
	protected $job_repository;

	/** @var MockObject|UpdateAllProducts $update_all_products */
	protected $update_all_products;

	public function setUp(): void {
		parent::setUp();

		$this->update_all_products = $this->createMock( UpdateAllProducts::class );

		$this->job_repository = $this->createStub( JobRepository::class );
		$this->job_repository->method( 'get' )->willReturnCallback(
			function ( $classname ) {
				if ( $classname === UpdateAllProducts::class ) {
					return $this->update_all_products;
				}
				return $this->createMock( $classname );
			}
		);

		$this->start_product_sync = new StartProductSync( $this->job_repository );
		$this->start_product_sync->register();
	}

	/**
	 * Should only schedule when Push mode of product sync is switched to enable.
	 */
	public function test_on_sync_mode_updated_schedule_resync_all_product() {
		$this->update_all_products->expects( $this->once() )->method( 'schedule' );

		do_action(
			'woocommerce_gla_sync_mode_updated',
			[ 'products' => [ 'push' => false ] ],
			[ 'products' => [ 'push' => true ] ]
		);
	}

	/**
	 * Should not schedule when Push mode of product sync is not switched to enable.
	 */
	public function test_on_sync_mode_updated_not_schedule_resync_all_product() {
		$this->update_all_products->expects( $this->never() )->method( 'schedule' );

		do_action(
			'woocommerce_gla_sync_mode_updated',
			[ 'products' => [ 'push' => false ] ],
			[ 'products' => [ 'push' => false ] ]
		);

		do_action(
			'woocommerce_gla_sync_mode_updated',
			[ 'products' => [ 'push' => true ] ],
			[ 'products' => [ 'push' => true ] ]
		);

		do_action(
			'woocommerce_gla_sync_mode_updated',
			[ 'products' => [ 'push' => true ] ],
			[ 'products' => [ 'push' => false ] ]
		);
	}

	public function test_on_sync_mode_updated_receiving_unexpected_structure() {
		$this->update_all_products->expects( $this->never() )->method( 'schedule' );

		$prev_sync_mode = [ 'products' => [ 'push' => false ] ];
		$sync_mode      = [ 'products' => [ 'push' => true ] ];

		do_action( 'woocommerce_gla_sync_mode_updated', $prev_sync_mode, 'abc' );
		do_action( 'woocommerce_gla_sync_mode_updated', $prev_sync_mode, 123 );
		do_action( 'woocommerce_gla_sync_mode_updated', $prev_sync_mode, false );
		do_action( 'woocommerce_gla_sync_mode_updated', $prev_sync_mode, [] );
		do_action( 'woocommerce_gla_sync_mode_updated', $prev_sync_mode, new \stdClass() );
		do_action( 'woocommerce_gla_sync_mode_updated', $prev_sync_mode, [ 'products' => [] ] );
		do_action( 'woocommerce_gla_sync_mode_updated', $prev_sync_mode, [ 'products' => new \stdClass() ] );

		do_action( 'woocommerce_gla_sync_mode_updated', 'abc', $sync_mode );
		do_action( 'woocommerce_gla_sync_mode_updated', 123, $sync_mode );
		do_action( 'woocommerce_gla_sync_mode_updated', false, $sync_mode );
		do_action( 'woocommerce_gla_sync_mode_updated', [], $sync_mode );
		do_action( 'woocommerce_gla_sync_mode_updated', new \stdClass(), $sync_mode );
		do_action( 'woocommerce_gla_sync_mode_updated', [ 'products' => [] ], $sync_mode );
		do_action( 'woocommerce_gla_sync_mode_updated', [ 'products' => new \stdClass() ], $sync_mode );
	}
}
