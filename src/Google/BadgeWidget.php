<?php
declare( strict_types=1 );

/**
 * Google ratings and reviews badge widget.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds
 */

namespace Automattic\WooCommerce\GoogleListingsAndAds\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;

defined( 'ABSPATH' ) || exit;

/**
 * Injects Google's store widget (formerly "Customer Reviews badge") script storefront-wide.
 *
 * Modeled on GlobalSiteTag's wp_head injection shape, but for a template-agnostic floating
 * widget rather than a tracking pixel. Google's own script renders the aggregate rating; no
 * ratings data is fetched, cached, or stored by this plugin.
 */
class BadgeWidget implements Service, Registerable, OptionsAwareInterface {

	use OptionsAwareTrait;
	use ConsentGatedScriptTrait;

	/** @var string Key of the badge widget enabled flag within OptionsInterface::MERCHANT_CENTER. */
	protected const SETTING_ENABLED = 'badge_widget_enabled';

	/** @var string Key of the badge widget position setting within OptionsInterface::MERCHANT_CENTER. */
	protected const SETTING_POSITION = 'badge_widget_position';

	/** @var string Default badge position, used when no position setting is stored yet. */
	protected const DEFAULT_POSITION = 'bottom-right';

	/** @var array Map of accepted position setting values to the store widget's expected position arguments. */
	protected const POSITION_MAP = [
		'bottom-left'  => 'LEFT_BOTTOM',
		'bottom-right' => 'RIGHT_BOTTOM',
	];

	/**
	 * @var WP
	 */
	protected $wp;

	/**
	 * BadgeWidget constructor.
	 *
	 * @param WP $wp
	 */
	public function __construct( WP $wp ) {
		$this->wp = $wp;
	}

	/**
	 * Register the service.
	 */
	public function register(): void {
		add_action(
			'wp_head',
			function () {
				$this->maybe_display_badge_snippet();
			},
			999999
		);
	}

	/**
	 * Display the Google ratings and reviews badge widget snippet, if the badge widget setting is
	 * enabled, a Merchant Center account is connected (i.e. a Merchant ID is available), and the
	 * shopper has granted marketing consent (or no consent-management plugin is installed).
	 * Google's own script renders the aggregate rating; no ratings data is fetched, cached, or
	 * stored here.
	 */
	public function maybe_display_badge_snippet(): void {
		$settings = $this->options->get( OptionsInterface::MERCHANT_CENTER, [] );

		if ( ! $this->is_badge_widget_enabled( $settings ) ) {
			return;
		}

		$merchant_id = $this->options->get_merchant_id();
		if ( ! $merchant_id ) {
			return;
		}

		if ( ! $this->wp->has_consent( 'marketing' ) ) {
			return;
		}

		echo $this->get_badge_snippet_markup( $merchant_id, $this->get_badge_position( $settings ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Whether the "Reviews badge widget" setting is currently enabled.
	 *
	 * @param array $settings The OptionsInterface::MERCHANT_CENTER settings array.
	 *
	 * @return bool
	 */
	protected function is_badge_widget_enabled( array $settings ): bool {
		return ! empty( $settings[ self::SETTING_ENABLED ] );
	}

	/**
	 * Resolve the merchant's chosen badge corner position, mapped to Google's expected argument.
	 * Falls back to the default position for a missing or unrecognized stored value.
	 *
	 * @param array $settings The OptionsInterface::MERCHANT_CENTER settings array.
	 *
	 * @return string
	 */
	protected function get_badge_position( array $settings ): string {
		$position = $settings[ self::SETTING_POSITION ] ?? self::DEFAULT_POSITION;

		return self::POSITION_MAP[ $position ] ?? self::POSITION_MAP[ self::DEFAULT_POSITION ];
	}

	/**
	 * Build the Google store widget (formerly "Customer Reviews badge") snippet markup.
	 *
	 * Per Google's documented widget embed code (support.google.com/merchants/answer/14632921):
	 * merchant_id and position are the only parameters passed. `region` is intentionally omitted
	 * so the widget falls back to Google's own globalization logic to determine it. Google's
	 * script renders the aggregate rating itself; no ratings data is passed or stored.
	 *
	 * The script element is created and appended dynamically, rather than emitted as a literal
	 * `<script src>` tag, so loading can be gated on client-side consent (see
	 * ConsentGatedScriptTrait) without the browser fetching it ahead of that check.
	 *
	 * @param int    $merchant_id
	 * @param string $position
	 *
	 * @return string
	 */
	protected function get_badge_snippet_markup( int $merchant_id, string $position ): string {
		$load_js = sprintf(
			'var s=document.createElement("script");s.id="merchantWidgetScript";' .
			's.src="https://www.gstatic.com/shopping/merchant/merchantwidget.js";' .
			's.addEventListener("load",function(){merchantwidget.start(%s);});' .
			'document.head.appendChild(s);',
			wp_json_encode(
				[
					'merchant_id' => $merchant_id,
					'position'    => $position,
				]
			)
		);

		return $this->get_consent_gated_script_markup( $load_js );
	}
}
