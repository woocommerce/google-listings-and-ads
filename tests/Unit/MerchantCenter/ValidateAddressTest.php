<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\MerchantCenter;


use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Settings;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\ContainerAwareUnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\MerchantTrait;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Class ContactInformationTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\MerchantCenter
 *
 * @property  MockObject|ContainerInterface $container_interface
 * @property  Settings $google_settings
 */
class ValidateAddressTest extends ContainerAwareUnitTest {

	use MerchantTrait;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->container_interface = $this->createMock( ContainerInterface::class );
		$this->google_settings     = new Settings ( $this->container_interface );

		$this->fields_to_validate = [
			'address_1' => $this->get_sample_address()->getStreetAddress(),
			'city' => $this->get_sample_address()->getLocality(),
			'country' => $this->get_sample_address()->getCountry(),
			'postcode' => $this->get_sample_address()->getPostalCode(),
			'state' => $this->get_sample_address()->getRegion(),
		];

		$this->locale_settings = [];		
	}


	public function test_address_with_no_errors() {

		$errors = $this->google_settings->validate_address( $this->fields_to_validate, $this->locale_settings );

		$this->assertEquals(
			0,
			count( $errors )
		);

	}

	public function test_address_with_empty_postcode_but_is_required() {

		$this->fields_to_validate['postcode'] = '';

		$errors = $this->google_settings->validate_address( $this->fields_to_validate, $this->locale_settings );

		$this->assertEquals(
			1,
			count( $errors )
		);

	}	

	public function test_address_with_empty_postcode_but_is__not_required() {

		$this->fields_to_validate['postcode'] = '';
		$this->locale_settings = [
			"postcode" => [ "required" => false ]
		];			

		$errors = $this->google_settings->validate_address( $this->fields_to_validate, $this->locale_settings );

		$this->assertEquals(
			0,
			count( $errors )
		);

	}
	
	public function test_address_with_multiple_required_empty_fields() {

		$this->fields_to_validate['postcode'] = '';
		$this->fields_to_validate['state'] = '';

		$errors = $this->google_settings->validate_address( $this->fields_to_validate, $this->locale_settings );

		$this->assertEquals(
			2,
			count( $errors )
		);

		$this->assertTrue( in_array ('postcode', $errors) );
		$this->assertTrue( in_array ('state', $errors) );

	}
	
	public function test_address_with_multiple_no_required_empty_fields() {

		$this->fields_to_validate['postcode'] = '';
		$this->fields_to_validate['state'] = '';

		$this->locale_settings = [
			"postcode" => [ "required" => false ],
			"state" => [ "required" => false ],
		];		

		$errors = $this->google_settings->validate_address( $this->fields_to_validate, $this->locale_settings );

		$this->assertEquals(
			0,
			count( $errors )
		);

	}	
	

}
