/**
 * External dependencies
 */
import { setWith, clone } from 'lodash';

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

// Referenced and modified from https://github.com/lodash/lodash/issues/1696#issuecomment-328335502
function chain( state, basePath = '' ) {
	const nextState = clone( state );
	const customizer = ( value ) => {
		if ( value === null || value === undefined ) {
			return {};
		}
		return clone( value );
	};

	return {
		set( path, value ) {
			const fullPath = basePath ? `${ basePath }.${ path }` : path;
			setWith( nextState, fullPath, value, customizer );
			return this;
		},
		end: () => nextState,
	};
}

function set( state, path, value ) {
	return chain( state ).set( path, value ).end();
}

const reducer = ( state = DEFAULT_STATE, action ) => {
	switch ( action.type ) {
		case TYPES.RECEIVE_SHIPPING_RATES: {
			return set( state, 'mc.shipping.rates', action.shippingRates );
		}

		case TYPES.UPSERT_SHIPPING_RATES: {
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

			return set( state, 'mc.shipping.rates', rates );
		}

		case TYPES.DELETE_SHIPPING_RATES: {
			const countryCodeSet = new Set( action.countryCodes );
			const rates = state.mc.shipping.rates.filter(
				( el ) => ! countryCodeSet.has( el.countryCode )
			);

			return set( state, 'mc.shipping.rates', rates );
		}

		case TYPES.RECEIVE_SHIPPING_TIMES: {
			return set( state, 'mc.shipping.times', action.shippingTimes );
		}

		case TYPES.UPSERT_SHIPPING_TIMES: {
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

			return set( state, 'mc.shipping.times', times );
		}

		case TYPES.DELETE_SHIPPING_TIMES: {
			const countryCodeSet = new Set( action.countryCodes );
			const times = state.mc.shipping.times.filter(
				( el ) => ! countryCodeSet.has( el.countryCode )
			);

			return set( state, 'mc.shipping.times', times );
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
			return set( state, 'mc.accounts.jetpack', action.account );
		}

		case TYPES.RECEIVE_ACCOUNTS_GOOGLE: {
			return set( state, 'mc.accounts.google', action.account );
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
			return set( state, 'mc.accounts.mc', action.account );
		}

		case TYPES.RECEIVE_ACCOUNTS_GOOGLE_MC_EXISTING: {
			return set( state, 'mc.accounts.existing_mc', action.accounts );
		}

		case TYPES.RECEIVE_ACCOUNTS_GOOGLE_ADS: {
			return set( state, 'mc.accounts.ads', action.account );
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
			const path = 'mc.accounts.ads_billing_status';
			return set( state, path, action.billingStatus );
		}

		case TYPES.RECEIVE_ACCOUNTS_GOOGLE_ADS_EXISTING: {
			return set( state, 'mc.accounts.existing_ads', action.accounts );
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
			return set( state, 'mc.countries', action.countries );
		}

		case TYPES.RECEIVE_TARGET_AUDIENCE:
		case TYPES.SAVE_TARGET_AUDIENCE: {
			return set( state, 'mc.target_audience', action.target_audience );
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
