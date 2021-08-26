<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\DB\Migration;

use Automattic\WooCommerce\GoogleListingsAndAds\DB\Migration\MigrationInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Migration\Migrator;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;

/**
 * Class MigratorTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\DB\Migration
 */
class MigratorTest extends UnitTest {

	public function test_does_not_run_migrations_if_no_version_change() {
		$mock_migration_1 = $this->createMock( MigrationInterface::class );
		$mock_migration_1->expects( $this->never() )
						 ->method( 'apply' );

		$migrator = new Migrator( [ $mock_migration_1 ] );

		$migrator->migrate( '1.0.0', '1.0.0' );
	}

	public function test_runs_matching_migrations_if_version_increase() {
		$mock_migration_0 = $this->createMock( MigrationInterface::class );
		$mock_migration_0->expects( $this->any() )
						 ->method( 'get_applicable_version' )
						 ->willReturn( '1.2.0' );
		$mock_migration_0->expects( $this->once() )
						 ->method( 'apply' );

		$mock_migration_1 = $this->createMock( MigrationInterface::class );
		$mock_migration_1->expects( $this->any() )
						 ->method( 'get_applicable_version' )
						 ->willReturn( '1.1.5' );
		$mock_migration_1->expects( $this->once() )
						 ->method( 'apply' );

		$mock_migration_2 = $this->createMock( MigrationInterface::class );
		$mock_migration_2->expects( $this->any() )
						 ->method( 'get_applicable_version' )
						 ->willReturn( '1.1.0' );
		$mock_migration_2->expects( $this->once() )
						 ->method( 'apply' );

		$mock_migration_3 = $this->createMock( MigrationInterface::class );
		$mock_migration_3->expects( $this->any() )
						 ->method( 'get_applicable_version' )
						 ->willReturn( '1.0.0' );
		$mock_migration_3->expects( $this->never() )
						 ->method( 'apply' );

		$mock_migration_4 = $this->createMock( MigrationInterface::class );
		$mock_migration_4->expects( $this->any() )
						 ->method( 'get_applicable_version' )
						 ->willReturn( '0.9.0' );
		$mock_migration_4->expects( $this->never() )
						 ->method( 'apply' );

		$migrator = new Migrator( [ $mock_migration_0, $mock_migration_1, $mock_migration_2, $mock_migration_3, $mock_migration_4 ] );

		$migrator->migrate( '1.0.0', '1.2.0' );
	}
}
