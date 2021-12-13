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
			return set( state, 'mc.settings', action.settings );
		}

		case TYPES.RECEIVE_ACCOUNTS_JETPACK: {
			return set( state, 'mc.accounts.jetpack', action.account );
		}

		case TYPES.RECEIVE_ACCOUNTS_GOOGLE: {
			return set( state, 'mc.accounts.google', action.account );
		}

		case TYPES.RECEIVE_ACCOUNTS_GOOGLE_ACCESS: {
			return set( state, 'mc.accounts.google_access', action.data );
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
			return set(
				state,
				'mc.accounts.ads',
				DEFAULT_STATE.mc.accounts.ads
			);
		}

		case TYPES.RECEIVE_ACCOUNTS_GOOGLE_ADS_BILLING_STATUS: {
			return set(
				state,
				'mc.accounts.ads_billing_status',
				action.billingStatus
			);
		}

		case TYPES.RECEIVE_ACCOUNTS_GOOGLE_ADS_EXISTING: {
			return set( state, 'mc.accounts.existing_ads', action.accounts );
		}

		case TYPES.RECEIVE_MC_CONTACT_INFORMATION: {
			return set( state, 'mc.contact', action.data );
		}

		case TYPES.RECEIVE_COUNTRIES: {
			return set( state, 'mc.countries', action.countries );
		}

		case TYPES.RECEIVE_TARGET_AUDIENCE:
		case TYPES.SAVE_TARGET_AUDIENCE: {
			return set( state, 'mc.target_audience', action.target_audience );
		}

		case TYPES.RECEIVE_ADS_CAMPAIGNS: {
			return set( state, 'ads_campaigns', action.adsCampaigns );
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

			return set( state, 'ads_campaigns', newAdsCampaigns );
		}

		case TYPES.DELETE_ADS_CAMPAIGN: {
			const { id } = action;
			const adsCampaign = state.ads_campaigns.filter(
				( el ) => el.id !== id
			);
			return set( state, 'ads_campaigns', adsCampaign );
		}

		case TYPES.RECEIVE_MC_SETUP: {
			return set( state, 'mc_setup', action.mcSetup );
		}

		case TYPES.RECEIVE_MC_PRODUCT_STATISTICS: {
			return set(
				state,
				'mc_product_statistics',
				action.mcProductStatistics
			);
		}

		case TYPES.RECEIVE_MC_ISSUES: {
			const { query, data } = action;
			const issues = state.mc_issues?.issues?.slice() || [];
			issues.splice(
				( query.page - 1 ) * query.per_page,
				query.per_page,
				...data.issues
			);

			return chain( state, 'mc_issues' )
				.set( 'issues', issues )
				.set( 'total', data.total )
				.end();
		}

		case TYPES.RECEIVE_MC_PRODUCT_FEED: {
			const { query, data } = action;
			const lastQuery = state.mc_product_feed || {};
			const stateSetter = chain( state, 'mc_product_feed' );

			if (
				lastQuery.per_page !== query.per_page ||
				lastQuery.order !== query.order ||
				lastQuery.orderby !== query.orderby
			) {
				// discard old stored data when pagination has changed.
				stateSetter.set( 'pages', {} );
			}

			return stateSetter
				.set( `pages.[${ query.page }]`, data.products )
				.set( 'per_page', query.per_page )
				.set( 'order', query.order )
				.set( 'orderby', query.orderby )
				.set( 'total', data.total )
				.end();
		}

		case TYPES.RECEIVE_REPORT: {
			const { reportKey, data } = action;
			return set( state, `report.${ reportKey }`, data );
		}

		// Page will be reloaded after all accounts have been disconnected, so no need to mutate state.
		case TYPES.DISCONNECT_ACCOUNTS_ALL:
		default:
			return state;
	}
};

export default reducer;
