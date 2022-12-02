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
 * @group AdsService
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

	public function test_is_setup_started() {
		$this->state->method( 'last_incomplete_step' )->willReturn( 'abc' );
		$this->options->method( 'get' )
			->with( OptionsInterface::ADS_SETUP_COMPLETED_AT )
			->willReturn( null );

		$this->assertTrue( $this->ads_service->is_setup_started() );

	}

	public function test_is_setup_started_last_incomplete_step_is_empty() {
		$this->state->method( 'last_incomplete_step' )->willReturn( '' );
		$this->options->method( 'get' )
			->with( OptionsInterface::ADS_SETUP_COMPLETED_AT )
			->willReturn( null );

		$this->assertFalse( $this->ads_service->is_setup_started() );

	}

	public function test_is_setup_started_already_completed() {
		$this->state->method( 'last_incomplete_step' )->willReturn( 'abc' );
		$this->options->method( 'get' )
			->with( OptionsInterface::ADS_SETUP_COMPLETED_AT )
			->willReturn( 123 );

		$this->assertFalse( $this->ads_service->is_setup_started() );

	}
}

