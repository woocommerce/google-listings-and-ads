/**
 * External dependencies
 */
import { expect, test } from '@playwright/test';

/**
 * Internal dependencies
 */
import {
	api,
	clearGCRNotificationsDismissed,
	clearMerchantId,
	createSimpleProduct,
	saveMCSettings,
	setMerchantId,
} from '../../utils/api';
import {
	denyMarketingConsent,
	dispatchMarketingConsentGranted,
	grantMarketingConsent,
} from '../../utils/consent';

test.describe.configure( { mode: 'serial' } );

const BADGE_WIDGET_SCRIPT_SRC =
	'https://www.gstatic.com/shopping/merchant/merchantwidget.js';

// The fake Merchant Center account ID set by the `gla-test/merchant-id` route.
const TEST_MERCHANT_ID = 1234;

// Stands in for Google's widget script, recording the arguments the plugin
// passes to `merchantwidget.start()` so tests can assert on them.
const BADGE_WIDGET_STUB = `window.merchantwidget = {
	start: ( args ) => { window.__merchantWidgetArgs = args; },
};`;

/**
 * @type {import('@playwright/test').Page} page
 */
let page = null;

test.describe( 'Google Customer Reviews Badge Widget', () => {
	test.beforeAll( async ( { browser } ) => {
		page = await browser.newPage();
		await setMerchantId();
	} );

	test.afterAll( async () => {
		await saveMCSettings( { gcr_badge_widget_position: 'bottom-right' } );
		await clearMerchantId();
		await clearGCRNotificationsDismissed();
		await page.close();
	} );

	test( 'should not inject the badge widget script when the setting is disabled', async () => {
		await page.goto( 'shop' );
		await grantMarketingConsent( page );
		await saveMCSettings( { gcr_badge_widget_enabled: false } );

		await page.reload();

		await expect( page.locator( '#merchantWidgetScript' ) ).toHaveCount(
			0
		);
	} );

	test( 'should inject the badge widget script when the setting is enabled and marketing consent is granted', async () => {
		await saveMCSettings( { gcr_badge_widget_enabled: true } );

		await page.reload();

		await expect( page.locator( '#merchantWidgetScript' ) ).toHaveAttribute(
			'src',
			BADGE_WIDGET_SCRIPT_SRC
		);
	} );

	test( 'should not fetch the badge widget script when marketing consent is denied', async () => {
		await denyMarketingConsent( page );

		await page.reload();

		await expect( page.locator( '#merchantWidgetScript' ) ).toHaveCount(
			0
		);
	} );

	test( 'should not fetch the badge widget script when marketing consent is denied and the consent API script is missing from the page', async () => {
		await denyMarketingConsent( page );

		await page.route( /wp-consent-api/, ( route ) => route.abort() );
		await page.reload();
		await page.unroute( /wp-consent-api/ );

		await expect( page.locator( '#merchantWidgetScript' ) ).toHaveCount(
			0
		);

		// unroute() only stops future requests from being blocked -- the page loaded above still
		// has no consent-api script. Reload once more so later tests get window.wp_set_consent back.
		await page.reload();
	} );

	test( 'should fetch the badge widget script once marketing consent is granted mid-visit, without an additional reload', async () => {
		await grantMarketingConsent( page );

		// Freeze timers so the consent gate's own grace-period timer can't be
		// what loads the script — isolates the wp_listen_for_consent_change
		// event-driven path from the timer-driven one.
		await page.clock.install();

		// The consent-gated wrapper script is emitted on every page view and
		// only its client-side gate decides whether to load the widget; with
		// timers frozen, its internal grace-period timer never fires on its own.
		await page.reload();
		await expect( page.locator( '#merchantWidgetScript' ) ).toHaveCount(
			0
		);

		await dispatchMarketingConsentGranted( page );

		await expect( page.locator( '#merchantWidgetScript' ) ).toHaveAttribute(
			'src',
			BADGE_WIDGET_SCRIPT_SRC
		);

		// Let time flow normally again for any test that runs after this one
		// in the file — otherwise a later test relying on a real timer (e.g.
		// the consent gate's own grace-period setTimeout) would silently
		// never fire, with nothing pointing back to this install() call.
		await page.clock.resume();
	} );

	test( 'should stop injecting the badge widget script once the setting is disabled again', async () => {
		// The setting has been enabled since earlier in this file — this
		// proves turning it back off is respected, distinct from the very
		// first test above, which only covers the never-enabled default.
		await saveMCSettings( { gcr_badge_widget_enabled: false } );

		await page.reload();

		await expect( page.locator( '#merchantWidgetScript' ) ).toHaveCount(
			0
		);
	} );

	test.describe( 'Widget arguments and page coverage', () => {
		let productId = null;

		test.beforeAll( async () => {
			productId = await createSimpleProduct();
			await page.route( BADGE_WIDGET_SCRIPT_SRC, ( route ) =>
				route.fulfill( {
					contentType: 'application/javascript',
					body: BADGE_WIDGET_STUB,
				} )
			);
			await saveMCSettings( {
				gcr_badge_widget_enabled: true,
				gcr_badge_widget_position: 'bottom-right',
			} );
			await page.goto( 'shop' );
			await grantMarketingConsent( page );
		} );

		test.afterAll( async () => {
			await api().delete( `products/${ productId }`, { force: true } );
			await page.unroute( BADGE_WIDGET_SCRIPT_SRC );
			await saveMCSettings( { gcr_badge_widget_enabled: false } );
		} );

		test( 'should start the widget with the connected Merchant ID and the bottom-right position', async () => {
			await page.reload();

			await page.waitForFunction( () => window.__merchantWidgetArgs );
			expect(
				await page.evaluate( () => window.__merchantWidgetArgs )
			).toEqual( {
				merchant_id: TEST_MERCHANT_ID,
				position: 'RIGHT_BOTTOM',
			} );
		} );

		test( 'should start the widget with the bottom-left position once the merchant chooses it', async () => {
			await saveMCSettings( {
				gcr_badge_widget_position: 'bottom-left',
			} );
			await page.reload();

			await page.waitForFunction( () => window.__merchantWidgetArgs );
			expect(
				await page.evaluate( () => window.__merchantWidgetArgs )
			).toEqual( {
				merchant_id: TEST_MERCHANT_ID,
				position: 'LEFT_BOTTOM',
			} );
		} );

		for ( const [ label, getPath ] of [
			[ 'the home page', () => '/' ],
			[ 'the shop page', () => '/shop/' ],
			[ 'a product page', () => `/?p=${ productId }` ],
			[ 'a page that does not exist', () => '/no-such-page/' ],
		] ) {
			test( `should inject the widget script on ${ label }`, async () => {
				await page.goto( getPath() );
				await grantMarketingConsent( page );
				await page.reload();

				await expect(
					page.locator( '#merchantWidgetScript' )
				).toHaveAttribute( 'src', BADGE_WIDGET_SCRIPT_SRC );
			} );
		}

		test( 'should not inject the widget script when no Merchant Center account is connected, and inject it again once one is', async () => {
			await clearMerchantId();
			await page.goto( 'shop' );
			await grantMarketingConsent( page );
			await page.reload();

			await expect( page.locator( '#merchantWidgetScript' ) ).toHaveCount(
				0
			);

			await setMerchantId();
			await page.reload();

			await expect(
				page.locator( '#merchantWidgetScript' )
			).toHaveAttribute( 'src', BADGE_WIDGET_SCRIPT_SRC );
		} );
	} );
} );
