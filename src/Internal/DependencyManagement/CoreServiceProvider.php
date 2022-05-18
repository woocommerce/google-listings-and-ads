<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Internal\DependencyManagement;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionScheduler;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\ActivationRedirect;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Admin;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\BulkEdit\BulkEditInitializer;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\BulkEdit\CouponBulkEdit;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\MetaBox\ChannelVisibilityMetaBox;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\MetaBox\CouponChannelVisibilityMetaBox;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\MetaBox\MetaBoxInitializer;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\MetaBox\MetaBoxInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\Attributes\AttributesTab;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\Attributes\VariationsAttributes;
use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AccountService as AdsAccountService;
use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AdsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AdsService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Connection as GoogleConnection;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Merchant;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\MerchantMetrics;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Settings as GoogleSettings;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\RESTControllers;
use Automattic\WooCommerce\GoogleListingsAndAds\Assets\AssetsHandler;
use Automattic\WooCommerce\GoogleListingsAndAds\Assets\AssetsHandlerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\ConnectionTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Coupon\CouponHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Coupon\CouponMetaHandler;
use Automattic\WooCommerce\GoogleListingsAndAds\Coupon\CouponSyncer;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Installer as DBInstaller;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Migration\Migrator;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\TableManager;
use Automattic\WooCommerce\GoogleListingsAndAds\Event\ClearProductStatsCache;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\GlobalSiteTag;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\GoogleHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\GoogleHelperAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\GoogleProductService;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\GooglePromotionService;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\RequestReviewStatuses;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\SiteVerificationMeta;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\ViewFactory;
use Automattic\WooCommerce\GoogleListingsAndAds\Installer;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\DeprecatedFilters;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\InstallTimestamp;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces\FirstInstallInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces\InstallableInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Logging\DebugLogger;
use Automattic\WooCommerce\GoogleListingsAndAds\Menu\Dashboard;
use Automattic\WooCommerce\GoogleListingsAndAds\Menu\GetStarted;
use Automattic\WooCommerce\GoogleListingsAndAds\Menu\ProductFeed;
use Automattic\WooCommerce\GoogleListingsAndAds\Menu\Reports;
use Automattic\WooCommerce\GoogleListingsAndAds\Menu\Settings;
use Automattic\WooCommerce\GoogleListingsAndAds\Menu\SetupAds;
use Automattic\WooCommerce\GoogleListingsAndAds\Menu\SetupMerchantCenter;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\AccountService as MerchantAccountService;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\ContactInformation;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantStatuses;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\PhoneVerification;
use Automattic\WooCommerce\GoogleListingsAndAds\Notes\CompleteSetup as CompleteSetupNote;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\TargetAudience;
use Automattic\WooCommerce\GoogleListingsAndAds\Notes\ContactInformation as ContactInformationNote;
use Automattic\WooCommerce\GoogleListingsAndAds\Notes\NoteInitializer;
use Automattic\WooCommerce\GoogleListingsAndAds\Notes\ReconnectWordPress as ReconnectWordPressNote;
use Automattic\WooCommerce\GoogleListingsAndAds\Notes\ReviewAfterClicks as ReviewAfterClicksNote;
use Automattic\WooCommerce\GoogleListingsAndAds\Notes\ReviewAfterConversions as ReviewAfterConversionsNote;
use Automattic\WooCommerce\GoogleListingsAndAds\Notes\SetupCampaign as SetupCampaignNote;
use Automattic\WooCommerce\GoogleListingsAndAds\Notes\SetupCampaignTwoWeeks as SetupCampaign2Note;
use Automattic\WooCommerce\GoogleListingsAndAds\Notes\SetupCouponSharing as SetupCouponSharingNote;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\AdsAccountState;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\AdsSetupCompleted;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\MerchantAccountState;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\MerchantSetupCompleted;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\Options;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\Transients;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\TransientsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\AttributeManager;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\BatchProductHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductFactory;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductFilter;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductMetaHandler;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductSyncer;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\GoogleGtagJs;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\Tracks as TracksProxy;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\ShippingZone;
use Automattic\WooCommerce\GoogleListingsAndAds\TaskList\CompleteSetup;
use Automattic\WooCommerce\GoogleListingsAndAds\Tracking\Events\ActivatedEvents;
use Automattic\WooCommerce\GoogleListingsAndAds\Tracking\Events\SiteClaimEvents;
use Automattic\WooCommerce\GoogleListingsAndAds\Tracking\Events\SiteVerificationEvents;
use Automattic\WooCommerce\GoogleListingsAndAds\Tracking\EventTracking;
use Automattic\WooCommerce\GoogleListingsAndAds\Tracking\TrackerSnapshot;
use Automattic\WooCommerce\GoogleListingsAndAds\Tracking\Tracks;
use Automattic\WooCommerce\GoogleListingsAndAds\Tracking\TracksAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tracking\TracksInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Utility\AddressUtility;
use Automattic\WooCommerce\GoogleListingsAndAds\Utility\DateTimeUtility;
use Automattic\WooCommerce\GoogleListingsAndAds\Utility\ISOUtility;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\ISO3166\ISO3166DataProvider;
use Automattic\WooCommerce\GoogleListingsAndAds\View\PHPViewFactory;
use Psr\Container\ContainerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class CoreServiceProvider
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Internal\DependencyManagement
 */
