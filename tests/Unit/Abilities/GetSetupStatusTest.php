<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Abilities;

use Automattic\WooCommerce\GoogleListingsAndAds\Abilities\GetSetupStatus;
use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AdsService;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;

/**
 * Class GetSetupStatusTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Abilities
 */
class GetSetupStatusTest extends UnitTest {

	public function test_registration_args_expose_read_only_woocommerce_ability(): void {
		$this->skip_without_abilities_api();

		$args = GetSetupStatus::get_registration_args();

		$this->assertSame( 'woocommerce', $args['category'] );
		$this->assertTrue( $args['meta']['show_in_rest'] );
		$this->assertTrue( $args['meta']['mcp']['public'] );
		$this->assertSame( 'tool', $args['meta']['mcp']['type'] );
		$this->assertTrue( $args['meta']['annotations']['readonly'] );
		$this->assertFalse( $args['meta']['annotations']['destructive'] );
		$this->assertTrue( $args['meta']['annotations']['idempotent'] );
	}

	public function test_build_response_uses_stored_account_ids_not_request_overrides(): void {
		$this->skip_without_abilities_api();

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$_GET['merchant_id'] = '999999';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$_GET['customer_id'] = '888888';

		$values = [
			OptionsInterface::GOOGLE_CONNECTED            => true,
			OptionsInterface::JETPACK_CONNECTED           => true,
			OptionsInterface::WP_TOS_ACCEPTED             => true,
			OptionsInterface::MERCHANT_ID                 => 12345,
			OptionsInterface::MC_SETUP_COMPLETED_AT       => 1700000000,
			OptionsInterface::MERCHANT_ACCOUNT_STATE      => [
				'verify' => [
					'status'  => 1,
					'message' => 'hidden',
					'data'    => [ 'token' => 'hidden' ],
				],
			],
			OptionsInterface::TARGET_AUDIENCE             => [
				'location'  => 'selected',
				'countries' => [ 'us', 'CA' ],
			],
			OptionsInterface::CONTACT_INFO_SETUP          => true,
			OptionsInterface::SITE_VERIFICATION           => [
				'verified' => 'verified',
				'meta_tag' => 'must not be exposed',
			],
			OptionsInterface::SHIPPING_RATES              => [ 'rate' => true ],
			OptionsInterface::SHIPPING_TIMES              => [ 'time' => true ],
			OptionsInterface::ADS_ID                      => 54321,
			OptionsInterface::ADS_SETUP_COMPLETED_AT      => 1700000100,
			OptionsInterface::ADS_ACCOUNT_STATE           => [
				'billing' => [
					'status'  => 0,
					'message' => 'hidden',
				],
			],
			OptionsInterface::ADS_ENHANCED_CONVERSIONS_ENABLED => true,
			OptionsInterface::ADS_EU_POLITICAL_DECLARATIONS_COMPLETE => true,
			OptionsInterface::ADS_HAS_UNCLAIMED_INCENTIVE => true,
			OptionsInterface::CAMPAIGN_CONVERT_STATUS     => [
				'status'  => 'converted',
				'updated' => 1700000200,
			],
			OptionsInterface::ONBOARDING_COMPLETED_AT     => 1700000300,
			OptionsInterface::SYNCABLE_PRODUCTS_COUNT     => 12,
			OptionsInterface::UPDATE_ALL_PRODUCTS_LAST_SYNC => 1700000400,
			OptionsInterface::API_PULL_SYNC_MODE          => [
				'products' => [
					'push' => true,
					'pull' => false,
				],
			],
		];

		$options = $this->createMock( OptionsInterface::class );
		$options->method( 'get' )
			->willReturnCallback(
				static function ( string $name, $fallback = null ) use ( $values ) {
					return $values[ $name ] ?? $fallback;
				}
			);

		$merchant_center = $this->createMock( MerchantCenterService::class );
		$merchant_center->method( 'is_setup_complete' )->willReturn( true );
		$merchant_center->method( 'is_connected' )->willReturn( true );

		$ads = $this->createMock( AdsService::class );
		$ads->method( 'is_setup_started' )->willReturn( true );
		$ads->method( 'is_setup_complete' )->willReturn( true );
		$ads->method( 'is_connected' )->willReturn( true );

		$response = GetSetupStatus::build_response( $options, $merchant_center, $ads );

		unset( $_GET['merchant_id'], $_GET['customer_id'] );

		$this->assertSame( 12345, $response['merchant_center']['merchant_id'] );
		$this->assertSame( 54321, $response['ads']['ads_id'] );
		$this->assertSame(
			[
				[
					'step'   => 'verify',
					'status' => 1,
				],
			],
			$response['merchant_center']['account_state']
		);
		$this->assertSame( [ 'verified' => 'verified' ], $response['merchant_center']['site_verification'] );
		$this->assertSame(
			[
				[
					'data_type' => 'products',
					'push'      => true,
					'pull'      => false,
				],
			],
			$response['sync']['api_pull_sync_mode']
		);
	}

	private function skip_without_abilities_api(): void {
		if ( ! interface_exists( \Automattic\WooCommerce\Abilities\AbilityDefinition::class ) ) {
			$this->markTestSkipped( 'WooCommerce AbilityDefinition interface is unavailable.' );
		}
	}
}
