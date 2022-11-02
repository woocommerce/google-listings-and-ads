<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Merchant;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Settings;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter\ContactInformationController;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\ContactInformation;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\ContainerAwareUnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\MerchantTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Utility\AddressUtility;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class PhoneNumberControllerTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\MerchantCenter
 *
 * @property MockObject|ContactInformation $contact_information
 * @property MockObject|Settings           $google_settings
 * @property MockObject|Merchant           $merchant
 * @property MockObject|OptionsInterface   $options
 * @property MockObject|AddressUtility     $address_utility
 * @property RESTServer                    $rest_server
 * @property ContactInformationController  $contact_information_controller
 */
class ContactInformationControllerTest extends ContainerAwareUnitTest {

	use MerchantTrait;

	protected const ROUTE = '/wc/gla/mc/contact-information';

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->contact_information            = $this->createMock( ContactInformation::class );
		$this->google_settings                = $this->createMock( Settings::class );
		$this->address_utility                = $this->createMock( AddressUtility::class );
		$this->rest_server                    = $this->container->get( RESTServer::class );
		$this->contact_information_controller = new ContactInformationController( $this->rest_server, $this->contact_information, $this->google_settings, $this->address_utility );

		$this->options = $this->createMock( OptionsInterface::class );
		$this->contact_information_controller->set_options_object( $this->options );

		do_action( 'rest_api_init' );
		$this->login_as_administrator();
	}

	public function test_register_route() {
		$this->assertArrayHasKey( self::ROUTE, $this->rest_server->get_routes() );
	}
}
