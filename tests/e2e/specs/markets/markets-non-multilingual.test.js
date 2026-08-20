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
		await clearServiceBasedMerchant();
	} );

	test.afterAll( async () => {
		await clearOnboardedMerchant();
		await page.close();
	} );

	test.describe( 'Shipping Rate - Manual', () => {
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
			await marketsPage.getEditButtonForRow( /Primary Market/ ).click();

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

			await expect(
				modal.getByLabel( 'Free shipping over a specific order value' )
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
			// This scenario has only the primary market — France must not
			// exist, matching the earlier "only the primary market row is
			// visible" assertion.
			await marketsPage.fulfillMarkets( [ PRIMARY_MARKET ] );

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
	} );

	test.describe( 'Shipping Rate - Flat', () => {
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
			await marketsPage.getEditButtonForRow( /Primary Market/ ).click();

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

		test( 'secondary market edit has no audience section, no shipping notice; Add modal shows country select', async () => {
			await marketsPage.getEditButtonForRow( 'France' ).click();
			const editModal = marketsPage.getEditMarketModal( 'France' );
			await expect( editModal ).toBeVisible();
			await expect(
				editModal.locator( '.woocommerce-tree-select-control' )
			).not.toBeAttached();
			await expect(
				marketsPage.getShippingInfoNotice( editModal )
			).not.toBeVisible();
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

		// Runs before the fold test on purpose: this block is serial on one page, and a
		// snackbar lingers, so asserting its absence afterwards would pass either way.
		test( 'a market created in its own right shows no fold snackbar', async () => {
			await marketsPage.fulfillCreateMarket( {
				id: 'ca',
				label: 'Canada',
				country: 'CA',
			} );
			await marketsPage.fulfillMarkets( [
				PRIMARY_MARKET,
				SECONDARY_MARKET,
				{ id: 'ca', label: 'Canada', country: 'CA' },
			] );

			await marketsPage.getHeaderAddMarketButton().click();
			const addModal = marketsPage.getAddMarketModal();
			await expect( addModal ).toBeVisible();

			await addModal.getByLabel( 'Market' ).selectOption( 'CA' );

			await addModal
				.getByRole( 'button', { name: 'Add market' } )
				.click();

			await expect( addModal ).not.toBeVisible();

			await expect(
				page.getByText(
					'was added to the Primary market, as its configuration matched the existing Primary market settings'
				)
			).toBeHidden();
		} );

		test( 'a market whose shipping matches Primary is folded into it, with a snackbar', async () => {
			// A matching shipping profile is answered with the primary market and the merge
			// flag rather than a created market.
			await marketsPage.fulfillCreateMarket( {
				...PRIMARY_MARKET,
				countries: [ 'US', 'CA' ],
				merged_into_primary: true,
			} );
			// Canada joined the primary market, so the refreshed list gives it no row.
			await marketsPage.fulfillMarkets( [
				{ ...PRIMARY_MARKET, countries: [ 'US', 'CA' ] },
				SECONDARY_MARKET,
			] );

			await marketsPage.getHeaderAddMarketButton().click();
			const addModal = marketsPage.getAddMarketModal();
			await expect( addModal ).toBeVisible();

			await addModal.getByLabel( 'Market' ).selectOption( 'CA' );

			await addModal
				.getByRole( 'button', { name: 'Add market' } )
				.click();

			await expect( addModal ).not.toBeVisible();

			await expect(
				page.locator( '.components-snackbar__content' ).last()
			).toContainText(
				'was added to the Primary market, as its configuration matched the existing Primary market settings'
			);

			await marketsPage.waitForMarketsTable();

			// Canada is covered by Primary, so the refreshed list gives it no row of its own.
			await expect(
				page.getByRole( 'row', { name: /^Canada/ } )
			).toBeHidden();
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
			await marketsPage.getEditButtonForRow( /Primary Market/ ).click();

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
