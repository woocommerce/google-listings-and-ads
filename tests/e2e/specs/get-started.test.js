const { merchant } = require( '@woocommerce/e2e-utils' );

describe( 'Merchant who is getting started', () => {
	beforeAll( async () => {
		await merchant.login();
	} );

	it( 'Clicks on the Marketing > GLA link, clicks on the call-to-action setup button to go to the Setup MC page', async () => {
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
		const pageTitle = await page.title();
		expect( pageTitle ).toContain( 'Google Listings & Ads' );

		// click on the call-to-action button.
		const setupButton = (
			await page.$x( "//a[text()='Set up free listings in Google']" )
		 )[ 0 ];
		await setupButton.click();
		await page.waitForNavigation();

		// check we are in the Setup MC Step 1 page.
		const topBarText = (
			await page.$x(
				"//*[text()='Get started with Google Listings & Ads']"
			)
		 )[ 0 ];
		expect( topBarText ).toBeTruthy();

		// there are some API calls running in the page before the steps are displayed.
		// wait for the text in Step 1 to show up.
		const step1Text = await page.waitForXPath(
			"//*[text()='Set up your accounts']"
		);
		expect( step1Text ).toBeTruthy();
	} );
} );
