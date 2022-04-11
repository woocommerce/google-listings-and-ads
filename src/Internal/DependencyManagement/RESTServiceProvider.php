<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Internal\DependencyManagement;

use Automattic\Jetpack\Connection\Manager;
use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AccountService as AdsAccountService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Ads;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsCampaign;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Connection;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Middleware;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Settings;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Ads\AccountController as AdsAccountController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Ads\BudgetRecommendationController as AdsBudgetRecommendationController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Ads\CampaignController as AdsCampaignController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Ads\ReportsController as AdsReportsController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Ads\SetupCompleteController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\DisconnectController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Google\AccountController as GoogleAccountController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Jetpack\AccountController as JetpackAccountController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter\AccountController as MerchantCenterAccountController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter\ConnectionController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter\ContactInformationController as MerchantCenterContactInformationController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter\IssuesController as MerchantCenterIssuesController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter\PhoneVerificationController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter\ProductFeedController as MerchantCenterProductFeedController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter\ProductStatisticsController as MerchantCenterProductStatsController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter\ProductVisibilityController as MerchantCenterProductVisibilityController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter\ReportsController as MerchantCenterReportsController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter\SettingsController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter\SettingsSyncController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter\ShippingRateBatchController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter\ShippingRateController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter\ShippingRateSuggestionsController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter\ShippingTimeBatchController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter\ShippingTimeController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter\SupportedCountriesController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter\TargetAudienceController;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\ProductFeedQueryHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\BudgetRecommendationQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\MerchantIssueQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\ShippingRateQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\GoogleHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\ProductSyncStats;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\AccountService as MerchantAccountService;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantStatuses;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\ContactInformation;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\PhoneVerification;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\ShippingZone;
use Automattic\WooCommerce\GoogleListingsAndAds\Utility\AddressUtility;
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
		$this->share( SettingsController::class );
		$this->share( ConnectionController::class );
		$this->share( AdsAccountController::class, AdsAccountService::class );
		$this->share( AdsCampaignController::class, AdsCampaign::class );
		$this->share_with_container( AdsReportsController::class );
		$this->share( GoogleAccountController::class, Connection::class );
		$this->share( JetpackAccountController::class, Manager::class, Middleware::class );
		$this->share( MerchantCenterProductStatsController::class, MerchantStatuses::class, ProductSyncStats::class );
		$this->share( MerchantCenterIssuesController::class, MerchantStatuses::class, ProductHelper::class );
		$this->share( MerchantCenterProductFeedController::class, ProductFeedQueryHelper::class );
		$this->share( MerchantCenterProductVisibilityController::class, ProductHelper::class, MerchantIssueQuery::class );
		$this->share( MerchantCenterContactInformationController::class, ContactInformation::class, Settings::class, AddressUtility::class );
		$this->share( AdsBudgetRecommendationController::class, BudgetRecommendationQuery::class, Ads::class );
		$this->share( PhoneVerificationController::class, PhoneVerification::class );
		$this->share( MerchantCenterAccountController::class, MerchantAccountService::class );
		$this->share_with_container( MerchantCenterReportsController::class );
		$this->share( ShippingRateBatchController::class, ShippingRateQuery::class );
		$this->share( ShippingRateController::class, ShippingRateQuery::class );
		$this->share( ShippingRateSuggestionsController::class, ShippingZone::class );
		$this->share_with_container( ShippingTimeBatchController::class );
		$this->share_with_container( ShippingTimeController::class );
		$this->share( TargetAudienceController::class, WP::class, WC::class, ShippingZone::class, GoogleHelper::class );
		$this->share( SupportedCountriesController::class, WC::class, GoogleHelper::class );
		$this->share( SettingsSyncController::class, Settings::class );
		$this->share( DisconnectController::class );
		$this->share( SetupCompleteController::class );
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
