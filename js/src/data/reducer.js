/**
 * External dependencies
 */
import { cloneDeep } from 'lodash';

/**
 * Internal dependencies
 */
import TYPES from './action-types';

const DEFAULT_STATE = {
	mc: {
		target_audience: null,
		countries: null,
		shipping: {
			rates: [],
		},
		settings: null,
	},
};

const reducer = ( state = DEFAULT_STATE, action ) => {
	switch ( action.type ) {
		case TYPES.RECEIVE_SHIPPING_RATES: {
			const { shippingRates } = action;
			const newState = cloneDeep( state );
			newState.mc.shipping.rates = shippingRates;
			return newState;
		}

		case TYPES.ADD_SHIPPING_RATE: {
			const { shippingRate } = action;
			const newState = cloneDeep( state );
			newState.mc.shipping.rates.push( shippingRate );
			return newState;
		}

		case TYPES.UPDATE_SHIPPING_RATE: {
			const { shippingRate } = action;
			const newState = cloneDeep( state );
			const idx = newState.mc.shipping.rates.findIndex(
				( el ) => el.countryCode === shippingRate.countryCode
			);

			if ( idx >= 0 ) {
				newState.mc.shipping.rates[ idx ] = shippingRate;
			} else {
				newState.mc.shipping.rates.push( shippingRate );
			}

			return newState;
		}

		case TYPES.DELETE_SHIPPING_RATE: {
			const { countryCode } = action;
			const newState = cloneDeep( state );
			newState.mc.shipping.rates = newState.mc.shipping.rates.filter(
				( el ) => el.countryCode !== countryCode
			);
			return newState;
		}

		case TYPES.RECEIVE_SETTINGS:
		case TYPES.SAVE_SETTINGS: {
			const { settings } = action;
			const newState = cloneDeep( state );
			newState.mc.settings = settings;
			return newState;
		}

		case TYPES.RECEIVE_COUNTRIES: {
			const { countries } = action;
			const newState = cloneDeep( state );
			newState.mc.countries = countries;
			return newState;
		}

		case TYPES.RECEIVE_TARGET_AUDIENCE:
		case TYPES.SAVE_TARGET_AUDIENCE: {
			const newState = cloneDeep( state );
			newState.mc.target_audience = action.target_audience;
			return newState;
		}

		default:
			return state;
	}
};

export default reducer;
