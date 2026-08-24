/**
 * Internal dependencies
 */
import MockRequests from '../mock-requests';
import { LOAD_STATE } from '../constants';

export const PRIMARY_MARKET = {
	id: 'primary',
	label: 'Primary Market',
	countries: [ 'US', 'CA' ],
	country: 'US',
	language: [ 'en' ],
	currency: [ 'USD' ],
	feed_label: 'US',
};

export const SECONDARY_MARKET = {
	id: 'fr',
	label: 'France',
	country: 'FR',
	language: [ 'fr' ],
	currency: [ 'EUR' ],
	feed_label: 'FR',
};

export const SHIPPING_RATES = [
	{
		country: 'US',
		currency: 'USD',
		rate: 10,
		options: { free_shipping_threshold: 50 },
	},
	{ country: 'CA', currency: 'CAD', rate: 15, options: {} },
	{ country: 'FR', currency: 'EUR', rate: 8, options: {} },
];

export const SHIPPING_TIMES = [
	{ country_code: 'US', time: 1, max_time: 5 },
	{ country_code: 'CA', time: 2, max_time: 7 },
	{ country_code: 'FR', time: 3, max_time: 10 },
];

export const LANGUAGES_CURRENCIES = {
	languages: [
		{ code: 'en', label: 'English' },
		{ code: 'fr', label: 'French' },
	],
	currencies: [
		{ code: 'USD', languages: [ 'en' ] },
		{ code: 'EUR', languages: [ 'fr' ] },
	],
};

export const MC_COUNTRIES = {
	countries: {
		US: { name: 'United States', currency: 'USD' },
		CA: { name: 'Canada', currency: 'CAD' },
		FR: { name: 'France', currency: 'EUR' },
		// Left unclaimed by PRIMARY_MARKET and SECONDARY_MARKET so it remains
		// selectable in the Add Market select for tests that create a new market.
		DE: { name: 'Germany', currency: 'EUR' },
	},
	continents: {
		NA: { name: 'North America', countries: [ 'US', 'CA' ] },
		EU: { name: 'Europe', countries: [ 'FR', 'DE' ] },
	},
};

/**
 * Builds the `shipping` object the backend embeds on each market response
 * (`MarketService::get_market_shipping()`), keyed by the market's `country`,
 * so the mock matches what `market-form.js` / the shipping table cells read
 * from `market.shipping`.
 *
 * @param {Object} market Market fixture (PRIMARY_MARKET, SECONDARY_MARKET, etc.).
 * @return {Object} The market's `shipping` sub-object.
 */
const buildMarketShipping = ( market ) => {
	const rate = SHIPPING_RATES.find( ( r ) => r.country === market.country );
	const time = SHIPPING_TIMES.find(
		( t ) => t.country_code === market.country
	);

	return {
		flat_rate: rate?.rate ?? null,
		free_shipping_threshold: rate?.options?.free_shipping_threshold ?? null,
		currency: rate?.currency ?? null,
		flat_time: time?.time ?? null,
		flat_max_time: time?.max_time ?? null,
	};
};

export default class MarketsPage extends MockRequests {
	/**
	 * @param {import('@playwright/test').Page} page
	 */
	constructor( page ) {
		super( page );
		this.page = page;
	}

	/**
	 * Override `glaData.isMultiLingualStore` before page scripts run.
	 * Must be called before goto().
	 *
	 * @param {boolean} value
	 * @return {Promise<void>}
	 */
	async mockIsMultiLingualStore( value ) {
		await this.page.addInitScript( ( isMultiLingualStore ) => {
			let _glaData;
			Object.defineProperty( window, 'glaData', {
				configurable: true,
				enumerable: true,
				get() {
					return _glaData;
				},
				set( newValue ) {
					_glaData = { ...newValue, isMultiLingualStore };
				},
			} );
		}, value );
	}

	/**
	 * Go to the Markets page.
	 *
	 * @return {Promise<void>}
	 */
	async goto() {
		await this.page.goto(
			'/wp-admin/admin.php?page=wc-admin&path=%2Fgoogle%2Fmarkets',
			{ waitUntil: LOAD_STATE.DOM_CONTENT_LOADED }
		);
	}