class CoreServiceProvider extends AbstractServiceProvider {

	/**
	 * @var array
	 */
	protected $provides = [
		Installer::class              => true,
		ActivationRedirect::class     => true,
		Admin::class                  => true,
		AddressUtility::class         => true,
		Reports::class                => true,
		AssetsHandlerInterface::class => true,
		BulkEditInitializer::class    => true,
		ContactInformationNote::class => true,
		CompleteSetup::class          => true,
		CompleteSetupNote::class      => true,
		CouponBulkEdit::class         => true,
		CouponHelper::class           => true,
		CouponMetaHandler::class      => true,
		CouponSyncer::class           => true,
		Dashboard::class              => true,
		DateTimeUtility::class        => true,
		EventTracking::class          => true,
		GetStarted::class             => true,
		GlobalSiteTag::class          => true,
		ISOUtility::class             => true,
		SiteVerificationEvents::class => true,
		OptionsInterface::class       => true,
		TransientsInterface::class    => true,
		ProductFeed::class            => true,
		ReconnectWordPressNote::class => true,
		ReviewAfterClicksNote::class  => true,
		RESTControllers::class        => true,
		Service::class                => true,
		Settings::class               => true,
		SetupAds::class               => true,
		SetupMerchantCenter::class    => true,
		SetupCampaignNote::class      => true,
		SetupCampaign2Note::class     => true,
		SetupCouponSharingNote::class => true,
		TableManager::class           => true,
		TrackerSnapshot::class        => true,
		Tracks::class                 => true,
		TracksInterface::class        => true,
		ProductSyncer::class          => true,
		ProductHelper::class          => true,
		ProductMetaHandler::class     => true,
		SiteVerificationMeta::class   => true,
		BatchProductHelper::class     => true,
		ProductFilter::class          => true,
		ProductRepository::class      => true,
		MetaBoxInterface::class       => true,
		MetaBoxInitializer::class     => true,
		ViewFactory::class            => true,
		DebugLogger::class            => true,
		MerchantStatuses::class       => true,
		PhoneVerification::class      => true,
		ContactInformation::class     => true,
		MerchantCenterService::class  => true,
		TargetAudience::class         => true,
		MerchantAccountState::class   => true,
		AdsAccountState::class        => true,
		DBInstaller::class            => true,
		AttributeManager::class       => true,
		ProductFactory::class         => true,
		AttributesTab::class          => true,
		VariationsAttributes::class   => true,
		DeprecatedFilters::class      => true,
		ShippingZone::class           => true,
		AdsAccountService::class      => true,
		MerchantAccountService::class => true,
	];

