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

export const setAudienceSelectedCountryCodes = ( selected ) => {
	return {
		type: TYPES.SET_AUDIENCE_SELECTED_COUNTRIES,
		selected,
	};
};

export function* fetchShippingRates() {
	try {
		const response = yield apiFetch( {
			path: `${ API_NAMESPACE }/mc/shipping/rates`,
		} );

		if ( ! response ) {
			throw new Error();
		}

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

export function* addShippingRate( shippingRate ) {
	const { countryCode, currency, rate } = shippingRate;

	try {
		const response = yield apiFetch( {
			path: `${ API_NAMESPACE }/mc/shipping/rates`,
			method: 'POST',
			data: {
				country_code: countryCode,
				currency,
				rate,
			},
		} );

		if ( ! response ) {
			throw new Error();
		}

		return {
			type: TYPES.ADD_SHIPPING_RATE,
			shippingRate,
		};
	} catch ( error ) {
		yield handleFetchError(
			error,
			__(
				'There was an error trying to add new shipping rate.',
				'google-listings-and-ads'
			)
		);
	}
}

export function* updateShippingRate( shippingRate ) {
	const { countryCode, currency, rate } = shippingRate;

	try {
		const response = yield apiFetch( {
			path: `${ API_NAMESPACE }/mc/shipping/rates`,
			method: 'POST',
			data: {
				country_code: countryCode,
				currency,
				rate,
			},
		} );

		if ( ! response ) {
			throw new Error();
		}

		return {
			type: TYPES.UPDATE_SHIPPING_RATE,
			shippingRate,
		};
	} catch ( error ) {
		yield handleFetchError(
			error,
			__(
				'There was an error trying to update shipping rate.',
				'google-listings-and-ads'
			)
		);
	}
}

export function* deleteShippingRate( countryCode ) {
	try {
		const response = yield apiFetch( {
			path: `${ API_NAMESPACE }/mc/shipping/rates/${ countryCode }`,
			method: 'DELETE',
		} );

		if ( ! response ) {
			throw new Error();
		}

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

export function* fetchSettings() {
	try {
		const response = yield apiFetch( {
			path: `${ API_NAMESPACE }/mc/settings`,
		} );

		if ( ! response ) {
			throw new Error();
		}

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
		const response = yield apiFetch( {
			path: `${ API_NAMESPACE }/mc/settings`,
			method: 'POST',
			data: settings,
		} );

		if ( ! response ) {
			throw new Error();
		}

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

		if ( ! response ) {
			throw new Error();
		}

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

		if ( ! response ) {
			throw new Error();
		}

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

		if ( ! response ) {
			throw new Error();
		}

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

		if ( ! response ) {
			throw new Error();
		}

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

export function* createMCAccount() {
	try {
		const response = yield apiFetch( {
			path: `${ API_NAMESPACE }/mc/accounts`,
			method: 'POST',
		} );

		if ( ! response ) {
			throw new Error();
		}

		return {
			type: TYPES.RECEIVE_ACCOUNTS_GOOGLE_MC,
			account: response,
		};
	} catch ( error ) {
		yield handleFetchError(
			error,
			__(
				'There was an error trying to create a Merchant Center account.',
				'google-listings-and-ads'
			)
		);
	}
}

export function* linkMCAccount( id ) {
	try {
		const response = yield apiFetch( {
			path: `${ API_NAMESPACE }/mc/accounts`,
			method: 'POST',
			data: { id },
		} );

		if ( ! response ) {
			throw new Error();
		}

		return {
			type: TYPES.RECEIVE_ACCOUNTS_GOOGLE_MC,
			account: response,
		};
	} catch ( error ) {
		yield handleFetchError(
			error,
			__(
				'There was an error trying to link your Merchant Center account.',
				'google-listings-and-ads'
			)
		);
	}
}
