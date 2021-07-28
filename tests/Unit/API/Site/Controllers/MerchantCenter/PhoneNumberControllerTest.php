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

defined( 'ABSPATH' ) || exit;

/**
 * Class PhoneNumberControllerTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\MerchantCenter
 *
 * @since x.x.x
 * @property MockObject|MerchantVerification $merchant_verification
 * @property MockObject|Merchant $merchant
 * @property MockObject|OptionsInterface $options
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
}
