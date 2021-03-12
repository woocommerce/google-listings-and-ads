/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';
import { dispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import TYPES from './action-types';
import { API_NAMESPACE } from './constants';

export function handleFetchError( error, message ) {
	const { createNotice } = dispatch( 'core/notices' );
	createNotice( 'error', message );

	// eslint-disable-next-line no-console
	console.log( error );
}

export function* fetchShippingRates() {
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
		yield handleFetchError(
			error,
			__(
				'There was an error loading shipping rates.',
				'google-listings-and-ads'
			)
		);
	}
}

export function* upsertShippingRate( shippingRate ) {
	const { countryCode, currency, rate } = shippingRate;

	try {
		yield apiFetch( {
			path: `${ API_NAMESPACE }/mc/shipping/rates`,
			method: 'POST',
			data: {
				country_code: countryCode,
				currency,
				rate,
			},
		} );

		return {
			type: TYPES.UPSERT_SHIPPING_RATE,
			shippingRate,
		};
	} catch ( error ) {
		yield handleFetchError(
			error,
			__(
				'There was an error trying to add / update shipping rate.',
				'google-listings-and-ads'
			)
		);
	}
}

export function* deleteShippingRate( countryCode ) {
	try {
		yield apiFetch( {
			path: `${ API_NAMESPACE }/mc/shipping/rates/${ countryCode }`,
			method: 'DELETE',
		} );

		return {
			type: TYPES.DELETE_SHIPPING_RATE,
			countryCode,
		};
	} catch ( error ) {
		yield handleFetchError(
			error,
			__(
				'There was an error trying to delete shipping rate.',
				'google-listings-and-ads'
			)
		);
	}
}

export function* fetchShippingTimes() {
	try {
		const response = yield apiFetch( {
			path: `${ API_NAMESPACE }/mc/shipping/times`,
		} );

		const shippingTimes = Object.values( response ).map( ( el ) => {
			return {
				countryCode: el.country_code,
				time: el.time,
			};
		} );

		return {
			type: TYPES.RECEIVE_SHIPPING_TIMES,
			shippingTimes,
		};
	} catch ( error ) {
		yield handleFetchError(
			error,
			__(
				'There was an error loading shipping times.',
				'google-listings-and-ads'
			)
		);
	}
}

export function* upsertShippingTime( shippingTime ) {
	const { countryCode, time } = shippingTime;

	try {
		yield apiFetch( {
			path: `${ API_NAMESPACE }/mc/shipping/times`,
			method: 'POST',
			data: {
				country_code: countryCode,
				time,
			},
		} );

		return {
			type: TYPES.UPSERT_SHIPPING_TIME,
			shippingTime,
		};
	} catch ( error ) {
		yield handleFetchError(
			error,
			__(
				'There was an error trying to add / update shipping time.',
				'google-listings-and-ads'
			)
		);
	}
}

export function* deleteShippingTime( countryCode ) {
	try {
		yield apiFetch( {
			path: `${ API_NAMESPACE }/mc/shipping/times/${ countryCode }`,
			method: 'DELETE',
		} );

		return {
			type: TYPES.DELETE_SHIPPING_TIME,
			countryCode,
		};
	} catch ( error ) {
		yield handleFetchError(
			error,
			__(
				'There was an error trying to delete shipping time.',
				'google-listings-and-ads'
			)
		);
	}
}

export function* fetchSettings() {
	try {
		const response = yield apiFetch( {
			path: `${ API_NAMESPACE }/mc/settings`,
		} );

		return {
			type: TYPES.RECEIVE_SETTINGS,
			settings: response,
		};
	} catch ( error ) {
		yield handleFetchError(
			error,
			__(
				'There was an error loading merchant center settings.',
				'google-listings-and-ads'
			)
		);
	}
}

export function* saveSettings( settings ) {
	try {
		yield apiFetch( {
			path: `${ API_NAMESPACE }/mc/settings`,
			method: 'POST',
			data: settings,
		} );

		return {
			type: TYPES.SAVE_SETTINGS,
			settings,
		};
	} catch ( error ) {
		yield handleFetchError(
			error,
			__(
				'There was an error trying to save settings.',
				'google-listings-and-ads'
			)
		);
	}
}

export function* fetchJetpackAccount() {
	try {
		const response = yield apiFetch( {
			path: `${ API_NAMESPACE }/jetpack/connected`,
		} );

		return {
			type: TYPES.RECEIVE_ACCOUNTS_JETPACK,
			account: response,
		};
	} catch ( error ) {
		yield handleFetchError(
			error,
			__(
				'There was an error loading Jetpack account info.',
				'google-listings-and-ads'
			)
		);
	}
}

