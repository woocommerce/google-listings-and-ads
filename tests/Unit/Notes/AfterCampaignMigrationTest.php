<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Notes;


use Automattic\WooCommerce\GoogleListingsAndAds\Notes\AfterCampaignMigration;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AdsService;

defined( 'ABSPATH' ) || exit;

/**
 * Class AfterCampaignMigrationTest
 *
 * Unit test for migration inbox notification
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Notes
 *
 * @group MigrationNotifications
 *
 * @property OptionsInterface        $options
 * @property AfterCampaignMigration  $note
 */
class AfterCampaignMigrationTest extends UnitTest {

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->options     = $this->createMock( OptionsInterface::class );
		$this->ads_service = $this->createMock( AdsService::class );

		$this->note = new AfterCampaignMigration();
		$this->note->set_options_object( $this->options );
		$this->note->set_ads_object( $this->ads_service );
	}

	public function test_name() {
		$this->assertEquals( 'gla-after-campaign-migration', $this->note->get_name() );
	}

	public function test_note_entry() {
		$note = $this->note->get_entry();

		$this->assertEquals( 'gla-after-campaign-migration', $note->get_name() );
		$this->assertEquals( 'gla', $note->get_source() );
		$this->assertEquals( 'read-more-upgrade-campaign', $note->get_actions()[0]->name );
	}

	public function test_should_add_already_added() {
		$this->note->get_entry()->save();

		$this->assertFalse( $this->note->should_be_added() );
	}

	public function test_should_add_when_migration_not_completed() {

		$this->ads_service->method('is_migration_completed')->willReturn( false );

		$this->assertFalse( $this->note->should_be_added() );
	}

	public function test_should_add_when_migration_is_completed() {

		$this->ads_service->method('is_migration_completed')->willReturn( true );

		$this->assertTrue( $this->note->should_be_added() );
	}

}
