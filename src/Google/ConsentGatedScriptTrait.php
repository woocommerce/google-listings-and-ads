<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Google;

defined( 'ABSPATH' ) || exit;

/**
 * Trait ConsentGatedScriptTrait
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Google
 */
trait ConsentGatedScriptTrait {

	/**
	 * Wrap script-loading JavaScript so the browser doesn't fetch the script at all until
	 * marketing consent is confirmed via the WP Consent API's client-side layer. Fails open
	 * (loads immediately) when that layer isn't present, matching WP::has_consent()'s own
	 * fail-open behavior on the PHP side. If consent isn't yet granted, loading is deferred
	 * until a wp_listen_for_consent_change event grants it, so a shopper who accepts later in
	 * the same visit doesn't need to reload the page.
	 *
	 * A literal `<script src>` tag starts fetching as soon as the HTML parser reaches it,
	 * regardless of surrounding JS, so $load_js must create and append the script element
	 * itself rather than the caller emitting a `<script src>` tag directly. $load_js should
	 * not set `.async`/`.defer` on the element it creates — both attributes only affect
	 * scripts inserted by the HTML parser; a script created via `createElement` and appended
	 * later is already non-blocking (fetched and executed async) by default regardless.
	 *
	 * The consent check itself waits for DOMContentLoaded before running. Both call sites
	 * (wp_head, wp_body_open) fire before the WP Consent API plugin's own script, which is
	 * enqueued in the footer — checking wp_has_consent immediately would always see it as
	 * undefined and fail open regardless of the shopper's actual choice.
	 *
	 * @param string $load_js Raw JavaScript that creates and appends the actual script element.
	 *
	 * @return string
	 */
	protected function get_consent_gated_script_markup( string $load_js ): string {
		return sprintf(
			'<script>(function(){function gclCheck(){function gclLoad(){%1$s}' .
			'if("function"!==typeof wp_has_consent||wp_has_consent("marketing")){gclLoad();}' .
			'else{var gclFired=false;document.addEventListener("wp_listen_for_consent_change",function(e){' .
			'if(!gclFired&&e.detail&&"allow"===e.detail.marketing){gclFired=true;gclLoad();}});}}' .
			'if("loading"===document.readyState){document.addEventListener("DOMContentLoaded",gclCheck);}else{gclCheck();}})();</script>',
			$load_js
		);
	}
}