export function* fetchGoogleAccount() {
	try {
		const response = yield apiFetch( {
			path: `${ API_NAMESPACE }/google/connected`,
		} );

		return {
			type: TYPES.RECEIVE_ACCOUNTS_GOOGLE,
			account: response,
		};
	} catch ( error ) {
		yield handleFetchError(
			error,
			__(
				'There was an error loading Google account info.',
				'google-listings-and-ads'
			)
		);
	}
}

export function* fetchGoogleMCAccount() {
	try {
		const response = yield apiFetch( {
			path: `${ API_NAMESPACE }/mc/connection`,
		} );

		return {
			type: TYPES.RECEIVE_ACCOUNTS_GOOGLE_MC,
			account: response,
		};
	} catch ( error ) {
		yield handleFetchError(
			error,
			__(
				'There was an error loading Google Merchant Center account info.',
				'google-listings-and-ads'
			)
		);
	}
}

export function* fetchExistingGoogleMCAccounts() {
	try {
		const response = yield apiFetch( {
			path: `${ API_NAMESPACE }/mc/accounts`,
		} );

		return {
			type: TYPES.RECEIVE_ACCOUNTS_GOOGLE_MC_EXISTING,
			accounts: response,
		};
	} catch ( error ) {
		yield handleFetchError(
			error,
			__(
				'There was an error getting your Google Merchant Center accounts.',
				'google-listings-and-ads'
			)
		);
	}
}

export function* fetchGoogleAdsAccount() {
	try {
		const response = yield apiFetch( {
			path: `${ API_NAMESPACE }/ads/connection`,
		} );

		return {
			type: TYPES.RECEIVE_ACCOUNTS_GOOGLE_ADS,
			account: response,
		};
	} catch ( error ) {
		yield handleFetchError(
			error,
			__(
				'There was an error loading Google Ads account info.',
				'google-listings-and-ads'
			)
		);
	}
}

export function* fetchGoogleAdsAccountBillingStatus() {
	try {
		const response = yield apiFetch( {
			path: `${ API_NAMESPACE }/ads/billing-status`,
		} );

		return {
			type: TYPES.RECEIVE_ACCOUNTS_GOOGLE_ADS_BILLING_STATUS,
			billingStatus: response,
		};
	} catch ( error ) {
		yield handleFetchError(
			error,
			__(
				'There was an error loading shipping rates.',
				'google-listings-and-ads'
			)
		);
	}
}

export function* fetchExistingGoogleAdsAccounts() {
	try {
		const response = yield apiFetch( {
			path: `${ API_NAMESPACE }/ads/accounts`,
		} );

		return {
			type: TYPES.RECEIVE_ACCOUNTS_GOOGLE_ADS_EXISTING,
			accounts: response,
		};
	} catch ( error ) {
		yield handleFetchError(
			error,
			__(
				'There was an error getting your Google Ads accounts.',
				'google-listings-and-ads'
			)
		);
	}
}

export function* fetchCountries() {
	try {
		const response = yield apiFetch( {
			path: `${ API_NAMESPACE }/mc/countries`,
		} );

		return {
			type: TYPES.RECEIVE_COUNTRIES,
			countries: response,
		};
	} catch ( error ) {
		yield handleFetchError(
			error,
			__(
				'There was an error loading supported country details.',
				'google-listings-and-ads'
			)
		);
	}
}

export function* fetchTargetAudience() {
	try {
		const response = yield apiFetch( {
			path: `${ API_NAMESPACE }/mc/target_audience`,
		} );

		return {
			type: TYPES.RECEIVE_TARGET_AUDIENCE,
			target_audience: response,
		};
	} catch ( error ) {
		yield handleFetchError(
			error,
			__(
				'There was an error loading target audience.',
				'google-listings-and-ads'
			)
		);
	}
}

export function receiveMCAccount( account ) {
	return {
		type: TYPES.RECEIVE_ACCOUNTS_GOOGLE_MC,
		account,
	};
}

export function receiveAdsAccount( account ) {
	return {
		type: TYPES.RECEIVE_ACCOUNTS_GOOGLE_ADS,
		account,
	};
}

export function* saveTargetAudience( targetAudience ) {
	try {
		yield apiFetch( {
			path: `${ API_NAMESPACE }/mc/target_audience`,
			method: 'POST',
			data: targetAudience,
		} );

		return {
			type: TYPES.SAVE_TARGET_AUDIENCE,
			target_audience: targetAudience,
		};
	} catch ( error ) {
		yield handleFetchError(
			error,
			__(
				'There was an error saving target audience data.',
				'google-listings-and-ads'
			)
		);
	}
}
