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
	clearError,
	receiveError,
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
} from './actions';

export function* getShippingRates() {
	yield clearError( 'getShippingRates' );

	// Move the implementation to here due to:
	//   1. Make it easier to maintain consistency in the selector names
	//      that `clearError` and `receiveError` need to pass in
	//   2. Enclosure the API request here instead of exposing a fetchShippingRates action,
	//      and it also makes sure we use `invalidateResolution` or `shouldInvalidate`
	//      when need to re-fetch data
	//   3. The only job in this original function is `yield fetchShippingRates();`.
	//      I think we can dispense with the need to declare another function
	try {
		const response = yield apiFetch( {
			path: `${ API_NAMESPACE }/mc/shipping/rates`,
		} );

		const shippingRates = Object.values( response ).map( ( el ) => {
			return {
				countryCode: el.country_code,
				currency: el.currency,
				rate: el.rate.toString(),
			};
		} );

		return {
			type: TYPES.RECEIVE_SHIPPING_RATES,
			shippingRates,
		};
	} catch ( error ) {
		return receiveError( error, 'getShippingRates' );
	}
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
