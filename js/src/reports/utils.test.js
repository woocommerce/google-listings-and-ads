/**
 * Internal dependencies
 */
import { getIdsFromQuery } from './utils';

// Copied from https://github.com/woocommerce/woocommerce-admin/blob/b35156dcf17b44b3a81ea4b4528445b432917fd5/packages/navigation/src/test/index.js#L184-L221
describe( 'getIdsFromQuery', () => {
	it( 'if the given query is empty, should return an empty array', () => {
		expect( getIdsFromQuery( '' ) ).toEqual( [] );
	} );

	it( 'if the given query is undefined, should return an empty array', () => {
		expect( getIdsFromQuery( undefined ) ).toEqual( [] );
	} );

	it( 'if the given query is does not contain any coma-separated numbers, should return an empty array', () => {
		expect( getIdsFromQuery( 'foo123,bar,baz1.' ) ).toEqual( [] );
	} );

	describe( 'if the given query contains numbers', () => {
		it( 'should return an array of them', () => {
			expect( getIdsFromQuery( '77,8,-1' ) ).toEqual( [ 77, 8, -1 ] );
		} );
		it( 'should consider `0` a valid id', () => {
			expect( getIdsFromQuery( '0' ) ).toEqual( [ 0 ] );
			expect( getIdsFromQuery( '77,0,1' ) ).toEqual( [ 77, 0, 1 ] );
		} );
		it( 'should map floats to integers', () => {
			expect( getIdsFromQuery( '77,8.54' ) ).toEqual( [ 77, 8 ] );
		} );
		it( 'should ignore duplicates', () => {
			expect( getIdsFromQuery( '77,8,8' ) ).toEqual( [ 77, 8 ] );
			// Consider two floats that maps to the same integer a duplicate.
			expect( getIdsFromQuery( '77,8.5,8.4' ) ).toEqual( [ 77, 8 ] );
		} );
		it( 'should ignore non numbers entries in the coma-separated list', () => {
			expect( getIdsFromQuery( '77,,8,foo,null,9' ) ).toEqual( [
				77,
				8,
				9,
			] );
		} );
	} );
} );
