<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\TagManager;

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

	/** @var Settings */
	protected $settings;

	public function setUp(): void {
		parent::setUp();

		$this->options = $this->createMock( OptionsInterface::class );

		$this->settings = new Settings();
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
