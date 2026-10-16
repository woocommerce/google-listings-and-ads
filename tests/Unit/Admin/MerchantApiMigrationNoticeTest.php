<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Admin;

use Automattic\WooCommerce\GoogleListingsAndAds\Admin\MerchantApiMigrationNotice;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MerchantApiMigrationNoticeTest extends TestCase {

	protected const PLUGIN_BASENAME = 'google-listings-and-ads/google-listings-and-ads.php';

	/** @var MockObject|WP $wp */
	protected $wp;

	/** @var MockObject|MerchantCenterService $merchant_center */
	protected $merchant_center;

	/**
	 * Setup tests
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->wp              = $this->createMock( WP::class );
		$this->merchant_center = $this->createMock( MerchantCenterService::class );
	}

	/**
	 * Test `register` hooks `maybe_render` into both admin notice actions at default priority.
	 *
	 * @return void
	 */
	public function test_register_hooks_admin_notices_and_network_admin_notices(): void {
		$instance = $this->get_notice_instance();

		$instance->register();

		$this->assertSame( 10, has_action( 'admin_notices', [ $instance, 'maybe_render' ] ) );
		$this->assertSame( 10, has_action( 'network_admin_notices', [ $instance, 'maybe_render' ] ) );

		// Plain TestCase does not reset global hooks between tests.
		remove_action( 'admin_notices', [ $instance, 'maybe_render' ] );
		remove_action( 'network_admin_notices', [ $instance, 'maybe_render' ] );
	}

	/**
	 * Test nothing renders for users who cannot update plugins, even when a
	 * qualifying update is available.
	 *
	 * @return void
	 */
	public function test_maybe_render_outputs_nothing_when_user_lacks_update_plugins_capability(): void {
		$this->wp->method( 'current_user_can' )->with( 'update_plugins' )->willReturn( false );
		$this->wp->method( 'get_site_transient' )->with( 'update_plugins' )->willReturn( $this->get_transient_with_update( '3.8.0' ) );

		$instance = $this->get_notice_instance( '3.7.5' );

		$this->assertSame( '', $this->capture_render_output( $instance ) );
	}

	/**
	 * Test nothing renders when Merchant Center is not connected, even when a
	 * qualifying update is available.
	 *
	 * @return void
	 */
	public function test_maybe_render_outputs_nothing_when_merchant_center_is_not_connected(): void {
		$this->wp->method( 'current_user_can' )->with( 'update_plugins' )->willReturn( true );
		$this->wp->method( 'get_site_transient' )->with( 'update_plugins' )->willReturn( $this->get_transient_with_update( '3.8.0' ) );

		$instance = $this->get_notice_instance( '3.7.5', false );

		$this->assertSame( '', $this->capture_render_output( $instance ) );
	}

	/**
	 * Test nothing renders when the update transient is `false` (the value
	 * WordPress returns before its first update check has run).
	 *
	 * @return void
	 */
	public function test_maybe_render_outputs_nothing_when_transient_is_false(): void {
		$this->wp->method( 'current_user_can' )->with( 'update_plugins' )->willReturn( true );
		$this->wp->method( 'get_site_transient' )->with( 'update_plugins' )->willReturn( false );

		$instance = $this->get_notice_instance( '3.7.5' );

		$this->assertSame( '', $this->capture_render_output( $instance ) );
	}

	/**
	 * Test nothing renders when the transient carries no update entry for this plugin.
	 *
	 * @return void
	 */
	public function test_maybe_render_outputs_nothing_when_transient_has_no_response_for_this_plugin(): void {
		$transient           = new \stdClass();
		$transient->response = [
			'some-other-plugin/some-other-plugin.php' => (object) [ 'new_version' => '9.9.9' ],
		];

		$this->wp->method( 'current_user_can' )->with( 'update_plugins' )->willReturn( true );
		$this->wp->method( 'get_site_transient' )->with( 'update_plugins' )->willReturn( $transient );

		$instance = $this->get_notice_instance( '3.7.5' );

		$this->assertSame( '', $this->capture_render_output( $instance ) );
	}

	/**
	 * Test nothing renders when the available update is below the migration target version.
	 *
	 * @return void
	 */
	public function test_maybe_render_outputs_nothing_when_available_version_is_below_target(): void {
		$this->wp->method( 'current_user_can' )->with( 'update_plugins' )->willReturn( true );
		$this->wp->method( 'get_site_transient' )->with( 'update_plugins' )->willReturn( $this->get_transient_with_update( '3.7.99' ) );

		$instance = $this->get_notice_instance( '3.7.5' );

		$this->assertSame( '', $this->capture_render_output( $instance ) );
	}

	/**
	 * Test nothing renders when the installed version equals the migration target,
	 * even though a newer update is available.
	 *
	 * @return void
	 */
	public function test_maybe_render_outputs_nothing_when_installed_version_equals_target(): void {
		$this->wp->method( 'current_user_can' )->with( 'update_plugins' )->willReturn( true );
		$this->wp->method( 'get_site_transient' )->with( 'update_plugins' )->willReturn( $this->get_transient_with_update( '3.9.0' ) );

		$instance = $this->get_notice_instance( '3.8.0' );

		$this->assertSame( '', $this->capture_render_output( $instance ) );
	}

	/**
	 * Test nothing renders when the installed version is a two-part string equal
	 * to the migration target ("3.8" vs "3.8.0"), which raw `version_compare`
	 * would wrongly treat as below the target.
	 *
	 * @return void
	 */
	public function test_maybe_render_outputs_nothing_when_installed_version_is_two_part_equal_to_target(): void {
		$this->wp->method( 'current_user_can' )->with( 'update_plugins' )->willReturn( true );
		$this->wp->method( 'get_site_transient' )->with( 'update_plugins' )->willReturn( $this->get_transient_with_update( '3.9.0' ) );

		$instance = $this->get_notice_instance( '3.8' );

		$this->assertSame( '', $this->capture_render_output( $instance ) );
	}

	/**
	 * Test the banner renders when the available update is a two-part string equal
	 * to the migration target ("3.8" vs "3.8.0"), which raw `version_compare`
	 * would wrongly treat as below the target.
	 *
	 * @return void
	 */
	public function test_maybe_render_outputs_banner_when_available_version_is_two_part_equal_to_target(): void {
		$this->wp->method( 'current_user_can' )->with( 'update_plugins' )->willReturn( true );
		$this->wp->method( 'get_site_transient' )->with( 'update_plugins' )->willReturn( $this->get_transient_with_update( '3.8' ) );

		$instance = $this->get_notice_instance( '3.7.5' );

		$output = $this->capture_render_output( $instance );

		$this->assertStringContainsString( 'notice-warning', $output );
		$this->assertStringContainsString( 'Critical Update:', $output );
	}

	/**
	 * Test nothing renders when the installed version is above the migration target.
	 *
	 * @return void
	 */
	public function test_maybe_render_outputs_nothing_when_installed_version_is_above_target(): void {
		$this->wp->method( 'current_user_can' )->with( 'update_plugins' )->willReturn( true );
		$this->wp->method( 'get_site_transient' )->with( 'update_plugins' )->willReturn( $this->get_transient_with_update( '4.0.0' ) );

		$instance = $this->get_notice_instance( '3.9.15' );

		$this->assertSame( '', $this->capture_render_output( $instance ) );
	}

	/**
	 * Test the banner renders with the expected markup when the migration target
	 * version is available and the installed version is below it.
	 *
	 * @return void
	 */
	public function test_maybe_render_outputs_banner_when_target_version_available(): void {
		$this->wp->method( 'current_user_can' )->with( 'update_plugins' )->willReturn( true );
		$this->wp->method( 'get_site_transient' )->with( 'update_plugins' )->willReturn( $this->get_transient_with_update( '3.8.0' ) );

		$instance = $this->get_notice_instance( '3.7.5' );

		$output = $this->capture_render_output( $instance );

		$this->assertStringContainsString( 'notice-warning', $output );
		$this->assertStringNotContainsString( 'is-dismissible', $output );
		$this->assertStringContainsString( 'Critical Update:', $output );
		$this->assertStringContainsString( 'August 18, 2026', $output );
		$this->assertStringContainsString( 'Update Plugin Now', $output );
		$this->assertStringContainsString( 'Read Migration Details', $output );
		$this->assertStringContainsString( 'href="' . esc_url( self_admin_url( 'plugins.php' ) ) . '"', $output );
		$this->assertStringContainsString( 'href="' . esc_url( MerchantApiMigrationNotice::MIGRATION_DETAILS_URL ) . '"', $output );
		$this->assertStringContainsString( 'https://ads-developers.googleblog.com/2026/04/merchant-api-is-coming-to-google-ads.html', $output );
		$this->assertStringContainsString( 'target="_blank"', $output );
		$this->assertStringContainsString( 'rel="noopener noreferrer"', $output );
	}

	/**
	 * Test the banner renders when the available version is newer than the target,
	 * covering migration releases past the cut-off without code change.
	 *
	 * @return void
	 */
	public function test_maybe_render_outputs_banner_when_available_version_is_newer_than_target(): void {
		$this->wp->method( 'current_user_can' )->with( 'update_plugins' )->willReturn( true );
		$this->wp->method( 'get_site_transient' )->with( 'update_plugins' )->willReturn( $this->get_transient_with_update( '4.1.2' ) );

		$instance = $this->get_notice_instance( '3.7.5' );

		$output = $this->capture_render_output( $instance );

		$this->assertStringContainsString( 'notice-warning', $output );
		$this->assertStringContainsString( 'Critical Update:', $output );
	}

	/**
	 * Test every rendered href is unchanged by a second pass through `esc_url`,
	 * confirming the markup builder escapes its links.
	 *
	 * @return void
	 */
	public function test_maybe_render_escapes_links_via_esc_url(): void {
		$this->wp->method( 'current_user_can' )->with( 'update_plugins' )->willReturn( true );
		$this->wp->method( 'get_site_transient' )->with( 'update_plugins' )->willReturn( $this->get_transient_with_update( '3.8.0' ) );

		$instance = $this->get_notice_instance( '3.7.5' );

		$output = $this->capture_render_output( $instance );

		preg_match_all( '/href="([^"]*)"/', $output, $matches );
		$this->assertCount( 2, $matches[1] );

		foreach ( $matches[1] as $href ) {
			$this->assertNotSame( '', $href );
			$this->assertSame( $href, esc_url( $href ) );
			$this->assertStringNotContainsString( ' ', $href );
			$this->assertStringNotContainsString( '<', $href );
		}
	}

	/**
	 * Smoke test in place of a provider-level test (no test file currently exists
	 * for `AdminServiceProvider`): the class is constructable with its real
	 * container dependency.
	 *
	 * @return void
	 */
	public function test_can_be_instantiated_with_real_wp_proxy(): void {
		$this->assertInstanceOf( MerchantApiMigrationNotice::class, new MerchantApiMigrationNotice( new WP(), $this->merchant_center ) );
	}

	/**
	 * Get a partial mock of the notice with the installed version and plugin
	 * basename stubbed, leaving all rendering logic real.
	 *
	 * @param string $installed_version Value returned by the stubbed `get_version`.
	 * @param bool   $is_connected      Value returned by the stubbed `MerchantCenterService::is_connected`.
	 *
	 * @return MockObject|MerchantApiMigrationNotice
	 */
	private function get_notice_instance( string $installed_version = '3.7.5', bool $is_connected = true ) {
		$this->merchant_center->method( 'is_connected' )->willReturn( $is_connected );

		$instance = $this->getMockBuilder( MerchantApiMigrationNotice::class )
			->setConstructorArgs( [ $this->wp, $this->merchant_center ] )
			->onlyMethods( [ 'get_version', 'get_plugin_basename' ] )
			->getMock();

		$instance->method( 'get_version' )->willReturn( $installed_version );
		$instance->method( 'get_plugin_basename' )->willReturn( self::PLUGIN_BASENAME );

		return $instance;
	}

	/**
	 * Build an `update_plugins` transient object reporting an available update
	 * for this plugin.
	 *
	 * @param string $new_version Available version to report.
	 *
	 * @return object
	 */
	private function get_transient_with_update( string $new_version ): object {
		$transient           = new \stdClass();
		$transient->response = [
			self::PLUGIN_BASENAME => (object) [
				'id'          => 'w.org/plugins/google-listings-and-ads',
				'slug'        => 'google-listings-and-ads',
				'plugin'      => self::PLUGIN_BASENAME,
				'new_version' => $new_version,
			],
		];

		return $transient;
	}

	/**
	 * Run `maybe_render` and return everything it echoes.
	 *
	 * @param MerchantApiMigrationNotice $instance Notice instance under test.
	 *
	 * @return string
	 */
	private function capture_render_output( MerchantApiMigrationNotice $instance ): string {
		ob_start();
		$instance->maybe_render();

		return ob_get_clean();
	}
}
