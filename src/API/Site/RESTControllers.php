<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseController;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\ContainerAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces\ContainerAwareInterface;

/**
 * Class RESTControllers
 *
 * Container used for:
 * - classes tagged with 'rest_controller'
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site
 */
class RESTControllers implements ContainerAwareInterface, Service, Registerable {

	use ContainerAwareTrait;

	/**
	 * Register a service.
	 */
	public function register(): void {
		add_action(
			'rest_api_init',
			function () {
				$this->register_controllers();
			}
		);
	}

	/**
	 * Register our individual rest controllers.
	 *
	 * The DI container can return a class-name string instead of an instance
	 * when the underlying class fails to autoload at definition-resolution time.
	 * That has been observed during the plugin upgrade flow, where in-memory
	 * autoload state lagged the new files on disk and triggered a fatal
	 * `InvalidClass` from the previous validate-then-throw approach. Skip
	 * non-instance entries here and log them so a transient autoload failure
	 * on a single page render does not take down the whole admin.
	 */
	protected function register_controllers(): void {
		$controllers = $this->container->get( 'rest_controller' );
		foreach ( $controllers as $controller ) {
			if ( ! $controller instanceof BaseController ) {
				$description = is_string( $controller )
					? sprintf( 'class-name string "%s"', $controller )
					: ( is_object( $controller ) ? sprintf( 'instance of %s', get_class( $controller ) ) : gettype( $controller ) );

				do_action(
					'woocommerce_gla_debug_message',
					sprintf( 'Expected a BaseController instance from the container; got %s. Skipping.', $description ),
					__METHOD__
				);
				continue;
			}
			$controller->register();
		}
	}
}
