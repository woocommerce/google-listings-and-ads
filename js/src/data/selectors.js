/**
 * External dependencies
 */
import { createRegistrySelector } from '@wordpress/data';
import createSelector from 'rememo';

/**
 * Internal dependencies
 */
import { STORE_KEY } from './constants';
import { getReportQuery, getReportKey, getPerformanceQuery } from './utils';

/**
 * @typedef {import('.~/data/actions').CountryCode} CountryCode
 * @typedef {import('.~/data/types.js').GeneralState} GeneralState
 * @typedef {import('.~/data/types.js').AssetEntityGroup} AssetEntityGroup
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
 * @typedef {Object} GoogleMCAccount
 * @property {number} id Account ID. It's 0 if not yet connected.
 * @property {string} status Connection status.
 */

/**
 * @typedef {Object} Tour
 * @property {string} id The tour ID
 * @property {boolean} checked True if the tour was checked by the user.
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

/**
 * Select the state of phone number associated with the Google Merchant Center account.
 *
 * Create another selector to separate the `hasFinishedResolution` state with `getGoogleMCContactInformation`.
 *
 * @param {Object} state The current store state will be injected by `wp.data`.
 * @return {{ data: ContactInformation|null, loaded: boolean }} The payload of contact information associated with the Google Merchant Center account and its loaded state.
 */
export const getGoogleMCPhoneNumber = createRegistrySelector(
	( select ) => ( state ) => {
		const selector = select( STORE_KEY );

		const loaded =
			!! getGoogleMCContactInformation( state ) ||
			selector.hasFinishedResolution( 'getGoogleMCContactInformation' );

		return {
			loaded,
			data: selector.getGoogleMCContactInformation(),
		};
	}
);

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
 * @typedef {import('.~/data/actions').Campaign} Campaign
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
 * @typedef {import('.~/data/actions').ProductStatistics } ProductStatistics
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

export const getPolicyCheck = ( state ) => {
	return state.mc.policy_check;
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
 * @typedef {Object} PerformaceData
 * @property {boolean} loaded Whether the data have been loaded.
 * @property {Object|null} data The programs performace data of specified parameters. It would return `null` before the data is fetched.
 */
/**
 * Select programs performace data according to parameters.
 *
 * @param  {Object} state The current store state will be injected by `wp.data`.
 * @param  {string} type Type of report, 'free' or 'paid'.
 * @param  {Object} query Query parameters in the URL.
 * @param  {string} dateReference Which date range to use, 'primary' or 'secondary'.
 *
 * @return {PerformaceData} Performace data.
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
 *
 * Return a tour by ID
 *
 * @param {Object} state The state
 * @param {string} tourId The tour ID to get
 * @return {Tour|null} The tour. It will be `null` if not yet fetched or fetched but doesn't exist.
 */
export const getTour = ( state, tourId ) => {
	return state.tours[ tourId ] || null;
};

/**
 * Return the customer accepted data terms.
 *
 * @param {Object} state The state
 * @return {boolean|null} TRUE if the user signed the TOS. It will be `null` if not yet fetched or fetched but doesn't exist.
 */
export const getAcceptedCustomerDataTerms = ( state ) => {
	return state.ads.conversion_tracking_setting.accepted_customer_data_terms;
};

/**
 * Return whether the user allowed enhanced conversion tracking.
 *
 * @param {Object} state The state
 * @return {string|null} Possible values are 'pending' | 'enabled' | 'disabled'. It will be `null` if not yet fetched or fetched but doesn't exist.
 */
export const getAllowEnhancedConversions = ( state ) => {
	return state.ads.conversion_tracking_setting.allow_enhanced_conversions;
};
