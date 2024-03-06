<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\DB;

use Automattic\WooCommerce\GoogleListingsAndAds\DB\ProductFeedQueryHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\UpdateMerchantProductStatuses;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantStatuses;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\MCStatus;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\Container\Container;
use PHPUnit\Framework\MockObject\MockObject;
use WC_Helper_Product;
use WP_REST_Request;
use wpdb;

/**
 * Class ProductFeedQueryHelperTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\DB
 */
class ProductFeedQueryHelperTest extends UnitTest {

	/** @var MockObject|wpdb $wpdb */
	protected $wpdb;

	/** @var MockObject|ProductRepository $product_repository */
	protected $product_repository;

	/** @var MockObject|MerchantCenterService $mc_service */
	protected $mc_service;

	/** @var MockObject|MerchantStatuses $merchant_statuses */
	protected $merchant_statuses;

	/** @var MockObject|ProductHelper $product_helper */
	protected $product_helper;

	/** @var MockObject|WP_REST_Request $request */
	protected $request;

	/** @var MockObject|UpdateMerchantProductStatuses $job */
	protected $job;

	/** @var ProductFeedQueryHelper $product_feed_query_helper */
	protected $product_feed_query_helper;

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
		$this->job                = $this->createMock( UpdateMerchantProductStatuses::class );

		$container = new Container();
		$container->share( MerchantCenterService::class, $this->mc_service );
		$container->share( MerchantStatuses::class, $this->merchant_statuses );
		$container->share( ProductHelper::class, $this->product_helper );

		$this->product_feed_query_helper = new ProductFeedQueryHelper( $this->wpdb, $this->product_repository );
		$this->product_feed_query_helper->set_container( $container );
	}

	public function test_get_product_feed_merchant_center_is_connected() {
		$product = WC_Helper_Product::create_simple_product();

		$this->job->expects( $this->exactly( 1 ) )
			->method( 'is_scheduled' )
			->willReturn( false );

		$this->mc_service->expects( $this->any() )
			->method( 'is_connected' )
			->willReturn( true );

		$this->merchant_statuses->expects( $this->exactly( 1 ) )
			->method( 'maybe_refresh_status_data' )
			->willReturn( $this->job );

		$this->product_repository->expects( $this->exactly( 1 ) )
			->method( 'find' )
			->willReturn( [ $product ] );

			$this->product_helper->expects( $this->exactly( 1 ) )
			->method( 'get_mc_status' )
			->with( $product )
			->willReturn( MCStatus::APPROVED );

		$response = $this->product_feed_query_helper->get( $this->request );

		$this->assertEquals(
			[
				[
					'id'        => $product->get_id(),
					'title'     => $product->get_name(),
					'visible'   => true,
					'status'    => MCStatus::APPROVED,
					'image_url' => wp_get_attachment_image_url( $product->get_image_id(), 'full' ),
					'price'     => $product->get_price(),
					'errors'    => [],
				],
			],
			$response
		);
	}

	public function test_get_product_feed_merchant_center_while_the_job_is_scheduled() {
		$product = WC_Helper_Product::create_simple_product();

		$this->mc_service->expects( $this->any() )
			->method( 'is_connected' )
			->willReturn( true );

		$this->job->expects( $this->exactly( 1 ) )
			->method( 'is_scheduled' )
			->willReturn( true );

		$this->merchant_statuses->expects( $this->exactly( 1 ) )
			->method( 'maybe_refresh_status_data' )
			->willReturn( $this->job );

		$this->product_repository->expects( $this->exactly( 1 ) )
			->method( 'find' )
			->willReturn( [ $product ] );

		$this->product_helper->expects( $this->exactly( 1 ) )
			->method( 'get_mc_status' )
			->with( $product )
			->willReturn( MCStatus::APPROVED );

		$response = $this->product_feed_query_helper->get( $this->request );

		$this->assertEquals(
			[
				[
					'id'        => $product->get_id(),
					'title'     => $product->get_name(),
					'visible'   => true,
					'status'    => '-',
					'image_url' => wp_get_attachment_image_url( $product->get_image_id(), 'full' ),
					'price'     => $product->get_price(),
					'errors'    => [],
				],
			],
			$response
		);
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
