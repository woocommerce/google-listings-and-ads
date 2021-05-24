<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Internal\DependencyManagement;

use Automattic\Jetpack\Config;
use Automattic\Jetpack\Connection\Manager;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces\ISO3166AwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\Container\Argument\RawArgument;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\ISO3166\ISO3166;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\ISO3166\ISO3166DataProvider;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Class ThirdPartyServiceProvider
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Internal\DependencyManagement
 */
class ThirdPartyServiceProvider extends AbstractServiceProvider {

	use PluginHelper;

	/**
	 * Array of classes provided by this container.
	 *
	 * Keys should be the class name, and the value can be anything (like `true`).
	 *
	 * @var array
	 */
	protected $provides = [
		Config::class              => true,
		Manager::class             => true,
		ISO3166DataProvider::class => true,
		ValidatorInterface::class  => true,
	];

	/**
	 * Use the register method to register items with the container via the
	 * protected $this->leagueContainer property or the `getLeagueContainer` method
	 * from the ContainerAwareTrait.
	 *
	 * @return void
	 */
	public function register() {
		$jetpack_id = new RawArgument( 'google-listings-and-ads' );
		$this->share( Manager::class )->addArgument( $jetpack_id );

		$this->share( Config::class )->addMethodCall(
			'ensure',
			[
				new RawArgument( 'connection' ),
				[
					'slug' => $jetpack_id->getValue(),
					'name' => __( 'Google Listings and Ads', 'google-listings-and-ads' ),
				],
			]
		);

		$this->share_concrete( ISO3166DataProvider::class, ISO3166::class );
		$this->getLeagueContainer()
			->inflector( ISO3166AwareInterface::class )
			->invokeMethod( 'set_iso3166_provider', [ ISO3166DataProvider::class ] );

		$this->share_concrete(
			ValidatorInterface::class,
			function () {
				return Validation::createValidatorBuilder()
					->addMethodMapping( 'load_validator_metadata' )
					->getValidator();
			}
		);
	}
}
