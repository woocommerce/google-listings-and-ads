<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\DB;

use Automattic\WooCommerce\GoogleListingsAndAds\DB\ProductFeedQueryHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantStatuses;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\Container\Container;
use WP_REST_Request;
use wpdb;

/**
 * Class ProductFeedQueryHelperTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\DB
 */
class ProductFeedQueryHelperTest extends UnitTest {

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->wpdb               = $this->createMock( wpdb::class );
		$this->product_repository = $this->createMock( ProductRepository::class );
		$this->mc_service         = $this->createMock( MerchantCenterService::class );
		$this->merchant_statuses  = $this->createMock( MerchantStatuses::class );
		$this->product_helper     = $this->createMock( ProductHelper::class );
		$this->request            = $this->createMock( WP_REST_Request::class );

		$container = new Container();
		$container->share( MerchantCenterService::class, $this->mc_service );
		$container->share( MerchantStatuses::class, $this->merchant_statuses );
		$container->share( ProductHelper::class, $this->product_helper );

		$this->product_feed_query_helper = new ProductFeedQueryHelper( $this->wpdb, $this->product_repository );
		$this->product_feed_query_helper->set_container( $container );
	}

	public function test_get_product_feed_merchant_center_is_connected() {
		$this->mc_service->expects( $this->any() )
			->method( 'is_connected' )
			->willReturn( true );

		$this->merchant_statuses->expects( $this->exactly( 1 ) )
			->method( 'maybe_refresh_status_data' );

		$this->product_feed_query_helper->get( $this->request );
	}

	public function test_get_product_feed_merchant_center_is_not_connected() {
		$this->mc_service->expects( $this->any() )
			->method( 'is_connected' )
			->willReturn( false );

		$this->merchant_statuses->expects( $this->exactly( 0 ) )
			->method( 'maybe_refresh_status_data' );

		$this->product_feed_query_helper->get( $this->request );
	}
}
