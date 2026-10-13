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
	 * Milliseconds a `true` consent reading is given to be corrected before being acted on.
	 *
	 * A static property rather than a `const`, since traits can't declare constants before PHP 8.2.
	 *
	 * @var int
	 */
	protected static $consent_grace_period_ms = 300;

	/**
	 * Wrap script-loading JavaScript so the browser doesn't fetch the script until marketing
	 * consent is confirmed via the WP Consent API's client-side layer. Fails open when that
	 * layer isn't present, matching WP::has_consent(). If consent isn't yet granted, loading
	 * waits for a wp_listen_for_consent_change event, so accepting mid-visit needs no reload.
	 *
	 * $load_js must create and append the script element itself: a literal `<script src>` tag
	 * is fetched as soon as it is parsed, regardless of surrounding JS. It should not set
	 * `.async`/`.defer`, which have no effect on a script appended after parsing.
	 *
	 * The check waits for DOMContentLoaded because the WP Consent API script is enqueued in the
	 * footer, after both call sites (wp_head, wp_body_open) have run. A `true` reading is given
	 * a short grace window ({@see self::$consent_grace_period_ms}) before being acted on, since
	 * a CMP that loads its consent engine asynchronously can report a default `true` and correct
	 * it shortly after. A `false` reading is acted on immediately.
	 *
	 * @param string $load_js Raw JavaScript that creates and appends the actual script element.
	 *
	 * @return string
	 */
	protected function get_consent_gated_script_markup( string $load_js ): string {
		return sprintf(
			'<script>(function(){function gclCheck(){' .
			'var gclLoaded=false;function gclLoad(){if(gclLoaded)return;gclLoaded=true;%1$s}' .
			'if("function"!==typeof wp_has_consent){gclLoad();return;}' .
			'document.addEventListener("wp_listen_for_consent_change",function(e){' .
			'if(e.detail&&"allow"===e.detail.marketing){gclLoad();}});' .
			'if(wp_has_consent("marketing")){setTimeout(function(){' .
			'if(!gclLoaded&&wp_has_consent("marketing")){gclLoad();}},%2$d);}}' .
			'if("loading"===document.readyState){document.addEventListener("DOMContentLoaded",gclCheck);}else{gclCheck();}})();</script>',
			$load_js,
			static::$consent_grace_period_ms
		);
	}
}
