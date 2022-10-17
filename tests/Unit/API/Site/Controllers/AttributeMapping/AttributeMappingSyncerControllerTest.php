<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\AttributeMapping;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\AttributeMapping\AttributeMappingSyncerController;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\JobRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\UpdateAllProducts;
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
	 * @var JobRepository
	 */
	private JobRepository $job_repository;

	/**
	 * @var UpdateAllProducts
	 */
	private UpdateAllProducts $update_all_products_job;


	public function setUp(): void {
		parent::setUp();
		$this->update_all_products_job = $this->createMock( UpdateAllProducts::class );
		$this->job_repository          = $this->createMock( JobRepository::class );
		$this->controller              = new AttributeMappingSyncerController( $this->server, $this->job_repository );
		$this->controller->register();
	}


	public function test_register_route() {
		$this->assertArrayHasKey( self::ROUTE_REQUEST_SYNCER, $this->server->get_routes() );
	}

	public function test_is_syncing_route_true() {
		$this->job_repository->expects( $this->once() )
			->method( 'get' )->willReturn( $this->update_all_products_job );

		$this->update_all_products_job->expects( $this->once() )
			->method( 'is_syncing' )->willReturn( true );

		$response = $this->do_request( self::ROUTE_REQUEST_SYNCER );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( [ 'is_syncing' => true ], $response->get_data() );
	}


	public function test_is_syncing_route_false() {
		$this->job_repository->expects( $this->once() )
			->method( 'get' )->willReturn( $this->update_all_products_job );

		$this->update_all_products_job->expects( $this->once() )
			->method( 'is_syncing' )->willReturn( false );

		$response = $this->do_request( self::ROUTE_REQUEST_SYNCER );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( [ 'is_syncing' => false ], $response->get_data() );
	}

}
