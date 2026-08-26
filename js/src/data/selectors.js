/**
 * External dependencies
 */
import { createRegistrySelector } from '@wordpress/data';
import createSelector from 'rememo';

/**
 * Internal dependencies
 */
import { STORE_KEY } from './constants';
import { generateKeyFromObject } from '~/utils/generateKeyFromObject';
import {
	arrayToUnderscoreKey,
	getReportQuery,
	getReportKey,
	getPerformanceQuery,
	getCountryCodesKey,
	getAdsBudgetMetricsKey,
} from './utils';

/**
 * @typedef {import('~/data/actions').CountryCode} CountryCode
 * @typedef {import('~/data/types.js').GeneralState} GeneralState
 * @typedef {import('~/data/types.js').AssetEntityGroup} AssetEntityGroup
 * @typedef {import('~/data/types.js').GoogleTagManagerAccount} GoogleTagManagerAccount
 */

/**
 * Select the general state.
 *
 * @param {Object} state The current store state will be injected by `wp.data`.
 * @return {GeneralState} General state.
 */
export const getGeneral = ( state ) => {
	return state.general;
};

export const getShippingRates = ( state ) => {
	return state.mc.shipping.rates;
};

export const getShippingTimes = ( state ) => {
	return state.mc.shipping.times;
};

export const getSettings = ( state ) => {
	return state.mc.settings;
};

/**
 * @typedef {Object} JetpackAccount
 * @property {'yes'|'no'} active Whether jetpack is connected.
 * @property {'yes'|'no'} owner Whether the current admin user is the jetpack owner.
 * @property {string|''} email Owner email. Available for jetpack owner.
 * @property {string|''} displayName Owner name. Available for jetpack owner.
 */

/**
 * @typedef {Object} Tour
 * @property {string} id The tour ID
 * @property {boolean} checked True if the tour was checked by the user.
 */

/**
 * @typedef {Object} PriceBenchmarkQueryParams
 * @property {string} [product_id] The product ID to get the price benchmark for.
 * @property {string} [order] The sort direction (e.g. 'asc' or 'desc').
 * @property {string} [orderby] The field to sort by.
 * @property {string} [search] The search query string.
 * @property {number} [page] The current page number.
 * @property {number} [per_page] The number of items per page.
 */

/**
 * @typedef {import('./actions').ApiError} ApiError
 */

/**
 * @typedef {Object} DetailedErrors
 * @property {string} slot Slot identifier for the error.
 * @property {ApiError} error Error object.
 */

/**
 * Select jetpack connection state.
 *
 * @param {Object} state The current store state will be injected by `wp.data`.
 * @return {JetpackAccount|null} The jetpack connection state. It would return `null` before the data is fetched.
 */
export const getJetpackAccount = ( state ) => {
	return state.mc.accounts.jetpack;
};

export const getGoogleAccount = ( state ) => {
	return state.mc.accounts.google;
};

export const getGoogleAccountAccess = ( state ) => {
	return state.mc.accounts.google_access;
};

export const getGoogleMCAccount = ( state ) => {
	return state.mc.accounts.mc;
};

export const getExistingGoogleMCAccounts = ( state ) => {
	return state.mc.accounts.existing_mc;
};

export const getGoogleAdsAccount = ( state ) => {
	return state.mc.accounts.ads;
};

export const getGoogleAdsAccountBillingStatus = ( state ) => {
	return state.mc.accounts.ads_billing_status;
};

export const getExistingGoogleAdsAccounts = ( state ) => {
	return state.mc.accounts.existing_ads;
};

export const getYouTubeAccount = ( state ) => {
	return state.mc.accounts.youtube;
};

/**
 * @param {Object} state The root state.
 * @return {GoogleTagManagerAccount|null} The Google Tag Manager connection state. Returns `null`
 *   before the data has been fetched.
 */
export const getGoogleTagManagerAccount = ( state ) => {
	return state.mc.accounts.google_tag_manager;
};

/**
 * @typedef {Object} Address
 * @property {string|null} street_address Street-level part of the address. `null` when empty.
 * @property {string|null} locality City, town or commune. `null` when empty.
 * @property {string|null} region Top-level administrative subdivision of the country. `null` when empty.
 * @property {string|null} postal_code Postal code or ZIP. `null` when empty.
 * @property {CountryCode} country Two-letter country code in ISO 3166-1 alpha-2 format. Example: 'US'.
 *
 * @typedef {Object} ContactInformation
 * @property {number} id The Google Merchant Center account ID.
 * @property {string|null} phone_number The phone number in international format associated with the Google Merchant Center account. Example: '+12133734253'. `null` if the phone number is not yet set.
 * @property {'verified'|'unverified'|null} phone_verification_status The verification status of the phone number associated with the Google Merchant Center account. `null` if the phone number is not yet set.
 * @property {Address|null} mc_address The address associated with the Google Merchant Center account. `null` if the address is not yet set.
 * @property {Address|null} wc_address The WooCommerce store address. `null` if the address is not yet set.
 * @property {boolean} is_mc_address_different Whether the Google Merchant Center account address is different than the WooCommerce store address.
 * @property {string[]} wc_address_errors The errors associated with the WooCommerce store address.
 */

