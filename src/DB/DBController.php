<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\DB;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Activateable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;

defined( 'ABSPATH' ) || exit;

/**
 * Class DBController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\DB
 */
class DBController implements Service, Registerable, Activateable {

	/**
	 * Activate the service.
	 *
	 * @return void
	 */
	public function activate(): void {
		// Handle setting up individual tables on activation.
	}

	/**
	 * Register a service.
	 */
	public function register(): void {
		// TODO: Implement register() method.
	}
}
