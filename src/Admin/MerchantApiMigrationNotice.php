<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Admin;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;
use stdClass;

/**
 * Class MerchantApiMigrationNotice
 *
 * Renders a non-dismissible admin warning banner urging merchants to update the
 * plugin before Google retires the Content API for Shopping on 18 August 2026.
 *
 * The banner only renders while an update at or above the migration target
 * version is available and the installed version is still below it, so it
 * disappears on its own once the merchant updates.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Admin
 */
class MerchantApiMigrationNotice implements Service, Registerable {

	use PluginHelper;

	/**
	 * The first plugin version using the Merchant API. The banner renders only
	 * while an update at or above this version is available and the installed
	 * version is below it.
	 *
	 * @var string
	 */
	public const MIGRATION_TARGET_VERSION = '3.8.0';

	/**
	 * Google's announcement of the Content API deprecation date.
	 *
	 * @var string
	 */
	public const MIGRATION_DETAILS_URL = 'https://ads-developers.googleblog.com/2026/04/merchant-api-is-coming-to-google-ads.html';

	/**
	 * @var WP
	 */
	protected $wp;

	/**
	 * @var MerchantCenterService
	 */
	protected $merchant_center;

	/**
	 * MerchantApiMigrationNotice constructor.
	 *
	 * @param WP                    $wp
	 * @param MerchantCenterService $merchant_center
	 */
	public function __construct( WP $wp, MerchantCenterService $merchant_center ) {
		$this->wp              = $wp;
		$this->merchant_center = $merchant_center;
	}

	/**
	 * Register a service.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'admin_notices', [ $this, 'maybe_render' ] );
		add_action( 'network_admin_notices', [ $this, 'maybe_render' ] );
	}

	/**
	 * Render the migration notice when a qualifying update is available.
	 *
	 * @return void
	 */
	public function maybe_render(): void {
		if ( ! $this->should_render() ) {
			return;
		}

		$this->render();
	}

	/**
	 * Whether the migration notice should render.
	 *
	 * @return bool
	 */
	private function should_render(): bool {
		if ( ! $this->wp->current_user_can( 'update_plugins' ) ) {
			return false;
		}

		if ( ! $this->merchant_center->is_connected() ) {
			return false;
		}

		$target = $this->pad_version( self::MIGRATION_TARGET_VERSION );

		if ( ! version_compare( $this->pad_version( $this->get_version() ), $target, '<' ) ) {
			return false;
		}

		$update = $this->get_available_update();
		if ( null === $update ) {
			return false;
		}

		return version_compare( $this->pad_version( $update->new_version ?? '' ), $target, '>=' );
	}

	/**
	 * Pad a version string to at least three components so `version_compare`
	 * treats versions like "3.8" and "3.8.0" as equal.
	 *
	 * @param string $version Version string.
	 *
	 * @return string
	 */
	private function pad_version( string $version ): string {
		return implode( '.', array_pad( explode( '.', $version ), 3, '0' ) );
	}

	/**
	 * Get the available update entry for this plugin, if WordPress is reporting one.
	 *
	 * @return stdClass|null
	 */
	private function get_available_update(): ?stdClass {
		$transient = $this->wp->get_site_transient( 'update_plugins' );

		if ( ! is_object( $transient ) || empty( $transient->response ) || ! is_array( $transient->response ) ) {
			return null;
		}

		return $transient->response[ $this->get_plugin_basename() ] ?? null;
	}

	/**
	 * Render the migration notice.
	 *
	 * @return void
	 */
	private function render(): void {
		$message = sprintf(
			/* translators: 1: opening strong tag 2: closing strong tag 3: opening strong tag 4: closing strong tag */
			__( '%1$sCritical Update:%2$s Your Google for WooCommerce connection will stop working after %3$sAugust 18, 2026%4$s due to mandatory Google API changes. Update your plugin today to keep your product sync and ads running smoothly.', 'google-listings-and-ads' ),
			'<strong>',
			'</strong>',
			'<strong>',
			'</strong>'
		);

		printf(
			'<div class="notice notice-warning"><p>%1$s</p><p><a href="%2$s" class="button button-primary">%3$s</a> <a href="%4$s" class="button button-secondary" target="_blank" rel="noopener noreferrer">%5$s</a></p></div>',
			wp_kses_post( $message ),
			esc_url( self_admin_url( 'plugins.php' ) ),
			esc_html__( 'Update Plugin Now', 'google-listings-and-ads' ),
			esc_url( self::MIGRATION_DETAILS_URL ),
			esc_html__( 'Read Migration Details', 'google-listings-and-ads' )
		);
	}
}
