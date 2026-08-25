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
	 * (loads immediately) when that layer isn't present at all, matching WP::has_consent()'s
	 * own fail-open behavior on the PHP side. If consent isn't yet granted, loading is deferred
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
	 * **A `true` reading at that point is not treated as immediately final.** Confirmed via a
	 * real headless-browser trace against CookieYes (`cookie-law-info`) + WP Consent API: its
	 * consent engine is fetched live from `cdn-cookieyes.com`, a cross-origin request, and in
	 * that trace `wp_has_consent('marketing')` returned `true` at DOMContentLoaded (a default,
	 * not a confirmed grant) and only corrected itself to `false` ~40ms later, by `window.load`
	 * — after this method had already fetched the script, which cannot be un-fetched. A same
	 * consent-plugin, different-CMP trace (GDPR Cookie Compliance, same-origin script) showed no
	 * such gap — `false` was already stable at DOMContentLoaded. So a `true` reading gets a
	 * short grace window (`GCL_GRACE_MS`) before being acted on, giving a CMP whose consent
	 * engine loads asynchronously/cross-origin a chance to correct a stale default first. A
	 * `false` reading is still acted on immediately — there's no correctness risk in *not*
	 * loading yet, only in loading too eagerly.
	 *
	 * @param string $load_js Raw JavaScript that creates and appends the actual script element.
	 *
	 * @return string
	 */
	protected function get_consent_gated_script_markup( string $load_js ): string {
		return sprintf(
			'<script>(function(){var GCL_GRACE_MS=300;function gclCheck(){' .
			'var gclLoaded=false;function gclLoad(){if(gclLoaded)return;gclLoaded=true;%1$s}' .
			'if("function"!==typeof wp_has_consent){gclLoad();return;}' .
			'document.addEventListener("wp_listen_for_consent_change",function(e){' .
			'if(e.detail&&"allow"===e.detail.marketing){gclLoad();}});' .
			'if(wp_has_consent("marketing")){setTimeout(function(){' .
			'if(!gclLoaded&&wp_has_consent("marketing")){gclLoad();}},GCL_GRACE_MS);}}' .
			'if("loading"===document.readyState){document.addEventListener("DOMContentLoaded",gclCheck);}else{gclCheck();}})();</script>',
			$load_js
		);
	}
}
