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
	await page.locator( '.wp-menu-name' ).getByText( 'Marketing' ).hover();

	// the submenu is in the DOM but not visible with the use of absolute positioning.
	// we wait for submenu to open by checking for "opensub" CSS class in Marketing menu item.
	await expect(
		page.locator( 'li#toplevel_page_woocommerce-marketing.opensub' )
	).toBeVisible();

	// the submenu is now opened, the GLA sub menu item is now visible to the user,
	// we can call `click` now.
	await page.getByRole( 'link', { name: 'Google Listings & Ads' } ).click();
	await page.waitForLoadState( 'networkidle' );

	await expect( page.title() ).resolves.toContain( 'Google Listings & Ads' );

	// click on the call-to-action button.
	await page.getByText( 'Start listing products →' ).first().click();
	await page.waitForLoadState( 'networkidle' );

	// Check we are in the Setup MC page.
	await expect(
		page.getByText( 'Get started with Google Listings & Ads' )
	).toBeVisible();

	// There are some API calls running in the page before the steps are displayed.
	// Assert we eventually see the setup page Step 1 header.
	await expect(
		page.getByRole( 'heading', { name: 'Set up your accounts' } )
	).toBeVisible();

	// Expect to land on the setup page URL.
	expect( page.url() ).toMatch( /path=%2Fgoogle%2Fsetup-mc/ );
} );