	/**
	 * Mock account connection requests needed for the full page to render.
	 *
	 * @return {Promise<void>}
	 */
	async mockConnectionRequests() {
		await this.mockJetpackConnected();
		await this.mockGoogleConnected();
		await this.mockMCConnected();
		await this.mockAdsAccountConnected();
	}

	/**
	 * Fulfill GET /wc/gla/mc/markets (list only, not sub-paths).
	 *
	 * @param {Array}  markets
	 * @param {number} [status=200]
	 * @return {Promise<void>}
	 */
	async fulfillMarkets( markets, status = 200 ) {
		await this.fulfillRequest(
			/\/wc\/gla\/mc\/markets(?!\/)/,
			markets,
			status,
			[ 'GET' ]
		);
	}

	/**
	 * Fulfill the market update request for a given market ID.
	 *
	 * Although the action uses `method: 'PUT'`, WordPress's apiFetch http-v1
	 * middleware converts PUT to POST with an X-HTTP-Method-Override header,
	 * so Playwright sees the request as POST.
	 *
	 * @param {string} id      Market ID, e.g. 'primary' or 'fr'.
	 * @param {Object} payload
	 * @param {number} [status=200]
	 * @return {Promise<void>}
	 */
	async fulfillMarketUpdate( id, payload, status = 200 ) {
		await this.fulfillRequest(
			new RegExp( `\\/wc\\/gla\\/mc\\/markets\\/${ id }\\b` ),
			payload,
			status,
			[ 'POST' ]
		);
	}

	/**
	 * Fulfill the market delete request for a given market ID.
	 *
	 * Although the action uses `method: 'DELETE'`, WordPress's apiFetch http-v1
	 * middleware converts it to POST with an X-HTTP-Method-Override header,
	 * so Playwright sees the request as POST.
	 *
	 * @param {string} id      Market ID, e.g. 'fr'.
	 * @param {Object} payload
	 * @param {number} [status=200]
	 * @return {Promise<void>}
	 */
	async fulfillMarketDelete( id, payload, status = 200 ) {
		await this.fulfillRequest(
			new RegExp( `\\/wc\\/gla\\/mc\\/markets\\/${ id }\\b` ),
			payload,
			status,
			[ 'POST' ]
		);
	}

	/**
	 * Fulfill POST /wc/gla/mc/markets (create).
	 *
	 * @param {Object} payload
	 * @param {number} [status=200]
	 * @return {Promise<void>}
	 */
	async fulfillCreateMarket( payload, status = 200 ) {
		await this.fulfillRequest(
			/\/wc\/gla\/mc\/markets(?!\/)/,
			payload,
			status,
			[ 'POST' ]
		);
	}

	/**
	 * Fulfill GET /wc/gla/mc/markets/languages-currencies.
	 *
	 * @param {Object} payload
	 * @return {Promise<void>}
	 */
	async fulfillMarketLanguagesCurrencies( payload ) {
		await this.fulfillRequest(
			/\/wc\/gla\/mc\/markets\/languages-currencies\b/,
			payload
		);
	}

	/**
	 * Fulfill GET /wc/gla/mc/shipping/rates.
	 *
	 * @param {Array}  rates
	 * @param {number} [status=200]
	 * @return {Promise<void>}
	 */
	async fulfillShippingRates( rates, status = 200 ) {
		await this.fulfillRequest(
			/\/wc\/gla\/mc\/shipping\/rates(?!\/)/,
			rates,
			status,
			[ 'GET' ]
		);
	}

	/**
	 * Fulfill GET /wc/gla/mc/shipping/times.
	 *
	 * @param {Array}  times
	 * @param {number} [status=200]
	 * @return {Promise<void>}
	 */
	async fulfillShippingTimes( times, status = 200 ) {
		await this.fulfillRequest(
			/\/wc\/gla\/mc\/shipping\/times(?!\/)/,
			times,
			status,
			[ 'GET' ]
		);
	}

	/**
	 * Fulfill POST /wc/gla/mc/shipping/rates/batch (upsert).
	 *
	 * @param {Object} payload
	 * @param {number} [status=200]
	 * @return {Promise<void>}
	 */
	async fulfillShippingRatesBatch( payload = {}, status = 200 ) {
		await this.fulfillRequest(
			/\/wc\/gla\/mc\/shipping\/rates\/batch\b/,
			payload,
			status,
			[ 'POST' ]
		);
	}

