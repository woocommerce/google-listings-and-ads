<?php


namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Merchant;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter\PhoneNumberController;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantVerification;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\ContainerAwareUnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\MerchantTrait;
use PHPUnit\Framework\MockObject\MockObject;
use WP_REST_Request;

defined( 'ABSPATH' ) || exit;

/**
 * Class PhoneNumberControllerTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\MerchantCenter
 *
 * @since x.x.x
 * @property MockObject|MerchantVerification $merchant_verification
 * @property MockObject|Merchant $merchant
 * @property RESTServer $rest_server
 * @property PhoneNumberController $phone_number_controller
 */
class PhoneNumberControllerTest extends ContainerAwareUnitTest {

	use MerchantTrait;

	const ROUTE = '/wc/gla/mc/phone-number';

	/**
	 * Runs before each test is executed.
	 */
	public function setUp() {
		parent::setUp();
		$this->merchant_verification = $this->createMock( MerchantVerification::class );
		$this->rest_server = $this->container->get( RESTServer::class );
		$this->phone_number_controller = new PhoneNumberController( $this->rest_server, $this->merchant_verification );

		$this->options = $this->createMock( OptionsInterface::class );
		$this->phone_number_controller->set_options_object( $this->options );

		do_action( 'rest_api_init' );
		$this->login_as_administrator();
	}

	public function test_register_route(  ) {
		$this->assertArrayHasKey( self::ROUTE, $this->rest_server->get_routes() );
	}

	public function test_phone_number_read_endpoint() {
		$this->merchant_verification->expects( $this->any() )
									->method( 'get_phone_number' )
									->willReturn(  $this->valid_account_phone_number );

		$this->options->expects( $this->any() )
					  ->method( 'get_merchant_id' )
					  ->willReturn(  $this->valid_account_id );

		$reflection = new \ReflectionClass(get_class($this->phone_number_controller));
		$method = $reflection->getMethod( 'get_phone_number_endpoint_read_callback' );
		$method->setAccessible(true);

		$callback = $method->invokeArgs($this->phone_number_controller,[]);
		/** @var \WP_REST_Response $response */
		$response = $callback( new WP_REST_Request( 'GET', self::ROUTE ));

		$this->assertInstanceOf( \WP_REST_Response::class, $response );
		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertArrayHasKey( 'phone_number',  $data );
		$this->assertEquals( $this->valid_account_phone_number, $data['phone_number']);
		$this->assertArrayHasKey( 'id', $data );
		$this->assertEquals( $this->valid_account_id, $data['id']);
	}

	public function test_phone_number_read_endpoint_exception() {
		$error_code = 401;
		$this->merchant_verification->expects( $this->any() )
									->method( 'get_phone_number' )
									->willThrowException( $this->get_account_exception( $error_code ) );

		$reflection = new \ReflectionClass(get_class($this->phone_number_controller));
		$method = $reflection->getMethod( 'get_phone_number_endpoint_read_callback' );
		$method->setAccessible(true);

		$callback = $method->invokeArgs($this->phone_number_controller,[]);
		/** @var \WP_REST_Response $response */
		$response = $callback( new WP_REST_Request( 'GET', self::ROUTE ));

		$this->assertInstanceOf( \WP_REST_Response::class, $response );
		$this->assertEquals( $error_code, $response->get_status() );
		$data = $response->get_data();
		$this->assertArrayHasKey( 'message',  $data );
		$this->assertEquals( $this->get_account_exception()->getMessage(), $data['message']);
	}

	public function test_phone_number_edit_endpoint_exception() {
		$this->merchant_verification->expects( $this->any() )
									->method( 'get_phone_number' )
									->willThrowException( $this->get_account_exception() );

		$reflection = new \ReflectionClass(get_class($this->phone_number_controller));
		$method = $reflection->getMethod( 'get_phone_number_endpoint_read_callback' );
		$method->setAccessible(true);

		$callback = $method->invokeArgs($this->phone_number_controller,[]);
		/** @var \WP_REST_Response $response */
		$response = $callback( new WP_REST_Request( 'GET', self::ROUTE ));

		$this->assertInstanceOf( \WP_REST_Response::class, $response );
		$this->assertEquals( 400, $response->get_status() );
		$data = $response->get_data();
		$this->assertArrayHasKey( 'message',  $data );
		$this->assertEquals( $this->get_account_exception()->getMessage(), $data['message']);
	}

}
