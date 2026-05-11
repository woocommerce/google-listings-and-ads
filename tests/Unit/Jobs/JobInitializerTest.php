<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionScheduler;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\DeleteProducts;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\JobException;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\JobInitializer;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\JobInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\JobRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\ResubmitExpiringProducts;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\UpdateProducts;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Container\PluginContainer as Container;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class JobInitializerTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Jobs
 */
class JobInitializerTest extends UnitTest {

	/** @var MockObject|ActionScheduler $action_scheduler */
	protected $action_scheduler;

	/** @var MockObject|Container $container */
	protected $container;

	/** @var JobRepository $job_repository */
	protected $job_repository;

	/** @var JobInitializer $job_initializer */
	protected $job_initializer;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->action_scheduler = $this->createMock( ActionScheduler::class );
		$this->container        = $this->createMock( Container::class );

		$this->job_repository = new JobRepository();
		$this->job_repository->set_container( $this->container );

		$this->job_initializer = new JobInitializer(
			$this->job_repository,
			$this->action_scheduler,
		);
	}

	public function test_register_no_jobs() {
		$this->container->expects( $this->once() )
			->method( 'get' )
			->with( JobInterface::class )
			->willReturn( [] );

		$this->job_initializer->register();
	}

	public function test_register_job_init() {
		$job = $this->create_mocked_job( UpdateProducts::class );
		$job->expects( $this->once() )->method( 'init' );

		$this->job_initializer->register();
	}

	public function test_register_for_job_with_start_hook() {
		$job = $this->create_mocked_job( DeleteProducts::class );
		$job->expects( $this->exactly( 2 ) )->method( 'get_start_hook' );

		$this->job_initializer->register();
	}

	public function test_register_for_recurring_job() {
		$job = $this->create_mocked_job( ResubmitExpiringProducts::class );
		$job->method( 'can_schedule' )->willReturn( true );

		$this->action_scheduler->expects( $this->once() )->method( 'schedule_cron' );

		$this->job_initializer->register();
	}

	public function test_is_needed() {
		$this->assertFalse( $this->job_initializer->is_needed() );
	}

	public function test_get_non_existing_job() {
		$this->container->expects( $this->once() )->method( 'get' )->willThrowException( new Exception() );

		$this->expectException( JobException::class );
		$this->expectExceptionMessage( 'does not exist' );

		$this->job_repository->get( UpdateProducts::class );
	}

	/**
	 * Create a mocked job and have the container return it when fetching jobs.
	 *
	 * @param string $job_class
	 * @return MockObject
	 */
	private function create_mocked_job( string $job_class ): MockObject {
		$job = $this->createMock( $job_class );
		$this->container->expects( $this->once() )
			->method( 'get' )
			->with( JobInterface::class )
			->willReturn( [ $job ] );

		return $job;
	}
}
