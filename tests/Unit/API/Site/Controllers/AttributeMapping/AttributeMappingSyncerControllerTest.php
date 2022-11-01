<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\AttributeMapping;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\AttributeMapping\AttributeMappingSyncerController;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\ProductSyncStats;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\UpdateAllProducts;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\RESTControllerUnitTest;

/**
 * Test suite for AttributeMappingSyncerControllerTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\AttributeMapping
 * @group AttributeMapping
 */
class AttributeMappingSyncerControllerTest extends RESTControllerUnitTest {


	protected const ROUTE_REQUEST_SYNCER = '/wc/gla/mc/mapping/sync';

	/**
	 * @var ProductSyncStats
	 */
	private ProductSyncStats $sync_stats;

	/**
	 * @var OptionsInterface
	 */
	private OptionsInterface $options;

	/**
	 * @var UpdateAllProducts
	 */
	private UpdateAllProducts $update_all_products_job;


	public function setUp(): void {
		parent::setUp();
		$this->update_all_products_job = $this->createMock( UpdateAllProducts::class );
		$this->sync_stats              = $this->createMock( ProductSyncStats::class );
		$this->options                 = $this->createMock( OptionsInterface::class );

		$this->controller = new AttributeMappingSyncerController( $this->server, $this->sync_stats );
		$this->controller->set_options_object( $this->options );
		$this->controller->register();
	}


	public function test_register_route() {
		$this->assertArrayHasKey( self::ROUTE_REQUEST_SYNCER, $this->server->get_routes() );
	}

	public function test_is_syncing_route_scheduled_true() {
		$this->sync_stats->expects( $this->once() )
			->method( 'get_count' )->willReturn( 4 );

		$response = $this->do_request( self::ROUTE_REQUEST_SYNCER );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals(
			[
				'is_scheduled' => true,
				'last_sync'    => null,
			],
			$response->get_data()
		);
	}


	public function test_is_syncing_route_scheduled_false() {
		$this->sync_stats->expects( $this->once() )
			->method( 'get_count' )->willReturn( 0 );

		$response = $this->do_request( self::ROUTE_REQUEST_SYNCER );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals(
			[
				'is_scheduled' => false,
				'last_sync'    => null,
			],
			$response->get_data()
		);
	}

	public function test_is_syncing_route_last_sync() {
		$this->sync_stats->expects( $this->once() )
			->method( 'get_count' )->willReturn( 0 );

		$this->options->expects( $this->once() )
			->method( 'get' )->with( OptionsInterface::UPDATE_ALL_PRODUCTS_LAST_SYNC )->willReturn( '123' );

		$response = $this->do_request( self::ROUTE_REQUEST_SYNCER );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals(
			[
				'is_scheduled' => false,
				'last_sync'    => '123',
			],
			$response->get_data()
		);
	}

}
