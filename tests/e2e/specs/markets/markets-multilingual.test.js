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
	TERTIARY_MARKET,
	PRIMARY_MARKET,
	SECONDARY_MARKET,
} from '../../utils/pages/markets';
import {
	MARKET_EXISTS_MESSAGE,
	MARKET_NOT_FOUND_MESSAGE,
} from './error-messages';

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
		await marketsPage.mockIsMultiLingualStore( true );
		await setOnboardedMerchant();
		await clearServiceBasedMerchant();
	} );

	test.afterAll( async () => {
		await clearOnboardedMerchant();
		await page.close();
	} );

	test.describe( 'Shipping Rate - Manual', () => {
		test.beforeAll( async () => {
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
			).not.toBeAttached();
			await expect(
				page.getByRole( 'columnheader', { name: 'Shipping rate' } )
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
			).not.toBeAttached();

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

		test( 'secondary market Edit modal has no audience section and shows the shipping notice', async () => {
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
		} );

		test( 'Add modal shows the country select and the shipping notice', async () => {
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

		test( 'successful save closes the Edit modal and refreshes the table', async () => {
			const updatedPrimaryMarket = {
				...PRIMARY_MARKET,
				countries: [ 'US' ],
			};
			await marketsPage.fulfillMarketUpdate(
				PRIMARY_MARKET.id,
				updatedPrimaryMarket
			);
			await marketsPage.fulfillMarkets( [
				updatedPrimaryMarket,
				SECONDARY_MARKET,
			] );

			await expect(
				page.getByRole( 'cell', {
					name: 'Primary Market (2 countries)',
					exact: true,
				} )
			).toBeVisible();

			await marketsPage.getEditButtonForRow( /Primary Market/ ).click();

			const modal = marketsPage.getEditPrimaryMarketModal();
			await expect( modal ).toBeVisible();

			await modal.getByRole( 'button', { name: 'Save' } ).click();

			await expect( modal ).not.toBeVisible();
			await expect(
				page.getByRole( 'cell', {
					name: 'Primary Market (1 country)',
					exact: true,
				} )
			).toBeVisible();

			await marketsPage.reloadWithMarkets(
				[ PRIMARY_MARKET, SECONDARY_MARKET ],
				{ waitForLanguagesCurrencies: true }
			);
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
				{ message: MARKET_NOT_FOUND_MESSAGE },
				404
			);

			await marketsPage.getEditButtonForRow( /Primary Market/ ).click();

			const modal = marketsPage.getEditPrimaryMarketModal();
			await expect( modal ).toBeVisible();

			await modal.getByRole( 'button', { name: 'Save' } ).click();

			await expect(
				marketsPage.getSnackbar( MARKET_NOT_FOUND_MESSAGE )
			).toBeVisible();

			await expect( modal ).toBeVisible();

			await modal.getByRole( 'button', { name: 'Cancel' } ).click();
		} );

		test( 'successful save closes the Add modal and adds the market to the table', async () => {
			await marketsPage.fulfillCreateMarket( TERTIARY_MARKET );
			await marketsPage.fulfillMarkets( [
				PRIMARY_MARKET,
				SECONDARY_MARKET,
				TERTIARY_MARKET,
			] );

			await expect(
				page.getByRole( 'cell', { name: 'Germany' } )
			).not.toBeAttached();

			await marketsPage.getHeaderAddMarketButton().click();
			const addModal = marketsPage.getAddMarketModal();
			await expect( addModal ).toBeVisible();

			await addModal.getByLabel( 'Market' ).selectOption( 'DE' );

			await addModal
				.getByRole( 'button', { name: 'Add market' } )
				.click();

			await expect( addModal ).not.toBeVisible();
			await expect(
				page.getByRole( 'cell', { name: 'Germany' } )
			).toBeVisible();

			await marketsPage.reloadWithMarkets(
				[ PRIMARY_MARKET, SECONDARY_MARKET ],
				{ waitForLanguagesCurrencies: true }
			);
		} );

		test( 'API error shows snackbar and keeps Add modal open', async () => {
			await marketsPage.fulfillCreateMarket(
				{ message: MARKET_EXISTS_MESSAGE },
				409
			);

			await marketsPage.getHeaderAddMarketButton().click();
			const addModal = marketsPage.getAddMarketModal();
			await expect( addModal ).toBeVisible();

			await addModal.getByLabel( 'Market' ).selectOption( 'DE' );

			await addModal
				.getByRole( 'button', { name: 'Add market' } )
				.click();

			await expect(
				marketsPage.getSnackbar( MARKET_EXISTS_MESSAGE )
			).toBeVisible();

			await expect( addModal ).toBeVisible();

			await addModal.getByRole( 'button', { name: 'Cancel' } ).click();
		} );

		test( 'countries already claimed by another market are excluded from the Add Market select', async () => {
			// Claims CA in addition to the primary market's US, so both are
			// excluded from the Add Market select; France and Germany stay selectable.
			const TERTIARY_COUNTRY = {
				id: 'ca',
				label: 'Canada',
				country: 'CA',
				language: [ 'en' ],
				currency: [ 'USD' ],
				feed_label: 'CA',
			};

			await marketsPage.reloadWithMarkets(
				[ PRIMARY_MARKET, TERTIARY_COUNTRY ],
				{ waitForLanguagesCurrencies: true }
			);

			await marketsPage.getHeaderAddMarketButton().click();
			const addModal = marketsPage.getAddMarketModal();
			const marketSelect = addModal.getByLabel( 'Market' );
			const options = marketSelect.locator( 'option' );
			const continentGroups = marketSelect.locator( 'optgroup' );

			// Placeholder + France + Germany. North America is omitted since
			// both its countries (US, CA) are claimed.
			await expect( options ).toHaveCount( 3 );
			await expect( options.filter( { hasText: 'Canada' } ) ).toHaveCount(
				0
			);
			await expect( options.filter( { hasText: 'France' } ) ).toHaveCount(
				1
			);
			await expect(
				options.filter( { hasText: 'Germany' } )
			).toHaveCount( 1 );

			await expect( continentGroups ).toHaveCount( 1 );
			await expect( continentGroups ).toHaveAttribute(
				'label',
				'Europe'
			);

			await addModal.getByRole( 'button', { name: 'Cancel' } ).click();

			// Restore the two-market fixture for subsequent tests in this file.
			await marketsPage.reloadWithMarkets(
				[ PRIMARY_MARKET, SECONDARY_MARKET ],
				{ waitForLanguagesCurrencies: true }
			);
		} );

		test( 'Add modal shows the grouped country select when adding the first market', async () => {
			await marketsPage.reloadWithMarkets( [ PRIMARY_MARKET ], {
				waitForLanguagesCurrencies: true,
			} );

			await marketsPage.getHeaderAddMarketButton().click();
			const addModal = marketsPage.getAddMarketModal();
			await expect( addModal ).toBeVisible();

			await expect( addModal.getByLabel( 'Market' ) ).toBeVisible();

			await addModal.getByRole( 'button', { name: 'Cancel' } ).click();

			// Restore the two-market fixture for subsequent tests in this file.
			await marketsPage.reloadWithMarkets(
				[ PRIMARY_MARKET, SECONDARY_MARKET ],
				{ waitForLanguagesCurrencies: true }
			);
		} );
	} );

	test.describe( 'Shipping Rate - Flat', () => {
		test.beforeAll( async () => {
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
			).not.toBeAttached();
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
			).not.toBeAttached();

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

			await expect( modal.getByLabel( 'Cost' ) ).not.toBeAttached();

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
			await expect( modal.getByLabel( 'Cost' ) ).not.toBeAttached();

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

			const updateRequest = marketsPage.registerMarketUpdateRequest(
				PRIMARY_MARKET.id
			);

			await modal.getByRole( 'button', { name: 'Save' } ).click();

			const body = ( await updateRequest ).postDataJSON();

			expect( body.shipping.free_shipping_threshold ).toBe( 75 );

			await expect( modal ).not.toBeVisible();
		} );

		test( 'secondary market Edit modal has no audience section', async () => {
			await marketsPage.getEditButtonForRow( 'France' ).click();
			const editModal = marketsPage.getEditMarketModal( 'France' );
			await expect( editModal ).toBeVisible();
			await expect(
				editModal.locator( '.woocommerce-tree-select-control' )
			).not.toBeAttached();
			await editModal.getByRole( 'button', { name: 'Cancel' } ).click();
			await expect( editModal ).not.toBeVisible();
		} );

		test( 'Add modal shows the country select', async () => {
			await marketsPage.getHeaderAddMarketButton().click();
			const addModal = marketsPage.getAddMarketModal();
			await expect( addModal ).toBeVisible();
			await expect( addModal.getByLabel( 'Market' ) ).toBeVisible();
			await addModal.getByRole( 'button', { name: 'Cancel' } ).click();
			await expect( addModal ).not.toBeVisible();
		} );

		test( 'successful save closes the Edit modal and refreshes the table', async () => {
			const updatedPrimaryMarket = {
				...PRIMARY_MARKET,
				countries: [ 'US' ],
			};
			await marketsPage.fulfillMarketUpdate(
				PRIMARY_MARKET.id,
				updatedPrimaryMarket
			);
			await marketsPage.fulfillMarkets( [
				updatedPrimaryMarket,
				SECONDARY_MARKET,
			] );

			await expect(
				page.getByRole( 'cell', {
					name: 'Primary Market (2 countries)',
					exact: true,
				} )
			).toBeVisible();

			await marketsPage.getEditButtonForRow( /Primary Market/ ).click();

			const modal = marketsPage.getEditPrimaryMarketModal();
			await expect( modal ).toBeVisible();

			await modal.getByRole( 'button', { name: 'Save' } ).click();

			await expect( modal ).not.toBeVisible();
			await expect(
				page.getByRole( 'cell', {
					name: 'Primary Market (1 country)',
					exact: true,
				} )
			).toBeVisible();

			await marketsPage.reloadWithMarkets( [
				PRIMARY_MARKET,
				SECONDARY_MARKET,
			] );
		} );

		test( 'API error shows snackbar and keeps Edit modal open', async () => {
			await marketsPage.fulfillMarketUpdate(
				PRIMARY_MARKET.id,
				{ message: MARKET_NOT_FOUND_MESSAGE },
				404
			);

			await marketsPage.getEditButtonForRow( /Primary Market/ ).click();

			const modal = marketsPage.getEditPrimaryMarketModal();
			await expect( modal ).toBeVisible();

			await modal.getByRole( 'button', { name: 'Save' } ).click();

			await expect(
				marketsPage.getSnackbar( MARKET_NOT_FOUND_MESSAGE )
			).toBeVisible();

			await expect( modal ).toBeVisible();

			await modal.getByRole( 'button', { name: 'Cancel' } ).click();
		} );

		test( 'successful save closes the Add modal and adds the market to the table', async () => {
			await marketsPage.fulfillCreateMarket( TERTIARY_MARKET );
			await marketsPage.fulfillMarkets( [
				PRIMARY_MARKET,
				SECONDARY_MARKET,
				TERTIARY_MARKET,
			] );

			await expect(
				page.getByRole( 'cell', { name: 'Germany' } )
			).not.toBeAttached();

			await marketsPage.getHeaderAddMarketButton().click();
			const addModal = marketsPage.getAddMarketModal();
			await expect( addModal ).toBeVisible();

			await addModal.getByLabel( 'Market' ).selectOption( 'DE' );

			await addModal
				.getByRole( 'button', { name: 'Add market' } )
				.click();

			await expect( addModal ).not.toBeVisible();
			await expect(
				page.getByRole( 'cell', { name: 'Germany' } )
			).toBeVisible();

			await marketsPage.reloadWithMarkets( [
				PRIMARY_MARKET,
				SECONDARY_MARKET,
			] );
		} );

		test( 'API error shows snackbar and keeps Add modal open', async () => {
			await marketsPage.fulfillCreateMarket(
				{ message: MARKET_EXISTS_MESSAGE },
				409
			);

			await marketsPage.getHeaderAddMarketButton().click();
			const addModal = marketsPage.getAddMarketModal();
			await expect( addModal ).toBeVisible();

			await addModal.getByLabel( 'Market' ).selectOption( 'DE' );

			await addModal
				.getByRole( 'button', { name: 'Add market' } )
				.click();

			await expect(
				marketsPage.getSnackbar( MARKET_EXISTS_MESSAGE )
			).toBeVisible();

			await expect( addModal ).toBeVisible();

			await addModal.getByRole( 'button', { name: 'Cancel' } ).click();
		} );

		test( 'API error shows snackbar and keeps Delete modal open', async () => {
			await marketsPage.fulfillMarketDelete(
				SECONDARY_MARKET.id,
				{ message: MARKET_NOT_FOUND_MESSAGE },
				404
			);

			await marketsPage.openDeleteMarketModal( 'France' );

			const modal = marketsPage.getDeleteMarketModal();
			await expect( modal ).toBeVisible();

			await modal.getByRole( 'button', { name: 'Delete' } ).click();

			await expect(
				marketsPage.getSnackbar( MARKET_NOT_FOUND_MESSAGE )
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
			).not.toBeAttached();
		} );
	} );

	test.describe( 'Shipping Rate - Automatic', () => {
		test.beforeAll( async () => {
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
			).not.toBeAttached();
			await expect(
				page.getByRole( 'columnheader', { name: 'Shipping rate' } )
			).not.toBeAttached();
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

		test( 'secondary market Edit modal has no audience section and shows the shipping rate notice', async () => {
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
		} );

		test( 'Add modal shows the country select and the shipping rate notice', async () => {
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

		test( 'successful save closes the Edit modal and refreshes the table', async () => {
			const updatedPrimaryMarket = {
				...PRIMARY_MARKET,
				countries: [ 'US' ],
			};
			await marketsPage.fulfillMarketUpdate(
				PRIMARY_MARKET.id,
				updatedPrimaryMarket
			);
			await marketsPage.fulfillMarkets( [
				updatedPrimaryMarket,
				SECONDARY_MARKET,
			] );

			await expect(
				page.getByRole( 'cell', {
					name: 'Primary Market (2 countries)',
					exact: true,
				} )
			).toBeVisible();

			await marketsPage.getEditButtonForRow( /Primary Market/ ).click();

			const modal = marketsPage.getEditPrimaryMarketModal();
			await expect( modal ).toBeVisible();

			await modal.getByRole( 'button', { name: 'Save' } ).click();

			await expect( modal ).not.toBeVisible();
			await expect(
				page.getByRole( 'cell', {
					name: 'Primary Market (1 country)',
					exact: true,
				} )
			).toBeVisible();

			await marketsPage.reloadWithMarkets(
				[ PRIMARY_MARKET, SECONDARY_MARKET ],
				{ waitForLanguagesCurrencies: true }
			);
		} );

		test( 'API error shows snackbar and keeps Edit modal open', async () => {
			await marketsPage.fulfillMarketUpdate(
				PRIMARY_MARKET.id,
				{ message: MARKET_NOT_FOUND_MESSAGE },
				404
			);

			await marketsPage.getEditButtonForRow( /Primary Market/ ).click();

			const modal = marketsPage.getEditPrimaryMarketModal();
			await expect( modal ).toBeVisible();

			await modal.getByRole( 'button', { name: 'Save' } ).click();

			await expect(
				marketsPage.getSnackbar( MARKET_NOT_FOUND_MESSAGE )
			).toBeVisible();

			await expect( modal ).toBeVisible();

			await modal.getByRole( 'button', { name: 'Cancel' } ).click();
		} );

		test( 'successful save closes the Add modal and adds the market to the table', async () => {
			await marketsPage.fulfillCreateMarket( TERTIARY_MARKET );
			await marketsPage.fulfillMarkets( [
				PRIMARY_MARKET,
				SECONDARY_MARKET,
				TERTIARY_MARKET,
			] );

			await expect(
				page.getByRole( 'cell', { name: 'Germany' } )
			).not.toBeAttached();

			await marketsPage.getHeaderAddMarketButton().click();
			const addModal = marketsPage.getAddMarketModal();
			await expect( addModal ).toBeVisible();

			await addModal.getByLabel( 'Market' ).selectOption( 'DE' );

			await addModal
				.getByRole( 'button', { name: 'Add market' } )
				.click();

			await expect( addModal ).not.toBeVisible();
			await expect(
				page.getByRole( 'cell', { name: 'Germany' } )
			).toBeVisible();

			await marketsPage.reloadWithMarkets(
				[ PRIMARY_MARKET, SECONDARY_MARKET ],
				{ waitForLanguagesCurrencies: true }
			);
		} );

		test( 'API error shows snackbar and keeps Add modal open', async () => {
			await marketsPage.fulfillCreateMarket(
				{ message: MARKET_EXISTS_MESSAGE },
				409
			);

			await marketsPage.getHeaderAddMarketButton().click();
			const addModal = marketsPage.getAddMarketModal();
			await expect( addModal ).toBeVisible();

			await addModal.getByLabel( 'Market' ).selectOption( 'DE' );

			await addModal
				.getByRole( 'button', { name: 'Add market' } )
				.click();

			await expect(
				marketsPage.getSnackbar( MARKET_EXISTS_MESSAGE )
			).toBeVisible();

			await expect( addModal ).toBeVisible();

			await addModal.getByRole( 'button', { name: 'Cancel' } ).click();
		} );

		test( 'API error shows snackbar and keeps Delete modal open', async () => {
			await marketsPage.fulfillMarketDelete(
				SECONDARY_MARKET.id,
				{ message: MARKET_NOT_FOUND_MESSAGE },
				404
			);

			await marketsPage.openDeleteMarketModal( 'France' );

			const modal = marketsPage.getDeleteMarketModal();
			await expect( modal ).toBeVisible();

			await modal.getByRole( 'button', { name: 'Delete' } ).click();

			await expect(
				marketsPage.getSnackbar( MARKET_NOT_FOUND_MESSAGE )
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
			).not.toBeAttached();
		} );
	} );
} );
