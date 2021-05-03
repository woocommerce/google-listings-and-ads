/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';
import { addQueryArgs } from '@wordpress/url';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import TYPES from './action-types';
import { API_NAMESPACE } from './constants';
import { getReportQuery, getReportKey } from './utils';

import {
	handleFetchError,
	fetchShippingRates,
	fetchShippingTimes,
	fetchSettings,
	fetchJetpackAccount,
	fetchGoogleAccount,
	fetchGoogleMCAccount,
	fetchExistingGoogleMCAccounts,
	fetchGoogleAdsAccount,
	fetchGoogleAdsAccountBillingStatus,
	fetchExistingGoogleAdsAccounts,
	fetchCountries,
	fetchTargetAudience,
	fetchAdsCampaigns,
	fetchMCSetup,
	receiveReport,
	receiveMCProductStatistics,
	receiveMCIssues,
} from './actions';

export function* getShippingRates() {
	yield fetchShippingRates();
}

export function* getShippingTimes() {
	yield fetchShippingTimes();
}

export function* getSettings() {
	yield fetchSettings();
}

export function* getJetpackAccount() {
	yield fetchJetpackAccount();
}

export function* getGoogleAccount() {
	yield fetchGoogleAccount();
}

export function* getGoogleMCAccount() {
	yield fetchGoogleMCAccount();
}

export function* getExistingGoogleMCAccounts() {
	yield fetchExistingGoogleMCAccounts();
}

export function* getGoogleAdsAccount() {
	yield fetchGoogleAdsAccount();
}

getGoogleAdsAccount.shouldInvalidate = ( action ) => {
	return action.type === TYPES.DISCONNECT_ACCOUNTS_GOOGLE_ADS;
};

export function* getGoogleAdsAccountBillingStatus() {
	yield fetchGoogleAdsAccountBillingStatus();
}

export function* getExistingGoogleAdsAccounts() {
	yield fetchExistingGoogleAdsAccounts();
}

export function* getCountries() {
	yield fetchCountries();
}

export function* getTargetAudience() {
	yield fetchTargetAudience();
}

export function* getAdsCampaigns() {
	yield fetchAdsCampaigns();
}

export function* getMCSetup() {
	yield fetchMCSetup();
}

export function* getMCProductStatistics() {
	try {
		const response = yield apiFetch( {
			path: `${ API_NAMESPACE }/mc/product-statistics`,
		} );

		yield receiveMCProductStatistics( response );
	} catch ( error ) {
		yield handleFetchError(
			error,
			__(
				'There was an error loading your merchant center product statistics.',
				'google-listings-and-ads'
			)
		);
	}
}

export function* getMCIssues( query ) {
	try {
		const response = yield apiFetch( {
			path: addQueryArgs( `${ API_NAMESPACE }/mc/issues`, query ),
		} );

		yield receiveMCIssues( query, response );
	} catch ( error ) {
		yield handleFetchError(
			error,
			__(
				'There was an error loading issues to resolve.',
				'google-listings-and-ads'
			)
		);
	}
}

const reportTypeMap = new Map( [
	[ 'free', 'mc' ],
	[ 'paid', 'ads' ],
] );

export function* getReport( category, type, query, dateReference ) {
	const reportQuery = getReportQuery( query, dateReference );
	const reportType = reportTypeMap.get( type );
	const url = `${ API_NAMESPACE }/${ reportType }/reports/${ category }`;
	const path = addQueryArgs( url, reportQuery );

	try {
		const data = yield apiFetch( { path } );
		const reportKey = getReportKey( category, type, reportQuery );
		yield receiveReport( reportKey, data );
	} catch ( error ) {
		yield handleFetchError(
			error,
			__(
				'There was an error loading report.',
				'google-listings-and-ads'
			)
		);
	}
}
