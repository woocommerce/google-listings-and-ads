<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Notes;

use Automattic\WooCommerce\GoogleListingsAndAds\Notes\AttributeMappingNewFeature;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;

defined( 'ABSPATH' ) || exit;

/**
 *
 * Unit test for the Attribute Mapping New Feature Note
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Notes
 *
 * @group AttributeMapping
 *
 * @property OptionsInterface        $options
 * @property AttributeMappingNewFeature  $note
 */
class AttributeMappingNewFeatureTest extends UnitTest {

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->options = $this->createMock( OptionsInterface::class );
		$this->note    = new AttributeMappingNewFeature();
		$this->note->set_options_object( $this->options );
	}

	public function test_name() {
		$this->assertEquals( 'gla-attribute-mapping-new-feature', $this->note->get_name() );
	}

	public function test_note_content() {
		$note = $this->note->get_entry();

		$this->assertEquals( 'gla-attribute-mapping-new-feature', $note->get_name() );
		$this->assertEquals( 'gla', $note->get_source() );
		$this->assertEquals( 'learn-more-attribute-mapping', $note->get_actions()[0]->name );
		$this->assertContains( 'admin.php?page=wc-admin&path=/google/attribute-mapping', $note->get_actions()[0]->query );
	}

	public function test_should_be_added_when_existing_user_and_not_already_added() {
		$this->options->expects( $this->once() )
			->method( 'get' )
			->with( OptionsInterface::INSTALL_TIMESTAMP )
			->willReturn( '1669730000' ); // Tue Nov 29 2022
		$this->assertTrue( $this->note->should_be_added() );
	}

	public function test_should_not_be_added_when_new_user() {
		$this->options->expects( $this->once() )
			->method( 'get' )
			->with( OptionsInterface::INSTALL_TIMESTAMP )
			->willReturn( '1669900000' ); // Thu Dec 01 2022

		$this->assertFalse( $this->note->should_be_added() );
	}

	public function test_should_not_be_added_if_already_added() {
		$this->note->get_entry()->save();

		$this->assertFalse( $this->note->should_be_added() );
	}



}
