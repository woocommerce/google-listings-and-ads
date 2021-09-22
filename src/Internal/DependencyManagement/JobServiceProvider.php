<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Internal\DependencyManagement;

use ActionScheduler as ActionSchedulerCore;
use ActionScheduler_AsyncRequest_QueueRunner as QueueRunnerAsyncRequest;
use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionScheduler;
use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionSchedulerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\AsyncActionRunner;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidClass;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\ValidateInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\AbstractProductSyncerBatchedJob;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\ActionSchedulerJobInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\ActionSchedulerJobMonitor;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\CleanupProductsJob;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\DeleteAllProducts;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\DeleteProducts;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\JobInitializer;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\JobInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\JobRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\ProductSyncerJobInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\ProductSyncStats;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\ResubmitExpiringProducts;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\UpdateAllProducts;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\UpdateCoupon;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\UpdateProducts;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\Update\CleanupProductTargetCountriesJob;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\Update\PluginUpdate;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\BatchProductHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductSyncer;
use Automattic\WooCommerce\GoogleListingsAndAds\Event\StartProductSync;
use Automattic\WooCommerce\GoogleListingsAndAds\Coupon;
use Automattic\WooCommerce\GoogleListingsAndAds\Product;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;

defined( 'ABSPATH' ) || exit;

/**
 * Class JobServiceProvider
 *
 * Provides the job classes and their related services to the container.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Internal\DependencyManagement
 */
class JobServiceProvider extends AbstractServiceProvider {

	use ValidateInterface;

	/**
	 * @var array
	 */
	protected $provides = [
		JobInterface::class              => true,
		ActionSchedulerInterface::class  => true,
		AsyncActionRunner::class         => true,
		ActionSchedulerJobMonitor::class => true,
	    Coupon\SyncerHooks::class        => true,
		PluginUpdate::class              => true,
	    Product\SyncerHooks::class       => true,
		ProductSyncStats::class          => true,	
		Service::class                   => true,
		JobRepository::class             => true,
	];

	/**
	 * Use the register method to register items with the container via the
	 * protected $this->leagueContainer property or the `getLeagueContainer` method
	 * from the ContainerAwareTrait.
	 *
	 * @return void
	 */
	public function register(): void {
		$this->share_with_tags(
			AsyncActionRunner::class,
			new QueueRunnerAsyncRequest( ActionSchedulerCore::store() ),
			ActionSchedulerCore::lock()
		);
		$this->share_with_tags( ActionScheduler::class, AsyncActionRunner::class );
		$this->share_with_tags( ActionSchedulerJobMonitor::class, ActionScheduler::class );
		$this->share_with_tags( ProductSyncStats::class, ActionScheduler::class );

		// share product syncer jobs
		$this->share_product_syncer_job( UpdateAllProducts::class );
		$this->share_product_syncer_job( DeleteAllProducts::class );
		$this->share_product_syncer_job( UpdateProducts::class );
		$this->share_product_syncer_job( DeleteProducts::class );
		$this->share_product_syncer_job( ResubmitExpiringProducts::class );
		$this->share_product_syncer_job( CleanupProductsJob::class );
		
		// share coupon syncer jobs.
		$this->share_coupon_syncer_job( UpdateCoupon::class );

		$this->share_with_tags(
			JobRepository::class,
			JobInterface::class
		);
		$this->conditionally_share_with_tags(
			JobInitializer::class,
			JobRepository::class,
			ActionScheduler::class
		);

		$this->share_with_tags(
		    Product\SyncerHooks::class,
		    BatchProductHelper::class,
		    ProductHelper::class,
		    JobRepository::class,
		    MerchantCenterService::class,
		    WC::class
		);
		
		$this->share_with_tags(
		    Coupon\SyncerHooks::class,
		    JobRepository::class,
		    MerchantCenterService::class,
		    WC::class
		);

		$this->share_with_tags( StartProductSync::class, JobRepository::class );
		$this->share_with_tags( PluginUpdate::class, JobRepository::class );

		// Share plugin update jobs
		$this->share_product_syncer_job( CleanupProductTargetCountriesJob::class );
	}

	/**
	 * Share an ActionScheduler job class
	 *
	 * @param string $class        The class name to add.
	 * @param mixed  ...$arguments Constructor arguments for the class.
	 *
	 * @throws InvalidClass When the given class does not implement the ActionSchedulerJobInterface.
	 */
	protected function share_action_scheduler_job( string $class, ...$arguments ) {
		$this->validate_interface( $class, ActionSchedulerJobInterface::class );
		$this->share_with_tags(
			$class,
			ActionScheduler::class,
			ActionSchedulerJobMonitor::class,
			...$arguments
		);
	}

	/**
	 * Share a product syncer job class
	 *
	 * @param string $class        The class name to add.
	 * @param mixed  ...$arguments Constructor arguments for the class.
	 *
	 * @throws InvalidClass When the given class does not implement the ProductSyncerJobInterface.
	 */
	protected function share_product_syncer_job( string $class, ...$arguments ) {
		$this->validate_interface( $class, ProductSyncerJobInterface::class );
		if ( is_subclass_of( $class, AbstractProductSyncerBatchedJob::class ) ) {
			$this->share_action_scheduler_job(
				$class,
				ProductSyncer::class,
				ProductRepository::class,
				BatchProductHelper::class,
				MerchantCenterService::class,
				...$arguments
			);
		} else {
			$this->share_action_scheduler_job(
				$class,
				ProductSyncer::class,
				ProductRepository::class,
				MerchantCenterService::class,
				...$arguments
			);
		}
	}
	
	/**
	 * Share a coupon syncer job class
	 *
	 * @param string $class        The class name to add.
	 * @param mixed  ...$arguments Constructor arguments for the class.
	 *
	 * @throws InvalidClass When the given class does not implement the ProductSyncerJobInterface.
	 */
	protected function share_coupon_syncer_job(string $class, ...$arguments)
    {
        // Coupon related jobs also should implement ProductSyncerJobInterface.
        $this->validate_interface($class, ProductSyncerJobInterface::class);
        $this->share_action_scheduler_job(
            $class,
            MerchantCenterService::class,
            ...$arguments
        );
    }
}
