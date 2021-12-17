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
} from '../utils';

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

	it( 'When the query does not contain `programs` or `products`, should have no `ids` field', () => {
		forAnyCategoryAndDateReference( ( category, dateReference ) => {
			const query = {};

			expect(
				getReportQuery( category, 'free', query, dateReference )
			).not.toHaveProperty( 'ids' );
		} );
	} );

	it( 'When the programs report query contains `programs`, should have `ids` field', () => {
		const query = {
			programs: '123,456',
		};

		expect(
			getReportQuery( 'programs', 'free', query, 'primary' )
		).toHaveProperty( 'ids', query.programs );
	} );

	it( 'When the products report query contains `products`, should have `ids` field, with values prepended with `gla_`', () => {
		// Search single product.
		const oneProductQuery = {
			products: '2468',
		};

		expect(
			getReportQuery( 'products', 'free', oneProductQuery, 'primary' )
		).toHaveProperty( 'ids', 'gla_2468' );

		// Search multiple products.
		const productsQuery = {
			products: '123,456,7890',
		};

		expect(
			getReportQuery( 'products', 'free', productsQuery, 'primary' )
		).toHaveProperty( 'ids', 'gla_123,gla_456,gla_7890' );
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

	it( 'Regardless of the property order within the `reportQuery` object, should get the same key', () => {
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
	it( 'When any given parameter is not a number, should return null', () => {
		expect( calculateDelta() ).toBeNull();
		expect( calculateDelta( 1 ) ).toBeNull();

		expect( calculateDelta( 1, null ) ).toBeNull();
		expect( calculateDelta( undefined, 1 ) ).toBeNull();

		expect( calculateDelta( 1, '' ) ).toBeNull();
		expect( calculateDelta( '1', 1 ) ).toBeNull();

		expect( calculateDelta( true, 1 ) ).toBeNull();
		expect( calculateDelta( 1, {} ) ).toBeNull();
	} );

	it( 'When the result is not finite, should return null', () => {
		expect( calculateDelta( 1, 0 ) ).toBeNull();
		expect( calculateDelta( NaN, 1 ) ).toBeNull();
	} );

	it( 'When both `base` and `value` are 0, should return 0', () => {
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
	it( 'should apply default values for `primary` and `secondary`', () => {
		const primary = { hello: 0 };
		const secondary = { howdy: 1 };

		expect( mapReportFieldsToPerformance() ).toEqual( {} );
		expect( mapReportFieldsToPerformance( undefined, secondary ) ).toEqual(
			{}
		);
		expect( mapReportFieldsToPerformance( primary ) ).toHaveProperty(
			'hello'
		);
	} );

	it( 'should take keys of `primary` as metric field keys', () => {
		const primary = { hello: 0, howdy: 1 };
		const primaryKeys = Object.keys( primary );
		const performance = mapReportFieldsToPerformance( primary, { hi: 2 } );
		const keys = Object.keys( performance );

		expect( keys ).toHaveLength( primaryKeys.length );
		expect( keys ).toEqual( expect.arrayContaining( primaryKeys ) );
	} );

	it( 'When the specified `fields` is given, should take it as metric field keys', () => {
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

	it( 'When the metric field exists in both `primary` and `secondary`, should flag no anticipated data is missing', () => {
		const performance = mapReportFieldsToPerformance( { a: 1 }, { a: 2 } );

		expect( performance ).toMatchObject( {
			a: {
				missingFreeListingsData: MISSING_FREE_LISTINGS_DATA.NONE,
			},
		} );
	} );

	it( "When the metric field doesn't exist in one of `primary` and `secondary`, should flag anticipated data is not returned from API", () => {
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
