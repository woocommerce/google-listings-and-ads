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
		audience: {
			location: {
				option: 'selected',
				selected: [ 'AU' ],
			},
		},
		shipping: {
			rates: [],
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

		case TYPES.DELETE_SHIPPING_RATE: {
			const { rate } = action;
			const newState = cloneDeep( state );
			newState.mc.shipping.rates = newState.mc.shipping.rates.filter(
				( el ) => el.country !== rate.country
			);
			return newState;
		}

		default:
			return state;
	}
};

export default reducer;
