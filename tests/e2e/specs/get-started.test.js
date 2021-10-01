/**
 * External dependencies
 */
import {
	merchant, // eslint-disable-line import/named
} from '@woocommerce/e2e-utils';

describe( 'Merchant who is getting started', () => {
	beforeAll( async () => {
		await merchant.login();
	} );

	it( 'Clicks on the Marketing > GLA link, clicks on the call-to-action setup button to go to the Setup MC page, should get to the accounts setup', async () => {
		// hover at the marketing link to open popup submenu.
		const marketingLink = (
			await page.$x( "//a[contains(., 'Marketing')]" )
		 )[ 0 ];
		await marketingLink.hover();

		// the submenu is in the DOM but not visible with the use of absolute positioning.
		// we wait for submenu to open by checking for "opensub" CSS class in Marketing menu item.
		await page.waitForSelector(
			'li#toplevel_page_woocommerce-marketing.opensub'
		);

		// the submenu is now opened, the GLA sub menu item is now visible to the user,
		// we can call `click` now.
		const glaLink = (
			await page.$x( "//a[text()='Google Listings & Ads']" )
		 )[ 0 ];
		await glaLink.click();

		await page.waitForNavigation();
		await expect( page.title() ).resolves.toContain(
			'Google Listings & Ads'
		);

		// click on the call-to-action button.
		const setupButton = (
			await page.$x( "//a[text()='Set up free listings in Google']" )
		 )[ 0 ];
		await setupButton.click();
		await page.waitForNavigation();

		// Check we are in the Setup MC page.
		await expect(
			page.waitForXPath(
				"//*[text()='Get started with Google Listings & Ads']"
			)
		).resolves.toBeTruthy();

		// There are some API calls running in the page before the steps are displayed.
		// Assert we eventually see the setup page Step 1 header.
		await expect(
			page.waitForXPath( "//*[text()='Set up your accounts']" )
		).resolves.toBeTruthy();

		// Expect to land on the setup page URL.
		expect( page.url() ).toMatch( /path=%2Fgoogle%2Fsetup-mc/ );
	} );
} );
