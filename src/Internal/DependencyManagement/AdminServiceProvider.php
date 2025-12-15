<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Internal\DependencyManagement;

use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Admin;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\BulkEdit\BulkEditInitializer;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\BulkEdit\CouponBulkEdit;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\MetaBox\ChannelVisibilityMetaBox;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\MetaBox\CouponChannelVisibilityMetaBox;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\MetaBox\MetaBoxInitializer;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\MetaBox\MetaBoxInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Redirect;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\SystemStatusService;
use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AdsService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\WP\NotificationsService;
use Automattic\WooCommerce\GoogleListingsAndAds\Assets\AssetsHandlerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\ConnectionTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Coupon\CouponHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Coupon\CouponMetaHandler;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\AdminConditional;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Conditional;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Menu\AttributeMapping;
use Automattic\WooCommerce\GoogleListingsAndAds\Menu\Dashboard;
use Automattic\WooCommerce\GoogleListingsAndAds\Menu\NotificationManager;
use Automattic\WooCommerce\GoogleListingsAndAds\Menu\GetStarted;
use Automattic\WooCommerce\GoogleListingsAndAds\Menu\ProductFeed;
use Automattic\WooCommerce\GoogleListingsAndAds\Menu\Reports;
use Automattic\WooCommerce\GoogleListingsAndAds\Menu\Settings;
use Automattic\WooCommerce\GoogleListingsAndAds\Menu\SetupAds;
use Automattic\WooCommerce\GoogleListingsAndAds\Menu\SetupMerchantCenter;
use Automattic\WooCommerce\GoogleListingsAndAds\Menu\Shipping;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\TargetAudience;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductMetaHandler;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;
use Automattic\WooCommerce\GoogleListingsAndAds\View\PHPViewFactory;

/**
 * Class AdminServiceProvider
 * Provides services which are only required for the WP admin dashboard.
 *
 * Note: These services will not be available in a REST API request.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Internal\DependencyManagement
 */
class AdminServiceProvider extends AbstractServiceProvider implements Conditional {

	use AdminConditional;

	/**
	 * @var array
	 */
	protected $provides = [
		Admin::class               => true,
		AttributeMapping::class    => true,
		BulkEditInitializer::class => true,
		ConnectionTest::class      => true,
		CouponBulkEdit::class      => true,
		Dashboard::class           => true,
		NotificationManager::class => true,
		GetStarted::class          => true,
		MetaBoxInterface::class    => true,
		MetaBoxInitializer::class  => true,
		ProductFeed::class         => true,
		Redirect::class            => true,
		Reports::class             => true,
		Settings::class            => true,
		SetupAds::class            => true,
		SetupMerchantCenter::class => true,
		Shipping::class            => true,
		SystemStatusService::class => true,
		Service::class             => true,
	];

	/**
	 * Use the register method to register items with the container via the
	 * protected $this->container property or the `getContainer` method
	 * from the ContainerAwareTrait.
	 *
	 * @return void
	 */
	public function register(): void {
		$this->share_with_tags(
			Admin::class,
			AssetsHandlerInterface::class,
			PHPViewFactory::class,
			MerchantCenterService::class,
			AdsService::class
		);
		$this->share_with_tags( PHPViewFactory::class );
		$this->share_with_tags( Redirect::class, WP::class );

		// Share bulk edit views
		$this->share_with_tags( CouponBulkEdit::class, CouponMetaHandler::class, MerchantCenterService::class, TargetAudience::class );
		$this->share_with_tags( BulkEditInitializer::class );

		// Share admin meta boxes
		$this->share_with_tags( ChannelVisibilityMetaBox::class, Admin::class, ProductMetaHandler::class, ProductHelper::class, MerchantCenterService::class );
		$this->share_with_tags( CouponChannelVisibilityMetaBox::class, Admin::class, CouponMetaHandler::class, CouponHelper::class, MerchantCenterService::class, TargetAudience::class );
		$this->share_with_tags( MetaBoxInitializer::class, Admin::class, MetaBoxInterface::class );

		$this->share_with_tags( ConnectionTest::class );

		$this->share_with_tags( AttributeMapping::class );
		$this->share_with_tags( Dashboard::class );
		$this->share_with_tags( NotificationManager::class, AssetsHandlerInterface::class );
		$this->share_with_tags( GetStarted::class );
		$this->share_with_tags( ProductFeed::class );
		$this->share_with_tags( Reports::class );
		$this->share_with_tags( Settings::class );
		$this->share_with_tags( SetupAds::class );
		$this->share_with_tags( SetupMerchantCenter::class );
		$this->share_with_tags( Shipping::class );
		$this->share_with_tags( SystemStatusService::class, NotificationsService::class, MerchantCenterService::class );
	}
}
