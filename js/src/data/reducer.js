/**
 * External dependencies
 */
import { setWith, clone } from 'lodash';

/**
 * Internal dependencies
 */
import TYPES from './action-types';

const DEFAULT_STATE = {
	general: {
		version: null,
		mcId: null,
		adsId: null,
	},
	mc: {
		target_audience: null,
		countries: null,
		policy_check: null,
		continents: null,
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
		mapping: {
			attributes: [],
			sources: {},
			rules: {
				items: [],
				total: null,
				pages: null,
			},
		},
	},
	ads_campaigns: null,
	all_ads_campaigns: null,
	campaign_asset_groups: {},
	mc_setup: null,
	mc_product_statistics: null,
	mc_issues: {
		account: null,
		product: null,
	},
	mc_review_request: {
		status: null,
		cooldown: null,
		issues: null,
		reviewEligibleRegions: [],
	},
	mc_product_feed: null,
	report: {},
	store_categories: [],
	tours: {},
	ads: {
		accountStatus: {
			hasAccess: null,
			inviteLink: null,
			step: null,
		},
	},
};

/**
 * @callback chainSet
 * @param {Array<string>|string} path The path of the property to set the new value.
 *   The `basePath`, which is called with `chainState`, would be contacted with `path` if it exists.
 *   Array `path` is used directly as each path properties without parsing.
 *   String `path` is parsed to an array of path properties by splitting '.' and property names enclosed in square brackets. Example: 'user.settings[darkMode].schedule'.
 * @param {*} value The value to set.
 * @return {ChainState} The instance.
 */

/**
 * @typedef {Object} ChainState
 * @property {chainSet} setIn A chainable function for setting value.
 * @property {()=>Object|Array} end Get back the updated new state after chaining calls.
 */

/**
 * A helper to chain multiple values setting into the same new state.
 *
 * Recursively creates a shallow copied new state from `state`,
 * and returns a chainable instance for setting values.
 * Objects are created for missing or `null` paths.
 *
 * Referenced and modified from https://github.com/lodash/lodash/issues/1696#issuecomment-328335502
 *
 * @param {Object|Array} state The state to create and set the new value.
 * @param {Array<string>|string} [basePath='']
 *   The base path to be contacted to the passed-in `path` when chaining calls.
 *   Use this when setting multiple values and don't want to repeat the base path multiple times.
 *
 * @return {ChainState} The chainable instance.
 */
function chainState( state, basePath = '' ) {
	const nextState = Object.assign( state.constructor(), state );
	const customizer = ( value ) => {
		if ( value === null || value === undefined ) {
			return {};
		}
		return clone( value );
	};
	// The `path` of lodash `setWith` can be either a string or an array.
	// Here combines `basePath` and `path` to the final path to be called with lodash `setWith`.
	const combineBasePath = ( path ) => {
		if ( basePath ) {
			if ( Array.isArray( basePath ) || Array.isArray( path ) ) {
				return [].concat( basePath, path );
			}
			return `${ basePath }.${ path }`;
		}
		return path;
	};

	return {
		setIn( path, value ) {
			const fullPath = combineBasePath( path );
			setWith( nextState, fullPath, value, customizer );
			return this;
		},
		end: () => nextState,
	};
}

/**
 * An immutable version of lodash `set` function with the same arguments.
 *
 * Recursively creates a shallow copied new state from `state`,
 * and sets the `value` at `path` of the new state.
 * Objects are created for missing or `null` paths.
 *
 * @param {Object|Array} state The state to create and set the new value.
 * @param {Array<string>|string} path The path of the property to set the new value.
 *   Array `path` is used directly as each path properties without parsing.
 *   String `path` is parsed to an array of path properties by splitting '.' and property names enclosed in square brackets. Example: 'user.settings[darkMode].schedule'.
 * @param {*} value The value to set.
 *
 * @return {Object|Array} The same type of passed-in `state` with placed `value` at `path` of the new state.
 */
function setIn( state, path, value ) {
	return chainState( state ).setIn( path, value ).end();
}