	/**
	 * Use the register method to register items with the container via the
	 * protected $this->leagueContainer property or the `getLeagueContainer` method
	 * from the ContainerAwareTrait.
	 *
	 * @return void
	 */
	public function register(): void {
		$this->conditionally_share_with_tags( DebugLogger::class );

		// Share our interfaces, possibly with concrete objects.
		$this->share_concrete( AssetsHandlerInterface::class, AssetsHandler::class );
		$this->share_concrete( TransientsInterface::class, Transients::class );
		$this->share_concrete(
			TracksInterface::class,
			$this->share_with_tags( Tracks::class, TracksProxy::class )
		);

		// Set up Options, and inflect classes that need options.
		$this->share_concrete( OptionsInterface::class, Options::class );
		$this->getLeagueContainer()
			 ->inflector( OptionsAwareInterface::class )
			 ->invokeMethod( 'set_options_object', [ OptionsInterface::class ] );

		// Share helper classes, and inflect classes that need it.
		$this->share_with_tags( GoogleHelper::class, WC::class );
		$this->getLeagueContainer()
			 ->inflector( GoogleHelperAwareInterface::class )
			 ->invokeMethod( 'set_google_helper_object', [ GoogleHelper::class ] );

		// Set up the TargetAudience service.
		$this->share_with_tags( TargetAudience::class, WC::class, OptionsInterface::class, GoogleHelper::class );

		// Set up MerchantCenter service, and inflect classes that need it.
		$this->share_with_tags( MerchantCenterService::class );
		$this->getLeagueContainer()
			 ->inflector( MerchantCenterAwareInterface::class )
			 ->invokeMethod( 'set_merchant_center_object', [ MerchantCenterService::class ] );

		// Set up Ads service, and inflect classes that need it.
		$this->share_with_tags( AdsAccountState::class );
		$this->share_with_tags( AdsService::class, AdsAccountState::class );
		$this->getLeagueContainer()
			 ->inflector( AdsAwareInterface::class )
			 ->invokeMethod( 'set_ads_object', [ AdsService::class ] );

		// Set up the installer.
		$installer_definition = $this->share_with_tags(
			Installer::class,
			InstallableInterface::class,
			FirstInstallInterface::class,
			WP::class
		);
		$installer_definition->setConcrete(
			function ( ...$arguments ) {
				return new Installer( ...$arguments );
			}
		);

		// Share utility classes
		$this->share_with_tags( AddressUtility::class );
		$this->share_with_tags( DateTimeUtility::class );
		$this->share_with_tags( ISOUtility::class, ISO3166DataProvider::class );

		// Share our regular service classes.
		$this->conditionally_share_with_tags(
			Admin::class,
			AssetsHandlerInterface::class,
			PHPViewFactory::class,
			MerchantCenterService::class,
			AdsService::class
		);
		$this->conditionally_share_with_tags( ActivationRedirect::class, WP::class );
		$this->conditionally_share_with_tags( GetStarted::class );
		$this->conditionally_share_with_tags( SetupMerchantCenter::class );
		$this->conditionally_share_with_tags( SetupAds::class );
		$this->conditionally_share_with_tags( Dashboard::class );
		$this->conditionally_share_with_tags( Reports::class );
		$this->conditionally_share_with_tags( ProductFeed::class );
		$this->conditionally_share_with_tags( Settings::class );
		$this->conditionally_share_with_tags( TrackerSnapshot::class );
		$this->conditionally_share_with_tags( EventTracking::class, ContainerInterface::class );
		$this->conditionally_share_with_tags( RESTControllers::class, ContainerInterface::class );
		$this->conditionally_share_with_tags( ConnectionTest::class, ContainerInterface::class );
		$this->conditionally_share_with_tags( CompleteSetup::class, AssetsHandlerInterface::class );
		$this->conditionally_share_with_tags( GlobalSiteTag::class, GoogleGtagJs::class, WP::class );
		$this->share_with_tags( SiteVerificationMeta::class );
		$this->conditionally_share_with_tags( MerchantSetupCompleted::class );
		$this->conditionally_share_with_tags( AdsSetupCompleted::class );
		$this->conditionally_share_with_tags( AdsAccountService::class, ContainerInterface::class );
		$this->conditionally_share_with_tags( MerchantAccountService::class, ContainerInterface::class );

		// Inbox Notes
		$this->share_with_tags( ContactInformationNote::class );
		$this->share_with_tags( CompleteSetupNote::class );
		$this->share_with_tags( ReconnectWordPressNote::class, GoogleConnection::class );
		$this->share_with_tags( ReviewAfterClicksNote::class, MerchantMetrics::class, WP::class );
		$this->share_with_tags( ReviewAfterConversionsNote::class, MerchantMetrics::class, WP::class );
		$this->share_with_tags( SetupCampaignNote::class, MerchantCenterService::class );
		$this->share_with_tags( SetupCampaign2Note::class, MerchantCenterService::class );
		$this->share_with_tags( SetupCouponSharingNote::class, MerchantStatuses::class );
		$this->share_with_tags( NoteInitializer::class, ActionScheduler::class );

		// Product attributes
		$this->conditionally_share_with_tags( AttributeManager::class );
		$this->conditionally_share_with_tags( AttributesTab::class, Admin::class, AttributeManager::class, MerchantCenterService::class );
		$this->conditionally_share_with_tags( VariationsAttributes::class, Admin::class, AttributeManager::class, MerchantCenterService::class );

		$this->share_with_tags( MerchantAccountState::class );
		$this->share_with_tags( MerchantStatuses::class );
		$this->share_with_tags( PhoneVerification::class, Merchant::class, WP::class, ISOUtility::class );
		$this->share_with_tags( ContactInformation::class, Merchant::class, GoogleSettings::class );
		$this->share_with_tags( ProductMetaHandler::class );
		$this->share( ProductHelper::class, ProductMetaHandler::class, WC::class, TargetAudience::class );
		$this->share_with_tags( ProductFilter::class, ProductHelper::class );
		$this->share_with_tags( ProductRepository::class, ProductMetaHandler::class, ProductFilter::class );
		$this->share_with_tags( ProductFactory::class, AttributeManager::class, WC::class );
		$this->share_with_tags(
			BatchProductHelper::class,
			ProductMetaHandler::class,
			ProductHelper::class,
			ValidatorInterface::class,
			ProductFactory::class,
			TargetAudience::class
		);
		$this->share_with_tags(
			ProductSyncer::class,
			GoogleProductService::class,
			BatchProductHelper::class,
			ProductHelper::class,
			MerchantCenterService::class,
			WC::class
		);

		// Coupon management classes
		$this->share_with_tags( CouponMetaHandler::class );
		$this->share_with_tags(
			CouponHelper::class,
			CouponMetaHandler::class,
			WC::class,
			MerchantCenterService::class
		);
		$this->share_with_tags(
			CouponSyncer::class,
			GooglePromotionService::class,
			CouponHelper::class,
			ValidatorInterface::class,
			MerchantCenterService::class,
			TargetAudience::class,
			WC::class
		);

		// Set up inflector for tracks classes.
		$this->getLeagueContainer()
			 ->inflector( TracksAwareInterface::class )
			 ->invokeMethod( 'set_tracks', [ TracksInterface::class ] );

		// Share admin meta boxes
		$this->conditionally_share_with_tags( ChannelVisibilityMetaBox::class, Admin::class, ProductMetaHandler::class, ProductHelper::class, MerchantCenterService::class );
		$this->conditionally_share_with_tags( CouponChannelVisibilityMetaBox::class, Admin::class, CouponMetaHandler::class, CouponHelper::class, MerchantCenterService::class, TargetAudience::class );
		$this->conditionally_share_with_tags( MetaBoxInitializer::class, Admin::class, MetaBoxInterface::class );

		// Share bulk edit views
		$this->share_with_tags( CouponBulkEdit::class, CouponMetaHandler::class, MerchantCenterService::class, TargetAudience::class );
		$this->share_with_tags( BulkEditInitializer::class );

		$this->share_with_tags( PHPViewFactory::class );

		// Share other classes.
		$this->share_with_tags( ActivatedEvents::class, $_SERVER );
		$this->share_with_tags( SiteVerificationEvents::class );
		$this->share_with_tags( SiteClaimEvents::class );

		$this->conditionally_share_with_tags( InstallTimestamp::class );
		$this->conditionally_share_with_tags( ClearProductStatsCache::class, MerchantStatuses::class );

		$this->share_with_tags( TableManager::class, 'db_table' );
		$this->share_with_tags( DBInstaller::class, TableManager::class, Migrator::class );

		$this->share_with_tags( DeprecatedFilters::class );

		$this->share_with_tags( ShippingZone::class, WC::class, GoogleHelper::class );
		$this->share_with_tags( RequestReviewStatuses::class );

	}
}