	/**
	 * Fulfill POST /wc/gla/mc/shipping/times/batch (upsert).
	 *
	 * @param {Object} payload
	 * @param {number} [status=200]
	 * @return {Promise<void>}
	 */
	async fulfillShippingTimesBatch( payload = {}, status = 200 ) {
		await this.fulfillRequest(
			/\/wc\/gla\/mc\/shipping\/times\/batch\b/,
			payload,
			status,
			[ 'POST' ]
		);
	}

	/**
	 * Fulfill GET /wc/gla/mc/countries.
	 *
	 * @param {Object} [payload]
	 * @return {Promise<void>}
	 */
	async fulfillMCCountries( payload = MC_COUNTRIES ) {
		await this.fulfillRequest( /\/wc\/gla\/mc\/countries\b/, payload );
	}

	/**
	 * Mock all requests needed to load the markets page for a given scenario.
	 * Call after mockIsMultiLingualStore() and before goto().
	 *
	 * @param {Object} options
	 * @param {string} options.shippingRate  One of 'manual', 'flat', 'automatic'.
	 * @param {Array}  [options.markets]      Defaults to [PRIMARY_MARKET, SECONDARY_MARKET].
	 * @param {boolean} [options.multilingual] Whether to also mock language/currency data.
	 * @return {Promise<void>}
	 */
	async mockMarketsPageRequests( {
		shippingRate,
		markets = [ PRIMARY_MARKET, SECONDARY_MARKET ],
		multilingual = false,
	} ) {
		await this.mockConnectionRequests();

		await this.fulfillTargetAudience( {
			location: 'selected',
			countries: [ 'US', 'CA' ],
			locale: 'en_US',
			language: 'English',
		} );

		await this.fulfillSettings( {
			shipping_rate: shippingRate,
			shipping_time: shippingRate === 'automatic' ? 'automatic' : 'flat',
		} );

		await this.fulfillMCCountries();

		// languages-currencies must be registered before the general /markets
		// mock so the LIFO stacking lets the specific handler intercept first.
		await this.fulfillMarketLanguagesCurrencies(
			multilingual
				? LANGUAGES_CURRENCIES
				: { languages: [], currencies: [] }
		);
		await this.fulfillMarkets(
			markets.map( ( market ) => ( {
				...market,
				shipping: buildMarketShipping( market ),
			} ) )
		);

		await this.fulfillShippingRates( SHIPPING_RATES );
		await this.fulfillShippingTimes( SHIPPING_TIMES );

		// Saving flat/automatic rates triggers a settings sync request.
		await this.mockSuccessfulSettingsSyncRequest();
	}

	/**
	 * Register a wait for the shipping rates batch upsert request.
	 *
	 * @param {Object} [options] Options forwarded to `page.waitForRequest`, e.g. `{ timeout: 1000 }`.
	 * @return {Promise<import('@playwright/test').Request>} The request.
	 */
	registerShippingRatesBatchRequest( options ) {
		return this.page.waitForRequest(
			( request ) =>
				request.url().includes( '/mc/shipping/rates/batch' ) &&
				request.method() === 'POST',
			options
		);
	}

	/**
	 * Register a wait for the market update request for a given market ID.
	 *
	 * Shipping fields (rate/threshold/times) are nested under `shipping` in
	 * this request's body rather than sent as a separate rates/times batch
	 * call — see `MarketForm.buildShippingPayload()`.
	 *
	 * @param {string} id      Market ID, e.g. 'primary' or 'fr'.
	 * @param {Object} [options] Options forwarded to `page.waitForRequest`, e.g. `{ timeout: 1000 }`.
	 * @return {Promise<import('@playwright/test').Request>} The request.
	 */
	registerMarketUpdateRequest( id, options ) {
		const pattern = new RegExp( `\\/wc\\/gla\\/mc\\/markets\\/${ id }\\b` );
		return this.page.waitForRequest(
			( request ) =>
				pattern.test( request.url() ) && request.method() === 'POST',
			options
		);
	}

