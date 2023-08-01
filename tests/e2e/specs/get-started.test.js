/**
 * External dependencies
 */
const { test, expect } = require( '@playwright/test' );

test.use( { storageState: process.env.ADMINSTATE } );

test( 'Merchant who is getting started clicks on the Marketing > GLA link, clicks on the call-to-action setup button to go to the Setup MC page, should get to the accounts setup', async ( {
	page,
} ) => {
	await page.goto( '/wp-admin/' );

	// hover at the marketing link to open popup submenu.
	const marketingLink = await page.$( "//a[contains(., 'Marketing')]" );
	await marketingLink.hover();

	// the submenu is in the DOM but not visible with the use of absolute positioning.
	// we wait for submenu to open by checking for "opensub" CSS class in Marketing menu item.
	await page.waitForSelector(
		'li#toplevel_page_woocommerce-marketing.opensub'
	);

	// the submenu is now opened, the GLA sub menu item is now visible to the user,
	// we can call `click` now.
	const glaLink = await page.$( "//a[text()='Google Listings & Ads']" );
	await glaLink.click();

	await page.waitForLoadState( 'networkidle' );
	await expect( page.title() ).resolves.toContain( 'Google Listings & Ads' );

	// click on the call-to-action button.
	const setupButton = await page.$(
		"//a[text()='Start listing products →']"
	);
	await setupButton.click();
	await page.waitForLoadState( 'networkidle' );

	// Check we are in the Setup MC page.
	await expect(
		page.waitForSelector(
			"//*[text()='Get started with Google Listings & Ads']"
		)
	).resolves.toBeTruthy();

	// There are some API calls running in the page before the steps are displayed.
	// Assert we eventually see the setup page Step 1 header.
	await expect(
		page.waitForSelector( "//*[text()='Set up your accounts']" )
	).resolves.toBeTruthy();

	// Expect to land on the setup page URL.
	expect( page.url() ).toMatch( /path=%2Fgoogle%2Fsetup-mc/ );
} );
