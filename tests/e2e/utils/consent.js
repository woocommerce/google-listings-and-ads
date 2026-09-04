/**
 * Helper functions for driving the real WP Consent API plugin's client-side behaviour,
 * shared by specs that verify consent-gated storefront script injection.
 *
 * @typedef { import( '@playwright/test' ).Page } Page
 */

/**
 * Grant marketing consent via the real WP Consent API client-side function.
 *
 * @param {Page} page
 */
export async function grantMarketingConsent( page ) {
	await page.evaluate( () => window.wp_set_consent( 'marketing', 'allow' ) );
}

/**
 * Deny marketing consent via the real WP Consent API client-side function.
 *
 * @param {Page} page
 */
export async function denyMarketingConsent( page ) {
	await page.evaluate( () => window.wp_set_consent( 'marketing', 'deny' ) );
}

/**
 * Dispatch the real `wp_listen_for_consent_change` event, the same event a
 * consent-management plugin fires when a shopper grants consent mid-visit.
 *
 * @param {Page} page
 */
export async function dispatchMarketingConsentGranted( page ) {
	await page.evaluate( () => {
		document.dispatchEvent(
			new CustomEvent( 'wp_listen_for_consent_change', {
				detail: { marketing: 'allow' },
			} )
		);
	} );
}
