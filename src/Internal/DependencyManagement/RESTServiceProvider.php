<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Internal\DependencyManagement;

use Automattic\Jetpack\Connection\Manager;
use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AccountService as AdsAccountService;
use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AssetSuggestionsService as AdsAssetSuggestionsService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Ads;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsCampaign;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Connection;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Merchant;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\MerchantMetrics;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Middleware;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Settings;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsAssetGroup;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Ads\AccountController as AdsAccountController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Ads\BudgetRecommendationController as AdsBudgetRecommendationController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Ads\CampaignController as AdsCampaignController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Ads\ReportsController as AdsReportsController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Ads\SetupCompleteController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Ads\AssetGroupController as AdsAssetGroupController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Ads\AssetSuggestionsController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\GTINMigrationController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\RestAPI\SyncController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\TourController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\DisconnectController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Google\AccountController as GoogleAccountController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Jetpack\AccountController as JetpackAccountController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter\AccountController as MerchantCenterAccountController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter\AttributeMappingCategoriesController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\AttributeMapping\AttributeMappingDataController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\AttributeMapping\AttributeMappingRulesController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\AttributeMapping\AttributeMappingSyncerController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter\RequestReviewController as MerchantCenterRequestReviewController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter\ConnectionController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter\ContactInformationController as MerchantCenterContactInformationController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter\IssuesController as MerchantCenterIssuesController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter\PolicyComplianceCheckController;
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
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter\SyncableProductsCountController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter\TargetAudienceController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter\PriceBenchmarksController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\RestAPI\AuthController as RestAPIAuthController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\WP\NotificationsService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\WP\OAuthService;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\ProductFeedQueryHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\AttributeMappingRulesQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\BudgetRecommendationQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\MerchantIssueQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\ShippingRateQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\RequestReviewStatuses;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\GoogleHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\JobRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\ProductSyncStats;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\AccountService as MerchantAccountService;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantStatuses;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\ContactInformation;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\PhoneVerification;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\PolicyComplianceCheck;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\TransientsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\AttributeMapping\AttributeMappingHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\ShippingSuggestionService;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\ShippingZone;
use Automattic\WooCommerce\GoogleListingsAndAds\Utility\AddressUtility;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\Container\Definition\DefinitionInterface;

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
	 * protected $this->container property or the `getContainer` method
	 * from the ContainerAwareTrait.
	 *
	 * @return void
	 */
	public function register(): void {
		$this->share( SettingsController::class, ShippingZone::class );
		$this->share( ConnectionController::class );
		$this->share( AdsAccountController::class, AdsAccountService::class );
		$this->share( AdsCampaignController::class, AdsCampaign::class );
		$this->share( AdsAssetGroupController::class, AdsAssetGroup::class );
		$this->share( AdsReportsController::class );
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
		$this->share( MerchantCenterRequestReviewController::class, Middleware::class, Merchant::class, RequestReviewStatuses::class, TransientsInterface::class );
		$this->share( MerchantCenterReportsController::class );
		$this->share( ShippingRateBatchController::class, ShippingRateQuery::class );
		$this->share( ShippingRateController::class, ShippingRateQuery::class );
		$this->share( ShippingRateSuggestionsController::class, ShippingSuggestionService::class );
		$this->share( ShippingTimeBatchController::class );
		$this->share( ShippingTimeController::class );
		$this->share( TargetAudienceController::class, WP::class, WC::class, ShippingZone::class, GoogleHelper::class );
		$this->share( SupportedCountriesController::class, WC::class, GoogleHelper::class );
		$this->share( SettingsSyncController::class, Settings::class );
		$this->share( DisconnectController::class );
		$this->share( SetupCompleteController::class, MerchantMetrics::class );
		$this->share( AssetSuggestionsController::class, AdsAssetSuggestionsService::class );
		$this->share( SyncableProductsCountController::class, JobRepository::class );
		$this->share( PolicyComplianceCheckController::class, PolicyComplianceCheck::class );
		$this->share( AttributeMappingDataController::class, AttributeMappingHelper::class );
		$this->share( AttributeMappingRulesController::class, AttributeMappingHelper::class, AttributeMappingRulesQuery::class );
		$this->share( AttributeMappingCategoriesController::class );
		$this->share( AttributeMappingSyncerController::class, ProductSyncStats::class );
		$this->share( TourController::class );
		$this->share( RestAPIAuthController::class, OAuthService::class, MerchantAccountService::class );
		$this->share( GTINMigrationController::class, JobRepository::class );
		$this->share( PriceBenchmarksController::class );
		$this->share( SyncController::class, NotificationsService::class );
	}

	/**
	 * Share a class.
	 *
	 * Overridden to include the RESTServer proxy with all classes.
	 *
	 * @param string $class_name   The class name to add.
	 * @param mixed  ...$arguments Constructor arguments for the class.
	 *
	 * @return DefinitionInterface
	 */
	protected function share( string $class_name, ...$arguments ): DefinitionInterface {
		return parent::share( $class_name, RESTServer::class, ...$arguments )->addTag( 'rest_controller' );
	}
}
