<?php
declare( strict_types=1 );

/**
 * Google Customer Reviews post-purchase opt-in prompt.
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
use WC_Order;

defined( 'ABSPATH' ) || exit;

/**
 * Injects Google's Customer Reviews post-purchase opt-in snippet on the order-confirmation page.
 *
 * Modeled on GlobalSiteTag's injection pattern, but hooks the template-agnostic wp_body_open
 * instead of woocommerce_before_thankyou so the same code path covers both classic and
 * block-based checkout.
 */
class ReviewsOptIn implements Service, Registerable, OptionsAwareInterface {

	use OptionsAwareTrait;
	use ConsentGatedScriptTrait;

	/** @var string Key of the post-purchase review collection flag within OptionsInterface::MERCHANT_CENTER. */
	protected const SETTING_KEY = 'collect_reviews_after_purchase';

	/** @var string Meta key used to mark an order as already prompted, to prevent duplicate injection. */
	protected const ORDER_PROMPTED_META_KEY = '_gla_gcr_opt_in_prompted';

	/**
	 * @var EstimatedDeliveryTimeResolver
	 */
	protected $delivery_time_resolver;

	/**
	 * @var WP
	 */
	protected $wp;

	/**
	 * ReviewsOptIn constructor.
	 *
	 * @param EstimatedDeliveryTimeResolver $delivery_time_resolver
	 * @param WP                            $wp
	 */
	public function __construct( EstimatedDeliveryTimeResolver $delivery_time_resolver, WP $wp ) {
		$this->delivery_time_resolver = $delivery_time_resolver;
		$this->wp                     = $wp;
	}

	/**
	 * Register the service.
	 */
	public function register(): void {
		add_action(
			'wp_body_open',
			function () {
				$this->maybe_display_opt_in_snippet();
			}
		);
	}

	/**
	 * Display the Google Customer Reviews opt-in snippet on the order-confirmation page, if all
	 * of the following hold: the "Collect reviews after purchase" setting is enabled, the shopper
	 * has granted marketing consent (or no consent-management plugin is installed), this is a
	 * verified order-confirmation page view, the order hasn't already been prompted, a Merchant
	 * Center account is connected, and an estimated delivery date can be resolved for the order's
	 * destination country. No fallback/default delivery date is ever invented.
	 */
	public function maybe_display_opt_in_snippet(): void {
		if ( ! is_order_received_page() ) {
			return;
		}

		if ( ! $this->is_reviews_collection_enabled() ) {
			return;
		}

		if ( ! $this->wp->has_consent( 'marketing' ) ) {
			return;
		}

		$order = $this->get_verified_order();
		if ( ! $order ) {
			return;
		}

		if ( 1 === (int) $order->get_meta( self::ORDER_PROMPTED_META_KEY, true ) ) {
			return;
		}

		$merchant_id = $this->options->get_merchant_id();
		if ( ! $merchant_id ) {
			return;
		}

		$delivery_country = $order->get_shipping_country() ?: $order->get_billing_country();
		if ( ! $delivery_country ) {
			return;
		}

		$estimated_delivery_date = $this->get_estimated_delivery_date( $order, $delivery_country );
		if ( ! $estimated_delivery_date ) {
			return;
		}

		// Mark the order as prompted, to avoid double-injection if the confirmation page is reloaded.
		$order->update_meta_data( self::ORDER_PROMPTED_META_KEY, 1 );
		$order->save_meta_data();

		$params = [
			'merchant_id'             => $merchant_id,
			'order_id'                => (string) $order->get_id(),
			'email'                   => $order->get_billing_email(),
			'delivery_country'        => $delivery_country,
			'estimated_delivery_date' => $estimated_delivery_date,
		];

		echo $this->get_opt_in_snippet_markup( $params ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Whether the "Collect reviews after purchase" setting is currently enabled.
	 *
	 * @return bool
	 */
	protected function is_reviews_collection_enabled(): bool {
		$settings = $this->options->get( OptionsInterface::MERCHANT_CENTER, [] );

		return ! empty( $settings[ self::SETTING_KEY ] );
	}

	/**
	 * Resolve and validate the order for the current order-confirmation page view.
	 *
	 * Mirrors the order-key verification WooCommerce itself performs for both the classic
	 * thank-you template and the block-based Order Confirmation block, since injecting via
	 * wp_body_open runs before either of those has had a chance to do it for this request.
	 *
	 * @return WC_Order|null
	 */
	protected function get_verified_order(): ?WC_Order {
		$order_id = absint( $this->wp->get_query_vars( 'order-received' ) );
		if ( ! $order_id ) {
			return null;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order instanceof WC_Order ) {
			return null;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$key = isset( $_GET['key'] ) ? wc_clean( wp_unslash( $_GET['key'] ) ) : '';
		if ( ! $key || ! $order->key_is_valid( $key ) ) {
			return null;
		}

		return $order;
	}

	/**
	 * Resolve the estimated delivery date for an order's destination country, via the
	 * EstimatedDeliveryTimeResolver (local table or Merchant Center, depending on the merchant's
	 * declared shipping_time setting). Returns null (never a fabricated date) when the
	 * mode-appropriate source has no entry for the country.
	 *
	 * @param WC_Order $order
	 * @param string   $delivery_country
	 *
	 * @return string|null Delivery date in YYYY-MM-DD format, or null if unresolvable.
	 */
	protected function get_estimated_delivery_date( WC_Order $order, string $delivery_country ): ?string {
		$max_transit_days = $this->delivery_time_resolver->get_max_transit_days_for_country( $delivery_country );
		if ( null === $max_transit_days ) {
			return null;
		}

		$date_created = $order->get_date_created();
		if ( ! $date_created ) {
			return null;
		}

		$delivery_date = clone $date_created;
		$delivery_date->modify( "+{$max_transit_days} days" );

		return $delivery_date->format( 'Y-m-d' );
	}

	/**
	 * Build the Google Customer Reviews opt-in snippet markup.
	 *
	 * Per Google's documented snippet (support.google.com/merchants/answer/14629205): merchant_id,
	 * order_id, email, delivery_country, and estimated_delivery_date. There is no products/GTIN
	 * parameter. `opt_in_style` is intentionally omitted to use Google's default.
	 *
	 * The script element is created and appended dynamically, rather than emitted as a literal
	 * `<script src>` tag, so loading can be gated on client-side consent (see
	 * ConsentGatedScriptTrait) without the browser fetching it ahead of that check.
	 *
	 * @param array $params
	 *
	 * @return string
	 */
	protected function get_opt_in_snippet_markup( array $params ): string {
		// phpcs:disable WordPress.WP.EnqueuedResources.NonEnqueuedScript -- Google-hosted script, not a local asset.
		$load_js = sprintf(
			'var s=document.createElement("script");' .
			's.src="https://apis.google.com/js/platform.js?onload=renderOptIn";s.async=true;s.defer=true;' .
			'window.renderOptIn=function(){window.gapi.load("surveyoptin",function(){window.gapi.surveyoptin.render(%s);});};' .
			'document.head.appendChild(s);',
			wp_json_encode( $params )
		);

		return $this->get_consent_gated_script_markup( $load_js );
		// phpcs:enable WordPress.WP.EnqueuedResources.NonEnqueuedScript
	}
}
