<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\TagManager;

use Automattic\WooCommerce\GoogleListingsAndAds\API\TagManager\Connection;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TagManager\Settings;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class SettingsTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\TagManager
 */
class SettingsTest extends UnitTest {

	/** @var MockObject|OptionsInterface */
	protected $options;

	/** @var MockObject|Connection */
	protected $connection;

	/** @var Settings */
	protected $settings;

	public function setUp(): void {
		parent::setUp();

		$this->options    = $this->createMock( OptionsInterface::class );
		$this->connection = $this->createMock( Connection::class );

		$this->settings = new Settings( $this->connection );
		$this->settings->set_options_object( $this->options );
	}

	public function test_is_snippet_injection_enabled_defaults_to_true_when_never_set() {
		$this->options->method( 'get' )
			->with( OptionsInterface::TAG_MANAGER_SETTINGS )
			->willReturn( [ 'snippet_injection_enabled' => null ] );

		$this->assertTrue( $this->settings->is_snippet_injection_enabled() );
	}

	public function test_is_snippet_injection_enabled_defaults_to_true_when_key_is_missing() {
		$this->options->method( 'get' )->willReturn( [] );

		$this->assertTrue( $this->settings->is_snippet_injection_enabled() );
	}

	public function test_is_snippet_injection_enabled_true_when_explicitly_enabled() {
		$this->options->method( 'get' )->willReturn( [ 'snippet_injection_enabled' => true ] );

		$this->assertTrue( $this->settings->is_snippet_injection_enabled() );
	}

	public function test_is_snippet_injection_enabled_false_when_explicitly_disabled() {
		$this->options->method( 'get' )->willReturn( [ 'snippet_injection_enabled' => false ] );

		$this->assertFalse( $this->settings->is_snippet_injection_enabled() );
	}

	public function test_is_snippet_injection_enabled_false_when_a_conflict_was_found_at_connection_and_never_set() {
		$this->options->method( 'get' )->willReturn( [ 'snippet_injection_enabled' => null ] );
		$this->connection->method( 'was_connected_with_ads_conversion_conflict' )->willReturn( true );

		$this->assertFalse( $this->settings->is_snippet_injection_enabled() );
	}

	public function test_is_snippet_injection_enabled_stays_true_when_a_conflict_is_found_after_connection() {
		$this->options->method( 'get' )->willReturn( [ 'snippet_injection_enabled' => null ] );
		$this->connection->method( 'was_connected_with_ads_conversion_conflict' )->willReturn( false );
		$this->connection->method( 'has_ads_conversion_conflict' )->willReturn( true );

		$this->assertTrue( $this->settings->is_snippet_injection_enabled() );
	}

	public function test_is_snippet_injection_enabled_explicit_choice_wins_over_a_conflict() {
		$this->options->method( 'get' )->willReturn( [ 'snippet_injection_enabled' => true ] );
		$this->connection->method( 'was_connected_with_ads_conversion_conflict' )->willReturn( true );

		$this->assertTrue( $this->settings->is_snippet_injection_enabled() );
	}

	public function test_is_snippet_injection_enabled_true_when_no_conflict_at_connection_and_never_set() {
		$this->options->method( 'get' )->willReturn( [] );
		$this->connection->method( 'was_connected_with_ads_conversion_conflict' )->willReturn( false );

		$this->assertTrue( $this->settings->is_snippet_injection_enabled() );
	}

	public function test_has_snippet_injection_choice_false_when_never_set() {
		$this->options->method( 'get' )->willReturnOnConsecutiveCalls( [ 'snippet_injection_enabled' => null ], [] );

		$this->assertFalse( $this->settings->has_snippet_injection_choice() );
		$this->assertFalse( $this->settings->has_snippet_injection_choice() );
	}

	public function test_has_snippet_injection_choice_true_when_turned_on_or_off() {
		$this->options->method( 'get' )->willReturnOnConsecutiveCalls(
			[ 'snippet_injection_enabled' => true ],
			[ 'snippet_injection_enabled' => false ]
		);

		$this->assertTrue( $this->settings->has_snippet_injection_choice() );
		$this->assertTrue( $this->settings->has_snippet_injection_choice() );
	}

	public function test_is_snippet_active_true_when_a_container_is_connected_and_injection_is_enabled() {
		$this->options->method( 'get' )->willReturn( [ 'snippet_injection_enabled' => true ] );
		$this->connection->method( 'get_connection_data' )->willReturn( [ 'container_public_id' => 'GTM-ABC1234' ] );

		$this->assertTrue( $this->settings->is_snippet_active() );
	}

	public function test_is_snippet_active_false_when_injection_is_disabled() {
		$this->options->method( 'get' )->willReturn( [ 'snippet_injection_enabled' => false ] );
		$this->connection->method( 'get_connection_data' )->willReturn( [ 'container_public_id' => 'GTM-ABC1234' ] );

		$this->assertFalse( $this->settings->is_snippet_active() );
	}

	public function test_is_snippet_active_false_when_the_container_has_no_public_id() {
		$this->options->method( 'get' )->willReturn( [ 'snippet_injection_enabled' => true ] );
		$this->connection->method( 'get_connection_data' )->willReturn( [ 'container_public_id' => null ] );

		$this->assertFalse( $this->settings->is_snippet_active() );
	}

	public function test_set_snippet_injection_enabled_stores_the_choice_in_its_own_option() {
		$this->options->method( 'get' )->willReturn( [ 'snippet_injection_enabled' => null ] );

		$this->options->expects( $this->once() )
			->method( 'update' )
			->with(
				OptionsInterface::TAG_MANAGER_SETTINGS,
				[ 'snippet_injection_enabled' => false ]
			)
			->willReturn( true );

		$this->assertTrue( $this->settings->set_snippet_injection_enabled( false ) );
	}

	public function test_delete_removes_the_stored_settings() {
		$this->options->expects( $this->once() )
			->method( 'delete' )
			->with( OptionsInterface::TAG_MANAGER_SETTINGS )
			->willReturn( true );

		$this->assertTrue( $this->settings->delete() );
	}
}
