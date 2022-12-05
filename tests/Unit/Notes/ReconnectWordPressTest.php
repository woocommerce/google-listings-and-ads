<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Notes;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Connection;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\Notes\ReconnectWordPress;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class ReconnectWordPressTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Notes
 *
 * @property MockObject|Connection $connection
 * @property OptionsInterface      $options
 * @property MerchantCenterService $merchant_center
 * @property ReconnectWordPress    $note
 */
class ReconnectWordPressTest extends UnitTest {

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->connection      = $this->createMock( Connection::class );
		$this->options         = $this->createMock( OptionsInterface::class );
		$this->merchant_center = $this->createMock( MerchantCenterService::class );

		$this->note = new ReconnectWordPress( $this->connection );
		$this->note->set_options_object( $this->options );
		$this->note->set_merchant_center_object( $this->merchant_center );
	}

	public function test_name() {
		$this->assertEquals( 'gla-reconnect-wordpress', $this->note->get_name() );
	}

	public function test_note_entry() {
		$note = $this->note->get_entry();

		$this->assertEquals( 'gla-reconnect-wordpress', $note->get_name() );
		$this->assertEquals( 'gla', $note->get_source() );
		$this->assertEquals( 'reconnect-wordpress', $note->get_actions()[0]->name );
	}

	public function test_should_add_already_added() {
		$this->note->get_entry()->save();

		$this->assertFalse( $this->note->should_be_added() );
	}

	public function test_should_add_not_added_and_mc_setup_not_complete() {
		$this->merchant_center->method( 'is_setup_complete' )->willReturn( false );

		$this->assertFalse( $this->note->should_be_added() );
	}

	public function test_should_add_not_added_and_mc_setup_complete() {
		$this->merchant_center->method( 'is_setup_complete' )->willReturn( true );

		$this->assertTrue( $this->note->should_be_added() );
	}

	public function test_should_add_connected() {
		$this->options->expects( $this->exactly( 2 ) )
			->method( 'get' )
			->with( OptionsInterface::JETPACK_CONNECTED )
			->willReturn( true );

		$this->merchant_center->method( 'is_setup_complete' )->willReturn( true );

		$this->assertFalse( $this->note->should_be_added() );
	}

	public function test_should_add_already_disconnected() {
		$this->options->expects( $this->exactly( 2 ) )
			->method( 'get' )
			->with( OptionsInterface::JETPACK_CONNECTED )
			->willReturn( false );

		$this->merchant_center->method( 'is_setup_complete' )->willReturn( true );

		$this->connection->expects( $this->never() )
			->method( 'get_status' );

		$this->assertTrue( $this->note->should_be_added() );
	}

	public function test_should_add_disconnected_after_status_check() {
		$this->options->expects( $this->exactly( 2 ) )
			->method( 'get' )
			->with( OptionsInterface::JETPACK_CONNECTED )
			->will( $this->onConsecutiveCalls( true, false ) );

		$this->merchant_center->method( 'is_setup_complete' )->willReturn( true );

		$this->connection->expects( $this->once() )
			->method( 'get_status' );

		$this->assertTrue( $this->note->should_be_added() );
	}
}
