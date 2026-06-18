<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Product\Ucp;

defined( 'ABSPATH' ) || exit;

/**
 * Class UcpEnablement
 *
 * Resolves whether Google UCP (Universal Commerce Protocol) eligibility data should be added to the
 * Merchant Center product feed.
 *
 * The full UCP flow depends on the external `woocommerce-ai` plugin, which owns the agentic-commerce
 * feature flag and the checkout/cart/profile surfaces. UCP support here is gated on that plugin being
 * present, recent enough, and enabled.
 *
 * NOTE (draft / WOOAI-634): This reads `woocommerce-ai` internals directly as a fallback. Once
 * `woocommerce-ai` exposes the stable enablement contract (WOOAI-637), the
 * `woocommerce_agentic_commerce_enabled` filter becomes the single source of truth and the fallback
 * below can be removed.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Product\Ucp
 */
class UcpEnablement {

	/**
	 * The woocommerce-ai option that toggles agentic commerce.
	 *
	 * @var string
	 */
	public const FEATURE_OPTION = 'wcai_agentic_enabled';

	/**
	 * The minimum woocommerce-ai version that ships the AgenticModule / UCP surface.
	 *
	 * TODO: pin once the UCP-capable woocommerce-ai version is released.
	 *
	 * @var string
	 */
	public const MIN_WCAI_VERSION = '0.3.0';

	/**
	 * Whether UCP eligibility data should be emitted for this site.
	 *
	 * Evaluated at runtime (during feed building) rather than at plugin boot, because both plugins
	 * load on `plugins_loaded` and load order is not guaranteed.
	 *
	 * @return bool
	 */
	public static function is_enabled(): bool {
		// Preferred path: the woocommerce-ai public enablement contract (WOOAI-637), if available.
		if ( has_filter( 'woocommerce_agentic_commerce_enabled' ) ) {
			/**
			 * Filters whether agentic commerce / UCP is enabled for this site.
			 *
			 * Owned and answered authoritatively by the woocommerce-ai plugin.
			 *
			 * @param bool $enabled Defaults to false when no authoritative listener is attached.
			 */
			return (bool) apply_filters( 'woocommerce_agentic_commerce_enabled', false );
		}

		// Fallback: detect the woocommerce-ai plugin and read its option directly.
		$version = static::detected_wcai_version();
		if ( null === $version ) {
			return false;
		}

		if ( version_compare( $version, self::MIN_WCAI_VERSION, '<' ) ) {
			return false;
		}

		return 'yes' === get_option( self::FEATURE_OPTION );
	}

	/**
	 * The detected woocommerce-ai plugin version, or null when the plugin is not present.
	 *
	 * Reads the `WCAI_VERSION` constant defined by woocommerce-ai. Isolated into its own method
	 * (and called via late static binding) so the plugin-detection branches can be exercised in
	 * tests without defining a process-global constant.
	 *
	 * @return string|null
	 */
	protected static function detected_wcai_version(): ?string {
		return defined( 'WCAI_VERSION' ) ? (string) constant( 'WCAI_VERSION' ) : null;
	}
}
