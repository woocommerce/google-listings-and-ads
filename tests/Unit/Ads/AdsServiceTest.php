<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Ads;

use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AdsService;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\AdsAccountState;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;

/**
 * Class AdsServiceTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Ads
 *
 * @property MockObject|AdsAccountState     $state
 * @property AdsService                     $ads_service
 * @property OptionsInterface               $options
 */
class AdsServiceTest extends UnitTest {

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->state   = $this->createMock( AdsAccountState::class );
		$this->options = $this->createMock( OptionsInterface::class );

		$this->ads_service = new AdsService( $this->state );
		$this->ads_service->set_options_object( $this->options );

	}

	public function test_convert_status_is_not_set() {
		$this->options->method( 'get' )
			->with( OptionsInterface::CAMPAIGN_CONVERT_STATUS )
			->willReturn( null );

		$this->assertEquals( $this->ads_service->is_migration_completed(), false );

	}

	public function test_convert_status_property_is_not_set() {
		$this->options->method( 'get' )
			->with( OptionsInterface::CAMPAIGN_CONVERT_STATUS )
			->willReturn( [ 'updated' => '123456789' ] );

		$this->assertEquals( $this->ads_service->is_migration_completed(), false );

	}

	public function test_convert_status_is_unconverted() {
		$this->options->method( 'get' )
			->with( OptionsInterface::CAMPAIGN_CONVERT_STATUS )
			->willReturn( [ 'status' => 'unconverted' ] );

		$this->assertEquals( $this->ads_service->is_migration_completed(), false );

	}

	public function test_convert_status_is_converted() {
		$this->options->method( 'get' )
			->with( OptionsInterface::CAMPAIGN_CONVERT_STATUS )
			->willReturn( [ 'status' => 'converted' ] );

		$this->assertEquals( $this->ads_service->is_migration_completed(), true );

	}

}

