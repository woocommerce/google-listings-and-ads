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

export const setAudienceSelectedCountries = ( selected ) => {
	return {
		type: TYPES.SET_AUDIENCE_SELECTED_COUNTRIES,
		selected,
	};
};

export function receiveShippingRates( shippingRates ) {
	return {
		type: TYPES.RECEIVE_SHIPPING_RATES,
		shippingRates,
	};
}

export function* addShippingRate( shippingRate ) {
	const { country, currency, rate } = shippingRate;

	try {
		const response = yield apiFetch( {
			path: `${ API_NAMESPACE }/mc/shipping/rates`,
			method: 'POST',
			data: {
				country_code: country,
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
export function* deleteShippingRate( rate ) {
	return {
		type: TYPES.DELETE_SHIPPING_RATE,
		rate,
	};
}
