<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs;

defined( 'ABSPATH' ) || exit;

/**
 * Class StartHook
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Jobs
 */
class StartHook {

	/**
	 * @var string
	 */
	protected $hook;

	/**
	 * @var int
	 */
	protected $argument_count;

	/**
	 * StartHook constructor.
	 *
	 * @param string $hook           The name of an action hook to attach the job's start method to
	 * @param int    $argument_count The number of arguments returned by the specified action hook
	 */
	public function __construct( string $hook, int $argument_count = 0 ) {
		$this->hook           = $hook;
		$this->argument_count = $argument_count;
	}

	/**
	 * @return string
	 */
	public function get_hook(): string {
		return $this->hook;
	}

	/**
	 * @return int
	 */
	public function get_argument_count(): int {
		return $this->argument_count;
	}

}
