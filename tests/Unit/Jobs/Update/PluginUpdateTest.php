<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Jobs\Update;

use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\JobRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\Update\DeleteYouTubeConversionReports;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\Update\PluginUpdate;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\UpdateAllProducts;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class PluginUpdateTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Jobs\Update
 */
class PluginUpdateTest extends UnitTest {

	/** @var MockObject|JobRepository $job_repository */
	protected $job_repository;

	/** @var PluginUpdate $plugin_update */
	protected $plugin_update;

	public function setUp(): void {
		parent::setUp();
		$this->job_repository = $this->createMock( JobRepository::class );
		$this->plugin_update  = new PluginUpdate( $this->job_repository );
	}

	public function test_upgrading_to_mapi_release_schedules_full_product_resync() {
		$update_all_products = $this->createMock( UpdateAllProducts::class );
		$update_all_products->expects( $this->once() )->method( 'schedule' );

		// 3.7.3 is above the older 1.x entries, so only the 3.8.0 re-sync and later entries fire: a store upgrading
		// from the Content API era must re-sync every product to pick up Merchant API ids.
		$this->job_repository->method( 'get' )
			->willReturnMap(
				[
					[ UpdateAllProducts::class, $update_all_products ],
					[ DeleteYouTubeConversionReports::class, $this->createMock( DeleteYouTubeConversionReports::class ) ],
				]
			);

		$this->plugin_update->install( '3.7.3', '3.8.0' );
	}

	public function test_upgrading_within_mapi_versions_does_not_reschedule_resync() {
		// No-refire boundary guard, not fix verification: the fires-on-upgrade case is covered by
		// test_upgrading_to_mapi_release_schedules_full_product_resync. This pins the gate at `<` so
		// a store already on the Merchant API release (>= 3.8.0) never re-runs the re-sync.
		$this->job_repository->method( 'get' )
			->willReturnCallback(
				function ( string $job ) {
					$this->assertNotSame( UpdateAllProducts::class, $job );
					return $this->createMock( $job );
				}
			);

		$this->plugin_update->install( '3.8.0', '3.8.1' );
	}

	public function test_upgrading_from_earlier_version_schedules_conversion_report_cleanup() {
		$cleanup = $this->createMock( DeleteYouTubeConversionReports::class );
		$cleanup->expects( $this->once() )->method( 'schedule' );

		$this->job_repository->expects( $this->once() )
			->method( 'get' )
			->with( DeleteYouTubeConversionReports::class )
			->willReturn( $cleanup );

		$this->plugin_update->install( '3.9.4', '3.9.5' );
	}

	public function test_upgrading_from_cleanup_release_does_not_reschedule_conversion_report_cleanup() {
		$this->job_repository->expects( $this->never() )->method( 'get' );

		$this->plugin_update->install( '3.9.5', '3.9.6' );
	}
}
