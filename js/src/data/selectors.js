/**
 * External dependencies
 */
import { createRegistrySelector } from '@wordpress/data';
import createSelector from 'rememo';

/**
 * Internal dependencies
 */
import { STORE_KEY } from './constants';
import { getReportKey, getPerformanceQuery } from './utils';

export const getShippingRates = ( state ) => {
	return state.mc.shipping.rates;
};

export const getShippingTimes = ( state ) => {
	return state.mc.shipping.times;
};

export const getSettings = ( state ) => {
	return state.mc.settings;
};

export const getJetpackAccount = ( state ) => {
	return state.mc.accounts.jetpack;
};

export const getGoogleAccount = ( state ) => {
	return state.mc.accounts.google;
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

export const getCountries = ( state ) => {
	return state.mc.countries;
};

export const getTargetAudience = ( state ) => {
	return state.mc.target_audience;
};

export const getAdsCampaigns = ( state ) => {
	return state.ads_campaigns;
};

export const getMCSetup = ( state ) => {
	return state.mc_setup;
};

export const getMCProductStatistics = ( state ) => {
	return state.mc_product_statistics;
};

// note: we use rememo createSelector here to cache the sliced issues array,
// to prevent returning new array to the consumer every time,
// which might cause rendering performance problem.
export const getMCIssues = createSelector(
	( state, query ) => {
		if ( ! state.mc_issues ) {
			return state.mc_issues;
		}

		const start = ( query.page - 1 ) * query.per_page;
		const end = start + query.per_page;

		return {
			issues: state.mc_issues.issues.slice( start, end ),
			total: state.mc_issues.total,
		};
	},
	( state ) => [
		state.mc_issues,
		state.mc_issues?.issues,
		state.mc_issues?.total,
	]
);

/**
 * Select report data according to parameters and report API query.
 *
 * @param  {Object} state The current store state will be injected by `wp.data`.
 * @param  {string} category Category of report, 'programs' or 'products'.
 * @param  {string} type Type of report, 'free' or 'paid'.
 * @param  {Object} reportQuery Query options of report API.
 * @param  {string} reportQuery.after Start date in 'YYYY-MM-DD' format.
 * @param  {string} reportQuery.before End date in 'YYYY-MM-DD' format.
 * @param  {Array<string>} reportQuery.fields An array of performance metrics field to retrieve.
 *
 * @return {Object|null} The report data of specified parameters. It would return `null` before the data is fetched.
 */
export const getReportByApiQuery = ( state, category, type, reportQuery ) => {
	const reportKey = getReportKey( category, type, reportQuery );
	return state.report[ reportKey ] || null;
};

/**
 * Select programs performace data according to parameters.
 *
 * @param  {Object} state The current store state will be injected by `wp.data`.
 * @param  {string} type Type of report, 'free' or 'paid'.
 * @param  {Object} query Query parameters in the URL.
 * @param  {string} dateReference Which date range to use, 'primary' or 'secondary'.
 *
 * @return {Object|null} The programs performace data of specified parameters. It would return `null` before the data is fetched.
 */
export const getDashboardPerformance = createRegistrySelector(
	( select ) => ( state, type, query, dateReference ) => {
		const report = select( STORE_KEY ).getReportByApiQuery(
			'programs',
			type,
			getPerformanceQuery( type, query, dateReference )
		);

		if ( report ) {
			return report.totals;
		}
		return report;
	}
);
