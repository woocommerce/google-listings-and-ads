/**
 * Internal dependencies
 */
import {
	getReportQuery,
	getReportKey,
	calculateDelta,
	mapReportFieldsToPerformance,
	freeFields,
	paidFields,
	MISSING_FREE_LISTINGS_DATA,
} from './utils';

/**
 * Calls given function with all combinations of possible categories and dataReferences.
 *
 * @param {(category: string, dateReference: string) => void} callback Function to be called with all combinations.
 */
function forAnyCategoryAndDateReference( callback ) {
	for ( const category of [ 'products', 'programs' ] ) {
		for ( const dateReference of [ 'primary', 'secondary' ] ) {
			callback( category, dateReference );
		}
	}
}

describe( 'getReportQuery', () => {
	test( "should foward the `orderby` query parameter if it's one of supported fields for given type", () => {
		forAnyCategoryAndDateReference( ( category, dateReference ) => {
			for ( const orderby of freeFields ) {
				expect(
					getReportQuery(
						category,
						'free',
						{ orderby },
						dateReference
					)
				).toHaveProperty( 'orderby', orderby );
			}
			for ( const orderby of paidFields ) {
				expect(
					getReportQuery(
						category,
						'paid',
						{ orderby },
						dateReference
					)
				).toHaveProperty( 'orderby', orderby );
			}
		} );
	} );
	test( 'should set the `orderby` property to the first supported field if provided `query.orderby` is not supported', () => {
		forAnyCategoryAndDateReference( ( category, dateReference ) => {
			// Some key that's totally off.
			const unsupported = 'unsupported_foo';
			const query = {
				orderby: unsupported,
			};
			expect(
				getReportQuery( category, 'free', query, dateReference )
			).toHaveProperty( 'orderby', freeFields[ 0 ] );
			expect(
				getReportQuery( category, 'paid', query, dateReference )
			).toHaveProperty( 'orderby', paidFields[ 0 ] );
			// Supported for paid, but not for free.
			expect(
				getReportQuery(
					category,
					'free',
					{ orderby: paidFields[ 0 ] },
					dateReference
				)
			).toHaveProperty( 'orderby', freeFields[ 0 ] );
		} );
	} );

	it( 'should have no `ids` field if the query does not contain `programs` or `products`', () => {
		forAnyCategoryAndDateReference( ( category, dateReference ) => {
			const query = {};

			expect(
				getReportQuery( category, 'free', query, dateReference ).ids
			).toBeUndefined();
		} );
	} );

	it( 'should have `ids` field if the programs report query contains `programs`', () => {
		const query = {
			programs: '123,456',
		};

		expect(
			getReportQuery( 'programs', 'free', query, 'primary' ).ids
		).toBe( query.programs );
	} );

	it( 'should have transformed `ids` field if the products report query contains `products`', () => {
		// Search single product.
		const oneProductQuery = {
			products: '2468',
		};

		expect(
			getReportQuery( 'products', 'free', oneProductQuery, 'primary' ).ids
		).toBe( 'gla_2468' );

		// Search multiple products.
		const productsQuery = {
			products: '123,456,7890',
		};

		expect(
			getReportQuery( 'products', 'free', productsQuery, 'primary' ).ids
		).toBe( 'gla_123,gla_456,gla_7890' );
	} );
} );

describe( 'getReportKey', () => {
	it( 'should get a key with given `category`, `type` and `reportQuery`', () => {
		const reportQuery = {
			foo: 'bar',
		};

		expect( getReportKey( 'programs', 'free', reportQuery ) ).toBe(
			'programs:free:{"foo":"bar"}'
		);
		expect( getReportKey( 'products', 'paid', reportQuery ) ).toBe(
			'products:paid:{"foo":"bar"}'
		);
	} );

	it( 'should get the same key regardless of the property order within the `reportQuery` object', () => {
		const sameKey = '::{"apple":"","banana":"","cat":"","dog":""}';

		let reportQuery = {
			apple: '',
			banana: '',
			cat: '',
			dog: '',
		};

		expect( getReportKey( '', '', reportQuery ) ).toBe( sameKey );

		reportQuery = {
			dog: '',
			cat: '',
			banana: '',
			apple: '',
		};

		expect( getReportKey( '', '', reportQuery ) ).toBe( sameKey );

		reportQuery = {
			banana: '',
			dog: '',
			apple: '',
			cat: '',
		};

		expect( getReportKey( '', '', reportQuery ) ).toBe( sameKey );
	} );
} );

