/**
 * Internal dependencies
 */
import ProductListingsPage from '../../utils/pages/setup-mc/step-2-product-listings';

/**
 * External dependencies
 */
const { test, expect } = require( '@playwright/test' );

test.use( { storageState: process.env.ADMINSTATE } );

test.describe.configure( { mode: 'serial' } );

/**
 * @type {import('../../utils/pages/setup-mc/step-2-product-listings.js').default} productListingsPage
 */
let productListingsPage = null;

/**
 * @type {import('@playwright/test').Page} page
 */
let page = null;

test.describe( 'Configure product listings', () => {
	test.beforeAll( async ( { browser } ) => {
		page = await browser.newPage();
		productListingsPage = new ProductListingsPage( page );
		await Promise.all( [
			// Mock Jetpack as connected
			productListingsPage.mockJetpackConnected(),

			// Mock google as connected.
			productListingsPage.mockGoogleConnected(),

			// Mock Merchant Center as connected
			productListingsPage.mockMCConnected(),

			// Mock MC step as product_listings
			productListingsPage.mockMCSetup( 'incomplete', 'product_listings' ),

			// Mock MC target audience
			productListingsPage.fulfillTargetAudience( {
				location: 'selected',
				countries: [ 'US' ],
				locale: 'en_US',
				language: 'English',
			} ),

			// Mock WC default country as US
			productListingsPage.fulfillWCDefaultCountry( {
				woocommerce_default_country: 'US',
			} ),
		] );

		await productListingsPage.goto();
	} );

	test.afterAll( async () => {
		await productListingsPage.closePage();
	} );

	test( 'should see the heading and the texts below', async () => {
		await expect(
			page.getByRole( 'heading', {
				name: 'Configure your product listings',
			} )
		).toBeVisible();

		await expect(
			page.getByText(
				'Your product listings will look something like this.'
			)
		).toBeVisible();

		await expect(
			page.getByText(
				'our product details, estimated shipping info and tax details will be displayed across Google.'
			)
		).toBeVisible();
	} );

	test( 'should select the default language English', async () => {
		const languageRadioRow = productListingsPage.getLanguageRadioRow();
		await expect( languageRadioRow ).toBeChecked();
	} );

	test( 'should see US but should not see UK in the country search box', async () => {
		const countrySearchBoxContainer =
			productListingsPage.getCountryInputSearchBoxContainer();
		await expect( countrySearchBoxContainer ).toContainText(
			'United States (US)'
		);
		await expect( countrySearchBoxContainer ).not.toContainText(
			'United Kingdom (UK)'
		);
	} );

	test( 'should see UK in the country search box after selecting UK', async () => {
		const countrySearchBoxContainer =
			productListingsPage.getCountryInputSearchBoxContainer();
		await expect( countrySearchBoxContainer ).not.toContainText(
			'United Kingdom (UK)'
		);
		await productListingsPage.selectCountryFromSearchBox(
			'United Kingdom (UK)'
		);
		await expect( countrySearchBoxContainer ).toContainText(
			'United Kingdom (UK)'
		);
	} );

	test( 'should hide country search box after clicking "All countries"', async () => {
		const countrySearchBox = productListingsPage.getCountryInputSearchBox();
		await expect( countrySearchBox ).toBeVisible();
		await productListingsPage.checkAllCountriesRadioButton();
		await expect( countrySearchBox ).not.toBeVisible();

		// Check the radio button of "Selected countries only" first in order to make the country search box visible again.
		await productListingsPage.checkSelectedCountriesOnlyRadioButton();
	} );

	test( 'should still see "Tax rate (required for U.S. only)" even if deselect US when the default country is US', async () => {
		const taxRateSection = productListingsPage.getTaxRateSection();
		await expect( taxRateSection ).toBeVisible();
		await productListingsPage.removeCountryFromSearchBox(
			'United States (US)'
		);
		await expect( taxRateSection ).toBeVisible();
	} );

	test( 'should hide "Tax rate (required for U.S. only)" if deselect US when the default country is not US', async () => {
		// Mock WC default country as TW, because Tax rate will always be shown if the default country is US.
		await productListingsPage.fulfillWCDefaultCountry( {
			woocommerce_default_country: 'TW',
		} );
		await page.reload();

		// Check the radio button of "Selected countries only" first in order to ensure the country search box is visible.
		await productListingsPage.checkSelectedCountriesOnlyRadioButton();

		const taxRateSection = productListingsPage.getTaxRateSection();
		await expect( taxRateSection ).toBeVisible();

		await productListingsPage.removeCountryFromSearchBox(
			'United States (US)'
		);

		await expect( taxRateSection ).not.toBeVisible();
	} );

	test.describe( 'Shipping rate is simple', () => {
		test.beforeAll( async () => {
			await page.reload();
			await productListingsPage.checkSimpleShippingRateRadioButton();
		} );

		test( 'should see "Estimated shipping rates" field', async () => {
			const estimatedRatesCard =
				productListingsPage.getEstimatedShippingRatesCard();
			const estimatedRatesInputBox =
				productListingsPage.getEstimatedShippingRatesInputBox();
			await expect( estimatedRatesCard ).toBeVisible();
			await expect( estimatedRatesInputBox ).toBeVisible();
		} );

		test( 'should see "Free shipping for all orders" tag if shipping rate is 0', async () => {
			await productListingsPage.fillEstimatedShippingRates( '0' );
			const freeShippingForAllOrdersTag =
				productListingsPage.getFreeShippingForAllOrdersTag();
			await expect( freeShippingForAllOrdersTag ).toBeVisible();
		} );

		test( 'should see "I offer free shipping for orders over a certain price" text if shipping rate is > 0', async () => {
			await productListingsPage.fillEstimatedShippingRates( '1' );
			const offerFreeShippingForOrdersText =
				productListingsPage.getOfferFreeShippingForOrdersText();
			await expect( offerFreeShippingForOrdersText ).toBeVisible();
		} );

		test( 'should see "Minimum order to qualify for free shipping" text if "offer free shipping for order..." is "Yes"', async () => {
			// Check the "Yes" button of "Offer free shipping for orders".
			await productListingsPage.checkOfferFreeShippingForOrdersRadioButton( 'Yes' );
			const minimumOrderForFreeShippingText =
				productListingsPage.getMinimumOrderForFreeShippingText();
			await expect( minimumOrderForFreeShippingText ).toBeVisible();
		} );

		test( 'should show error message if clicking "Continue" button when shipping time is < 0', async () => {
			await productListingsPage.fillEstimatedShippingTimes( '-1' );
			await productListingsPage.clickContinueButton();
			const estimatedTimesError =
				productListingsPage.getEstimatedShippingTimesError();
			await expect( estimatedTimesError ).toBeVisible();
		} );
	} );

	test.describe( 'Shipping rate is complex', () => {
		let estimatedRatesCard;
		let estimatedTimesCard;

		test.beforeAll( async () => {
			// Check simple shipping rate first so we can get "Shipping rates" and "Shipping times" fields.
			await productListingsPage.checkSimpleShippingRateRadioButton();
			estimatedRatesCard =
				productListingsPage.getEstimatedShippingRatesCard();
			estimatedTimesCard =
				productListingsPage.getEstimatedShippingTimesCard();

			// Check complex shipping rate
			await productListingsPage.checkComplexShippingRateRadioButton();
		} );

		test( 'should not see "Estimated shipping rates" and "Estimated shipping times" field', async () => {
			await expect( estimatedRatesCard ).not.toBeVisible();
			await expect( estimatedTimesCard ).not.toBeVisible();
		} );

		test( 'should see the "Continue" to be enabled', async () => {
			const continueButton = productListingsPage.getContinueButton();
			await expect( continueButton ).toBeEnabled();
		} );
	} );

	test.describe( 'Shipping rate is recommended', () => {
		test.beforeAll( async () => {
			await productListingsPage.checkRecommendedShippingRateRadioButton();
		} );

		test( 'should see "Estimated shipping times" field', async () => {
			const estimatedTimesCard =
				productListingsPage.getEstimatedShippingTimesCard();
			await expect( estimatedTimesCard ).toBeVisible();
		} );
	} );

	test.describe( 'Links', () => {
		test.beforeAll( async () => {
			// Check simple shipping rate first so we can get "Shipping rates" and "Shipping times" fields.
			await productListingsPage.checkSimpleShippingRateRadioButton();
		} );

		test( 'should contain the correct URL for "Read more for Language" link', async () => {
			const link = productListingsPage.getReadMoreLanguageLink();
			await expect( link ).toBeVisible();
			await expect( link ).toHaveAttribute(
				'href',
				'https://support.google.com/merchants/answer/160637'
			);
		} );

		test( 'should contain the correct URL for "Read more for Shipping Rates" link', async () => {
			const link = productListingsPage.getReadMoreShippingRatesLink();
			await expect( link ).toBeVisible();
			await expect( link ).toHaveAttribute(
				'href',
				'https://support.google.com/merchants/answer/7050921'
			);
		} );

		test( 'should contain the correct URL for "Read more for Shipping Times" link', async () => {
			const link = productListingsPage.getReadMoreShippingTimesLink();
			await expect( link ).toBeVisible();
			await expect( link ).toHaveAttribute(
				'href',
				'https://support.google.com/merchants/answer/7050921'
			);
		} );

		test( 'should contain the correct URL for "Read more for Tax Rate" link', async () => {
			const link = productListingsPage.getReadMoreTaxRateLink();
			await expect( link ).toBeVisible();
			await expect( link ).toHaveAttribute(
				'href',
				'https://support.google.com/merchants/answer/160162'
			);
		} );
	} );
} );
