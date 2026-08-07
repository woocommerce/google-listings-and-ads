<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\Google\BadgeWidget;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class BadgeWidgetTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Google
 */
class BadgeWidgetTest extends UnitTest {

	/** @var MockObject|OptionsInterface $options */
	protected $options;

	/** @var BadgeWidget $badge_widget */
	protected $badge_widget;

	protected const TEST_MERCHANT_ID = 12345;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->options      = $this->createMock( OptionsInterface::class );
		$this->badge_widget = new BadgeWidget();
		$this->badge_widget->set_options_object( $this->options );
	}

	protected function mock_settings( array $overrides = [] ): void {
		$this->options->method( 'get' )
			->with( OptionsInterface::MERCHANT_CENTER, [] )
			->willReturn(
				array_merge(
					[ 'badge_widget_enabled' => true ],
					$overrides
				)
			);

		$this->options->method( 'get_merchant_id' )->willReturn( self::TEST_MERCHANT_ID );
	}

	public function test_injects_snippet_when_enabled_and_merchant_id_available() {
		$this->mock_settings();

		$this->expectOutputRegex( '/ratingbadge\.render/' );

		$this->badge_widget->maybe_display_badge_snippet();
	}

	public function test_snippet_contains_merchant_id_and_default_position() {
		$this->mock_settings();

		ob_start();
		$this->badge_widget->maybe_display_badge_snippet();
		$output = ob_get_clean();

		$this->assertStringContainsString( '"merchant_id":' . self::TEST_MERCHANT_ID, $output );
		$this->assertStringContainsString( '"position":"BOTTOM_RIGHT"', $output );
	}

	public function test_snippet_contains_configured_position() {
		$this->mock_settings( [ 'badge_widget_position' => 'bottom-left' ] );

		ob_start();
		$this->badge_widget->maybe_display_badge_snippet();
		$output = ob_get_clean();

		$this->assertStringContainsString( '"position":"BOTTOM_LEFT"', $output );
	}

	public function test_no_injection_when_setting_disabled() {
		$this->mock_settings( [ 'badge_widget_enabled' => false ] );

		$this->expectOutputString( '' );

		$this->badge_widget->maybe_display_badge_snippet();
	}

	public function test_no_injection_when_merchant_center_not_connected() {
		$this->options->method( 'get' )
			->with( OptionsInterface::MERCHANT_CENTER, [] )
			->willReturn( [ 'badge_widget_enabled' => true ] );
		$this->options->method( 'get_merchant_id' )->willReturn( 0 );

		$this->expectOutputString( '' );

		$this->badge_widget->maybe_display_badge_snippet();
	}
}