describe( 'calculateDelta', () => {
	it( 'should return null if any parameter to be calculated is not a number', () => {
		expect( calculateDelta() ).toBeNull();
		expect( calculateDelta( 1 ) ).toBeNull();

		expect( calculateDelta( 1, null ) ).toBeNull();
		expect( calculateDelta( undefined, 1 ) ).toBeNull();

		expect( calculateDelta( 1, '' ) ).toBeNull();
		expect( calculateDelta( '1', 1 ) ).toBeNull();

		expect( calculateDelta( true, 1 ) ).toBeNull();
		expect( calculateDelta( 1, {} ) ).toBeNull();
	} );

	it( 'should return null if the result is not finite', () => {
		expect( calculateDelta( 1, 0 ) ).toBeNull();
		expect( calculateDelta( NaN, 1 ) ).toBeNull();
	} );

	it( 'should return 0 if both base and value are 0', () => {
		expect( calculateDelta( 0, 0 ) ).toBe( 0 );
	} );

	it( 'should return delta percentage rounded to second decimal', () => {
		expect( calculateDelta( 20, 10 ) ).toBe( 100 );
		expect( calculateDelta( 25, 10 ) ).toBe( 150 );

		expect( calculateDelta( 0, 10 ) ).toBe( -100 );
		expect( calculateDelta( 5, 10 ) ).toBe( -50 );

		expect( calculateDelta( 4, 3 ) ).toBe( 33.33 );
		expect( calculateDelta( 5, 3 ) ).toBe( 66.67 );
	} );
} );

describe( 'mapReportFieldsToPerformance', () => {
	it( 'should take keys of `primary` as metric field keys', () => {
		const primary = { hello: 0, howdy: 1 };
		const primaryKeys = Object.keys( primary );
		const performance = mapReportFieldsToPerformance( primary, { hi: 2 } );
		const keys = Object.keys( performance );

		expect( keys ).toHaveLength( primaryKeys.length );
		expect( keys ).toEqual( expect.arrayContaining( primaryKeys ) );
	} );

	it( "should take specified `fields` as metric field keys if it's given", () => {
		const primary = { hello: 0, howdy: 1 };
		const performance = mapReportFieldsToPerformance(
			primary,
			{},
			freeFields
		);
		const keys = Object.keys( performance );

		expect( keys ).toHaveLength( freeFields.length );
		expect( keys ).toEqual( expect.arrayContaining( freeFields ) );
	} );

	it( 'should flag no anticipated data is missing if the same field exists in both `primary` and `secondary`', () => {
		const performance = mapReportFieldsToPerformance( { a: 1 }, { a: 2 } );

		expect( performance ).toMatchObject( {
			a: {
				missingFreeListingsData: MISSING_FREE_LISTINGS_DATA.NONE,
			},
		} );
	} );

	it( "should flag anticipated data is not returned from API if the field doesn't exists in one of `primary` and `secondary`", () => {
		// "a" does not exist in `secondary`
		let performance = mapReportFieldsToPerformance( { a: 1 }, { b: 2 } );
		expect( performance ).toMatchObject( {
			a: {
				missingFreeListingsData: MISSING_FREE_LISTINGS_DATA.FOR_REQUEST,
			},
		} );

		// "a" does not exist in `primary`
		const keys = [ 'a' ];
		performance = mapReportFieldsToPerformance( { b: 2 }, { a: 1 }, keys );
		expect( performance ).toMatchObject( {
			a: {
				missingFreeListingsData: MISSING_FREE_LISTINGS_DATA.FOR_REQUEST,
			},
		} );
	} );
} );