	/**
	 * Wait for the markets table card to be present in the DOM.
	 *
	 * @return {Promise<void>}
	 */
	async waitForMarketsTable() {
		await this.page
			.locator( '.gla-markets-dashboard__card' )
			.waitFor( { state: 'attached' } );
	}

	/**
	 * @return {import('@playwright/test').Locator} Locator for the multilingual flat shipping notice.
	 */
	getMultilingualFlatShippingNotice() {
		return this.page.locator( '.gla-multilingual-flat-shipping-notice' );
	}

	/**
	 * Returns a locator for the Edit button on the row matching `rowText`,
	 * scoped to that row so it isn't affected by row order.
	 *
	 * @param {string|RegExp} rowText Text used to locate the row, e.g. a market name.
	 * @return {import('@playwright/test').Locator} Locator for the row's Edit button.
	 */
	getEditButtonForRow( rowText ) {
		return this.page
			.getByRole( 'row' )
			.filter( { hasText: rowText } )
			.getByRole( 'button', { name: 'Edit' } );
	}

	/**
	 * Returns a locator for the overflow "Actions" buttons that appear on rows
	 * eligible for deletion (secondary markets). Delete lives inside this
	 * dropdown menu, not as a standalone button.
	 *
	 * @return {import('@playwright/test').Locator} Locator for all Actions overflow buttons.
	 */
	getDeleteButtons() {
		return this.page.getByRole( 'button', { name: 'Actions' } );
	}

	/**
	 * Opens the Delete confirmation modal for the row matching `rowText`, via
	 * the row's "Actions" overflow menu.
	 *
	 * @param {string} rowText Text used to locate the row, e.g. a market name.
	 * @return {Promise<void>}
	 */
	async openDeleteMarketModal( rowText ) {
		await this.page
			.getByRole( 'row' )
			.filter( { hasText: rowText } )
			.getByRole( 'button', { name: 'Actions' } )
			.click();

		await this.page.getByRole( 'menuitem', { name: 'Delete' } ).click();
	}

	/**
	 * @return {import('@playwright/test').Locator} Locator for the "Delete market" confirmation dialog.
	 */
	getDeleteMarketModal() {
		return this.page.getByRole( 'dialog', { name: 'Delete market' } );
	}

	/**
	 * @return {import('@playwright/test').Locator} Locator for the "Edit primary market" dialog.
	 */
	getEditPrimaryMarketModal() {
		return this.page.getByRole( 'dialog', {
			name: 'Edit primary market',
		} );
	}

	/**
	 * @param {string} marketName
	 * @return {import('@playwright/test').Locator} Locator for the "Edit <marketName>" dialog.
	 */
	getEditMarketModal( marketName ) {
		return this.page.getByRole( 'dialog', {
			name: `Edit ${ marketName }`,
		} );
	}

	/**
	 * @return {import('@playwright/test').Locator} Locator for the "Add market" dialog.
	 */
	getAddMarketModal() {
		return this.page.getByRole( 'dialog', { name: 'Add market' } );
	}

	/**
	 * The "Add market" button in the page header (not inside the modal).
	 *
	 * @return {import('@playwright/test').Locator} Locator for the header "Add market" button.
	 */
	getHeaderAddMarketButton() {
		return this.page
			.locator( '.gla-markets-header' )
			.getByRole( 'button', { name: 'Add market' } );
	}

	/**
	 * @param {import('@playwright/test').Locator} [container] Defaults to the full page.
	 * @return {import('@playwright/test').Locator} Locator for the shipping info notice.
	 */
	getShippingInfoNotice( container ) {
		return ( container || this.page ).locator(
			'.gla-shipping-info-notice'
		);
	}

	/**
	 * @param {import('@playwright/test').Locator} [container]
	 * @return {import('@playwright/test').Locator} Locator for the locale section.
	 */
	getLocaleSection( container ) {
		return ( container || this.page ).locator(
			'.gla-market-fields__locale-section'
		);
	}

	/**
	 * @param {import('@playwright/test').Locator} [container]
	 * @return {import('@playwright/test').Locator} Locator for the multilingual plugin prompt.
	 */
	getMultiLingualPluginPrompt( container ) {
		return ( container || this.page ).locator(
			'.gla-multilingual-plugin-prompt'
		);
	}
}
