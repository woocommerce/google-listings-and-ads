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

	test( 'should fetch the badge widget script once marketing consent is granted mid-visit, without an additional reload', async () => {
		await grantMarketingConsent( page );

		// Freeze timers so the consent gate's own grace-period timer can't be
		// what loads the script — isolates the wp_listen_for_consent_change
		// event-driven path from the timer-driven one.
		await page.clock.install();

		// New navigation: the consent cookie set above is now attached, so the
		// server-side gate passes and the consent-gated wrapper script loads,
		// but its internal timer is frozen and never fires on its own.
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
} );