/**
 * Select the state of contact information associated with the Google Merchant Center account.
 *
 * @param {Object} state The current store state will be injected by `wp.data`.
 * @return {ContactInformation|null} The contact information associated with the Google Merchant Center account. It would return `null` before the data is fetched.
 */
export const getGoogleMCContactInformation = ( state ) => {
	return state.mc.contact;
};

export const getMCCountriesAndContinents = createSelector(
	( state ) => {
		const { countries, continents } = state.mc;

		return {
			countries,
			continents,
		};
	},
	( state ) => [ state.mc.countries, state.mc.continents ]
);

export const getTargetAudience = ( state ) => {
	return state.mc.target_audience;
};

/**
 * @typedef {import('~/data/actions').Campaign} Campaign
 */

/**
 * Get the Ads Campaign
 *
 * @param  {Object} state The current store state will be injected by `wp.data`.
 * @param  {Object} query Campaigns options.
 * @param  {boolean} query.exclude_removed Whether to exclude removed campaigns.
 *
 * @return {Array<Campaign>} campaign data.
 */
export const getAdsCampaigns = ( state, query ) => {
	if ( query?.exclude_removed === false ) {
		return state.all_ads_campaigns;
	}

	return state.ads_campaigns;
};

/**
 * Get campaigns that are missing the EU political advertising declaration.
 *
 * @param {Object} state The current store state will be injected by `wp.data`.
 * @return {Array<{id: number, name: string}>|null} List of campaigns missing the EU declaration, or null if not yet loaded.
 */
export const getAdsCampaignsMissingEuDeclaration = ( state ) => {
	return state.ads_campaigns_missing_eu_declaration;
};

/**
 * Get the enhanced conversions setting.
 * This setting indicates whether enhanced conversions are enabled for the Google Ads account.
 *
 * @param {Object} state The current store state will be injected by `wp.data`.
 * @return {boolean} The enhanced conversions setting. Returns `true` if enabled, `false` otherwise.
 */
export const getEnableEnhancedConversions = ( state ) => {
	return state.ads.enable_enhanced_conversions;
};

/**
 * Gets the ads settings object.
 *
 * @param {Object} state The current store state will be injected by `wp.data`.
 * @return {Object|null} The ads settings object, or null if not yet loaded.
 */
export const getAdsSettings = ( state ) => {
	return state.ads.settings;
};

/**
 * Gets the asset groups by the given campaign ID.
 *
 * @param {Object} state The current store state will be injected by `wp.data`.
 * @param {number} campaignId The ID of the campaign to get the asset groups.
 *
 * @return {AssetEntityGroup[]|null} The asset groups of the specified campaign.
 */
export const getCampaignAssetGroups = ( state, campaignId ) => {
	return state.campaign_asset_groups[ campaignId ] || null;
};

export const getMCSetup = ( state ) => {
	return state.mc_setup;
};

/**
 * @typedef {import('~/data/actions').ProductStatistics } ProductStatistics
 */

/**
 * Get the MC product statistics data.
 *
 * @param {Object} state The current store state will be injected by `wp.data`.
 *
 * @return {ProductStatistics|null} The MC product statistics data. Returns `null` if data have not yet loaded.
 */
export const getMCProductStatistics = ( state ) => {
	return state.mc_product_statistics;
};

export const getMCReviewRequest = ( state ) => {
	return state.mc_review_request;
};

// note: we use rememo createSelector here to cache the sliced issues array,
// to prevent returning new array to the consumer every time,
// which might cause rendering performance problem.
export const getMCIssues = createSelector(
	( state, query ) => {
		const mcIssues = state.mc_issues[ query.issue_type ];

		if ( ! mcIssues ) {
			return mcIssues;
		}

		const start = ( query.page - 1 ) * query.per_page;
		const end = start + query.per_page;

		return {
			issues: mcIssues.issues.slice( start, end ),
			total: mcIssues.total,
		};
	},
	( state ) => [ state.mc_issues ]
);

