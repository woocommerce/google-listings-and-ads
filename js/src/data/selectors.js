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

export const getGoogleMCContactInformation = ( state ) => {
	return state.mc.contact;
};

// Create another selector to separate the `hasFinishedResolution` state with `getGoogleMCContactInformation`.
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
