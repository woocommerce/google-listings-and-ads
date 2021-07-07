/**
 * Internal dependencies
 */
import { paidFields } from '.~/data/utils';
import { getIdsFromQuery, aggregateIntervals } from './utils';

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

describe( 'aggregateIntervals', () => {
	function toIntervals( ...tuples ) {
		return tuples.map( ( [ interval, ...values ] ) => ( {
			interval,
			subtotals: values.reduce(
				( acc, value, i ) => ( {
					...acc,
					[ paidFields[ i ] ]: value,
				} ),
				{}
			),
		} ) );
	}

	it( 'if both `intervals` parameters are not provided, should return null', () => {
		expect( aggregateIntervals() ).toBeNull();
		expect( aggregateIntervals( null ) ).toBeNull();
		expect( aggregateIntervals( null, null ) ).toBeNull();
		expect( aggregateIntervals( undefined, null ) ).toBeNull();
	} );

	it( 'should sort aggregated intervals in ascending order of string code by `interval`', () => {
		const intervals1 = toIntervals(
			[ '2021-01' ],
			[ '2021-03' ],
			[ '2021-05' ],
			[ '2021-07' ]
		);
		const intervals2 = toIntervals(
			[ '2021-02' ],
			[ '2021-04' ],
			[ '2021-06' ],
			[ '2021-08' ]
		);
		const expectedIntervals = toIntervals(
			[ '2021-01' ],
			[ '2021-02' ],
			[ '2021-03' ],
			[ '2021-04' ],
			[ '2021-05' ],
			[ '2021-06' ],
			[ '2021-07' ],
			[ '2021-08' ]
		);

		const result = aggregateIntervals( intervals1, intervals2 );

		expectedIntervals.forEach( ( item, i ) => {
			expect( result[ i ] ).toMatchObject( item );
		} );
	} );

	describe( 'aggregate intervals', () => {
		it( "should merge two given intervals' items by the same `interval`, and the `subtotals` from the same `interval` items should be aggregated by summation of each its metric", () => {
			const intervals1 = toIntervals(
				[ '2021-01', 12, 34 ],
				[ '2021-02', 34, 56 ]
			);
			const intervals2 = toIntervals(
				[ '2021-01', 56, 78 ],
				[ '2021-02', 78, 90 ]
			);
			const expectedIntervals = toIntervals(
				[ '2021-01', 68, 112 ],
				[ '2021-02', 112, 146 ]
			);

			const result = aggregateIntervals( intervals1, intervals2 );

			expect( result ).toHaveLength( expectedIntervals.length );
			expectedIntervals.forEach( ( item, i ) => {
				expect( result[ i ] ).toMatchObject( item );
			} );
		} );

		it( "should merge two given intervals' items to a union result by `interval`", () => {
			const intervals1 = toIntervals( [ '2021-01', 12 ] );
			const intervals2 = toIntervals( [ '2021-02', 34 ] );
			const expectedIntervals = toIntervals(
				[ '2021-01', 12 ],
				[ '2021-02', 34 ]
			);

			const result = aggregateIntervals( intervals1, intervals2 );

			expect( result ).toHaveLength( expectedIntervals.length );
			expectedIntervals.forEach( ( item, i ) => {
				expect( result[ i ] ).toMatchObject( item );
			} );
		} );

		it( 'When any metric in `paidFields` does not exist in `subtotals`, should fill its value with 0', () => {
			const intervals1 = toIntervals( [ '2021-01', , 1, , 3 ] );
			const intervals2 = toIntervals( [ '2021-01', , , 2, , 4 ] );
			const expectedIntervals = toIntervals( [
				'2021-01',
				0,
				1,
				2,
				3,
				4,
			] );

			const result = aggregateIntervals( intervals1, intervals2 );

			expectedIntervals.forEach( ( item, i ) => {
				expect( result[ i ] ).toMatchObject( item );
			} );
		} );
	} );
} );