export const getMCProductFeed = ( state, query ) => {
	if ( ! state.mc_product_feed ) {
		return state.mc_product_feed;
	}

	return {
		products: state.mc_product_feed.pages[ query.page ],
		total: state.mc_product_feed.total,
	};
};

/**
 * @typedef {Object} ReportQuery
 * @property {string} after Start date in 'YYYY-MM-DD' format.
 * @property {string} before End date in 'YYYY-MM-DD' format.
 * @property {Array<string>} fields An array of performance metrics field to retrieve.
 * @property {string} [ids] Filter product or campaign by a comma separated list of IDs.
 * @property {string} [orderby] Column to order the results by, this must be one of the fields in requesting.
 * @property {string} [order] Results order, 'desc' or 'asc'.
 * @property {string} [interval] How to segment the data. Note that the 'free' type data only supports segmenting by day,
 *                                         but the 'paid' type report allows any of the following values:
 *                                         'day', 'week', 'month', 'quarter', 'year'
 */

/**
 * Select report data according to parameters and report API query.
 *
 * @param  {Object} state The current store state will be injected by `wp.data`.
 * @param  {string} category Category of report, 'programs' or 'products'.
 * @param  {string} type Type of report, 'free' or 'paid'.
 * @param  {ReportQuery} reportQuery Query options of report API.
 *
 * @return {Object|null} The report data of specified parameters. It would return `null` before the data is fetched.
 */
export const getReportByApiQuery = ( state, category, type, reportQuery ) => {
	const reportKey = getReportKey( category, type, reportQuery );
	return state.report[ reportKey ] || null;
};

/**
 * @typedef {Object} ReportSchema
 * @property {boolean} loaded Whether the data have been loaded.
 * @property {ReportData} data Fetched report data.
 * @property {ReportQuery} reportQuery The actual, resolved query used to request the report. Available synchronously.
 * @template ReportData
 */

/**
 * Select report data according to parameters and URL query.
 *
 * @param  {Object} state The current store state will be injected by `wp.data`.
 * @param  {string} category Category of report, 'programs' or 'products'.
 * @param  {string} type Type of report, 'free' or 'paid'.
 * @param  {Object} query Query parameters in the URL.
 * @param  {string} dateReference Which date range to use, 'primary' or 'secondary'.
 *
 * @return {ReportSchema} Report data.
 */
export const getReport = createRegistrySelector(
	( select ) => ( state, category, type, query, dateReference ) => {
		const selector = select( STORE_KEY );
		const reportQuery = getReportQuery(
			category,
			type,
			query,
			dateReference
		);
		const args = [ category, type, reportQuery ];

		return {
			reportQuery,
			loaded: selector.hasFinishedResolution(
				'getReportByApiQuery',
				args
			),
			data: selector.getReportByApiQuery( ...args ),
		};
	}
);

/**
 * @typedef {Object} PerformanceData
 * @property {boolean} loaded Whether the data have been loaded.
 * @property {Object|null} data The programs performance data of specified parameters. It would return `null` before the data is fetched.
 */
/**
 * Select programs performance data according to parameters.
 *
 * @param  {Object} state The current store state will be injected by `wp.data`.
 * @param  {string} type Type of report, 'free' or 'paid'.
 * @param  {Object} query Query parameters in the URL.
 * @param  {string} dateReference Which date range to use, 'primary' or 'secondary'.
 *
 * @return {PerformanceData} Performance data.
 */
export const getDashboardPerformance = createRegistrySelector(
	( select ) => ( state, type, query, dateReference ) => {
		const selector = select( STORE_KEY );
		const args = [
			'programs',
			type,
			getPerformanceQuery( type, query, dateReference ),
		];
		const report = selector.getReportByApiQuery( ...args );

		return {
			data: report ? report.totals : null,
			loaded: selector.hasFinishedResolution(
				'getReportByApiQuery',
				args
			),
		};
	}
);

export const getMappingAttributes = ( state ) => {
	return state.mc.mapping.attributes;
};

export const getMappingSources = ( state, attributeKey ) => {
	return state.mc.mapping.sources[ attributeKey ];
};

export const getMappingRules = createSelector(
	( state, pagination ) => {
		const stateRules = { ...state.mc.mapping.rules };
		const { page, perPage } = pagination;

		const start = ( page - 1 ) * perPage;
		const end = start + perPage;

		return {
			rules: stateRules?.items.slice( start, end ) || [],
			total: stateRules.total,
			pages: stateRules.pages,
		};
	},
	( state ) => [ state.mc.mapping.rules ]
);

export const getStoreCategories = ( state ) => {
	return state.store_categories;
};

