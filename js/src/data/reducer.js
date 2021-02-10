/**
 * External dependencies
 */
import { cloneDeep } from 'lodash';

/**
 * Internal dependencies
 */
import TYPES from './action-types';

// TODO: initial data should come from resolvers.
const DEFAULT_STATE = {
	mc: {
		audience: {
			location: {
				option: 'selected',
				selected: [ 'AU', 'CN', 'US' ],
			},
		},
		shipping: {
			rates: [],
		},
		settings: null,
		accounts: {
			jetpack: null,
			google: null,
			mc: null,
		},
	},
};

const reducer = ( state = DEFAULT_STATE, action ) => {
	switch ( action.type ) {
		case TYPES.SET_AUDIENCE_SELECTED_COUNTRIES: {
			const newState = cloneDeep( state );
			newState.mc.audience.location.selected = action.selected;
			return newState;
		}

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

		case TYPES.RECEIVE_ACCOUNTS_JETPACK: {
			const { account } = action;
			const newState = cloneDeep( state );
			newState.mc.accounts.jetpack = account;
			return newState;
		}

		case TYPES.RECEIVE_ACCOUNTS_GOOGLE: {
			const { account } = action;
			const newState = cloneDeep( state );
			newState.mc.accounts.google = account;
			return newState;
		}

		default:
			return state;
	}
};

export default reducer;
