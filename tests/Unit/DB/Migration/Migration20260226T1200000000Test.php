<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\DB\Migration;

use Automattic\WooCommerce\GoogleListingsAndAds\DB\Migration\Migration20260226T1200000000;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use wpdb;

/**
 * Class Migration20260226T1200000000Test
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\DB\Migration
 */
class Migration20260226T1200000000Test extends UnitTest {

	public function test_get_applicable_version_returns_3_5_3() {
		$wpdb    = $this->createStub( wpdb::class );
		$options = $this->createStub( OptionsInterface::class );

		$migration = new Migration20260226T1200000000( $wpdb, $options );

		$this->assertSame( '3.5.3', $migration->get_applicable_version() );
	}

	public function test_apply_sets_pull_false_for_all_datatypes_when_current_value_is_array() {
		$sync_mode_with_pull_enabled = [
			'products' => [
				'push' => true,
				'pull' => true,
			],
			'coupons'  => [
				'push' => false,
				'pull' => true,
			],
			'shipping' => [
				'push' => true,
				'pull' => true,
			],
			'settings' => [
				'push' => true,
				'pull' => false,
			],
		];

		$options = $this->createMock( OptionsInterface::class );
		$options->expects( $this->once() )
			->method( 'get' )
			->with( OptionsInterface::API_PULL_SYNC_MODE )
			->willReturn( $sync_mode_with_pull_enabled );

		$expected_normalized = [
			'products' => [
				'push' => true,
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
		];

		$options->expects( $this->once() )
			->method( 'update' )
			->with( OptionsInterface::API_PULL_SYNC_MODE, $expected_normalized );

		$wpdb      = $this->createStub( wpdb::class );
		$migration = new Migration20260226T1200000000( $wpdb, $options );

		$migration->apply();
	}

	public function test_apply_uses_default_structure_when_current_value_is_not_array() {
		$options = $this->createMock( OptionsInterface::class );
		$options->expects( $this->once() )
			->method( 'get' )
			->with( OptionsInterface::API_PULL_SYNC_MODE )
			->willReturn( null );

		$options->expects( $this->never() )
			->method( 'update' );

		$wpdb      = $this->createStub( wpdb::class );
		$migration = new Migration20260226T1200000000( $wpdb, $options );

		$migration->apply();
	}

	public function test_apply_handles_malformed_datatype_entry_by_using_default() {
		$sync_mode_malformed = [
			'products' => [
				'push' => true,
				'pull' => true,
			],
			'coupons'  => 'not_an_array',
			'shipping' => [],
			'settings' => [
				'push' => false,
			],
		];

		$options = $this->createMock( OptionsInterface::class );
		$options->expects( $this->once() )
			->method( 'get' )
			->with( OptionsInterface::API_PULL_SYNC_MODE )
			->willReturn( $sync_mode_malformed );

		// Only entries that are arrays with a 'pull' key get pull set to false; others are unchanged.
		$expected = [
			'products' => [
				'push' => true,
				'pull' => false,
			],
			'coupons'  => 'not_an_array',
			'shipping' => [],
			'settings' => [
				'push' => false,
			],
		];

		$options->expects( $this->once() )
			->method( 'update' )
			->with( OptionsInterface::API_PULL_SYNC_MODE, $expected );

		$wpdb      = $this->createStub( wpdb::class );
		$migration = new Migration20260226T1200000000( $wpdb, $options );

		$migration->apply();
	}

	public function test_apply_only_modifies_existing_keys_with_pull() {
		$sync_mode_partial = [
			'products' => [
				'push' => true,
				'pull' => true,
			],
			'settings' => [
				'push' => false,
				'pull' => true,
			],
		];

		$options = $this->createMock( OptionsInterface::class );
		$options->expects( $this->once() )
			->method( 'get' )
			->with( OptionsInterface::API_PULL_SYNC_MODE )
			->willReturn( $sync_mode_partial );

		$expected = [
			'products' => [
				'push' => true,
				'pull' => false,
			],
			'settings' => [
				'push' => false,
				'pull' => false,
			],
		];

		$options->expects( $this->once() )
			->method( 'update' )
			->with( OptionsInterface::API_PULL_SYNC_MODE, $expected );

		$wpdb      = $this->createStub( wpdb::class );
		$migration = new Migration20260226T1200000000( $wpdb, $options );

		$migration->apply();
	}
}