/**
 * Retrieves the tours data from the state object.
 *
 * @param {Object} state - The Redux state object.
 * @return {Object.<string, Tour>} The tours data from the state. It will be `null` if not yet fetched or fetched but doesn't exist.
 */
export const getTours = ( state ) => {
	return state.tours || null;
};

/**
 * Return object containing properties hasAccess, inviteLink and step for the Google Ads account.
 *
 * @param {Object} state The state
 * @return {Object} The ads status containing the hasAccess, inviteLink and step properties.
 */
export const getGoogleAdsAccountStatus = ( state ) => {
	return state.ads.accountStatus;
};

/**
 * Retrieves ad budget recommendations for provided country codes.
 * If no recommendations are found, it returns `null`.
 *
 * @param {Object} state The state
 * @param {Array<CountryCode>} [countryCodes] - An array of country code strings to retrieve the budget recommendations for.
 * @return {Object|null} The recommendations. It will be `null` if not yet fetched or fetched but doesn't exist.
 */
export const getAdsBudgetRecommendations = ( state, countryCodes = [] ) => {
	const key = getCountryCodesKey( countryCodes );
	return state.ads.budgetRecommendations[ key ] || null;
};

export const getAdsBudgetMetrics = ( state, countryCodes, budget ) => {
	const key = getAdsBudgetMetricsKey( countryCodes, budget );
	return state.ads.budgetMetrics[ key ] || null;
};

/**
 * Retrieves the CYO incentives from the state.
 *
 * @param {Object} state The state
 * @return {Array|null} The CYO incentives. It will be `null` if not yet fetched or fetched but doesn't exist.
 */
export const getCYOIncentives = ( state ) => {
	return state.ads.cyo_incentives?.incentives ?? null;
};

/**
 * Return the GTIN Migration status.
 *
 * @param {Object} state The state
 * @return {Object} The GTIN Migration status.
 */
export const getGtinMigrationStatus = ( state ) => {
	return state.gtinMigrationStatus;
};

/**
 * Retrieves the price benchmark summary from the state.
 *
 * @param {Object} state - The state object containing price benchmark data.
 * @return {Object} The price benchmark summary.
 */
export const getPriceBenchmarkSummary = ( state ) => {
	return state.price_benchmark.summary;
};

/**
 * Retrieves the price benchmark suggestions from the state. If `product_id` is provided in the arguments,
 * it returns the suggestions for that specific product. Otherwise, it generates a key from the arguments
 * and retrieves the suggestions based on that key.
 *
 * @param {Object} state - The state object containing price benchmark data.
 * @param {PriceBenchmarkQueryParams} args - Arguments to generate the key for suggestions.
 * @return {Object} The price benchmark suggestions and meta.
 */
export const getPriceBenchmarkSuggestions = createSelector(
	( state, args ) => {
		if ( args.product_id ) {
			return state.price_benchmark.suggestions.items[ args.product_id ];
		}

		const key = generateKeyFromObject( args );
		const itemsById = state.price_benchmark.suggestions.items;
		const ids =
			state.price_benchmark.suggestions.queries[ key ]?.items || [];
		return {
			items: ids.map( ( id ) => itemsById[ id ] ).filter( Boolean ),
			meta: state.price_benchmark.suggestions.queries[ key ]?.meta,
		};
	},
	( state, args ) => [
		state.price_benchmark.suggestions,
		generateKeyFromObject( args ),
	]
);

/**
 * Retrieves the price benchmark suggestion for a specific product.
 *
 * @param {Object} state - The Redux state object containing price benchmark data.
 * @param {string|number} productId - The unique identifier of the product.
 * @return {Object} The price benchmark suggestion for the specified product, or undefined if not found.
 */
export const getPriceBenchmarkSuggestion = ( state, productId ) => {
	return state.price_benchmark.suggestions.items[ productId ];
};

/**
 * Retrieves ad recommendations of a specific type from the state.
 *
 * @param {Object} state - The Redux state object containing ads data.
 * @param {Array<string>} types - The types of ad recommendations to retrieve.
 * @return {Object|null} The recommendations for the specified type, or null if not found.
 */
export const getAdsRecommendations = ( state, types, campaign_id = null ) => {
	const keyToHash = campaign_id ? [ campaign_id, ...types ] : types;
	const key = arrayToUnderscoreKey( keyToHash );
	return state.ads.recommendations[ key ] || null;
};

/**
 * Get detailed error objects whose `slot` matches any of the provided slots.
 *
 * If `slots` is not an array or is empty, the function returns null.
 * The function filters `state.detailed_errors`, skipping falsy entries, and
 * returns only those errors whose `slot` value is included in `errorSlots`.
 *
 * @param {Object} state - State containing detailed errors.
 * @param {Array<DetailedErrors>} state.detailed_errors - Array of detailed error objects.
 * @param {Array<string|number>} slots - Array of slot identifiers to match against each error's `slot`.
 * @return {Array<Object>} Array of matching error objects, or an empty array when `slots` is not a non-empty array.
 */
