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
			times: [],
		},
		settings: null,
		accounts: {
			jetpack: null,
			google: null,
			mc: null,
			ads: null,
			existing_mc: null,
			existing_ads: null,
			ads_billing_status: null,
		},
	},
	ads_campaigns: null,
};

const getNextStateForShipping = ( state ) => {
	return {
		...state,
		mc: {
			...state.mc,
			shipping: {
				...state.mc.shipping,
			},
		},
	};
};

const reducer = ( state = DEFAULT_STATE, action ) => {
	switch ( action.type ) {
		case TYPES.RECEIVE_SHIPPING_RATES: {
			const { shippingRates } = action;
			const newState = getNextStateForShipping( state );
			newState.mc.shipping.rates = shippingRates;
			return newState;
		}

		case TYPES.UPSERT_SHIPPING_RATES: {
			const nextState = getNextStateForShipping( state );
			const { countryCodes, currency, rate } = action.shippingRate;
			const rates = [ ...state.mc.shipping.rates ];

			countryCodes.forEach( ( countryCode ) => {
				const shippingRate = { countryCode, currency, rate };
				const idx = rates.findIndex(
					( el ) => el.countryCode === countryCode
				);

				if ( idx >= 0 ) {
					rates[ idx ] = shippingRate;
				} else {
					rates.push( shippingRate );
				}
			} );

			nextState.mc.shipping.rates = rates;
			return nextState;
		}

		case TYPES.DELETE_SHIPPING_RATES: {
			const nextState = getNextStateForShipping( state );
			const countryCodeSet = new Set( action.countryCodes );
			const rates = state.mc.shipping.rates.filter(
				( el ) => ! countryCodeSet.has( el.countryCode )
			);

			nextState.mc.shipping.rates = rates;
			return nextState;
		}

		case TYPES.RECEIVE_SHIPPING_TIMES: {
			const { shippingTimes } = action;
			const newState = getNextStateForShipping( state );
			newState.mc.shipping.times = shippingTimes;
			return newState;
		}

		case TYPES.UPSERT_SHIPPING_TIMES: {
			const nextState = getNextStateForShipping( state );
			const { countryCodes, time } = action.shippingTime;
			const times = [ ...state.mc.shipping.times ];

			countryCodes.forEach( ( countryCode ) => {
				const shippingTime = { countryCode, time };
				const idx = times.findIndex(
					( el ) => el.countryCode === countryCode
				);

				if ( idx >= 0 ) {
					times[ idx ] = shippingTime;
				} else {
					times.push( shippingTime );
				}
			} );

			nextState.mc.shipping.times = times;
			return nextState;
		}

		case TYPES.DELETE_SHIPPING_TIMES: {
			const nextState = getNextStateForShipping( state );
			const countryCodeSet = new Set( action.countryCodes );
			const times = state.mc.shipping.times.filter(
				( el ) => ! countryCodeSet.has( el.countryCode )
			);

			nextState.mc.shipping.times = times;
			return nextState;
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

		case TYPES.RECEIVE_ACCOUNTS_GOOGLE_MC: {
			const { account } = action;
			const newState = cloneDeep( state );
			newState.mc.accounts.mc = account;
			return newState;
		}

		case TYPES.RECEIVE_ACCOUNTS_GOOGLE_MC_EXISTING: {
			const { accounts } = action;
			const newState = cloneDeep( state );
			newState.mc.accounts.existing_mc = accounts;
			return newState;
		}

		case TYPES.RECEIVE_ACCOUNTS_GOOGLE_ADS: {
			const { account } = action;
			const newState = cloneDeep( state );
			newState.mc.accounts.ads = account;
			return newState;
		}

		case TYPES.DISCONNECT_ACCOUNTS_GOOGLE_ADS: {
			return {
				...state,
				mc: {
					...state.mc,
					accounts: {
						...state.mc.accounts,
						ads: DEFAULT_STATE.mc.accounts.ads,
					},
				},
			};
		}

		case TYPES.RECEIVE_ACCOUNTS_GOOGLE_ADS_BILLING_STATUS: {
			const { billingStatus } = action;
			const newState = cloneDeep( state );
			newState.mc.accounts.ads_billing_status = billingStatus;
			return newState;
		}

		case TYPES.RECEIVE_ACCOUNTS_GOOGLE_ADS_EXISTING: {
			const { accounts } = action;
			const newState = cloneDeep( state );
			newState.mc.accounts.existing_ads = accounts;
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

		case TYPES.RECEIVE_ADS_CAMPAIGNS: {
			const newState = {
				...state,
				ads_campaigns: action.adsCampaigns,
			};
			return newState;
		}

		// Page will be reloaded after all accounts have been disconnected, so no need to mutate state.
		case TYPES.DISCONNECT_ACCOUNTS_ALL:
		default:
			return state;
	}
};

export default reducer;
