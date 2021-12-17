/**
 * External dependencies
 */
import { cloneDeep, set, get, isPlainObject } from 'lodash';

/**
 * Internal dependencies
 */
import reducer from './reducer';
import TYPES from './action-types';

// Copied from https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Object/freeze
function deepFreeze( object ) {
	// Retrieve the property names defined on object
	const propNames = Object.getOwnPropertyNames( object );

	// Freeze properties before freezing self
	for ( const name of propNames ) {
		const value = object[ name ];
		if ( value && typeof value === 'object' ) {
			deepFreeze( value );
		}
	}

	return Object.freeze( object );
}

function attachRef( object, ignore, checkingGroups = [], paths = [] ) {
	const refKey = '__refForCheck';

	for ( const [ name, value ] of Object.entries( object ) ) {
		if ( isPlainObject( value ) ) {
			const checkPaths = [ ...paths, name ];
			attachRef( value, ignore, checkingGroups, checkPaths );

			const currentPath = checkPaths.join( '.' );
			if ( ! ignore.includes( currentPath ) ) {
				const ref = {
					whyFail: `If there's no mutation at \`state.${ currentPath }\`, it should be kept the same reference.`,
				};
				checkingGroups.push( {
					ref,
					refPath: `${ currentPath }.${ refKey }`,
				} );
				value[ refKey ] = ref;
			}
		}
	}
	return checkingGroups;
}

