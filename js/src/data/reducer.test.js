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

describe( 'reducer', () => {
	let defaultState;

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
} );