export const getDetailedErrorBySlots = ( state, slots ) => {
	if ( ! Array.isArray( slots ) || slots.length === 0 ) {
		return [];
	}

	return state.detailed_errors.filter( ( error ) => {
		return slots.includes( error?.slot );
	} );
};

/**
 * Retrieves the GenAI media assets from the state for a given URL and type.
 *
 * @param {Object} state - The Redux state object containing GenAI assets data.
 * @param {string} url - The URL associated with the GenAI assets.
 * @param {'marketing_image'|'square_marketing_image'|'portrait_marketing_image'|undefined} [assetType] - The type of media asset to retrieve.
 * @return {Array<string>} The media assets for the specified URL and type, or an empty array if not found.
 */
export const getGenAIMediaAssets = ( state, url, assetType ) => {
	const mediaAssets = state.gen_ai_assets?.[ url ]?.media;

	if ( ! url || ! mediaAssets ) {
		return [];
	}

	if ( assetType ) {
		return mediaAssets[ assetType ] ?? [];
	}

	return mediaAssets;
};

/**
 * Retrieves the GenAI text assets from the state for a given URL and type.
 *
 * @param {Object} state - The Redux state object containing GenAI assets data.
 * @param {string} url - The URL associated with the GenAI assets.
 * @param {'headline'|'long_headline'|'description'|undefined} [assetType] - The type of text asset to retrieve.
 * @return {Array<string>} The text assets for the specified URL and type, or an empty array if not found.
 */
export const getGenAITextAssets = ( state, url, assetType ) => {
	const textAssets = state.gen_ai_assets?.[ url ]?.text;

	if ( ! url || ! textAssets ) {
		return [];
	}

	if ( assetType ) {
		return textAssets[ assetType ] ?? [];
	}

	return textAssets;
};

/**
 * Retrieves all markets from the state.
 *
 * @param {Object} state - The Redux state object containing markets data.
 * @return {Array} The list of markets.
 */
export const getMarkets = ( state ) => {
	return state.mc.markets;
};

/**
 * Retrieves a specific market from the state by its ID.
 *
 * @param {Object} state - The Redux state object containing markets data.
 * @param {string|number} id - The unique identifier of the market.
 * @return {Object|undefined} The market with the specified ID, or undefined if not found.
 */
export const getMarket = ( state, id ) => {
	return state.mc.markets.find( ( market ) => market.id === id );
};

/**
 * @typedef {Object} MCLanguage
 * @property {string} code BCP 47 language code (e.g. `"en"`).
 * @property {string} label Human-readable language name (e.g. `"English"`).
 */

/**
 * @typedef {Object} MCCurrency
 * @property {string} code ISO 4217 currency code (e.g. `"USD"`).
 * @property {string} symbol Currency symbol (e.g. `"$"`).
 */

/**
 * Select available languages and currencies from the store's local installation (e.g. WPML).
 * Triggers the single resolver that populates both state properties.
 *
 * @param {Object} state The current store state.
 * @return {{languages: Array<MCLanguage>|null, currencies: Array<MCCurrency>|null}} Available languages and currencies, or null values before data is fetched.
 */
export const getAvailableLanguagesCurrencies = ( state ) => {
	const { languages, currencies } = state.mc;
	return { languages, currencies };
};

/**
 * Select the available languages from the store's local installation (e.g. WPML).
 * Call getAvailableLanguagesCurrencies to trigger the data fetch.
 *
 * @param {Object} state The current store state.
 * @return {Array<MCLanguage>|null} Available languages, or null before data is fetched.
 */
export const getAvailableLanguages = ( state ) => state.mc.languages;

/**
 * Select the available currencies from the store's local installation (e.g. WPML/WCML).
 * Call getAvailableLanguagesCurrencies to trigger the data fetch.
 *
 * @param {Object} state The current store state.
 * @return {Array<MCCurrency>|null} Available store currencies, or null before data is fetched.
 */
export const getAvailableStoreCurrencies = ( state ) => state.mc.currencies;

/**
 * @typedef {Object} Notification
 * @property {string} id Notification ID.
 * @property {number} triggered_at Unix timestamp of when the notification was triggered.
 */

/**
 * @param {Object} state
 * @return {Array<Notification>} Current notifications, or empty array if none.
 */
export const getNotifications = ( state ) => {
	return state.notifications ?? [];
};
