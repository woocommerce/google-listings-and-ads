/**
 * External dependencies
 */
import { expect, test } from '@playwright/test';

/**
 * Internal dependencies
 */
import { clearOnboardedMerchant, setOnboardedMerchant } from '../../utils/api';
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

test.describe( 'Markets – non-multilingual store', () => {
	test.beforeAll( async ( { browser } ) => {
		page = await browser.newPage();
		marketsPage = new MarketsPage( page );
		await setOnboardedMerchant();
	} );

	test.afterAll( async () => {
		await clearOnboardedMerchant();
		await page.close();
	} );

	test.describe( 'shipping_rate = manual', () => {
		test.beforeAll( async () => {
			await marketsPage.mockIsMultiLingualStore( false );
			await marketsPage.mockMarketsPageRequests( {
				shippingRate: 'manual',
				multilingual: false,
			} );
			await marketsPage.goto();
			await marketsPage.waitForMarketsTable();
		} );

		test( 'table shows Market, Country, and Shipping columns', async () => {
			await expect(
				page.getByRole( 'columnheader', { name: 'Market' } )
			).toBeVisible();
			await expect(
				page.getByRole( 'columnheader', { name: 'Country' } )
			).toBeVisible();
			await expect(
				page.getByRole( 'columnheader', { name: 'Shipping' } )
			).toBeVisible();

			await expect(
				page.getByRole( 'columnheader', { name: 'Language' } )
			).not.toBeVisible();
			await expect(
				page.getByRole( 'columnheader', { name: 'Currency' } )
			).not.toBeVisible();
		} );

		test( 'only the primary market row is visible', async () => {
			await expect(
				page.getByRole( 'cell', {
					name: /Primary Market/,
				} )
			).toBeVisible();

			await expect(
				page.getByRole( 'cell', { name: 'France' } )
			).not.toBeVisible();
		} );

		test( 'no Delete button is rendered', async () => {
			await expect( marketsPage.getDeleteButtons() ).toHaveCount( 0 );
		} );

		test( 'Edit modal for primary market shows countries field and shipping notice, no locale section', async () => {
			await marketsPage.getEditButton( 0 ).click();

			const modal = marketsPage.getEditPrimaryMarketModal();
			await expect( modal ).toBeVisible();

			await expect(
				modal.locator( '.woocommerce-tree-select-control' )
			).toBeVisible();

			await expect(
				marketsPage.getShippingInfoNotice( modal )
			).toContainText( 'Shipping is managed in Google Merchant Center' );

			await expect(
				marketsPage.getLocaleSection( modal )
			).not.toBeAttached();

			await modal.getByRole( 'button', { name: 'Cancel' } ).click();
			await expect( modal ).not.toBeVisible();
		} );

		// No secondary market exists in this scenario. The Add modal renders no
		// form fields (MarketFields returns null) — only the plugin prompt — because
		// adding markets requires a multilingual plugin when shipping is manual.
		test( 'Add modal shows multilingual plugin prompt and no submit button', async () => {
			await marketsPage.getHeaderAddMarketButton().click();

			const modal = marketsPage.getAddMarketModal();
			await expect( modal ).toBeVisible();

			await expect(
				modal.getByText(
					'Install a multilingual plugin to add markets'
				)
			).toBeVisible();

			await expect(
				modal
					.locator( '.app-modal__footer' )
					.getByRole( 'button', { name: 'Add market' } )
			).not.toBeVisible();

			await modal.getByRole( 'button', { name: 'Cancel' } ).click();
			await expect( modal ).not.toBeVisible();
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

			await marketsPage.getEditButton( 0 ).click();

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

			await marketsPage.getEditButton( 0 ).click();

			const modal = marketsPage.getEditPrimaryMarketModal();
			await expect( modal ).toBeVisible();

			await modal.getByRole( 'button', { name: 'Save' } ).click();

			await expect(
				page.locator( '.components-snackbar__content' )
			).toBeVisible();

			await expect( modal ).toBeVisible();

			await modal.getByRole( 'button', { name: 'Cancel' } ).click();
		} );
	} );

	test.describe( 'shipping_rate = flat', () => {
		test.beforeAll( async () => {
			await marketsPage.mockIsMultiLingualStore( false );
			await marketsPage.mockMarketsPageRequests( {
				shippingRate: 'flat',
				multilingual: false,
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
				page.getByRole( 'columnheader', { name: 'Country' } )
			).not.toBeVisible();
			await expect(
				page.getByRole( 'columnheader', { name: 'Language' } )
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

		test( 'Delete is absent for primary market and present for secondary', async () => {
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

		test( 'Edit modal for primary market shows countries, flat shipping controls, no locale section', async () => {
			await marketsPage.getEditButton( 0 ).click();

			const modal = marketsPage.getEditPrimaryMarketModal();
			await expect( modal ).toBeVisible();

			await expect(
				modal.locator( '.woocommerce-tree-select-control' )
			).toBeVisible();

			await expect(
				modal.getByLabel( 'Estimated shipping rates' )
			).toBeVisible();

			await expect(
				modal.getByText( 'Estimated shipping times' )
			).toBeVisible();

			await expect(
				marketsPage.getLocaleSection( modal )
			).not.toBeAttached();

			await expect(
				marketsPage.getShippingInfoNotice( modal )
			).not.toBeVisible();

			await modal.getByRole( 'button', { name: 'Cancel' } ).click();
			await expect( modal ).not.toBeVisible();
		} );

		test( 'secondary market edit has no audience section; Add modal shows country select', async () => {
			// Index 1 targets the France row Edit button.
			await marketsPage.getEditButton( 1 ).click();
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

			await marketsPage.getEditButton( 0 ).click();

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

			await marketsPage.getEditButton( 0 ).click();

			const modal = marketsPage.getEditPrimaryMarketModal();
			await expect( modal ).toBeVisible();

			await modal.getByRole( 'button', { name: 'Save' } ).click();

			await expect(
				page.locator( '.components-snackbar__content' )
			).toBeVisible();

			await expect( modal ).toBeVisible();

			await modal.getByRole( 'button', { name: 'Cancel' } ).click();
		} );
	} );

	test.describe( 'shipping_rate = automatic', () => {
		test.beforeAll( async () => {
			await marketsPage.mockIsMultiLingualStore( false );
			await marketsPage.mockMarketsPageRequests( {
				shippingRate: 'automatic',
				multilingual: false,
			} );
			await marketsPage.goto();
			await marketsPage.waitForMarketsTable();
		} );

		test( 'table shows Market and Shipping times columns only', async () => {
			await expect(
				page.getByRole( 'columnheader', { name: 'Market' } )
			).toBeVisible();
			await expect(
				page.getByRole( 'columnheader', { name: 'Shipping times' } )
			).toBeVisible();

			await expect(
				page.getByRole( 'columnheader', { name: 'Language' } )
			).not.toBeVisible();
			await expect(
				page.getByRole( 'columnheader', { name: 'Currency' } )
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

		test( 'Edit modal for primary market shows countries, shipping rate notice, and shipping times', async () => {
			await marketsPage.getEditButton( 0 ).click();

			const modal = marketsPage.getEditPrimaryMarketModal();
			await expect( modal ).toBeVisible();

			await expect(
				modal.locator( '.woocommerce-tree-select-control' )
			).toBeVisible();

			await expect(
				marketsPage.getShippingInfoNotice( modal )
			).toContainText( 'Shipping rates are synced automatically' );

			await expect(
				modal.getByText( 'Estimated shipping times' )
			).toBeVisible();

			await expect(
				marketsPage.getLocaleSection( modal )
			).not.toBeAttached();

			await modal.getByRole( 'button', { name: 'Cancel' } ).click();
			await expect( modal ).not.toBeVisible();
		} );

		test( 'secondary market edit has no audience section; Add modal shows country select', async () => {
			// Index 1 targets the France row Edit button.
			await marketsPage.getEditButton( 1 ).click();
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

			await marketsPage.getEditButton( 0 ).click();

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

			await marketsPage.getEditButton( 0 ).click();

			const modal = marketsPage.getEditPrimaryMarketModal();
			await expect( modal ).toBeVisible();

			await modal.getByRole( 'button', { name: 'Save' } ).click();

			await expect(
				page.locator( '.components-snackbar__content' )
			).toBeVisible();

			await expect( modal ).toBeVisible();

			await modal.getByRole( 'button', { name: 'Cancel' } ).click();
		} );
	} );
} );
