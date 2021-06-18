const { merchant } = require( '@woocommerce/e2e-utils' );

describe( 'Merchant who is getting started', () => {
	beforeAll( async () => {
		await merchant.login();
	} );

	it( 'Goes to GLA page, click on the call-to-action button and navigate to the Setup MC page', async () => {
		const marketingLink = (
			await page.$x( "//a[contains(., 'Marketing')]" )
		 )[ 0 ];
		await marketingLink.hover();

		// when the submenu is opened, there will be an "opensub" CSS class for Marketing menu item.
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
		await expect( page.title() ).resolves.toMatch(
			'Google Listings & Ads'
		);
	} );
} );
