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
			google_access: null,
		},
		contact: null,
	},
	ads_campaigns: null,
	mc_setup: null,
	mc_product_statistics: null,
	mc_issues: null,
	mc_product_feed: null,
	report: {},
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
			return {
				...state,
				mc: {
					...state.mc,
					settings: {
						...state.mc.settings,
						...action.settings,
					},
				},
			};
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

		case TYPES.RECEIVE_ACCOUNTS_GOOGLE_ACCESS: {
			return {
				...state,
				mc: {
					...state.mc,
					accounts: {
						...state.mc.accounts,
						google_access: action.data,
					},
				},
			};
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

		case TYPES.RECEIVE_MC_CONTACT_INFORMATION: {
			const newState = {
				...state,
				mc: {
					...state.mc,
					contact: action.data,
				},
			};
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

		case TYPES.UPDATE_ADS_CAMPAIGN: {
			const { id, data } = action;
			const idx = state.ads_campaigns.findIndex( ( el ) => el.id === id );
			const adsCampaign = state.ads_campaigns[ idx ];

			const updatedCampaign = {
				...adsCampaign,
				...data,
			};

			const newAdsCampaigns = [ ...state.ads_campaigns ];
			newAdsCampaigns[ idx ] = updatedCampaign;

			const newState = {
				...state,
				ads_campaigns: newAdsCampaigns,
			};
			return newState;
		}

		case TYPES.DELETE_ADS_CAMPAIGN: {
			const { id } = action;

			return {
				...state,
				ads_campaigns: state.ads_campaigns.filter(
					( el ) => el.id !== id
				),
			};
		}

		case TYPES.RECEIVE_MC_SETUP: {
			const newState = {
				...state,
				mc_setup: action.mcSetup,
			};
			return newState;
		}

		case TYPES.RECEIVE_MC_PRODUCT_STATISTICS: {
			const newState = {
				...state,
				mc_product_statistics: action.mcProductStatistics,
			};
			return newState;
		}

		case TYPES.RECEIVE_MC_ISSUES: {
			const { query, data } = action;
			const newState = {
				...state,
				mc_issues: {
					...state.mc_issues,
					issues:
						( state.mc_issues?.issues && [
							...state.mc_issues.issues,
						] ) ||
						[],
				},
			};

			newState.mc_issues.issues.splice(
				( query.page - 1 ) * query.per_page,
				query.per_page,
				...data.issues
			);
			newState.mc_issues.total = data.total;

			return newState;
		}

		case TYPES.RECEIVE_MC_PRODUCT_FEED: {
			const { query, data } = action;
			const newState = {
				...state,
				mc_product_feed: {
					...state.mc_product_feed,
					pages: {
						...state.mc_product_feed?.pages,
					},
				},
			};

			if (
				newState.mc_product_feed.per_page !== query.per_page ||
				newState.mc_product_feed.order !== query.order ||
				newState.mc_product_feed.orderby !== query.orderby
			) {
				// discard old stored data when pagination has changed.
				newState.mc_product_feed.pages = {};
			}

			newState.mc_product_feed.per_page = query.per_page;
			newState.mc_product_feed.order = query.order;
			newState.mc_product_feed.orderby = query.orderby;
			newState.mc_product_feed.total = data.total;
			newState.mc_product_feed.pages[ query.page ] = data.products;

			return newState;
		}

		case TYPES.RECEIVE_REPORT: {
			const { reportKey, data } = action;
			return {
				...state,
				report: {
					...state.report,
					[ reportKey ]: data,
				},
			};
		}

		// Page will be reloaded after all accounts have been disconnected, so no need to mutate state.
		case TYPES.DISCONNECT_ACCOUNTS_ALL:
		default:
			return state;
	}
};

export default reducer;