describe( 'reducer', () => {
	let defaultState;
	let prepareState;

	beforeEach( () => {
		defaultState = deepFreeze( {
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
		} );

		/**
		 * Creates a deep freeze state based on the default state,
		 * and also implants a `assertConsistentRef` function for reference check.
		 * An initial value of a specific path can be set optionally to facilitate testing.
		 *
		 * Usage of `assertConsistentRef`:
		 *   After getting the new state from `reducer( preparedState, action )`,
		 *   calls `newState.assertConsistentRef()` for reference checking.
		 *
		 * @param {string} [path] The path of state to be set.
		 * @param {*} [value] The initial value to be set.
		 * @param {true|Array<string>} [ignoreRefCheckOnMutatingPath] Indicate state paths that don't require reference check.
		 *   `true` - Set `true` to use the passed-in `path` parameter as the ignoring path.
		 *   `Array<string>` - Given an array of state paths to be ignored.
		 *
		 * @return {Object} Prepared state.
		 */
		prepareState = ( path, value, ignoreRefCheckOnMutatingPath ) => {
			const state = cloneDeep( defaultState );
			if ( path ) {
				set( state, path, value );
			}

			// Prepare the paths to be ignored the reference check.
			const ignorePaths = [];
			if ( ignoreRefCheckOnMutatingPath === true ) {
				ignorePaths.push( path );
			} else if ( Array.isArray( ignoreRefCheckOnMutatingPath ) ) {
				ignorePaths.push( ...ignoreRefCheckOnMutatingPath );
			}
			const checkingGroups = attachRef( state, ignorePaths );

			state.assertConsistentRef = function () {
				if ( this === state ) {
					throw new Error(
						'Please use the state returned by `reducer` to check this assertion in the test case.'
					);
				}

				checkingGroups.forEach( ( { refPath, ref } ) => {
					// If you see this failed assertion, it may be because a reference has been changed unexpectedly, please check if other states have been changed. Or, the reference chage is expected but hasn't be specified ignoring. Please add that path to the `ignoreRefCheckOnMutatingPath` parameter.
					expect( get( this, refPath ) ).toBe( ref );
				} );
			};

			return deepFreeze( state );
		};
	} );

	describe( 'General reducer behaviors', () => {
		it( 'should return default state by default', () => {
			const state = reducer( undefined, {} );

			expect( state ).toEqual( defaultState );
		} );

		it( 'when no action is matched, should return the same reference of state as the passed-in `state` parameter', () => {
			const originalState = {};

			expect( reducer( originalState, {} ) ).toBe( originalState );
		} );

		it( 'when action is not required to change state, should return the same reference of state as the passed-in `state` parameter', () => {
			const originalState = {};
			const state = reducer( originalState, {
				type: TYPES.DISCONNECT_ACCOUNTS_ALL,
			} );

			expect( state ).toBe( originalState );
		} );
	} );

	describe( 'Merchant center shipping rate at `mc.shipping.rates`', () => {
		const path = 'mc.shipping.rates';

		it( 'should return with received shipping rates', () => {
			const action = {
				type: TYPES.RECEIVE_SHIPPING_RATES,
				shippingRates: [
					{
						countryCode: 'US',
						currency: 'USD',
						rate: 4.99,
					},
					{
						countryCode: 'CA',
						currency: 'USD',
						rate: 25,
					},
				],
			};
			const state = reducer( prepareState(), action );

			state.assertConsistentRef();
			expect( state ).toHaveProperty( path, action.shippingRates );
		} );

		it( 'should return with upserted shipping rates by matching `countryCode`', () => {
			const originalState = prepareState( path, [
				{
					countryCode: 'US',
					currency: 'USD',
					rate: 4.99,
				},
				{
					countryCode: 'CA',
					currency: 'USD',
					rate: 25,
				},
			] );
			const action = {
				type: TYPES.UPSERT_SHIPPING_RATES,
				shippingRate: {
					countryCodes: [ 'JP', 'CA' ],
					currency: 'USD',
					rate: 12,
				},
			};
			const state = reducer( originalState, action );

			state.assertConsistentRef();
			expect( state ).toHaveProperty( path, [
				{
					countryCode: 'US',
					currency: 'USD',
					rate: 4.99,
				},
				{
					countryCode: 'CA',
					currency: 'USD',
					rate: 12,
				},
				{
					countryCode: 'JP',
					currency: 'USD',
					rate: 12,
				},
			] );
		} );

		it( 'should return with remaining shipping rates after deleting specific items by matching `countryCode`', () => {
			const originalState = prepareState( path, [
				{
					countryCode: 'US',
					currency: 'USD',
					rate: 4.99,
				},
				{
					countryCode: 'CA',
					currency: 'USD',
					rate: 25,
				},
				{
					countryCode: 'JP',
					currency: 'USD',
					rate: 12,
				},
			] );
			const action = {
				type: TYPES.DELETE_SHIPPING_RATES,
				countryCodes: [ 'US', 'JP' ],
			};
			const state = reducer( originalState, action );

			state.assertConsistentRef();
			expect( state ).toHaveProperty( path, [
				{
					countryCode: 'CA',
					currency: 'USD',
					rate: 25,
				},
			] );
		} );
	} );

	describe( 'Merchant center shipping time at `mc.shipping.times`', () => {
		const path = 'mc.shipping.times';

		it( 'should return with received shipping times', () => {
			const action = {
				type: TYPES.RECEIVE_SHIPPING_TIMES,
				shippingTimes: [
					{
						countryCode: 'US',
						time: 7,
					},
					{
						countryCode: 'CA',
						time: 12,
					},
				],
			};
			const state = reducer( prepareState(), action );

			state.assertConsistentRef();
			expect( state ).toHaveProperty( path, action.shippingTimes );
		} );

		it( 'should return with upserted shipping times by matching `countryCode`', () => {
			const originalState = prepareState( path, [
				{
					countryCode: 'US',
					time: 7,
				},
				{
					countryCode: 'CA',
					time: 12,
				},
			] );
			const action = {
				type: TYPES.UPSERT_SHIPPING_TIMES,
				shippingTime: {
					countryCodes: [ 'JP', 'CA' ],
					time: 15,
				},
			};
			const state = reducer( originalState, action );

			state.assertConsistentRef();
			expect( state ).toHaveProperty( path, [
				{
					countryCode: 'US',
					time: 7,
				},
				{
					countryCode: 'CA',
					time: 15,
				},
				{
					countryCode: 'JP',
					time: 15,
				},
			] );
		} );

		it( 'should return with remaining shipping times after deleting specific items by matching `countryCode`', () => {
			const originalState = prepareState( path, [
				{
					countryCode: 'US',
					time: 7,
				},
				{
					countryCode: 'CA',
					time: 12,
				},
				{
					countryCode: 'JP',
					time: 15,
				},
			] );
			const action = {
				type: TYPES.DELETE_SHIPPING_TIMES,
				countryCodes: [ 'US', 'JP' ],
			};
			const state = reducer( originalState, action );

			state.assertConsistentRef();
			expect( state ).toHaveProperty( path, [
				{
					countryCode: 'CA',
					time: 12,
				},
			] );
		} );
	} );

	describe( 'Google Ads account connection', () => {
		const path = 'mc.accounts.ads';

		it( 'should return with received Google Ads account connection', () => {
			const action = {
				type: TYPES.RECEIVE_ACCOUNTS_GOOGLE_ADS,
				account: { id: 123456789 },
			};
			const state = reducer( prepareState(), action );

			state.assertConsistentRef();
			expect( state ).toHaveProperty( path, action.account );
		} );

		it( 'should return with default Google Ads account connection when getting disconnect action', () => {
			const originalState = prepareState( path, { id: 123456789 }, true );
			const action = { type: TYPES.DISCONNECT_ACCOUNTS_GOOGLE_ADS };
			const state = reducer( originalState, action );

			state.assertConsistentRef();
			expect( state ).toHaveProperty( path, get( defaultState, path ) );
		} );
	} );

	describe( 'Ads campaigns', () => {
		const path = 'ads_campaigns';

		it( 'should return with received ads campaigns', () => {
			const action = {
				type: TYPES.RECEIVE_ADS_CAMPAIGNS,
				adsCampaigns: [ { id: 123 }, { id: 456 } ],
			};
			const state = reducer( prepareState(), action );

			state.assertConsistentRef();
			expect( state ).toHaveProperty( path, action.adsCampaigns );
		} );

		it( 'should patch the given data properties and return with updated ads campaign by matching `id`', () => {
			const originalState = prepareState( path, [
				{
					id: 123,
					status: 'paused',
					name: 'how do you turn this on',
					amount: 50,
				},
				{
					id: 456,
					status: 'enabled',
					name: 'alpaca simulator',
					amount: 999,
				},
			] );
			const action = {
				type: TYPES.UPDATE_ADS_CAMPAIGN,
				id: 123,
				data: { name: 'robin hood', amount: 10000, status: 'enabled' },
			};
			const state = reducer( originalState, action );

			state.assertConsistentRef();
			expect( state ).toHaveProperty( path, [
				{
					id: 123,
					status: 'enabled',
					name: 'robin hood',
					amount: 10000,
				},
				{
					id: 456,
					status: 'enabled',
					name: 'alpaca simulator',
					amount: 999,
				},
			] );
		} );

		it( 'should return with remaining ads campaigns after deleting specific one by matching `id`', () => {
			const originalState = prepareState( path, [
				{ id: 123 },
				{ id: 456 },
				{ id: 789 },
			] );
			const action = { type: TYPES.DELETE_ADS_CAMPAIGN, id: 456 };
			const state = reducer( originalState, action );

			state.assertConsistentRef();
			expect( state ).toHaveProperty( path, [
				{ id: 123 },
				{ id: 789 },
			] );
		} );
	} );

	describe( 'Merchant Center issues', () => {
		const path = 'mc_issues';

		it( 'should update issues array by ascending order of paging 1, 2, ..., final, and return with received issues and total number of issues', () => {
			const pageOneState = reducer( prepareState(), {
				type: TYPES.RECEIVE_MC_ISSUES,
				query: { page: 1, per_page: 2 },
				data: { total: 5, issues: [ '#1', '#2' ] },
			} );
			const pageTwoState = reducer( pageOneState, {
				type: TYPES.RECEIVE_MC_ISSUES,
				query: { page: 2, per_page: 2 },
				data: { total: 5, issues: [ '#3', '#4' ] },
			} );
			const pageThreeState = reducer( pageTwoState, {
				type: TYPES.RECEIVE_MC_ISSUES,
				query: { page: 3, per_page: 2 },
				data: { total: 5, issues: [ '#5' ] },
			} );

			pageOneState.assertConsistentRef();
			pageTwoState.assertConsistentRef();
			pageThreeState.assertConsistentRef();
			expect( pageOneState ).toHaveProperty( path, {
				total: 5,
				issues: [ '#1', '#2' ],
			} );
			expect( pageTwoState ).toHaveProperty( path, {
				total: 5,
				issues: [ '#1', '#2', '#3', '#4' ],
			} );
			expect( pageThreeState ).toHaveProperty( path, {
				total: 5,
				issues: [ '#1', '#2', '#3', '#4', '#5' ],
			} );
		} );
	} );

	describe( 'Merchant Center product feed', () => {
		const path = 'mc_product_feed';

		it( 'should store paginated data by `query` and return with received product feed, total number of product feed and some query data', () => {
			const pageOneState = reducer( prepareState(), {
				type: TYPES.RECEIVE_MC_PRODUCT_FEED,
				query: {
					order: 'asc',
					orderby: 'title',
					per_page: 2,
					page: 1,
				},
				data: {
					total: 7,
					products: [ '#1', '#2' ],
				},
			} );
			// Support for storing product feed regardless of pagination loading order.
			const pageFourState = reducer( pageOneState, {
				type: TYPES.RECEIVE_MC_PRODUCT_FEED,
				query: {
					order: 'asc',
					orderby: 'title',
					per_page: 2,
					page: 4,
				},
				data: {
					total: 7,
					products: [ '#7' ],
				},
			} );

			pageOneState.assertConsistentRef();
			pageFourState.assertConsistentRef();
			expect( pageOneState ).toHaveProperty( path, {
				order: 'asc',
				orderby: 'title',
				per_page: 2,
				total: 7,
				pages: { '1': [ '#1', '#2' ] },
			} );
			expect( pageFourState ).toHaveProperty( path, {
				order: 'asc',
				orderby: 'title',
				per_page: 2,
				total: 7,
				pages: { '1': [ '#1', '#2' ], '4': [ '#7' ] },
			} );
		} );

		it.each( [
			[ 'order', 'desc' ],
			[ 'orderby', 'visible' ],
			[ 'per_page', 5 ],
		] )(
			'when the `query.%s` is changed, should discard `pages` and return with received product feed',
			( key, value ) => {
				const baseQuery = {
					order: 'asc',
					orderby: 'title',
					per_page: 2,
				};
				const initValue = {
					...baseQuery,
					total: 7,
					pages: { '1': [ '#1', '#2' ], '4': [ '#7' ] },
				};
				const originalState = prepareState( path, initValue, [
					path,
					`${ path }.pages`,
				] );
				const action = {
					type: TYPES.RECEIVE_MC_PRODUCT_FEED,
					query: {
						...baseQuery,
						[ key ]: value,
						page: 2,
					},
					data: {
						total: 7,
						products: [ '#3', '#4' ],
					},
				};
				const state = reducer( originalState, action );

				state.assertConsistentRef();
				expect( state ).toHaveProperty( path, {
					...baseQuery,
					[ key ]: value,
					total: 7,
					pages: { '2': [ '#3', '#4' ] },
				} );
			}
		);
	} );

	describe( 'Reports of programs and products', () => {
		const path = 'report';

		it( 'should store paginated data by `reportKey` and return with received report data', () => {
			const pageOneState = reducer( prepareState(), {
				type: TYPES.RECEIVE_REPORT,
				reportKey: 'key1',
				data: '#1',
			} );
			// Support for storing report data regardless of pagination loading order.
			const pageFourState = reducer( pageOneState, {
				type: TYPES.RECEIVE_REPORT,
				reportKey: 'key4',
				data: '#4',
			} );

			pageOneState.assertConsistentRef();
			pageFourState.assertConsistentRef();
			expect( pageOneState ).toHaveProperty( `${ path }.key1`, '#1' );
			expect( pageFourState ).toHaveProperty( `${ path }.key1`, '#1' );
			expect( pageFourState ).toHaveProperty( `${ path }.key4`, '#4' );
		} );
	} );

	describe( 'Remaining actions simply update the data payload to the specific path of state and return the updated state', () => {
		// The readability is better than applying the formatting here.
		/* eslint-disable prettier/prettier */
		// prettier-ignore
		const argumentsTuples = [
			[ TYPES.RECEIVE_SETTINGS, 'settings', 'mc.settings' ],
			[ TYPES.SAVE_SETTINGS, 'settings', 'mc.settings' ],
			[ TYPES.RECEIVE_ACCOUNTS_JETPACK, 'account', 'mc.accounts.jetpack' ],
			[ TYPES.RECEIVE_ACCOUNTS_GOOGLE, 'account', 'mc.accounts.google' ],
			[ TYPES.RECEIVE_ACCOUNTS_GOOGLE_ACCESS, 'data', 'mc.accounts.google_access' ],
			[ TYPES.RECEIVE_ACCOUNTS_GOOGLE_MC, 'account', 'mc.accounts.mc' ],
			[ TYPES.RECEIVE_ACCOUNTS_GOOGLE_MC_EXISTING, 'accounts', 'mc.accounts.existing_mc' ],
			[ TYPES.RECEIVE_ACCOUNTS_GOOGLE_ADS_BILLING_STATUS, 'billingStatus', 'mc.accounts.ads_billing_status' ],
			[ TYPES.RECEIVE_ACCOUNTS_GOOGLE_ADS_EXISTING, 'accounts', 'mc.accounts.existing_ads' ],
			[ TYPES.RECEIVE_MC_CONTACT_INFORMATION, 'data', 'mc.contact' ],
			[ TYPES.RECEIVE_COUNTRIES, 'countries', 'mc.countries' ],
			[ TYPES.RECEIVE_TARGET_AUDIENCE, 'target_audience', 'mc.target_audience' ],
			[ TYPES.SAVE_TARGET_AUDIENCE, 'target_audience', 'mc.target_audience' ],
			[ TYPES.RECEIVE_MC_SETUP, 'mcSetup', 'mc_setup' ],
			[ TYPES.RECEIVE_MC_PRODUCT_STATISTICS, 'mcProductStatistics', 'mc_product_statistics' ],
		];
		/* eslint-enable prettier/prettier */

		it.each( argumentsTuples )(
			'for type `%s`, it should return with received %s at `%s`',
			( type, key, path ) => {
				const payload = { hello: 'WordPress' };
				const action = { type, [ key ]: payload };
				const state = reducer( prepareState(), action );

				state.assertConsistentRef();
				expect( state ).toHaveProperty( path, payload );
			}
		);
	} );
} );
