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

defined( 'ABSPATH' ) || exit;

/**
 * Injects Google's ratings and reviews badge widget script storefront-wide.
 *
 * Modeled on GlobalSiteTag's wp_head injection shape, but for a template-agnostic floating
 * widget rather than a tracking pixel. Google's own script renders the aggregate rating; no
 * ratings data is fetched, cached, or stored by this plugin.
 */
class BadgeWidget implements Service, Registerable, OptionsAwareInterface {

	use OptionsAwareTrait;

	/** @var string Key of the badge widget enabled flag within OptionsInterface::MERCHANT_CENTER. */
	protected const SETTING_ENABLED = 'badge_widget_enabled';

	/** @var string Key of the badge widget position setting within OptionsInterface::MERCHANT_CENTER. */
	protected const SETTING_POSITION = 'badge_widget_position';

	/** @var string Default badge position, used when no position setting is stored yet. */
	protected const DEFAULT_POSITION = 'bottom-right';

	/** @var array Map of accepted position setting values to Google's expected position arguments. */
	protected const POSITION_MAP = [
		'bottom-left'  => 'BOTTOM_LEFT',
		'bottom-right' => 'BOTTOM_RIGHT',
	];

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
	 * enabled and a Merchant Center account is connected (i.e. a Merchant ID is available). Google's
	 * own script renders the aggregate rating; no ratings data is fetched, cached, or stored here.
	 */
	public function maybe_display_badge_snippet(): void {
		if ( ! $this->is_badge_widget_enabled() ) {
			return;
		}

		$merchant_id = $this->options->get_merchant_id();
		if ( ! $merchant_id ) {
			return;
		}

		echo $this->get_badge_snippet_markup( $merchant_id, $this->get_badge_position() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Whether the "Reviews badge widget" setting is currently enabled.
	 *
	 * @return bool
	 */
	protected function is_badge_widget_enabled(): bool {
		$settings = $this->options->get( OptionsInterface::MERCHANT_CENTER, [] );

		return ! empty( $settings[ self::SETTING_ENABLED ] );
	}

	/**
	 * Resolve the merchant's chosen badge corner position, mapped to Google's expected argument.
	 * Falls back to the default position for a missing or unrecognized stored value.
	 *
	 * @return string
	 */
	protected function get_badge_position(): string {
		$settings = $this->options->get( OptionsInterface::MERCHANT_CENTER, [] );
		$position = $settings[ self::SETTING_POSITION ] ?? self::DEFAULT_POSITION;

		return self::POSITION_MAP[ $position ] ?? self::POSITION_MAP[ self::DEFAULT_POSITION ];
	}

	/**
	 * Build the Google ratings and reviews badge widget snippet markup.
	 *
	 * Per Google's documented badge embed code: merchant_id and position are the only parameters.
	 * Google's script renders the aggregate rating itself; no ratings data is passed or stored.
	 *
	 * @param int    $merchant_id
	 * @param string $position
	 *
	 * @return string
	 */
	protected function get_badge_snippet_markup( int $merchant_id, string $position ): string {
		// phpcs:disable WordPress.WP.EnqueuedResources.NonEnqueuedScript -- Google-hosted script, not a local asset.
		return sprintf(
			'<script src="https://apis.google.com/js/platform.js?onload=renderBadge" async defer></script>' .
			'<script>window.renderBadge=function(){var c=document.createElement("div");document.body.appendChild(c);window.gapi.load("ratingbadge",function(){window.gapi.ratingbadge.render(c,%s);});};</script>',
			wp_json_encode(
				[
					'merchant_id' => $merchant_id,
					'position'    => $position,
				]
			)
		);
		// phpcs:enable WordPress.WP.EnqueuedResources.NonEnqueuedScript
	}
}
