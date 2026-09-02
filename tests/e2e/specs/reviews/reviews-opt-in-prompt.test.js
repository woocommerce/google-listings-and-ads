/**
 * External dependencies
 */
import { expect, test } from '@playwright/test';

/**
 * Internal dependencies
 */
import {
	clearGCRNotificationsDismissed,
	clearMerchantId,
	clearShippingTime,
	createSimpleProduct,
	getOrder,
	saveMCSettings,
	setMerchantId,
	setShippingTime,
} from '../../utils/api';
import { checkout, singleProductAddToCart } from '../../utils/customer';
import {
	denyMarketingConsent,
	dispatchMarketingConsentGranted,
	grantMarketingConsent,
} from '../../utils/consent';

test.describe.configure( { mode: 'serial' } );

const OPT_IN_SCRIPT_LOCATOR = 'script[src*="apis.google.com/js/platform.js"]';

/**
 * @type {import('@playwright/test').Page} page
 */
let page = null;

/**
 * Countries a shipping time has already been seeded for, in this test run.
 *
 * @type {Set<string>}
 */
const seededCountries = new Set();

/**
 * Complete a checkout for a fresh product, then seed a shipping time for the
 * resulting order's destination country so EstimatedDeliveryTimeResolver can
 * resolve a delivery date for it.
 *
 * Whatever gate this call is meant to test (the setting, or consent) must
 * already be in its intended state *before* calling this — the real
 * post-checkout redirect to the order-received page is itself a qualifying
 * page view once a shipping time exists for the order's country, so if every
 * gate already passes at that point, the prompt injects and marks the order
 * as prompted right then. A caller that changes a gate afterwards and then
 * revisits the URL to assert on it would be checking a second view that the
 * "already prompted" dedup guard blocks regardless of that gate's state.
 *
 * @return {Promise<string>} The order-received URL.
 */
async function checkoutToQualifyingOrder() {
	const productId = await createSimpleProduct();
	await singleProductAddToCart( page, productId );
	await checkout( page );

	const orderReceivedUrl = page.url();
	const parsedUrl = new URL( orderReceivedUrl );
	// Pretty permalinks put the order ID in the path (.../order-received/40/);
	// plain permalinks put it in the query string (?order-received=40).
	const orderId =
		parsedUrl.searchParams.get( 'order-received' ) ||
		parsedUrl.pathname.match( /order-received\/(\d+)/ )?.[ 1 ];
	const order = await getOrder( orderId );
	const country = order.shipping.country || order.billing.country;

	if ( ! seededCountries.has( country ) ) {
		await setShippingTime( country, 5, 5 );
		seededCountries.add( country );
	}

	return orderReceivedUrl;
}

test.describe( 'Google Customer Reviews Post-Purchase Opt-In Prompt', () => {
	test.beforeAll( async ( { browser } ) => {
		page = await browser.newPage();
		await setMerchantId();
		await page.goto( 'shop' );
		await grantMarketingConsent( page );
	} );

	test.afterAll( async () => {
		await clearMerchantId();
		await clearGCRNotificationsDismissed();
		await Promise.all(
			[ ...seededCountries ].map( ( country ) =>
				clearShippingTime( country )
			)
		);
		await page.close();
	} );

	test( 'does not inject the opt-in prompt on a qualifying order when the setting is disabled', async () => {
		await saveMCSettings( { gcr_collect_reviews_after_purchase: false } );

		await checkoutToQualifyingOrder();

		await expect( page.locator( OPT_IN_SCRIPT_LOCATOR ) ).toHaveCount( 0 );
	} );

	test( 'injects the opt-in prompt on a qualifying order-received page when the setting is enabled', async () => {
		await saveMCSettings( { gcr_collect_reviews_after_purchase: true } );

		// No extra navigation after this: a shipping time already exists for
		// this address's country (seeded by the previous test), so the real
		// post-checkout redirect to the order-received page is itself the
		// qualifying view — asserting on the current page, not a revisit.
		await checkoutToQualifyingOrder();

		await expect( page.locator( OPT_IN_SCRIPT_LOCATOR ) ).toHaveAttribute(
			'src',
			/apis\.google\.com\/js\/platform\.js\?onload=renderOptIn/
		);
	} );

	test( 'does not fetch the opt-in prompt script when marketing consent is denied', async () => {
		// Deny *before* checking out — the setting is still enabled from the
		// previous test, so if consent weren't already denied by the time of
		// the real post-checkout page view, the prompt would inject and mark
		// the order as prompted right there, and a later revisit would fail
		// for the wrong reason (dedup), not because of the consent gate.
		await denyMarketingConsent( page );

		await checkoutToQualifyingOrder();

		await expect( page.locator( OPT_IN_SCRIPT_LOCATOR ) ).toHaveCount( 0 );
	} );

	test( 'fetches the opt-in prompt script once marketing consent is granted mid-visit, without an additional reload', async () => {
		// Consent is still denied from the previous test, so this order's
		// real post-checkout page view does not inject or mark it as
		// prompted — leaving room for the deliberate reload below.
		const orderReceivedUrl = await checkoutToQualifyingOrder();
		await grantMarketingConsent( page );

		// Freeze timers so the consent gate's own grace-period timer can't be
		// what loads the script — isolates the wp_listen_for_consent_change
		// event-driven path from the timer-driven one.
		await page.clock.install();

		// New navigation: the consent cookie set above is now attached, so
		// the server-side gate passes and the consent-gated wrapper script
		// loads, but its internal timer is frozen and never fires on its own.
		await page.goto( orderReceivedUrl );
		await expect( page.locator( OPT_IN_SCRIPT_LOCATOR ) ).toHaveCount( 0 );

		await dispatchMarketingConsentGranted( page );

		await expect( page.locator( OPT_IN_SCRIPT_LOCATOR ) ).toHaveAttribute(
			'src',
			/apis\.google\.com\/js\/platform\.js\?onload=renderOptIn/
		);
	} );

	test( 'does not inject the opt-in prompt once the setting is disabled again, on a separate qualifying order', async () => {
		// The setting has been enabled since earlier in this file — this
		// proves turning it back off is respected, distinct from the very
		// first test above, which only covers the never-enabled default.
		await saveMCSettings( { gcr_collect_reviews_after_purchase: false } );

		await checkoutToQualifyingOrder();

		await expect( page.locator( OPT_IN_SCRIPT_LOCATOR ) ).toHaveCount( 0 );
	} );
} );
