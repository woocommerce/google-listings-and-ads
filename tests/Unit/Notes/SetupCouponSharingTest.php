<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Notes;

use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AdsService;
use Automattic\WooCommerce\GoogleListingsAndAds\Coupon\CouponMetaHandler;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantStatuses;
use Automattic\WooCommerce\GoogleListingsAndAds\Notes\SetupCouponSharing;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\ChannelVisibility;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class SetupCouponSharingTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Notes
 */
class SetupCouponSharingTest extends UnitTest {

	/** @var MockObject|AdsService $ads_service */
	protected $ads_service;

	/** @var MerchantCenterService $merchant_center */
	protected $merchant_center;

	/** @var MockObject|MerchantStatuses $merchant_statuses */
	protected $merchant_statuses;

	/** @var OptionsInterface $options */
	protected $options;

	/** @var ReviewAfterConversions $note */
	protected $note;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->ads_service       = $this->createMock( AdsService::class );
		$this->merchant_center   = $this->createMock( MerchantCenterService::class );
		$this->merchant_statuses = $this->createMock( MerchantStatuses::class );
		$this->options           = $this->createMock( OptionsInterface::class );

		$this->note = new SetupCouponSharing( $this->merchant_statuses );
		$this->note->set_ads_object( $this->ads_service );
		$this->note->set_merchant_center_object( $this->merchant_center );
		$this->note->set_options_object( $this->options );
	}

	public function test_name() {
		$this->assertEquals( 'gla-coupon-optin', $this->note->get_name() );
	}

	public function test_note_entry() {
		$note = $this->note->get_entry();

		$this->assertEquals( 'gla-coupon-optin', $note->get_name() );
		$this->assertEquals( 'gla', $note->get_source() );
		$this->assertEquals( 'coupon-views', $note->get_actions()[0]->name );
	}

	public function test_should_not_add_already_added() {
		$this->note->get_entry()->save();

		$this->assertFalse( $this->note->should_be_added() );
	}

	public function test_should_not_add_unsupported_promotion_country() {
		$this->merchant_center->method( 'is_promotion_supported_country' )->willReturn( false );

		$this->assertFalse( $this->note->should_be_added() );
	}

	public function test_should_not_add_not_connected() {
		$this->merchant_center->method( 'is_promotion_supported_country' )->willReturn( true );
		$this->merchant_statuses->method( 'get_product_statistics' )->willThrowException( new Exception( 'error' ) );

		$this->assertFalse( $this->note->should_be_added() );
	}

	public function test_should_not_add_no_active_products() {
		$this->merchant_center->method( 'is_promotion_supported_country' )->willReturn( true );
		$this->mock_product_statistics( 0 );

		$this->assertFalse( $this->note->should_be_added() );
	}

	public function test_should_not_add_no_coupons() {
		$this->merchant_center->method( 'is_promotion_supported_country' )->willReturn( true );
		$this->mock_product_statistics( 5 );

		$this->assertFalse( $this->note->should_be_added() );
	}

	public function test_should_not_add_shared_coupons_found() {
		$this->merchant_center->method( 'is_promotion_supported_country' )->willReturn( true );
		$this->mock_product_statistics( 5 );
		$this->create_coupon( true );

		$this->assertFalse( $this->note->should_be_added() );
	}

	public function test_should_not_add_ads_setup_not_completed() {
		$this->merchant_center->method( 'is_promotion_supported_country' )->willReturn( true );
		$this->mock_product_statistics( 5 );
		$this->create_coupon( false );

		$this->ads_service->method( 'is_setup_complete' )->willReturn( false );
		$this->mock_option_value(
			OptionsInterface::MC_SETUP_COMPLETED_AT,
			time() - ( 7 * DAY_IN_SECONDS )
		);

		$this->assertFalse( $this->note->should_be_added() );
	}

	public function test_should_not_add_ads_setup_completed() {
		$this->merchant_center->method( 'is_promotion_supported_country' )->willReturn( true );
		$this->mock_product_statistics( 5 );
		$this->create_coupon( false );

		$this->ads_service->method( 'is_setup_complete' )->willReturn( true );
		$this->mock_option_value(
			OptionsInterface::MC_SETUP_COMPLETED_AT,
			time() - ( 2 * DAY_IN_SECONDS )
		);

		$this->assertFalse( $this->note->should_be_added() );
	}

	public function test_should_add() {
		$this->merchant_center->method( 'is_promotion_supported_country' )->willReturn( true );
		$this->mock_product_statistics( 5 );
		$this->create_coupon( false );

		$this->ads_service->method( 'is_setup_complete' )->willReturn( true );
		$this->mock_option_value(
			OptionsInterface::MC_SETUP_COMPLETED_AT,
			time() - ( 7 * DAY_IN_SECONDS )
		);

		$this->assertTrue( $this->note->should_be_added() );
	}

	/**
	 * Mock an option return value.
	 *
	 * @param string $key          Option name.
	 * @param mixed  $return_value Value to mock.
	 */
	private function mock_option_value( string $key, $return_value ) {
		$this->options->expects( $this->once() )->method( 'get' )->with( $key )->willReturn( $return_value );
	}

	/**
	 * Mock the return value for product statistics.
	 *
	 * @param int $active_products Amount of active products to mock.
	 */
	private function mock_product_statistics( int $active_products ) {
		$this->merchant_statuses->expects( $this->once() )->method( 'get_product_statistics' )->willReturn(
			[
				'statistics' => [
					'active' => $active_products,
				],
			]
		);
	}

	/**
	 * Creates a regular coupon (which is not set to sync and show)
	 *
	 * @param bool $shared Should this coupon be set to sync and show.
	 */
	private function create_coupon( bool $shared = true ) {
		$visibility = $shared ? ChannelVisibility::SYNC_AND_SHOW : ChannelVisibility::DONT_SYNC_AND_SHOW;

		wp_insert_post(
			[
				'post_type'   => 'shop_coupon',
				'post_status' => 'publish',
				'meta_input'  => [
					CouponMetaHandler::KEY_VISIBILITY => $visibility,
				],
			]
		);
	}
}
