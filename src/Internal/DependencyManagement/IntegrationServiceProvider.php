<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Internal\DependencyManagement;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\ValidateInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Integration\IntegrationInitializer;
use Automattic\WooCommerce\GoogleListingsAndAds\Integration\IntegrationInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Integration\WooCommerceBrands;
use Automattic\WooCommerce\GoogleListingsAndAds\Integration\WooCommercePreOrders;
use Automattic\WooCommerce\GoogleListingsAndAds\Integration\WooCommerceProductBundles;
use Automattic\WooCommerce\GoogleListingsAndAds\Integration\WPCOMProxy;
use Automattic\WooCommerce\GoogleListingsAndAds\Integration\YoastWooCommerceSeo;
use Automattic\WooCommerce\GoogleListingsAndAds\Integration\JetpackWPCOM;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\AttributeManager;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;

defined( 'ABSPATH' ) || exit;

/**
 * Class IntegrationServiceProvider
 *
 * Provides the integration classes and their related services to the container.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Internal\DependencyManagement
 */
class IntegrationServiceProvider extends AbstractServiceProvider {

	use ValidateInterface;

	/**
	 * @var array
	 */
	protected $provides = [
		Service::class                => true,
		IntegrationInterface::class   => true,
		IntegrationInitializer::class => true,
	];

	/**
	 * @return void
	 */
	public function register(): void {
		$this->share_with_tags( YoastWooCommerceSeo::class );
		$this->share_with_tags( WooCommerceBrands::class, WP::class );
		$this->share_with_tags( WooCommerceProductBundles::class, AttributeManager::class );
		$this->share_with_tags( WooCommercePreOrders::class, ProductHelper::class );
		$this->conditionally_share_with_tags( JetpackWPCOM::class );
		$this->share_with_tags( WPCOMProxy::class );

		$this->share_with_tags(
			IntegrationInitializer::class,
			IntegrationInterface::class
		);
	}
}
