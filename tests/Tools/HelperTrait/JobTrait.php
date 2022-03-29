<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait;

/**
 * Trait Job
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait
 */
trait JobTrait {

	/**
	 * Adding Job start hook
	 */
	protected function add_job_start_hook() {
		add_action(
			$this->job->get_start_hook()->get_hook(),
			function ( ...$args ) {
				$this->job->schedule( $args );
			},
			10,
			$this->job->get_start_hook()->get_argument_count()
		);
	}
}
