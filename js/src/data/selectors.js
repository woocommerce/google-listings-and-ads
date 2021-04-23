/**
 * External dependencies
 */
import { createRegistrySelector } from '@wordpress/data';
import { getDateParamsFromQuery } from '@woocommerce/date';

/**
 * Internal dependencies
 */
import { STORE_KEY } from './constants';
import { getReportQuery, getReportKey } from './utils';

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

export const getMCIssues = ( state, query ) => {
	const key = JSON.stringify( query );
	return state.mc_issues[ key ];
};

/**
 * Select report data according to parameters.
 *
 * @param  {Object} state The current store state will be injected by `wp.data`.
 * @param  {string} category Category of report, 'programs' or 'products'.
 * @param  {string} type Type of report, 'free' or 'paid'.
 * @param  {Object} query Query parameters in the URL.
 * @param  {string} dateReference Which date range to use, 'primary' or 'secondary'.
 *
 * @return {Object|null} The report data of specified parameters. It would return `null` before the data is fetched.
 */
export const getReport = ( state, category, type, query, dateReference ) => {
	const reportQuery = getReportQuery( query, dateReference );
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
		const report = select( STORE_KEY ).getReport(
			'programs',
			type,
			getDateParamsFromQuery( query ),
			dateReference
		);

		if ( report ) {
			return report.totals;
		}
		return report;
	}
);
