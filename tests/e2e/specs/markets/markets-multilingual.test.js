/**
 * External dependencies
 */
import { expect, test } from '@playwright/test';

/**
 * Internal dependencies
 */
import {
	clearOnboardedMerchant,
	clearServiceBasedMerchant,
	setOnboardedMerchant,
} from '../../utils/api';
import { removeCountryFromSearchBox } from '../../utils/page';
import MarketsPage, {
	PRIMARY_MARKET,
	SECONDARY_MARKET,
} from '../../utils/pages/markets';

test.use( { storageState: process.env.ADMINSTATE } );

test.describe.configure( { mode: 'serial' } );

/**
 * @type {MarketsPage}
 */
let marketsPage = null;

/**
 * @type {import('@playwright/test').Page}
 */
let page = null;

test.describe( 'Markets – multilingual store', () => {
	test.beforeAll( async ( { browser } ) => {
		page = await browser.newPage();
		marketsPage = new MarketsPage( page );
		await setOnboardedMerchant();
		await clearServiceBasedMerchant();
	} );

	test.afterAll( async () => {
		await clearOnboardedMerchant();
		await page.close();
	} );

	test.describe( 'Shipping Rate - Manual', () => {
		test.beforeAll( async () => {
			await marketsPage.mockIsMultiLingualStore( true );
			await marketsPage.mockMarketsPageRequests( {
				shippingRate: 'manual',
				multilingual: true,
			} );
			await marketsPage.goto();
			await marketsPage.waitForMarketsTable();
		} );

		test( 'table shows Market, Language, and Currency columns', async () => {
			await expect(
				page.getByRole( 'columnheader', { name: 'Market' } )
			).toBeVisible();
			await expect(
				page.getByRole( 'columnheader', { name: 'Language' } )
			).toBeVisible();
			await expect(
				page.getByRole( 'columnheader', { name: 'Currency' } )
			).toBeVisible();

			await expect(
				page.getByRole( 'columnheader', { name: 'Country' } )
			).not.toBeVisible();
			await expect(
				page.getByRole( 'columnheader', { name: 'Shipping rate' } )
			).not.toBeVisible();
		} );

		test( 'both primary and secondary market rows are visible', async () => {
			await expect(
				page.getByRole( 'cell', { name: /Primary Market/ } )
			).toBeVisible();
			await expect(
				page.getByRole( 'cell', { name: 'France' } )
			).toBeVisible();
		} );

		test( 'global search filters the markets table', async () => {
			const searchBox = page.getByRole( 'searchbox', {
				name: 'Search',
			} );

			await searchBox.fill( 'France' );

			await expect(
				page.getByRole( 'cell', { name: 'France' } )
			).toBeVisible();
			await expect(
				page.getByRole( 'cell', { name: /Primary Market/ } )
			).not.toBeVisible();

			await searchBox.fill( '' );
			await expect(
				page.getByRole( 'cell', { name: /Primary Market/ } )
			).toBeVisible();
		} );

		test( 'Delete is absent for primary and present for secondary', async () => {
			const deleteButtons = marketsPage.getDeleteButtons();
			await expect( deleteButtons ).toHaveCount( 1 );

			await expect(
				page
					.getByRole( 'row' )
					.filter( { hasText: /Primary Market/ } )
					.getByRole( 'button', { name: 'Actions' } )
			).not.toBeAttached();

			await expect(
				page
					.getByRole( 'row' )
					.filter( { hasText: 'France' } )
					.getByRole( 'button', { name: 'Actions' } )
			).toBeEnabled();
		} );

		test( 'Edit modal for primary market shows countries, locale section, and shipping notice', async () => {
			await marketsPage.getEditButtonForRow( /Primary Market/ ).click();

			const modal = marketsPage.getEditPrimaryMarketModal();
			await expect( modal ).toBeVisible();

			await expect(
				modal.locator( '.woocommerce-tree-select-control' )
			).toBeVisible();

			const localeSection = marketsPage.getLocaleSection( modal );
			await expect( localeSection ).toBeVisible();
			await expect(
				localeSection.getByText( 'Language', { exact: true } )
			).toBeVisible();
			await expect(
				localeSection.getByText( 'Currency', { exact: true } )
			).toBeVisible();

			await expect(
				marketsPage.getShippingInfoNotice( modal )
			).toContainText( 'Shipping is managed in Google Merchant Center' );

			await expect(
				modal.getByLabel( 'Free shipping over a specific order value' )
			).not.toBeAttached();

			await modal.getByRole( 'button', { name: 'Cancel' } ).click();
			await expect( modal ).not.toBeVisible();
		} );

		test( 'secondary market edit has no audience section but shows shipping notice; Add modal shows country select and notice', async () => {
			await marketsPage.getEditButtonForRow( 'France' ).click();
			const editModal = marketsPage.getEditMarketModal( 'France' );
			await expect( editModal ).toBeVisible();
			await expect(
				editModal.locator( '.woocommerce-tree-select-control' )
			).not.toBeAttached();
			await expect(
				marketsPage.getShippingInfoNotice( editModal )
			).toContainText( 'Shipping is managed in Google Merchant Center' );
			await editModal.getByRole( 'button', { name: 'Cancel' } ).click();
			await expect( editModal ).not.toBeVisible();

			await marketsPage.getHeaderAddMarketButton().click();
			const addModal = marketsPage.getAddMarketModal();
			await expect( addModal ).toBeVisible();
			await expect( addModal.getByLabel( 'Market' ) ).toBeVisible();
			await expect(
				marketsPage.getShippingInfoNotice( addModal )
			).toContainText( 'Shipping is managed in Google Merchant Center' );
			await addModal.getByRole( 'button', { name: 'Cancel' } ).click();
			await expect( addModal ).not.toBeVisible();
		} );

		test( 'successful save closes the Edit modal', async () => {
			await marketsPage.fulfillMarketUpdate(
				PRIMARY_MARKET.id,
				PRIMARY_MARKET
			);
			await marketsPage.fulfillMarkets( [
				PRIMARY_MARKET,
				SECONDARY_MARKET,
			] );

			await marketsPage.getEditButtonForRow( /Primary Market/ ).click();

			const modal = marketsPage.getEditPrimaryMarketModal();
			await expect( modal ).toBeVisible();

			await modal.getByRole( 'button', { name: 'Save' } ).click();

			await expect( modal ).not.toBeVisible();
		} );

		test( "removing a country from the primary market's audience and saving sends the updated country list", async () => {
			await marketsPage.fulfillMarketUpdate(
				PRIMARY_MARKET.id,
				PRIMARY_MARKET
			);

			await marketsPage.getEditButtonForRow( /Primary Market/ ).click();
			const modal = marketsPage.getEditPrimaryMarketModal();

			await removeCountryFromSearchBox( page, 'Canada' );

			const updateRequest = page.waitForRequest(
				( request ) =>
					new RegExp(
						`\\/wc\\/gla\\/mc\\/markets\\/${ PRIMARY_MARKET.id }\\b`
					).test( request.url() ) && request.method() === 'POST'
			);

			await modal.getByRole( 'button', { name: 'Save' } ).click();

			const body = ( await updateRequest ).postDataJSON();
			expect( body.countries ).toEqual( [ 'US' ] );
		} );

		test( 'selecting multiple currencies for a market saves all of them', async () => {
			await marketsPage.fulfillMarketUpdate( SECONDARY_MARKET.id, {
				...SECONDARY_MARKET,
				language: [ 'fr', 'en' ],
				currency: [ 'EUR', 'USD' ],
			} );

			await marketsPage.getEditButtonForRow( 'France' ).click();
			const modal = marketsPage.getEditMarketModal( 'France' );

			await modal
				.locator(
					'.gla-searchable-select-control:has-text("Language")'
				)
				.getByRole( 'combobox' )
				.click();
			await modal.getByRole( 'option', { name: 'English' } ).click();

			await modal
				.locator(
					'.gla-searchable-select-control:has-text("Currency")'
				)
				.getByRole( 'combobox' )
				.click();
			await modal.getByRole( 'option', { name: 'USD' } ).click();

			const updateRequest = page.waitForRequest(
				( request ) =>
					new RegExp(
						`\\/wc\\/gla\\/mc\\/markets\\/${ SECONDARY_MARKET.id }\\b`
					).test( request.url() ) && request.method() === 'POST'
			);

			await modal.getByRole( 'button', { name: 'Save' } ).click();

			const body = ( await updateRequest ).postDataJSON();
			expect( body.currency.sort() ).toEqual( [ 'EUR', 'USD' ] );
		} );

		test( 'API error shows snackbar and keeps Edit modal open', async () => {
			await marketsPage.fulfillMarketUpdate(
				PRIMARY_MARKET.id,
				{ message: 'Internal server error' },
				500
			);

			await marketsPage.getEditButtonForRow( /Primary Market/ ).click();

			const modal = marketsPage.getEditPrimaryMarketModal();
			await expect( modal ).toBeVisible();

			await modal.getByRole( 'button', { name: 'Save' } ).click();

			await expect(
				page.locator( '.components-snackbar__content' ).last()
			).toBeVisible();

			await expect( modal ).toBeVisible();

			await modal.getByRole( 'button', { name: 'Cancel' } ).click();
		} );

		test( 'successful save closes the Add modal', async () => {
			await marketsPage.fulfillCreateMarket( {
				id: 'ca',
				label: 'Canada',
			} );

			await marketsPage.getHeaderAddMarketButton().click();
			const addModal = marketsPage.getAddMarketModal();
			await expect( addModal ).toBeVisible();

			await addModal.getByLabel( 'Market' ).selectOption( 'CA' );

			await addModal
				.getByRole( 'button', { name: 'Add market' } )
				.click();

			await expect( addModal ).not.toBeVisible();
		} );

		test( 'API error shows snackbar and keeps Add modal open', async () => {
			await marketsPage.fulfillCreateMarket(
				{ message: 'Internal server error' },
				500
			);

			await marketsPage.getHeaderAddMarketButton().click();
			const addModal = marketsPage.getAddMarketModal();
			await expect( addModal ).toBeVisible();

			await addModal.getByLabel( 'Market' ).selectOption( 'CA' );

			await addModal
				.getByRole( 'button', { name: 'Add market' } )
				.click();

			await expect(
				page.locator( '.components-snackbar__content' ).last()
			).toBeVisible();

			await expect( addModal ).toBeVisible();

			await addModal.getByRole( 'button', { name: 'Cancel' } ).click();
		} );

		test( 'countries already claimed by another market are excluded from the Add Market select', async () => {
			// A secondary market for Canada, one of the primary market's own
			// countries, so both US (claimed by the primary market itself)
			// and CA (claimed here) are excluded from the Add Market select,
			// leaving only the placeholder option.
			const TERTIARY_COUNTRY = {
				id: 'ca',
				label: 'Canada',
				country: 'CA',
				language: [ 'en' ],
				currency: [ 'USD' ],
				feed_label: 'CA',
			};

			await marketsPage.fulfillMarkets( [
				PRIMARY_MARKET,
				TERTIARY_COUNTRY,
			] );
			await marketsPage.goto();
			await marketsPage.waitForMarketsTable();

			await marketsPage.getHeaderAddMarketButton().click();
			const addModal = marketsPage.getAddMarketModal();

			await expect(
				addModal.getByLabel( 'Market' ).locator( 'option' )
			).toHaveCount( 1 );

			await addModal.getByRole( 'button', { name: 'Cancel' } ).click();

			// Restore the two-market fixture for subsequent tests in this file.
			await marketsPage.fulfillMarkets( [
				PRIMARY_MARKET,
				SECONDARY_MARKET,
			] );
			await marketsPage.goto();
			await marketsPage.waitForMarketsTable();
		} );
	} );

	test.describe( 'Shipping Rate - Flat', () => {
		test.beforeAll( async () => {
			await marketsPage.mockIsMultiLingualStore( true );
			await marketsPage.mockMarketsPageRequests( {
				shippingRate: 'flat',
				multilingual: true,
			} );
			await marketsPage.goto();
			await marketsPage.waitForMarketsTable();
		} );

		test( 'table shows Market, Shipping rate, Shipping times, Free shipping columns', async () => {
			await expect(
				page.getByRole( 'columnheader', { name: 'Market' } )
			).toBeVisible();
			await expect(
				page.getByRole( 'columnheader', { name: 'Shipping rate' } )
			).toBeVisible();
			await expect(
				page.getByRole( 'columnheader', { name: 'Shipping times' } )
			).toBeVisible();
			await expect(
				page.getByRole( 'columnheader', { name: 'Free shipping' } )
			).toBeVisible();

			await expect(
				page.getByRole( 'columnheader', { name: 'Language' } )
			).not.toBeVisible();
		} );

		test( 'multilingual flat shipping notice is shown on the dashboard', async () => {
			await expect(
				marketsPage.getMultilingualFlatShippingNotice()
			).toContainText(
				'Your current shipping setup is not compatible with multilingual feeds'
			);
		} );

		test( 'both primary and secondary market rows are visible', async () => {
			await expect(
				page.getByRole( 'cell', { name: /Primary Market/ } )
			).toBeVisible();
			await expect(
				page.getByRole( 'cell', { name: 'France' } )
			).toBeVisible();
		} );

		test( 'Delete is absent for primary and present for secondary', async () => {
			await expect( marketsPage.getDeleteButtons() ).toHaveCount( 1 );

			await expect(
				page
					.getByRole( 'row' )
					.filter( { hasText: /Primary Market/ } )
					.getByRole( 'button', { name: 'Actions' } )
			).not.toBeAttached();

			await expect(
				page
					.getByRole( 'row' )
					.filter( { hasText: 'France' } )
					.getByRole( 'button', { name: 'Actions' } )
			).toBeEnabled();
		} );

		test( 'Edit modal for primary market shows countries and flat shipping controls', async () => {
			await marketsPage.getEditButtonForRow( /Primary Market/ ).click();

			const modal = marketsPage.getEditPrimaryMarketModal();
			await expect( modal ).toBeVisible();

			await expect(
				modal.locator( '.woocommerce-tree-select-control' )
			).toBeVisible();

			await expect(
				marketsPage.getLocaleSection( modal )
			).not.toBeAttached();

			await expect(
				modal.getByLabel( 'Estimated shipping rates' )
			).toBeVisible();
			await expect(
				modal.getByText( 'Estimated shipping times' )
			).toBeVisible();

			await expect(
				marketsPage.getShippingInfoNotice( modal )
			).not.toBeVisible();

			await modal.getByRole( 'button', { name: 'Cancel' } ).click();
			await expect( modal ).not.toBeVisible();
		} );

		test( 'shows the existing free shipping threshold for the primary market', async () => {
			await marketsPage.getEditButtonForRow( /Primary Market/ ).click();

			const modal = marketsPage.getEditPrimaryMarketModal();
			await expect( modal ).toBeVisible();

			await expect(
				modal.getByLabel( 'Free shipping over a specific order value' )
			).toBeChecked();
			await expect( modal.getByLabel( 'Cost' ) ).toHaveValue( '50.00' );

			await modal.getByRole( 'button', { name: 'Cancel' } ).click();
			await expect( modal ).not.toBeVisible();
		} );

		test( "the free shipping Cost input shows the market's own currency", async () => {
			await marketsPage.getEditButtonForRow( /Primary Market/ ).click();

			const primaryModal = marketsPage.getEditPrimaryMarketModal();
			const primaryCostField = primaryModal
				.locator( '.components-input-control__container' )
				.filter( { has: page.getByLabel( 'Cost' ) } );
			await expect(
				primaryCostField.locator( '.components-input-control__suffix' )
			).toHaveText( 'USD' );

			await primaryModal
				.getByRole( 'button', { name: 'Cancel' } )
				.click();

			await marketsPage.getEditButtonForRow( 'France' ).click();
			const franceModal = marketsPage.getEditMarketModal( 'France' );

			await franceModal
				.getByLabel( 'Free shipping over a specific order value' )
				.check();

			const franceCostField = franceModal
				.locator( '.components-input-control__container' )
				.filter( { has: page.getByLabel( 'Cost' ) } );
			await expect(
				franceCostField.locator( '.components-input-control__suffix' )
			).toHaveText( 'EUR' );

			await franceModal.getByRole( 'button', { name: 'Cancel' } ).click();
		} );

		test( 'unchecking free shipping hides the Cost input', async () => {
			await marketsPage.getEditButtonForRow( /Primary Market/ ).click();

			const modal = marketsPage.getEditPrimaryMarketModal();
			await expect( modal ).toBeVisible();

			await modal
				.getByLabel( 'Free shipping over a specific order value' )
				.uncheck();

			await expect( modal.getByLabel( 'Cost' ) ).not.toBeVisible();

			await modal.getByRole( 'button', { name: 'Cancel' } ).click();
			await expect( modal ).not.toBeVisible();
		} );

		test( 'secondary market with no threshold shows an unchecked checkbox; checking it reveals the Cost input', async () => {
			await marketsPage.getEditButtonForRow( 'France' ).click();

			const modal = marketsPage.getEditMarketModal( 'France' );
			await expect( modal ).toBeVisible();

			const checkbox = modal.getByLabel(
				'Free shipping over a specific order value'
			);
			await expect( checkbox ).not.toBeChecked();
			await expect( modal.getByLabel( 'Cost' ) ).not.toBeVisible();

			await checkbox.check();
			await expect( modal.getByLabel( 'Cost' ) ).toBeVisible();

			await modal.getByRole( 'button', { name: 'Cancel' } ).click();
			await expect( modal ).not.toBeVisible();
		} );

		test( 'checking free shipping without a value blocks Save and shows a validation error', async () => {
			await marketsPage.getEditButtonForRow( 'France' ).click();

			const modal = marketsPage.getEditMarketModal( 'France' );
			await expect( modal ).toBeVisible();

			await modal
				.getByLabel( 'Free shipping over a specific order value' )
				.check();

			await modal.getByRole( 'button', { name: 'Save' } ).click();

			await expect(
				modal.getByText(
					'Please enter minimum order for free shipping.'
				)
			).toBeVisible();
			await expect( modal ).toBeVisible();

			await modal.getByRole( 'button', { name: 'Cancel' } ).click();
			await expect( modal ).not.toBeVisible();
		} );

		test( 'editing the Cost value and saving sends the updated threshold', async () => {
			await marketsPage.fulfillMarketUpdate(
				PRIMARY_MARKET.id,
				PRIMARY_MARKET
			);

			await marketsPage.getEditButtonForRow( /Primary Market/ ).click();

			const modal = marketsPage.getEditPrimaryMarketModal();
			await expect( modal ).toBeVisible();

			const costInput = modal.getByLabel( 'Cost' );
			await costInput.fill( '75' );
			// Blur by clicking elsewhere in the modal, so the new value commits
			// to the form before Save is pressed.
			await modal.getByText( 'Estimated shipping rates' ).click();

			const ratesBatchRequest =
				marketsPage.registerShippingRatesBatchRequest();

			await modal.getByRole( 'button', { name: 'Save' } ).click();

			const request = await ratesBatchRequest;
			const body = request.postDataJSON();

			expect(
				body.rates.every(
					( rate ) => rate.options.free_shipping_threshold === 75
				)
			).toBe( true );

			await expect( modal ).not.toBeVisible();
		} );

		test( 'saving with an unchanged Cost value does not send a rates batch request', async () => {
			await marketsPage.fulfillMarketUpdate(
				PRIMARY_MARKET.id,
				PRIMARY_MARKET
			);

			const ratesBatchRequest = marketsPage
				.registerShippingRatesBatchRequest( { timeout: 1000 } )
				.catch( () => null );

			await marketsPage.getEditButtonForRow( /Primary Market/ ).click();

			const modal = marketsPage.getEditPrimaryMarketModal();
			await expect( modal ).toBeVisible();

			const costInput = modal.getByLabel( 'Cost' );
			await costInput.fill( '50' );
			await modal.getByText( 'Estimated shipping rates' ).click();

			await modal.getByRole( 'button', { name: 'Save' } ).click();

			await expect( modal ).not.toBeVisible();
			expect( await ratesBatchRequest ).toBeNull();
		} );

		test( 'secondary market edit has no audience section; Add modal shows country select', async () => {
			await marketsPage.getEditButtonForRow( 'France' ).click();
			const editModal = marketsPage.getEditMarketModal( 'France' );
			await expect( editModal ).toBeVisible();
			await expect(
				editModal.locator( '.woocommerce-tree-select-control' )
			).not.toBeAttached();
			await editModal.getByRole( 'button', { name: 'Cancel' } ).click();
			await expect( editModal ).not.toBeVisible();

			await marketsPage.getHeaderAddMarketButton().click();
			const addModal = marketsPage.getAddMarketModal();
			await expect( addModal ).toBeVisible();
			await expect( addModal.getByLabel( 'Market' ) ).toBeVisible();
			await addModal.getByRole( 'button', { name: 'Cancel' } ).click();
			await expect( addModal ).not.toBeVisible();
		} );

		test( 'successful save closes the Edit modal', async () => {
			await marketsPage.fulfillMarketUpdate(
				PRIMARY_MARKET.id,
				PRIMARY_MARKET
			);
			await marketsPage.fulfillMarkets( [
				PRIMARY_MARKET,
				SECONDARY_MARKET,
			] );

			await marketsPage.getEditButtonForRow( /Primary Market/ ).click();

			const modal = marketsPage.getEditPrimaryMarketModal();
			await expect( modal ).toBeVisible();

			await modal.getByRole( 'button', { name: 'Save' } ).click();

			await expect( modal ).not.toBeVisible();
		} );

		test( 'API error shows snackbar and keeps Edit modal open', async () => {
			await marketsPage.fulfillMarketUpdate(
				PRIMARY_MARKET.id,
				{ message: 'Internal server error' },
				500
			);

			await marketsPage.getEditButtonForRow( /Primary Market/ ).click();

			const modal = marketsPage.getEditPrimaryMarketModal();
			await expect( modal ).toBeVisible();

			await modal.getByRole( 'button', { name: 'Save' } ).click();

			await expect(
				page.locator( '.components-snackbar__content' ).last()
			).toBeVisible();

			await expect( modal ).toBeVisible();

			await modal.getByRole( 'button', { name: 'Cancel' } ).click();
		} );

		test( 'successful save closes the Add modal', async () => {
			await marketsPage.fulfillCreateMarket( {
				id: 'ca',
				label: 'Canada',
			} );

			await marketsPage.getHeaderAddMarketButton().click();
			const addModal = marketsPage.getAddMarketModal();
			await expect( addModal ).toBeVisible();

			await addModal.getByLabel( 'Market' ).selectOption( 'CA' );

			await addModal
				.getByRole( 'button', { name: 'Add market' } )
				.click();

			await expect( addModal ).not.toBeVisible();
		} );

		test( 'API error shows snackbar and keeps Add modal open', async () => {
			await marketsPage.fulfillCreateMarket(
				{ message: 'Internal server error' },
				500
			);

			await marketsPage.getHeaderAddMarketButton().click();
			const addModal = marketsPage.getAddMarketModal();
			await expect( addModal ).toBeVisible();

			await addModal.getByLabel( 'Market' ).selectOption( 'CA' );

			await addModal
				.getByRole( 'button', { name: 'Add market' } )
				.click();

			await expect(
				page.locator( '.components-snackbar__content' ).last()
			).toBeVisible();

			await expect( addModal ).toBeVisible();

			await addModal.getByRole( 'button', { name: 'Cancel' } ).click();
		} );

		test( 'API error shows snackbar and keeps Delete modal open', async () => {
			await marketsPage.fulfillMarketDelete(
				SECONDARY_MARKET.id,
				{ message: 'Internal server error' },
				500
			);

			await marketsPage.openDeleteMarketModal( 'France' );

			const modal = marketsPage.getDeleteMarketModal();
			await expect( modal ).toBeVisible();

			await modal.getByRole( 'button', { name: 'Delete' } ).click();

			// `.last()`: the preceding "Add modal" error test's snackbar may
			// still be visible (snackbars auto-dismiss after a delay).
			await expect(
				page.locator( '.components-snackbar__content' ).last()
			).toBeVisible();

			await expect( modal ).toBeVisible();

			await modal.getByRole( 'button', { name: 'Cancel' } ).click();
		} );

		// Runs last in this describe block: it permanently removes France from
		// the mocked markets list, and the next describe block navigates fresh.
		test( 'successful delete removes the market row and closes the modal', async () => {
			await marketsPage.fulfillMarketDelete( SECONDARY_MARKET.id, {} );
			await marketsPage.fulfillMarkets( [ PRIMARY_MARKET ] );

			await marketsPage.openDeleteMarketModal( 'France' );

			const modal = marketsPage.getDeleteMarketModal();
			await expect( modal ).toBeVisible();
			await expect( modal ).toContainText( 'France' );

			await modal.getByRole( 'button', { name: 'Delete' } ).click();

			await expect( modal ).not.toBeVisible();
			await expect(
				page.getByRole( 'cell', { name: 'France' } )
			).not.toBeVisible();
		} );
	} );

	test.describe( 'Shipping Rate - Automatic', () => {
		test.beforeAll( async () => {
			await marketsPage.mockIsMultiLingualStore( true );
			await marketsPage.mockMarketsPageRequests( {
				shippingRate: 'automatic',
				multilingual: true,
			} );
			await marketsPage.goto();
			await marketsPage.waitForMarketsTable();
		} );

		test( 'table shows Market, Language, Currency, and Shipping times columns', async () => {
			await expect(
				page.getByRole( 'columnheader', { name: 'Market' } )
			).toBeVisible();
			await expect(
				page.getByRole( 'columnheader', { name: 'Language' } )
			).toBeVisible();
			await expect(
				page.getByRole( 'columnheader', { name: 'Currency' } )
			).toBeVisible();
			await expect(
				page.getByRole( 'columnheader', { name: 'Shipping times' } )
			).toBeVisible();

			await expect(
				page.getByRole( 'columnheader', { name: 'Country' } )
			).not.toBeVisible();
			await expect(
				page.getByRole( 'columnheader', { name: 'Shipping rate' } )
			).not.toBeVisible();
		} );

		test( 'no multilingual flat shipping notice on the dashboard', async () => {
			await expect(
				marketsPage.getMultilingualFlatShippingNotice()
			).not.toBeAttached();
		} );

		test( 'both primary and secondary market rows are visible', async () => {
			await expect(
				page.getByRole( 'cell', { name: /Primary Market/ } )
			).toBeVisible();
			await expect(
				page.getByRole( 'cell', { name: 'France' } )
			).toBeVisible();
		} );

		test( 'Delete is absent for primary and present for secondary', async () => {
			await expect( marketsPage.getDeleteButtons() ).toHaveCount( 1 );

			await expect(
				page
					.getByRole( 'row' )
					.filter( { hasText: /Primary Market/ } )
					.getByRole( 'button', { name: 'Actions' } )
			).not.toBeAttached();

			await expect(
				page
					.getByRole( 'row' )
					.filter( { hasText: 'France' } )
					.getByRole( 'button', { name: 'Actions' } )
			).toBeEnabled();
		} );

		test( 'Edit modal for primary market shows countries, locale section, and shipping rate notice', async () => {
			await marketsPage.getEditButtonForRow( /Primary Market/ ).click();

			const modal = marketsPage.getEditPrimaryMarketModal();
			await expect( modal ).toBeVisible();

			await expect(
				modal.locator( '.woocommerce-tree-select-control' )
			).toBeVisible();

			const localeSection = marketsPage.getLocaleSection( modal );
			await expect( localeSection ).toBeVisible();
			await expect(
				localeSection.getByText( 'Language', { exact: true } )
			).toBeVisible();
			await expect(
				localeSection.getByText( 'Currency', { exact: true } )
			).toBeVisible();

			await expect(
				marketsPage.getShippingInfoNotice( modal )
			).toContainText( 'Shipping rates are synced automatically' );

			await expect(
				modal.getByText( 'Estimated shipping times' )
			).toBeVisible();

			await modal.getByRole( 'button', { name: 'Cancel' } ).click();
			await expect( modal ).not.toBeVisible();
		} );

		test( 'secondary market edit has no audience section, shows shipping rate notice; Add modal shows country select and notice', async () => {
			await marketsPage.getEditButtonForRow( 'France' ).click();
			const editModal = marketsPage.getEditMarketModal( 'France' );
			await expect( editModal ).toBeVisible();
			await expect(
				editModal.locator( '.woocommerce-tree-select-control' )
			).not.toBeAttached();
			await expect(
				marketsPage.getShippingInfoNotice( editModal )
			).toContainText( 'Shipping rates are synced automatically' );
			await editModal.getByRole( 'button', { name: 'Cancel' } ).click();
			await expect( editModal ).not.toBeVisible();

			await marketsPage.getHeaderAddMarketButton().click();
			const addModal = marketsPage.getAddMarketModal();
			await expect( addModal ).toBeVisible();
			await expect( addModal.getByLabel( 'Market' ) ).toBeVisible();
			await expect(
				marketsPage.getShippingInfoNotice( addModal )
			).toContainText( 'Shipping rates are synced automatically' );
			await addModal.getByRole( 'button', { name: 'Cancel' } ).click();
			await expect( addModal ).not.toBeVisible();
		} );

		test( 'successful save closes the Edit modal', async () => {
			await marketsPage.fulfillMarketUpdate(
				PRIMARY_MARKET.id,
				PRIMARY_MARKET
			);
			await marketsPage.fulfillMarkets( [
				PRIMARY_MARKET,
				SECONDARY_MARKET,
			] );

			await marketsPage.getEditButtonForRow( /Primary Market/ ).click();

			const modal = marketsPage.getEditPrimaryMarketModal();
			await expect( modal ).toBeVisible();

			await modal.getByRole( 'button', { name: 'Save' } ).click();

			await expect( modal ).not.toBeVisible();
		} );

		test( 'API error shows snackbar and keeps Edit modal open', async () => {
			await marketsPage.fulfillMarketUpdate(
				PRIMARY_MARKET.id,
				{ message: 'Internal server error' },
				500
			);

			await marketsPage.getEditButtonForRow( /Primary Market/ ).click();

			const modal = marketsPage.getEditPrimaryMarketModal();
			await expect( modal ).toBeVisible();

			await modal.getByRole( 'button', { name: 'Save' } ).click();

			await expect(
				page.locator( '.components-snackbar__content' ).last()
			).toBeVisible();

			await expect( modal ).toBeVisible();

			await modal.getByRole( 'button', { name: 'Cancel' } ).click();
		} );

		test( 'successful save closes the Add modal', async () => {
			await marketsPage.fulfillCreateMarket( {
				id: 'ca',
				label: 'Canada',
			} );

			await marketsPage.getHeaderAddMarketButton().click();
			const addModal = marketsPage.getAddMarketModal();
			await expect( addModal ).toBeVisible();

			await addModal.getByLabel( 'Market' ).selectOption( 'CA' );

			await addModal
				.getByRole( 'button', { name: 'Add market' } )
				.click();

			await expect( addModal ).not.toBeVisible();
		} );

		test( 'API error shows snackbar and keeps Add modal open', async () => {
			await marketsPage.fulfillCreateMarket(
				{ message: 'Internal server error' },
				500
			);

			await marketsPage.getHeaderAddMarketButton().click();
			const addModal = marketsPage.getAddMarketModal();
			await expect( addModal ).toBeVisible();

			await addModal.getByLabel( 'Market' ).selectOption( 'CA' );

			await addModal
				.getByRole( 'button', { name: 'Add market' } )
				.click();

			await expect(
				page.locator( '.components-snackbar__content' ).last()
			).toBeVisible();

			await expect( addModal ).toBeVisible();

			await addModal.getByRole( 'button', { name: 'Cancel' } ).click();
		} );

		test( 'API error shows snackbar and keeps Delete modal open', async () => {
			await marketsPage.fulfillMarketDelete(
				SECONDARY_MARKET.id,
				{ message: 'Internal server error' },
				500
			);

			await marketsPage.openDeleteMarketModal( 'France' );

			const modal = marketsPage.getDeleteMarketModal();
			await expect( modal ).toBeVisible();

			await modal.getByRole( 'button', { name: 'Delete' } ).click();

			// `.last()`: the preceding "Add modal" error test's snackbar may
			// still be visible (snackbars auto-dismiss after a delay).
			await expect(
				page.locator( '.components-snackbar__content' ).last()
			).toBeVisible();

			await expect( modal ).toBeVisible();

			await modal.getByRole( 'button', { name: 'Cancel' } ).click();
		} );

		// Runs last in this describe block: it permanently removes France
		// from the mocked markets list.
		test( 'successful delete removes the market row and closes the modal', async () => {
			await marketsPage.fulfillMarketDelete( SECONDARY_MARKET.id, {} );
			await marketsPage.fulfillMarkets( [ PRIMARY_MARKET ] );

			await marketsPage.openDeleteMarketModal( 'France' );

			const modal = marketsPage.getDeleteMarketModal();
			await expect( modal ).toBeVisible();
			await expect( modal ).toContainText( 'France' );

			await modal.getByRole( 'button', { name: 'Delete' } ).click();

			await expect( modal ).not.toBeVisible();
			await expect(
				page.getByRole( 'cell', { name: 'France' } )
			).not.toBeVisible();
		} );
	} );
} );
