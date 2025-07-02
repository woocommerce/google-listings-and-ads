/**
 * Internal dependencies
 */
import ProductListingsPage from '../../utils/pages/onboarding/step-2-product-listings';
import {
	getCountryInputSearchBoxContainer,
	getCountryInputSearchBox,
	selectCountryFromSearchBox,
} from '../../utils/page';

/**
 * External dependencies
 */
const { test, expect } = require( '@playwright/test' );

test.use( { storageState: process.env.ADMINSTATE } );

test.describe.configure( { mode: 'serial' } );

/**
 * @type {import('../../utils/pages/onboarding/step-2-product-listings.js').default} productListingsPage
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

			// Mock MC target audience, only mocks GET method
			productListingsPage.fulfillTargetAudience(
				{
					location: 'selected',
					countries: [ 'US' ],
					locale: 'en_US',
					language: 'English',
				},
				[ 'GET' ]
			),

			// Mock WC default country as US
			productListingsPage.fulfillWCDefaultCountry( {
				woocommerce_default_country: 'US',
			} ),

			// Mock MC settings
			productListingsPage.fulfillSettings( {
				shipping_rate: 'flat',
				shipping_rates_count: 0,
				tax_rate: 'destination',
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

	test( 'should see US but should not see UK in the country search box', async () => {
		const countrySearchBoxContainer =
			getCountryInputSearchBoxContainer( page );
		await expect( countrySearchBoxContainer ).toContainText(
			'United States (US)'
		);
		await expect( countrySearchBoxContainer ).not.toContainText(
			'United Kingdom (UK)'
		);
	} );

	test( 'should see UK in the country search box and send the target audience POST request after selecting UK', async () => {
		const countrySearchBoxContainer =
			getCountryInputSearchBoxContainer( page );
		await expect( countrySearchBoxContainer ).not.toContainText(
			'United Kingdom (UK)'
		);

		const requestPromise = page.waitForRequest(
			( request ) =>
				request.url().includes( '/gla/mc/target_audience' ) &&
				request.method() === 'POST' &&
				request.postDataJSON().countries.includes( 'GB' )
		);

		await selectCountryFromSearchBox( page, 'United Kingdom (UK)' );

		await expect( countrySearchBoxContainer ).toContainText(
			'United Kingdom (UK)'
		);

		const request = await requestPromise;
		const response = await request.response();
		const responseBody = await response.json();

		expect( response.status() ).toBe( 201 );
		expect( responseBody.status ).toBe( 'success' );
		expect( responseBody.message ).toBe(
			'Successfully updated the Target Audience settings.'
		);
	} );

	test( 'should hide country search box after clicking "All countries"', async () => {
		const countrySearchBox = getCountryInputSearchBox( page );
		await expect( countrySearchBox ).toBeVisible();
		await productListingsPage.checkAllCountriesRadioButton();
		await expect( countrySearchBox ).not.toBeVisible();

		// Check the radio button of "Selected countries only" first in order to make the country search box visible again.
		await productListingsPage.checkSelectedCountriesOnlyRadioButton();
	} );

	test.describe( 'Automatic rate', () => {
		test.beforeAll( async () => {
			await page.reload();
		} );

		test( 'shouldnt display automatic rate if no shipping methods are set up, shipping_rates_count = 0', async () => {
			await expect(
				productListingsPage.getRecommendedShippingRateRadioRow()
			).toHaveCount( 0 );
		} );
	} );

	test.describe( 'Shipping rate is simple', () => {
		test.beforeAll( async () => {
			await productListingsPage.fulfillShippingTimes( [] );
			await page.reload();

			// Check another shipping rate first in case the simple shipping rate radio button is already checked.
			await productListingsPage.checkComplexShippingRateRadioButton();
		} );

		test( 'should send settings POST request after checking simple shipping rate radio button', async () => {
			const requestPromise =
				productListingsPage.registerShippingRateRadioButtonRequests(
					'flat'
				);

			// Check simple shipping rate
			await productListingsPage.checkSimpleShippingRateRadioButton();

			const request = await requestPromise;
			const response = await request.response();
			const responseBody = await response.json();

			expect( response.status() ).toBe( 200 );
			expect( responseBody.status ).toBe( 'success' );
			expect( responseBody.message ).toBe(
				'Merchant Center Settings successfully updated.'
			);
			expect( responseBody.data.shipping_rate ).toBe( 'flat' );
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

		test( 'should see "Minimum order to qualify for free shipping" text if fee shipping checkbox is checked', async () => {
			// Check the checkbox of "Offer free shipping for orders".
			await productListingsPage.checkOfferFreeShippingCheckbox();
			const minimumOrderForFreeShippingText =
				productListingsPage.getMinimumOrderForFreeShippingText();
			await expect( minimumOrderForFreeShippingText ).toBeVisible();
		} );

		test( 'should show error message if min or max shipping time is not set', async () => {
			await productListingsPage.clickContinueButton();
			const estimatedTimesError =
				productListingsPage.getEstimatedShippingTimesNullError();
			await expect( estimatedTimesError ).toBeVisible();
		} );

		test( 'should show error message if min shipping time is bigger than max time', async () => {
			await productListingsPage.fillEstimatedShippingTimes( '9', '7' );
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
		} );

		test( 'should send settings POST request after checking complex shipping rate radio button', async () => {
			const requestPromise =
				productListingsPage.registerShippingRateRadioButtonRequests(
					'manual'
				);

			// Check complex shipping rate
			await productListingsPage.checkComplexShippingRateRadioButton();

			const request = await requestPromise;
			const response = await request.response();
			const responseBody = await response.json();

			expect( response.status() ).toBe( 200 );
			expect( responseBody.status ).toBe( 'success' );
			expect( responseBody.message ).toBe(
				'Merchant Center Settings successfully updated.'
			);
			expect( responseBody.data.shipping_rate ).toBe( 'manual' );
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
			productListingsPage.fulfillSettings(
				{
					shipping_rate: 'flat',
					shipping_rates_count: 1, // Set shipping rates count to 1 to show the recommended shipping rate radio button.
				},
				200,
				[ 'GET' ]
			);

			await page.reload();
			// Check another shipping rate first in case the recommended shipping rate radio button is already checked.
			await productListingsPage.checkSimpleShippingRateRadioButton();
		} );

		test( 'should send settings POST request after checking recommended shipping rate radio button', async () => {
			const requestPromise =
				productListingsPage.registerShippingRateRadioButtonRequests(
					'automatic'
				);

			// Check recommended shipping rate
			await productListingsPage.checkRecommendedShippingRateRadioButton();

			const request = await requestPromise;
			const response = await request.response();
			const responseBody = await response.json();

			expect( response.status() ).toBe( 200 );
			expect( responseBody.status ).toBe( 'success' );
			expect( responseBody.message ).toBe(
				'Merchant Center Settings successfully updated.'
			);
			expect( responseBody.data.shipping_rate ).toBe( 'automatic' );
		} );

		test( 'should see "Estimated shipping times" field', async () => {
			const estimatedTimesCard =
				productListingsPage.getEstimatedShippingTimesCard();
			await expect( estimatedTimesCard ).toBeVisible();
		} );

		test( 'should send shipping times batch POST request aftering filling "Estimated shipping times" field', async () => {
			const requestPromise = page.waitForRequest(
				( request ) =>
					request.url().includes( '/gla/mc/shipping/times/batch' ) &&
					request.method() === 'POST' &&
					request.postDataJSON().time === 14
			);

			await productListingsPage.fillEstimatedShippingTimes( '14', '20' );

			const request = await requestPromise;
			const response = await request.response();
			const responseBody = await response.json();

			expect( response.status() ).toBe( 201 );
			expect( responseBody.success[ 0 ].status ).toBe( 'success' );
			expect( responseBody.success[ 0 ].message ).toBe(
				'Successfully added time for country: "US".'
			);
		} );
	} );

	test.describe( 'Links', () => {
		test.beforeAll( async () => {
			// Check simple shipping rate first so we can get "Shipping rates" and "Shipping times" fields.
			await productListingsPage.checkSimpleShippingRateRadioButton();
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
	} );

	test.describe( 'Click "Continue" button', () => {
		test.beforeAll( async () => {
			await productListingsPage.checkRecommendedShippingRateRadioButton();
			await productListingsPage.fillEstimatedShippingTimes( '14', '20' );
			await productListingsPage.fulfillBillingStatusRequest( {
				status: 'pending',
			} );
		} );

		test( 'should see the heading of next step after clicking "Continue"', async () => {
			await productListingsPage.clickContinueButton();

			await expect(
				page.getByRole( 'heading', {
					name: 'Create a campaign to advertise your products',
					exact: true,
				} )
			).toBeVisible();
		} );
	} );
} );
