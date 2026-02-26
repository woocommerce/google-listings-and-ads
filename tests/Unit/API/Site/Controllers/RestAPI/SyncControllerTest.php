<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\RestAPI;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\RestAPI\SyncController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\WP\NotificationsService;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\RESTControllerUnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\OptionsTrait;

/**
 * Class SyncControllerTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\RestAPI
 */
class SyncControllerTest extends RESTControllerUnitTest {

	use OptionsTrait;

	protected const ROUTE = '/wc/gla/sync';

	public function setUp(): void {
		parent::setUp();

		$notifications_service = $this->getMockBuilder( NotificationsService::class )
			->disableOriginalConstructor()
			->setMethodsExcept( [ 'set_options_object', 'get_current_sync_mode', 'get_default_sync_mode' ] )
			->getMock();

		$this->controller = new SyncController( $this->server, $notifications_service );
		$options          = $this->create_options_mock();

		$notifications_service->set_options_object( $options );
		$this->controller->set_options_object( $options );
		$this->controller->register();
	}

	public function test_get_sync_mode() {
		$response = $this->do_request( self::ROUTE );

		$this->assertEquals(
			[
				'products' => [
					'push' => true,
					'pull' => false,
				],
				'coupons'  => [
					'push' => true,
					'pull' => false,
				],
				'shipping' => [
					'push' => true,
					'pull' => false,
				],
				'settings' => [
					'push' => true,
					'pull' => false,
				],
			],
			$response->get_data()
		);
	}

	public function test_post_sync_mode_with_partial_configs() {
		$response = $this->do_request(
			self::ROUTE,
			'POST',
			[
				'products' => [
					'push' => false,
					'pull' => false,
				],
				'coupons'  => [
					'push' => false,
					'pull' => false,
				],
			]
		);

		$this->assertEquals(
			[
				'products' => [
					'push' => false,
					'pull' => false,
				],
				'coupons'  => [
					'push' => false,
					'pull' => false,
				],
				'shipping' => [
					'push' => true,
					'pull' => false,
				],
				'settings' => [
					'push' => true,
					'pull' => false,
				],
			],
			$response->get_data()
		);
	}
	public function test_post_sync_mode_with_partial_modes() {
		$response = $this->do_request(
			self::ROUTE,
			'POST',
			[
				'shipping' => [ 'push' => false ],
				'settings' => [ 'pull' => false ],
			]
		);

		$this->assertEquals(
			[
				'products' => [
					'push' => true,
					'pull' => false,
				],
				'coupons'  => [
					'push' => true,
					'pull' => false,
				],
				'shipping' => [
					'push' => false,
					'pull' => false,
				],
				'settings' => [
					'push' => true,
					'pull' => false,
				],
			],
			$response->get_data()
		);
	}

	public function test_post_sync_mode_with_full_configs() {
		$response = $this->do_request(
			self::ROUTE,
			'POST',
			[
				'products' => [
					'push' => true,
					'pull' => false,
				],
				'coupons'  => [
					'push' => true,
					'pull' => false,
				],
				'shipping' => [
					'push' => true,
					'pull' => false,
				],
				'settings' => [
					'push' => true,
					'pull' => false,
				],
			]
		);

		$this->assertEquals(
			[
				'products' => [
					'push' => true,
					'pull' => false,
				],
				'coupons'  => [
					'push' => true,
					'pull' => false,
				],
				'shipping' => [
					'push' => true,
					'pull' => false,
				],
				'settings' => [
					'push' => true,
					'pull' => false,
				],
			],
			$response->get_data()
		);

		$response = $this->do_request(
			self::ROUTE,
			'POST',
			[
				'products' => [
					'push' => false,
					'pull' => true,
				],
				'coupons'  => [
					'push' => false,
					'pull' => true,
				],
				'shipping' => [
					'push' => false,
					'pull' => true,
				],
				'settings' => [
					'push' => false,
					'pull' => true,
				],
			]
		);

		$this->assertEquals(
			[
				'products' => [
					'push' => false,
					'pull' => false,
				],
				'coupons'  => [
					'push' => false,
					'pull' => false,
				],
				'shipping' => [
					'push' => false,
					'pull' => false,
				],
				'settings' => [
					'push' => false,
					'pull' => false,
				],
			],
			$response->get_data()
		);
	}

	public function test_post_sync_mode_ignore_invalid_params() {
		$response = $this->do_request(
			self::ROUTE,
			'POST',
			[
				'test_invalid_config' => [
					'push' => false,
					'pull' => false,
				],
				'coupons'             => [
					'test_invalid_mode' => false,
					'pull'              => false,
				],
			]
		);

		$this->assertEquals(
			[
				'products' => [
					'push' => true,
					'pull' => false,
				],
				'coupons'  => [
					'push' => true,
					'pull' => false,
				],
				'shipping' => [
					'push' => true,
					'pull' => false,
				],
				'settings' => [
					'push' => true,
					'pull' => false,
				],
			],
			$response->get_data()
		);
	}

	public function test_post_sync_mode_do_action() {
		$spy = $this->getMockBuilder( \stdClass::class )
			->addMethods( [ 'callback' ] )
			->getMock();

		$spy->expects( $this->once() )
			->method( 'callback' )
			->with(
				$this->equalTo(
					[
						'products' => [
							'push' => true,
							'pull' => false,
						],
						'coupons'  => [
							'push' => true,
							'pull' => false,
						],
						'shipping' => [
							'push' => true,
							'pull' => false,
						],
						'settings' => [
							'push' => true,
							'pull' => false,
						],
					]
				),
				$this->equalTo(
					[
						'products' => [
							'push' => false,
							'pull' => false,
						],
						'coupons'  => [
							'push' => true,
							'pull' => false,
						],
						'shipping' => [
							'push' => false,
							'pull' => false,
						],
						'settings' => [
							'push' => true,
							'pull' => false,
						],
					]
				)
			);

		add_action( 'woocommerce_gla_sync_mode_updated', [ $spy, 'callback' ], 10, 2 );

		$this->do_request(
			self::ROUTE,
			'POST',
			[
				'products' => [
					'push' => false,
					'pull' => false,
				],
				'shipping' => [
					'push' => false,
					'pull' => false,
				],
			]
		);
	}
}
