<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Internal\DependencyManagement;

use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AdsRecommendationsService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsCampaign;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsReport;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\TargetAudience;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators\AbandonedOnboardingEvaluator;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators\CampaignNoSalesEvaluator;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators\CouponsNotSyncedEvaluator;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators\EnhancedConversionsOffEvaluator;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators\NotOnboarded90DaysEvaluator;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators\PausedCampaignEvaluator;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators\ProductIssuesEvaluator;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators\ReadyButNoSalesEvaluator;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators\RecommendationsAvailableEvaluator;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators\SalesNotGrowingEvaluator;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators\SkippedCampaignEvaluator;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators\Sold10ItemsEvaluator;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators\TrackingOffEvaluator;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationCacheInvalidator;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationEvaluatorInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationService;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OnboardingCompleted;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\ServiceBasedMerchantState;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;

defined( 'ABSPATH' ) || exit;

/**
 * Class NotificationsServiceProvider
 *
 * Provides the notification service and its evaluators to the container. Keeping these
 * registrations in a dedicated provider stops CoreServiceProvider from growing unbounded
 * as new notifications are added.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Internal\DependencyManagement
 */
class NotificationsServiceProvider extends AbstractServiceProvider {

	/**
	 * @var array
	 */
	protected $provides = [
		Service::class                           => true,
		NotificationEvaluatorInterface::class    => true,
		NotificationService::class               => true,
		NotificationCacheInvalidator::class      => true,
		AbandonedOnboardingEvaluator::class      => true,
		CampaignNoSalesEvaluator::class          => true,
		CouponsNotSyncedEvaluator::class         => true,
		EnhancedConversionsOffEvaluator::class   => true,
		NotOnboarded90DaysEvaluator::class       => true,
		PausedCampaignEvaluator::class           => true,
		ProductIssuesEvaluator::class            => true,
		ReadyButNoSalesEvaluator::class          => true,
		RecommendationsAvailableEvaluator::class => true,
		SalesNotGrowingEvaluator::class          => true,
		SkippedCampaignEvaluator::class          => true,
		Sold10ItemsEvaluator::class              => true,
		TrackingOffEvaluator::class              => true,
	];

	/**
	 * Registers the notification services with the container.
	 */
	public function register(): void {
		$this->share_with_tags( NotificationService::class, WP::class );
		$this->share_with_tags( NotificationCacheInvalidator::class );
		$this->share_with_tags( SkippedCampaignEvaluator::class, AdsCampaign::class, OnboardingCompleted::class, ServiceBasedMerchantState::class );
		$this->share_with_tags( AbandonedOnboardingEvaluator::class, ServiceBasedMerchantState::class, OnboardingCompleted::class );
		$this->share_with_tags( NotOnboarded90DaysEvaluator::class, OnboardingCompleted::class );
		$this->share_with_tags( EnhancedConversionsOffEvaluator::class );
		$this->share_with_tags( TrackingOffEvaluator::class );
		$this->share_with_tags( ProductIssuesEvaluator::class, ServiceBasedMerchantState::class );
		$this->share_with_tags( Sold10ItemsEvaluator::class );
		$this->share_with_tags( ReadyButNoSalesEvaluator::class, WC::class );
		$this->share_with_tags( CouponsNotSyncedEvaluator::class, MerchantCenterService::class, TargetAudience::class );
		$this->share_with_tags( SalesNotGrowingEvaluator::class );
		$this->share_with_tags( PausedCampaignEvaluator::class, AdsCampaign::class );
		$this->share_with_tags( CampaignNoSalesEvaluator::class, AdsCampaign::class, AdsReport::class, AdsRecommendationsService::class );
		$this->share_with_tags( RecommendationsAvailableEvaluator::class, AdsRecommendationsService::class, AdsCampaign::class );
	}
}
