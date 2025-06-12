/**
 * External dependencies
 */
import { expect, test } from '@playwright/test';

/**
 * Internal dependencies
 */
import { clearOnboardedMerchant } from '../../utils/api';
import priceBenchmarkSuggestionsData from '../../utils/__fixtures__/price-benchmark-suggestions.json';
import priceBenchmarkProductSuggestionsData from '../../utils/__fixtures__/price-benchmark-product-suggestions.json';
import PriceBenchmarkPage from '../../utils/pages/price-benchmark';

test.use( { storageState: process.env.ADMINSTATE } );

test.describe.configure( { mode: 'serial' } );

/**
 * @type {import('../../utils/pages/price-benchmark').default} priceBenchmarkPage
 */
let priceBenchmarkPage = null;

/**
 * @type {import('@playwright/test').Page} page
 */
let page = null;

const ACCOUNT_NOT_ENABLED_ERROR = {
	message: 'Unable to retrieve price benchmark data',
	errors: {
		forbidden:
			'Market Insights not enabled for account 5337422187. For more information check https://support.google.com/merchants/answer/9625913',
	},
};

test.describe( 'Price Benchmark Page', () => {
	test.beforeAll( async ( { browser } ) => {
		page = await browser.newPage();
		priceBenchmarkPage = new PriceBenchmarkPage( page );
		await Promise.all( [ priceBenchmarkPage.mockRequests() ] );
		await priceBenchmarkPage.goto();
	} );

	test.afterAll( async () => {
		await clearOnboardedMerchant();
		await page.close();
	} );

	test.describe( 'Price Comparison Chart Functionality', () => {
		test.beforeEach( async () => {
			await priceBenchmarkPage.goto();
		} );

		test( 'Renders loading state while fetching data', async () => {
			await priceBenchmarkPage.fulfillPriceBenchmarkSuggestions( [] );

			// Set up the handler for the summary request that won't resolve immediately
			const deferredHandler = priceBenchmarkPage.withFulfillDeferred();
			await deferredHandler.fulfillPriceBenchmarkSummary( {
				price_higher: 10,
				price_lower: 20,
				price_similar: 30,
				price_unknown: 40,
				total_products: 100,
			} );

			const loadingElement = page.locator(
				'.gla-horizontal-stacked-bar--loading'
			);
			await expect( loadingElement ).toBeVisible();

			deferredHandler.continueFulfill();
			await expect( loadingElement ).toBeHidden();
		} );

		test( 'Does not render the chart if there are no products', async () => {
			await priceBenchmarkPage.fulfillPriceBenchmarkSuggestions( [] );
			await priceBenchmarkPage.fulfillPriceBenchmarkSummary( {
				price_higher: 0,
				price_lower: 0,
				price_similar: 0,
				price_unknown: 0,
				total_products: 0,
			} );

			const emptyStateNotice = page.locator(
				'.gla-price-benchmark__empty-metrics'
			);

			await expect( emptyStateNotice ).toBeVisible();

			const comparisonChartElement = page.locator(
				'.gla-price-benchmark__comparison-chart'
			);
			await expect( comparisonChartElement ).not.toBeVisible();
		} );

		test( 'Does not render the chart if Market Insights not enabled for the account.', async () => {
			await priceBenchmarkPage.fulfillPriceBenchmarkSuggestions( [] );

			await priceBenchmarkPage.fulfillPriceBenchmarkSummary(
				ACCOUNT_NOT_ENABLED_ERROR,
				403
			);

			// Wait for the summary URL to be requested before assertions
			await page.waitForRequest(
				/\/wc\/gla\/mc\/price-benchmarks\/summary\b/
			);

			const errorMessage = page.locator(
				'.components-snackbar__content'
			);
			await expect( errorMessage ).not.toBeVisible();

			const comparisonChartElement = page.locator(
				'.gla-price-benchmark__comparison-chart'
			);
			await expect( comparisonChartElement ).not.toBeVisible();

			const emptyStateNotice = page.locator(
				'.gla-price-benchmark__empty-metrics'
			);

			await expect( emptyStateNotice ).toBeVisible();
		} );

		test( 'Renders an error message for summary API errors other than 403', async () => {
			await priceBenchmarkPage.fulfillPriceBenchmarkSuggestions( [] );

			await priceBenchmarkPage.fulfillPriceBenchmarkSummary(
				{
					message: 'An error occured.',
				},
				404
			);

			const errorMessage = page.locator(
				'.components-snackbar__content'
			);
			await expect( errorMessage ).toBeVisible();
			await expect( errorMessage ).toContainText(
				'There was an error getting the price benchmark summary. An error occured.'
			);
		} );

		test( 'Render the chart if there are products', async () => {
			await priceBenchmarkPage.fulfillPriceBenchmarkSuggestions(
				priceBenchmarkSuggestionsData
			);
			await priceBenchmarkPage.fulfillPriceBenchmarkSummary( {
				price_higher: 10,
				price_lower: 20,
				price_similar: 30,
				price_unknown: 40,
				total_products: 100,
			} );

			const comparisonChartElement = page.locator(
				'.gla-price-benchmark__comparison-chart'
			);
			await expect( comparisonChartElement ).toBeVisible();
		} );
	} );

	test.describe( 'Price Benchmark Suggestions Functionality', () => {
		test.beforeEach( async () => {
			await priceBenchmarkPage.goto();
		} );

		test( 'Shows no results if there is no data', async () => {
			await priceBenchmarkPage.fulfillPriceBenchmarkSuggestions( [] );

			const emptyStateNotice = page.locator(
				'.gla-price-benchmark__empty-metrics'
			);

			await expect( emptyStateNotice ).toBeVisible();
			await expect( emptyStateNotice ).toContainText(
				'You do not have any sale price suggestions at this moment.'
			);
			await expect( emptyStateNotice ).toContainText(
				'Find out if you meet all eligibility criteria to receive suggestions in the future.'
			);
		} );

		test( 'Shows empty state notice when Market Insights is not enabled for the account.', async () => {
			await priceBenchmarkPage.fulfillPriceBenchmarkSuggestions(
				ACCOUNT_NOT_ENABLED_ERROR,
				403
			);
			const emptyStateNotice = page.locator(
				'.gla-price-benchmark__empty-metrics'
			);
			await expect( emptyStateNotice ).toBeVisible();
			await expect( emptyStateNotice ).toContainText(
				'You do not have any sale price suggestions at this moment.'
			);
			await expect( emptyStateNotice ).toContainText(
				'Find out if you meet all eligibility criteria to receive suggestions in the future.'
			);
		} );

		test( 'Displays the product and action columns only in the table when screen is resized to 400px', async () => {
			await priceBenchmarkPage.fulfillPriceBenchmarkSuggestions(
				priceBenchmarkSuggestionsData
			);
			await page.setViewportSize( { width: 400, height: 800 } );

			const tableHeaderColumns = page.locator( 'table thead tr th' );
			await expect( tableHeaderColumns ).toHaveCount( 2 );

			await expect( tableHeaderColumns.nth( 0 ) ).toHaveText( 'Product' );
			await expect( tableHeaderColumns.nth( 1 ) ).toHaveText( 'Action' );

			await page.setViewportSize( { width: 1280, height: 720 } );
		} );

		test( 'Navigates to the next page and triggers a request with page=2', async () => {
			const nextPageButton = page.locator( '[aria-label="Next page"]' );

			const [ pageRequest ] = await Promise.all( [
				page.waitForRequest(
					( request ) =>
						request
							.url()
							.includes( '/wc/gla/mc/price-benchmarks' ) &&
						request.url().includes( 'page=2' ) &&
						request.method() === 'GET'
				),
				nextPageButton.click(),
			] );

			expect( pageRequest ).toBeTruthy();
		} );

		test( 'Displays error message when data view fails to load', async () => {
			await priceBenchmarkPage.fulfillPriceBenchmarkSuggestions( [] );
			await priceBenchmarkPage.goto();

			// Mock 500 response for the data view script only once.
			const once = priceBenchmarkPage.withFulfillTimes( 1 );
			await once.fulfillRequest(
				/\/js\/build\/wp-dataviews-shim.js(\/.*)?\b/,
				{},
				500,
				[ 'GET' ]
			);

			const errorMessage = page.locator(
				'.gla-price-benchmark__error-message'
			);
			await expect( errorMessage ).toBeVisible();
			await expect( errorMessage ).toContainText(
				'There was an error loading the price benchmark suggestions.'
			);
		} );

		test( 'FAQ link is present and has the correct URL', async () => {
			await priceBenchmarkPage.fulfillPriceBenchmarkSuggestions(
				priceBenchmarkSuggestionsData
			);
			// Get the faq link by .dataviews__view-actions > .components-external-link by text.
			const faqLink = page.locator(
				'.dataviews__view-actions .components-external-link'
			);
			await expect( faqLink ).toBeVisible();
			await expect( faqLink ).toContainText( 'FAQ' );
			await expect( faqLink ).toHaveAttribute(
				'href',
				'https://woocommerce.com/document/google-for-woocommerce/faq/'
			);
		} );
	} );

	test.describe( 'Change Price Modal Functionality', () => {
		test( 'Clicking "Change Price" link renders the modal', async () => {
			await priceBenchmarkPage.goto();
			await priceBenchmarkPage.fulfillPriceBenchmarkSuggestions(
				priceBenchmarkSuggestionsData
			);
			await priceBenchmarkPage.fulfillPriceBenchmarkProductSuggestions(
				33,
				priceBenchmarkProductSuggestionsData
			);

			await priceBenchmarkPage.fulfillWCProduct(
				{
					regular_price: '100.00',
				},
				[ 'GET' ]
			);

			const changePriceLink =
				await priceBenchmarkPage.getFirstProductChangePriceLink();
			await changePriceLink.click();

			const changePriceModal =
				await priceBenchmarkPage.getChangePriceModal();
			await expect( changePriceModal ).toBeVisible();
		} );

		test( 'Clicking the close button closes the modal', async () => {
			const closeButton = await priceBenchmarkPage.getCloseModalButton();
			await closeButton.click();

			const changePriceModal =
				await priceBenchmarkPage.getChangePriceModal();
			await expect( changePriceModal ).not.toBeVisible();
		} );

		test( 'Displays error message when user inputs a negative price', async () => {
			await priceBenchmarkPage.goto();
			await priceBenchmarkPage.fulfillPriceBenchmarkSuggestions(
				priceBenchmarkSuggestionsData
			);
			await priceBenchmarkPage.fulfillPriceBenchmarkProductSuggestions(
				33,
				priceBenchmarkProductSuggestionsData
			);
			await priceBenchmarkPage.fulfillWCProduct(
				{
					regular_price: '100.00',
				},
				[ 'GET' ]
			);

			// Open the modal again
			const changePriceLink =
				await priceBenchmarkPage.getFirstProductChangePriceLink();
			await changePriceLink.click();

			const priceInput = await priceBenchmarkPage.getPriceInputModal();
			await priceInput.fill( '-5' );
			await priceInput.blur();

			const error = priceBenchmarkPage.getPriceInputError();
			await expect( error ).toHaveText(
				'New price must be greater than or equals to zero.'
			);

			const changePriceButton =
				await priceBenchmarkPage.getChangePriceModalButton();
			await expect( changePriceButton ).toBeDisabled();
		} );

		test( 'Clicking "Change Price" button with a valid price closes the modal and updates the table', async () => {
			await priceBenchmarkPage.goto();

			await priceBenchmarkPage.fulfillPriceBenchmarkSuggestions(
				priceBenchmarkSuggestionsData
			);
			await priceBenchmarkPage.fulfillPriceBenchmarkProductSuggestions(
				33,
				priceBenchmarkProductSuggestionsData
			);

			await priceBenchmarkPage.fulfillWCProduct(
				{
					regular_price: '100.00',
				},
				[ 'GET' ]
			);

			await priceBenchmarkPage.fulfillWCProduct(
				{
					regular_price: '120.00',
				},
				[ 'POST' ]
			);

			const changePriceLink =
				await priceBenchmarkPage.getFirstProductChangePriceLink();
			await changePriceLink.click();

			const priceInput = await priceBenchmarkPage.getPriceInputModal();
			await priceInput.fill( '120' );
			priceInput.blur();

			const error = priceBenchmarkPage.getPriceInputError();
			await expect( error ).not.toBeVisible();

			const changePriceButton =
				await priceBenchmarkPage.getChangePriceModalButton();
			await expect( changePriceButton ).toBeEnabled();
			await changePriceButton.click();

			const changePriceModal =
				await priceBenchmarkPage.getChangePriceModal();
			await expect( changePriceModal ).not.toBeVisible();

			const firstProductCells = await priceBenchmarkPage
				.getFirstProductRow()
				.locator( 'td' );
			await expect( firstProductCells.nth( 2 ) ).toHaveText(
				'NT$120.00'
			);
		} );

		test( 'Changes price for a product variation', async () => {
			await priceBenchmarkPage.goto();
			await priceBenchmarkPage.fulfillPriceBenchmarkSuggestions(
				priceBenchmarkSuggestionsData
			);
			await priceBenchmarkPage.fulfillPriceBenchmarkProductSuggestions(
				3,
				priceBenchmarkProductSuggestionsData
			);
			await priceBenchmarkPage.fulfillWCProduct(
				{
					id: 3,
					parent_id: 33,
					type: 'variation',
					regular_price: '100.00',
				},
				[ 'GET' ]
			);

			// Setup the POST request handler and create a promise to track if it's called
			let variationRequestReceived = false;
			const variationRequestPromise = page.waitForRequest(
				( request ) => {
					if (
						request.method() === 'POST' &&
						request.url().includes( '/products/33/variations/3' )
					) {
						variationRequestReceived = true;
						return true;
					}
					return false;
				}
			);

			// Mock POST response for updating the variation
			await priceBenchmarkPage.fulfillRequest(
				/\/products\/33\/variations\/3\b/,
				{
					id: 3,
					parent_id: 33,
					type: 'variation',
					regular_price: '120.00',
				},
				200,
				[ 'POST' ]
			);

			// Click the change price link
			const changePriceLink =
				await priceBenchmarkPage.getFirstProductChangePriceLink();
			await changePriceLink.click();

			// Verify the modal is displayed
			const changePriceModal =
				await priceBenchmarkPage.getChangePriceModal();
			await expect( changePriceModal ).toBeVisible();

			// Input the new price and submit
			const priceInput = await priceBenchmarkPage.getPriceInputModal();
			await priceInput.fill( '220' );
			await priceInput.blur();

			const changePriceButton =
				await priceBenchmarkPage.getChangePriceModalButton();
			await expect( changePriceButton ).toBeEnabled();

			// Click the change price button and wait for the request to be made
			await Promise.all( [
				variationRequestPromise,
				changePriceButton.click(),
			] );

			// Check if the correct endpoint was called
			expect( variationRequestReceived ).toBeTruthy();

			// Verify the modal is closed
			await expect( changePriceModal ).not.toBeVisible();

			// Verify the table shows the updated price
			const firstProductCells = await priceBenchmarkPage
				.getFirstProductRow()
				.locator( 'td' );
			await expect( firstProductCells.nth( 2 ) ).toHaveText(
				'NT$220.00'
			);
		} );

		test.describe( 'Sales Price Functionality', () => {
			test( 'Displays "This product is currently on sale." text when there is a sale price', async () => {
				await priceBenchmarkPage.goto();

				await priceBenchmarkPage.fulfillPriceBenchmarkSuggestions(
					priceBenchmarkSuggestionsData
				);
				await priceBenchmarkPage.fulfillPriceBenchmarkProductSuggestions(
					33,
					priceBenchmarkProductSuggestionsData
				);

				await priceBenchmarkPage.fulfillWCProduct(
					{
						regular_price: '100.00',
						sale_price: '90.00',
						on_sale: true,
					},
					[ 'GET' ]
				);

				const changePriceLink =
					await priceBenchmarkPage.getFirstProductChangePriceLink();
				await changePriceLink.click();

				const changePriceModal =
					await priceBenchmarkPage.getChangePriceModal();

				const saleText = changePriceModal.locator(
					'.components-notice__content:has-text("This product is currently on sale.")'
				);
				await expect( saleText ).toBeVisible();
			} );

			test( 'Displays error message when user inputs a price lower than the sale price', async () => {
				const priceInput =
					await priceBenchmarkPage.getPriceInputModal();
				await priceInput.fill( '80' );
				await priceInput.blur();

				const error = priceBenchmarkPage.getPriceInputError();
				await expect( error ).toHaveText(
					'New price must be greater than the sales price (NT$90.00).'
				);

				const changePriceButton =
					await priceBenchmarkPage.getChangePriceModalButton();
				await expect( changePriceButton ).toBeDisabled();
			} );

			test( 'Allows changing the price to a value higher than the sales price', async () => {
				const priceInput =
					await priceBenchmarkPage.getPriceInputModal();
				await priceInput.fill( '91' );
				await priceInput.blur();

				const changePriceButton =
					await priceBenchmarkPage.getChangePriceModalButton();
				await expect( changePriceButton ).toBeEnabled();
				await changePriceButton.click();

				const changePriceModal =
					await priceBenchmarkPage.getChangePriceModal();
				await expect( changePriceModal ).not.toBeVisible();

				const firstProductCells = await priceBenchmarkPage
					.getFirstProductRow()
					.locator( 'td' );
				await expect( firstProductCells.nth( 2 ) ).toHaveText(
					'NT$91.00'
				);
			} );

			test( 'Does not display "Product is currently on sale" text when there is no sale price', async () => {
				await priceBenchmarkPage.goto();

				await priceBenchmarkPage.fulfillPriceBenchmarkSuggestions(
					priceBenchmarkSuggestionsData
				);

				await priceBenchmarkPage.fulfillWCProduct(
					{
						regular_price: '100.00',
					},
					[ 'GET' ]
				);

				const changePriceLink =
					await priceBenchmarkPage.getFirstProductChangePriceLink();
				await changePriceLink.click();

				const changePriceModal =
					await priceBenchmarkPage.getChangePriceModal();

				const saleText = changePriceModal.locator(
					'span.gla-badge__content:has-text("Product is currently on sale")'
				);
				await expect( saleText ).not.toBeVisible();
			} );

			test( 'Does not display "Product is currently on sale" text when the product is not on sale but has a sale price', async () => {
				await priceBenchmarkPage.goto();

				await priceBenchmarkPage.fulfillPriceBenchmarkSuggestions(
					priceBenchmarkSuggestionsData
				);
				await priceBenchmarkPage.fulfillPriceBenchmarkProductSuggestions(
					33,
					priceBenchmarkProductSuggestionsData
				);

				await priceBenchmarkPage.fulfillWCProduct(
					{
						regular_price: '100.00',
						sale_price: '90.00',
						on_sale: false,
					},
					[ 'GET' ]
				);

				const changePriceLink =
					await priceBenchmarkPage.getFirstProductChangePriceLink();
				await changePriceLink.click();

				const changePriceModal =
					await priceBenchmarkPage.getChangePriceModal();

				const saleText = changePriceModal.locator(
					'span.gla-badge__content:has-text("Product is currently on sale")'
				);
				await expect( saleText ).not.toBeVisible();
			} );
		} );
	} );

	test.describe( 'Price Benchmark Suggestions Banner', () => {
		test( 'Shows the banner when the user is not onboarded', async () => {
			await priceBenchmarkPage.goto();
			await priceBenchmarkPage.fulfillPriceBenchmarkSuggestions(
				priceBenchmarkSuggestionsData
			);

			const banner = page.locator(
				'.gla-price-benchmark-suggestions-banner'
			);

			await expect( banner ).toBeVisible();

			const title = banner.locator(
				'.gla-price-benchmark-suggestions-banner__text h3'
			);
			await expect( title ).toContainText(
				'Price Benchmark & Suggestions'
			);
		} );

		test( 'Hides the banner when dismissed', async () => {
			await priceBenchmarkPage.fulfillUsersPreferences();
			await priceBenchmarkPage.fulfillPriceBenchmarkSuggestions(
				priceBenchmarkSuggestionsData
			);

			const banner = page.locator(
				'.gla-price-benchmark-suggestions-banner'
			);
			const dismissButton = banner.locator(
				'button:has-text("Dismiss")'
			);

			await dismissButton.click();

			await expect( banner ).not.toBeVisible();
		} );
	} );
} );
