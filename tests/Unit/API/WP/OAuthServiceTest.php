<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\WP;

use Automattic\WooCommerce\GoogleListingsAndAds\API\WP\OAuthService;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\Container\Container;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Deactivateable;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\Jetpack;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\AccountService;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\TrackingTrait;
use PHPUnit\Framework\MockObject\MockObject;
use WP_Error;
use Exception;
use Jetpack_Options;

defined( 'ABSPATH' ) || exit;

/**
 * Class OAuthServiceTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\WP
 */
class OAuthServiceTest extends UnitTest {

	use TrackingTrait;

	/**
	 * @var OAuthService
	 */
	protected $service;

	/**
	 * @var Container
	 */
	protected $container;

	/**
	 * @var OptionsInterface|MockObject
	 */
	protected $options;

	/**
	 * @var Jetpack|MockObject
	 */
	protected $jp;

	/**
	 * @var AccountService|MockObject
	 */
	protected $account_service;


	protected const DUMMY_BLOG_ID = '123';

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->container       = new Container();
		$this->options         = $this->createMock( OptionsInterface::class );
		$this->jp              = $this->createMock( Jetpack::class );
		$this->account_service = $this->createMock( AccountService::class );

		// Mock the Blog ID from Jetpack.
		add_filter(
			'jetpack_options',
			function ( $value, $name ) {
				if ( $name === 'id' ) {
					return self::DUMMY_BLOG_ID;
				}

				return $value;
			},
			10,
			2
		);

		$this->container->addShared( Jetpack::class, $this->jp );
		$this->container->addShared( AccountService::class, $this->account_service );
		$this->service = new OAuthService();
		$this->service->set_options_object( $this->options );
		$this->service->set_container( $this->container );
	}

	public function test_deactivate_does_not_call_wpcom_api_when_jetpack_not_connected() {
		$this->assertInstanceOf( Deactivateable::class, $this->service );

		$this->jp->expects( $this->never() )
			->method( 'remote_request' );

		$this->account_service->expects( $this->never() )
			->method( 'reset_wpcom_api_authorization_data' );

		$this->service->deactivate();

		$this->assertEquals( 0, did_action( 'woocommerce_gla_error' ) );
	}

	public function test_deactivation_ok() {
		$this->assertInstanceOf( Deactivateable::class, $this->service );

		// Mock the options to return true for Jetpack connected.
		$this->options->expects( $this->once() )->method( 'get' )->with( OptionsInterface::JETPACK_CONNECTED )->willReturn( true );

		$this->jp->expects( $this->once() )
			->method( 'remote_request' )->willReturn(
				[
					'body'     => '{"success":true}',
					'response' => [ 'code' => 200 ],
				]
			);

		$this->account_service->expects( $this->once() )
			->method( 'reset_wpcom_api_authorization_data' );

		$this->service->deactivate();
	}

	public function test_deactivation_with_wp_error() {
		$this->assertInstanceOf( Deactivateable::class, $this->service );

		// Mock the options to return true for Jetpack connected.
		$this->options->expects( $this->once() )->method( 'get' )->with( OptionsInterface::JETPACK_CONNECTED )->willReturn( true );

		$this->jp->expects( $this->once() )
			->method( 'remote_request' )->willReturn( new WP_Error( 'error', 'error message' ) );

		$this->account_service->expects( $this->never() )
			->method( 'reset_wpcom_api_authorization_data' );

		// The exception should be caught and ignored.
		$this->service->deactivate();

		$this->assertEquals( 1, did_action( 'woocommerce_gla_error' ) );
	}

	public function test_revoke_wpcom_api_auth() {
		$this->jp->expects( $this->once() )
			->method( 'remote_request' )
			->willReturn(
				[
					'body'     => '{"success":true}',
					'response' => [ 'code' => 200 ],
				]
			);

		$this->account_service->expects( $this->once() )
			->method( 'reset_wpcom_api_authorization_data' );

		$this->account_service->expects( $this->once() )
			->method( 'reset_wpcom_api_authorization_data' );

		$this->expect_track_event(
			'revoke_wpcom_api_authorization',
			[
				'status'  => 200,
				'blog_id' => Jetpack_Options::get_option( 'id' ),
			]
		);

		$response = $this->service->revoke_wpcom_api_auth();

		$this->assertEquals( '{"success":true}', $response );
	}

	public function test_revoke_wpcom_api_auth_wp_error() {
		$this->jp->expects( $this->once() )
			->method( 'remote_request' )
			->willReturn(
				new WP_Error( 'error', 'error message' )
			);

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'error message' );
		$this->expectExceptionCode( 400 );

		$this->account_service->expects( $this->never() )
			->method( 'reset_wpcom_api_authorization_data' );

		$this->expect_track_event(
			'revoke_wpcom_api_authorization',
			[
				'status'  => 400,
				'error'   => 'error message',
				'blog_id' => Jetpack_Options::get_option( 'id' ),
			]
		);

		$this->service->revoke_wpcom_api_auth();
	}

	public function test_revoke_wpcom_api_auth_status_error() {
		$this->jp->expects( $this->once() )
			->method( 'remote_request' )
			->willReturn(
				[
					'body'     => '{"message":"error message"}',
					'response' => [ 'code' => 400 ],
				]
			);

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'error message' );
		$this->expectExceptionCode( 400 );

		$this->account_service->expects( $this->never() )
			->method( 'reset_wpcom_api_authorization_data' );

		$this->expect_track_event(
			'revoke_wpcom_api_authorization',
			[
				'status'  => 400,
				'error'   => 'error message',
				'blog_id' => Jetpack_Options::get_option( 'id' ),
			]
		);

		$this->service->revoke_wpcom_api_auth();
	}
}
