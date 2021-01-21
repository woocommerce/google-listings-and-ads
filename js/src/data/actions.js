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

// TODO: call API to delete shipping rate.
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
