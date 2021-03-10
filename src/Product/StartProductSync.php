<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Product;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\ContainerAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces\ContainerAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\UpdateAllProducts;

defined( 'ABSPATH' ) || exit;

/**
 * Class StartProductSync
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Product
 */
class StartProductSync implements ContainerAwareInterface, Registerable, Service {

	use ContainerAwareTrait;

	/**
	 * Register a service.
	 */
	public function register(): void {
		add_action(
			'gla_mc_settings_sync',
			function() {
				$this->start_update();
			}
		);
	}

	/**
	 * Start updating all products.
	 */
	protected function start_update() {
		/** @var UpdateAllProducts $update */
		$update = $this->container->get( UpdateAllProducts::class );
		$update->start();
	}
}
