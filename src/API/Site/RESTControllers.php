<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter\SettingsController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Onboarding\GoogleConnectController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Onboarding\JetpackConnectController;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\ValidateInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Psr\Container\ContainerInterface;

/**
 * Class RESTControllers
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site
 */
class RESTControllers implements Service, Registerable {

	use ValidateInterface;

	/**
	 * Our container object.
	 *
	 * @var ContainerInterface
	 */
	protected $container;

	/**
	 * Array of controller classes to register.
	 *
	 * @var string[]
	 */
	protected $controllers = [
		GoogleConnectController::class,
		JetpackConnectController::class,
		SettingsController::class,
	];

	/**
	 * RESTControllers constructor.
	 *
	 * @param ContainerInterface $container
	 */
	public function __construct( ContainerInterface $container ) {
		$this->container = $container;
	}

	/**
	 * Register a service.
	 */
	public function register(): void {
		add_action(
			'rest_api_init',
			function() {
				$this->register_controllers();
			}
		);
	}

	/**
	 * Register our individual rest controllers.
	 */
	protected function register_controllers(): void {
		foreach ( $this->controllers as $controller ) {
			/** @var BaseController $controller */
			$controller = $this->container->get( $controller );
			$this->validate_instanceof( $controller, BaseController::class );
			$controller->register();
		}
	}
}
