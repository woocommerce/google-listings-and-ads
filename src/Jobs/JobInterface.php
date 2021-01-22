<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\DependencyManagement\JobServiceProvider;

defined( 'ABSPATH' ) || exit;

/**
 * Interface JobInterface
 *
 * Note: In order for the jobs to be initialized/registered, they need to be added to the container.
 *
 * @see JobServiceProvider to add job classes to the container.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Jobs
 */
interface JobInterface extends Service {

	/**
	 * Get the name of the job.
	 *
	 * @return string
	 */
	public function get_name(): string;

	/**
	 * Init the job.
	 */
	public function init(): void;

}
