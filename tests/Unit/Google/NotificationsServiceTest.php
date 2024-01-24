<?php
	declare( strict_types=1 );

	namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Google;

	use Automattic\WooCommerce\GoogleListingsAndAds\Google\NotificationsService;
	use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductHelper;
	use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductRepository;
	use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
	use Automattic\WooCommerce\GoogleListingsAndAds\Value\NotificationStatus;
	use PHPUnit\Framework\MockObject\MockObject;
	use WC_Helper_Product;

	defined( 'ABSPATH' ) || exit;

	/**
	 * Class NotificationsServiceTest
	 * @group NotificationsService
	 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Google
	 */
	class NotificationsServiceTest extends UnitTest {

		/**
		 * @var NotificationsService
		 */
		public $service;

		/**
		 * @var MockObject|ProductHelper
		 */
		public $product_helper;

		/**
		 * @var MockObject|ProductRepository
		 */
		public $product_repository;

		const DUMMY_BLOG_ID = "123";

		/**
		 * Runs before each test is executed.
		 */
		public function setUp(): void {
			parent::setUp();

			$this->product_helper     = $this->createMock( ProductHelper::class );
			$this->product_repository = $this->createMock( ProductRepository::class );

			// Mock the Blog ID from Jetpack
			add_filter('jetpack_options', function ( $value, $name ) {
				if ( $name === 'id' ) {
					return self::DUMMY_BLOG_ID;
				}

				return $value;
			}, 10, 2 );

		}

		/**
		 * Test if the route is correct
		 */
		public function test_route() {
			$this->service = $this->get_mock( [ $this->product_repository, $this->product_helper ], [] );


			$blog_id = self::DUMMY_BLOG_ID;
			$this->assertEquals( $this->service->get_route(), "https://public-api.wordpress.com/wpcom/v2/sites/{$blog_id}/partners/google/notifications" );
		}

		/**
		 * Test set status
		 */
		public function test_set_status() {
			/**
			 * @var \WC_Product $product
			 */
			$product = WC_Helper_Product::create_simple_product();
			$this->product_helper->expects( $this->once() )->method( 'get_wc_product' )->with( $product->get_id() )->willReturn( $product );
			$this->product_helper->expects( $this->once() )->method( 'set_notification_status' )->with( $product, NotificationStatus::NOTIFICATION_UPDATED );
			$this->service = $this->get_mock( [ $this->product_repository, $this->product_helper ], [] );

			$this->service->set_status( $product->get_id(), NotificationStatus::NOTIFICATION_UPDATED );
		}


		public function test_get_before_notification_status() {
			$this->service = $this->get_mock( [ $this->product_repository, $this->product_helper ], [] );
			$status = $this->service->get_before_notification_status( NotificationsService::TOPIC_PRODUCT_CREATED );
			$this->assertEquals( $status, NotificationStatus::NOTIFICATION_PENDING_CREATE);

			$status = $this->service->get_before_notification_status( NotificationsService::TOPIC_PRODUCT_DELETED );
			$this->assertEquals( $status, NotificationStatus::NOTIFICATION_PENDING_DELETE);

			$status = $this->service->get_before_notification_status( NotificationsService::TOPIC_PRODUCT_UPDATED );
			$this->assertEquals( $status, NotificationStatus::NOTIFICATION_PENDING_UPDATE);
		}

		public function test_get_after_notification_status() {
			$this->service = $this->get_mock( [ $this->product_repository, $this->product_helper ], [] );
			$status = $this->service->get_after_notification_status( NotificationsService::TOPIC_PRODUCT_CREATED );
			$this->assertEquals( $status, NotificationStatus::NOTIFICATION_CREATED);

			$status = $this->service->get_after_notification_status( NotificationsService::TOPIC_PRODUCT_DELETED );
			$this->assertEquals( $status, NotificationStatus::NOTIFICATION_DELETED);

			$status = $this->service->get_after_notification_status( NotificationsService::TOPIC_PRODUCT_UPDATED );
			$this->assertEquals( $status, NotificationStatus::NOTIFICATION_UPDATED);
		}

		/**
		 * Test notify() function with a call with success response.
		 */
		public function test_notify() {
			$this->service = $this->get_mock( [ $this->product_repository, $this->product_helper ], [ 'do_request', 'set_status' ] );


			$topic   = 'product.create';
			$item_id = 1;

			$args = [
				'method'  => 'POST',
				'timeout' => 30,
				'headers' => [
					'x-woocommerce-topic' => $topic
				],
				'body' => [
					'item_id' => $item_id,
				],
				'url' =>  $this->service->get_route(),
			];

			$this->service->expects( $this->once() )->method( 'do_request' )->with( $args )->willReturn( [ 'code' => 200 ] );
			$this->service->expects( $this->once() )->method( 'set_status' )->with( $item_id, NotificationStatus::NOTIFICATION_CREATED );
			$this->assertTrue( $this->service->notify( $item_id , $topic ) );

		}

		/**
		 * Test filter_product function
		 */
		public function test_filter_product() {
			/**
			 * @var \WC_Product $product
			 */
			$product = WC_Helper_Product::create_simple_product();
			$this->product_repository->expects( $this->once() )->method( 'find_notification_products' )->with( $product->get_id(), NotificationStatus::NOTIFICATION_PENDING_CREATE )->willReturn( [ $product->get_id() ] );
			$this->service = $this->get_mock( [ $this->product_repository, $this->product_helper ], [] );
			$this->service->filter_product( $product->get_id(), NotificationsService::TOPIC_PRODUCT_CREATED );

		}

		/**
		 * Test notify() function with a call with wp_error response.
		 */
		public function test_notify_wp_error() {
			$this->service = $this->get_mock( [ $this->product_repository, $this->product_helper ], [ 'do_request', 'set_status' ] );


			$this->service->expects( $this->once() )->method( 'do_request' )->willReturn( new \WP_Error( 'error', 'error message' ) );
			$this->service->expects( $this->never() )->method( 'set_status' );
			$this->assertFalse( $this->service->notify( 1 , 'topic') );
			$this->assertEquals( did_action( 'woocommerce_gla_error' ), 1 );
		}

		/**
		 * Test notify() function with a call with an error response.
		 */
		public function test_notify_response_error() {

			$this->service = $this->get_mock( [ $this->product_repository, $this->product_helper ], [ 'do_request', 'set_status' ] );

			$this->service->expects( $this->once() )->method( 'do_request' )->willReturn(  [ 'response' => [ 'code' => 400, 'body' => 'Bad request' ] ] );
			$this->service->expects( $this->never() )->method( 'set_status' );
			$this->assertFalse( $this->service->notify( 1 , 'topic') );
			$this->assertEquals( did_action( 'woocommerce_gla_error' ), 1 );
		}

		/**
		 * Mocks the service
		 * @return NotificationsService
		 */
		public function get_mock( $constructor_args, $methods ) {
			return $this->getMockBuilder( NotificationsService::class)
				->setConstructorArgs( $constructor_args )
				->onlyMethods( $methods )
				->getMock();
		}

	}
