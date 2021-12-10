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
} );