const reducer = ( state = DEFAULT_STATE, action ) => {
	switch ( action.type ) {
		case TYPES.RECEIVE_SHIPPING_RATES: {
			return setIn( state, 'mc.shipping.rates', action.shippingRates );
		}

		case TYPES.UPSERT_SHIPPING_RATES: {
			const { shippingRates: upsertedShippingRates } = action;
			const shippingRates = [ ...state.mc.shipping.rates ];

			upsertedShippingRates.forEach( ( upsertedShippingRate ) => {
				const idx = shippingRates.findIndex(
					( shippingRate ) =>
						shippingRate.id === upsertedShippingRate.id
				);

				if ( idx >= 0 ) {
					shippingRates[ idx ] = upsertedShippingRate;
				} else {
					shippingRates.push( upsertedShippingRate );
				}
			} );

			return setIn( state, 'mc.shipping.rates', shippingRates );
		}

		case TYPES.DELETE_SHIPPING_RATES: {
			const { ids } = action;
			const shippingRates = state.mc.shipping.rates.filter(
				( el ) => ! ids.includes( el.id )
			);

			return setIn( state, 'mc.shipping.rates', shippingRates );
		}

		case TYPES.RECEIVE_SHIPPING_TIMES: {
			return setIn( state, 'mc.shipping.times', action.shippingTimes );
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

			return setIn( state, 'mc.shipping.times', times );
		}

		case TYPES.DELETE_SHIPPING_TIMES: {
			const countryCodeSet = new Set( action.countryCodes );
			const times = state.mc.shipping.times.filter(
				( el ) => ! countryCodeSet.has( el.countryCode )
			);

			return setIn( state, 'mc.shipping.times', times );
		}

		case TYPES.RECEIVE_SETTINGS: {
			return setIn( state, 'mc.settings', action.settings );
		}

		case TYPES.SAVE_SETTINGS: {
			const nextSettings = {
				...state.mc.settings,
				...action.settings,
			};
			return setIn( state, 'mc.settings', nextSettings );
		}

		case TYPES.RECEIVE_ACCOUNTS_JETPACK: {
			return setIn( state, 'mc.accounts.jetpack', action.account );
		}

		case TYPES.RECEIVE_ACCOUNTS_GOOGLE: {
			return setIn( state, 'mc.accounts.google', action.account );
		}

		case TYPES.RECEIVE_ACCOUNTS_GOOGLE_ACCESS: {
			return setIn( state, 'mc.accounts.google_access', action.data );
		}

		case TYPES.RECEIVE_ACCOUNTS_GOOGLE_MC: {
			return setIn( state, 'mc.accounts.mc', action.account );
		}

		case TYPES.RECEIVE_ACCOUNTS_GOOGLE_MC_EXISTING: {
			return setIn( state, 'mc.accounts.existing_mc', action.accounts );
		}

		case TYPES.RECEIVE_ACCOUNTS_GOOGLE_ADS: {
			return setIn( state, 'mc.accounts.ads', action.account );
		}

		case TYPES.DISCONNECT_ACCOUNTS_GOOGLE_ADS: {
			return setIn(
				state,
				'mc.accounts.ads',
				DEFAULT_STATE.mc.accounts.ads
			);
		}

		case TYPES.RECEIVE_ACCOUNTS_GOOGLE_ADS_BILLING_STATUS: {
			return setIn(
				state,
				'mc.accounts.ads_billing_status',
				action.billingStatus
			);
		}

		case TYPES.RECEIVE_ACCOUNTS_GOOGLE_ADS_EXISTING: {
			return setIn( state, 'mc.accounts.existing_ads', action.accounts );
		}

		case TYPES.RECEIVE_MC_CONTACT_INFORMATION: {
			return setIn( state, 'mc.contact', action.data );
		}

		case TYPES.RECEIVE_MC_COUNTRIES_AND_CONTINENTS: {
			const { data } = action;
			return chainState( state, 'mc' )
				.setIn( 'countries', data.countries )
				.setIn( 'continents', data.continents )
				.end();
		}

		case TYPES.RECEIVE_TARGET_AUDIENCE:
		case TYPES.SAVE_TARGET_AUDIENCE: {
			return setIn( state, 'mc.target_audience', action.target_audience );
		}

		case TYPES.RECEIVE_ADS_CAMPAIGNS: {
			if ( action.query?.exclude_removed === false ) {
				return setIn( state, 'all_ads_campaigns', action.adsCampaigns );
			}
			return setIn( state, 'ads_campaigns', action.adsCampaigns );
		}

		case TYPES.CREATE_ADS_CAMPAIGN: {
			const adsCampaigns = [
				...( state.ads_campaigns || [] ),
				action.createdCampaign,
			];
			return setIn( state, 'ads_campaigns', adsCampaigns );
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

			return setIn( state, 'ads_campaigns', newAdsCampaigns );
		}

		case TYPES.DELETE_ADS_CAMPAIGN: {
			const { id } = action;
			const adsCampaign = state.ads_campaigns.filter(
				( el ) => el.id !== id
			);
			return setIn( state, 'ads_campaigns', adsCampaign );
		}

		case TYPES.RECEIVE_CAMPAIGN_ASSET_GROUPS: {
			return setIn(
				state,
				[ 'campaign_asset_groups', action.campaignId ],
				action.assetGroups
			);
		}

		case TYPES.CREATE_CAMPAIGN_ASSET_GROUP: {
			const { campaignId, assetGroup } = action;
			const assetGroups = [
				...( state.campaign_asset_groups[ campaignId ] || [] ),
				assetGroup,
			];

			return setIn(
				state,
				[ 'campaign_asset_groups', campaignId ],
				assetGroups
			);
		}

		case TYPES.RECEIVE_MC_SETUP: {
			return setIn( state, 'mc_setup', action.mcSetup );
		}

		case TYPES.RECEIVE_MC_PRODUCT_STATISTICS: {
			return setIn(
				state,
				'mc_product_statistics',
				action.mcProductStatistics
			);
		}

		case TYPES.RECEIVE_MC_REVIEW_REQUEST: {
			return setIn( state, 'mc_review_request', action.mcReviewRequest );
		}

		case TYPES.RECEIVE_MC_ISSUES: {
			const { query, data } = action;
			const issues =
				state.mc_issues[ query.issue_type ]?.issues.slice() || [];
			issues.splice(
				( query.page - 1 ) * query.per_page,
				query.per_page,
				...data.issues
			);

			return chainState( state, `mc_issues.${ query.issue_type }` )
				.setIn( 'issues', issues )
				.setIn( 'total', data.total )
				.end();
		}

		case TYPES.RECEIVE_MC_PRODUCT_FEED: {
			const { query, data } = action;
			const lastQuery = state.mc_product_feed || {};
			const stateSetter = chainState( state, 'mc_product_feed' );

			if (
				lastQuery.per_page !== query.per_page ||
				lastQuery.order !== query.order ||
				lastQuery.orderby !== query.orderby
			) {
				// discard old stored data when pagination has changed.
				stateSetter.setIn( 'pages', {} );
			}

			return stateSetter
				.setIn( [ 'pages', query.page ], data.products )
				.setIn( 'per_page', query.per_page )
				.setIn( 'order', query.order )
				.setIn( 'orderby', query.orderby )
				.setIn( 'total', data.total )
				.end();
		}

		case TYPES.RECEIVE_REPORT: {
			const { reportKey, data } = action;
			return setIn( state, [ 'report', reportKey ], data );
		}

		case TYPES.POLICY_CHECK: {
			const { data } = action;
			return setIn( state, 'mc.policy_check', data );
		}

		case TYPES.RECEIVE_MAPPING_ATTRIBUTES: {
			return setIn( state, 'mc.mapping.attributes', action.attributes );
		}

		case TYPES.RECEIVE_MAPPING_SOURCES: {
			const { attributeKey, sources } = action;

			return setIn(
				state,
				[ 'mc', 'mapping', 'sources', attributeKey ],
				sources
			);
		}

		case TYPES.RECEIVE_MAPPING_RULES: {
			const { rules, pagination } = action;
			const newRulesState = [ ...state.mc.mapping.rules.items ];

			const start = ( pagination.page - 1 ) * pagination.perPage;
			const deleteCount = pagination.perPage;
			newRulesState.splice( start, deleteCount, ...rules );

			return chainState( state, 'mc.mapping.rules' )
				.setIn( 'items', newRulesState )
				.setIn( 'total', pagination.total )
				.setIn( 'pages', pagination.pages )
				.end();
		}

		case TYPES.UPSERT_MAPPING_RULE: {
			const { rule } = action;
			const newRulesState = [ ...state.mc.mapping.rules.items ];

			const ruleIndex = newRulesState.findIndex(
				( el ) => el.id === rule.id
			);

			if ( ruleIndex >= 0 ) {
				newRulesState[ ruleIndex ] = rule;
			} else {
				newRulesState.push( rule );
			}

			return setIn( state, 'mc.mapping.rules.items', newRulesState );
		}

		case TYPES.DELETE_MAPPING_RULE: {
			const rules = state.mc.mapping.rules.items.filter(
				( el ) => el.id !== action.rule.id
			);

			return setIn( state, 'mc.mapping.rules.items', rules );
		}

		case TYPES.RECEIVE_STORE_CATEGORIES: {
			const { storeCategories } = action;
			return setIn( state, 'store_categories', storeCategories );
		}

		case TYPES.RECEIVE_TOUR:
		case TYPES.UPSERT_TOUR: {
			const { tour } = action;
			return setIn( state, [ 'tours', tour.id ], tour );
		}

		case TYPES.HYDRATE_PREFETCHED_DATA: {
			const stateSetter = chainState( state, 'general' );

			[ 'version', 'mcId', 'adsId' ].forEach( ( key ) => {
				if ( action.data.hasOwnProperty( key ) ) {
					stateSetter.setIn( key, action.data[ key ] );
				}
			} );

			return stateSetter.end();
		}

		case TYPES.RECEIVE_GOOGLE_ADS_ACCOUNT_STATUS: {
			const {
				data: { has_access: hasAccess, invite_link: inviteLink, step },
			} = action;

			return chainState( state, 'ads.accountStatus' )
				.setIn( 'hasAccess', hasAccess )
				.setIn( 'inviteLink', inviteLink )
				.setIn( 'step', step )
				.end();
		}

		// Page will be reloaded after all accounts have been disconnected, so no need to mutate state.
		case TYPES.DISCONNECT_ACCOUNTS_ALL:
		default:
			return state;
	}
};

export default reducer;
