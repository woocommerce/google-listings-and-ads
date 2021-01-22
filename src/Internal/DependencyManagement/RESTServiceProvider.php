<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Internal\DependencyManagement;

use Automattic\Jetpack\Connection\Manager;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Proxy as Middleware;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Connection;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Google\AccountController as GoogleAccountController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Ads\AccountController as AdsAccountController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Ads\CampaignController as AdsCampaignController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Google\SiteVerificationController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Jetpack\AccountController as JetpackAccountController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter\ConnectionController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter\SettingsController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter\ShippingRateBatchController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter\ShippingRateController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter\ShippingTimeBatchController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter\ShippingTimeController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter\TargetAudienceController;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\Container\Definition\DefinitionInterface;
use Psr\Container\ContainerInterface;

/**
 * Class RESTServiceProvider
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Internal\DependencyManagement
 */
class RESTServiceProvider extends AbstractServiceProvider {

	/**
	 * Returns a boolean if checking whether this provider provides a specific
	 * service or returns an array of provided services if no argument passed.
	 *
	 * @param string $service
	 *
	 * @return boolean
	 */
	public function provides( string $service ): bool {
		return 'rest_controller' === $service;
	}

	/**
	 * Use the register method to register items with the container via the
	 * protected $this->leagueContainer property or the `getLeagueContainer` method
	 * from the ContainerAwareTrait.
	 *
	 * @return void
	 */
	public function register() {
		$this->share_with_options( SettingsController::class );
		$this->share( ConnectionController::class );
		$this->share( AdsAccountController::class, Middleware::class );
		$this->share_with_container( AdsCampaignController::class );
		$this->share( GoogleAccountController::class, Connection::class );
		$this->share( JetpackAccountController::class, Manager::class );
		$this->share_with_container( ShippingRateBatchController::class );
		$this->share_with_container( ShippingRateController::class );
		$this->share_with_container( ShippingTimeBatchController::class );
		$this->share_with_container( ShippingTimeController::class );
		$this->share_with_container( SiteVerificationController::class );
		$this->share_with_options( TargetAudienceController::class );
	}

	/**
	 * Share a class.
	 *
	 * Overridden to include the RESTServer proxy with all classes.
	 *
	 * @param string $class        The class name to add.
	 * @param mixed  ...$arguments Constructor arguments for the class.
	 *
	 * @return DefinitionInterface
	 */
	protected function share( string $class, ...$arguments ): DefinitionInterface {
		return parent::share( $class, RESTServer::class, ...$arguments )->addTag( 'rest_controller' );
	}

	/**
	 * Share a class with the OptionsInterface object provided.
	 *
	 * @param string $class        The class name to add.
	 * @param mixed  ...$arguments Constructor arguments for the class.
	 *
	 * @return DefinitionInterface
	 */
	protected function share_with_options( string $class, ...$arguments ): DefinitionInterface {
		return $this->share( $class, OptionsInterface::class, ...$arguments );
	}

	/**
	 * Share a class with only the container object provided.
	 *
	 * @param string $class The class name to add.
	 *
	 * @return DefinitionInterface
	 */
	protected function share_with_container( string $class ): DefinitionInterface {
		return parent::share( $class, ContainerInterface::class )->addTag( 'rest_controller' );
	}
}
