/**
 * Internal dependencies
 */
import {
	getTargetCountries,
	ensureRateRows,
	ensureTimeRows,
	updateRateRows,
	updateTimes,
} from './shipping-rows';

describe( 'getTargetCountries', () => {
	test( 'returns the values.countries array for the primary market', () => {
		expect(
			getTargetCountries( true, { countries: [ 'US', 'CA', 'MX' ] } )
		).toEqual( [ 'US', 'CA', 'MX' ] );
	} );

	test( 'returns an empty array when primary has no countries', () => {
		expect( getTargetCountries( true, {} ) ).toEqual( [] );
	} );

	test( 'wraps the single values.country for non-primary markets', () => {
		expect( getTargetCountries( false, { country: 'FR' } ) ).toEqual( [
			'FR',
		] );
	} );

	test( 'returns an empty array when non-primary has no country', () => {
		expect( getTargetCountries( false, {} ) ).toEqual( [] );
	} );
} );

describe( 'ensureRateRows', () => {
	test( 'returns the input array unchanged when every target country already has a row', () => {
		const rates = [
			{ country: 'US', currency: 'USD', rate: 10, options: {} },
		];
		expect( ensureRateRows( rates, [ 'US' ], 'USD' ) ).toBe( rates );
	} );

	test( 'appends a zero-rate row for each missing target country', () => {
		const rates = [
			{ country: 'US', currency: 'USD', rate: 10, options: {} },
		];
		const result = ensureRateRows( rates, [ 'US', 'FR' ], 'EUR' );

		expect( result ).toHaveLength( 2 );
		expect( result[ 1 ] ).toEqual( {
			country: 'FR',
			currency: 'EUR',
			rate: 0,
			options: {},
		} );
	} );

	test( 'works against an empty rates array', () => {
		const result = ensureRateRows( [], [ 'FR' ], 'EUR' );

		expect( result ).toEqual( [
			{ country: 'FR', currency: 'EUR', rate: 0, options: {} },
		] );
	} );
} );

describe( 'ensureTimeRows', () => {
	test( 'returns the input array unchanged when every target country already has a row', () => {
		const times = [ { countryCode: 'US', time: 3, maxTime: 5 } ];
		expect( ensureTimeRows( times, [ 'US' ] ) ).toBe( times );
	} );

	test( 'appends a zero-time row for each missing target country', () => {
		const times = [ { countryCode: 'US', time: 3, maxTime: 5 } ];
		const result = ensureTimeRows( times, [ 'US', 'FR' ] );

		expect( result ).toHaveLength( 2 );
		expect( result[ 1 ] ).toEqual( {
			countryCode: 'FR',
			time: 0,
			maxTime: 0,
		} );
	} );

	test( 'works against an empty times array', () => {
		const result = ensureTimeRows( [], [ 'FR' ] );

		expect( result ).toEqual( [
			{ countryCode: 'FR', time: 0, maxTime: 0 },
		] );
	} );
} );

describe( 'updateRateRows', () => {
	test( 'patches only rows whose country is in the target list', () => {
		const rates = [
			{ country: 'US', currency: 'USD', rate: 10, options: {} },
			{ country: 'FR', currency: 'EUR', rate: 8, options: {} },
		];

		const result = updateRateRows( rates, [ 'FR' ], { rate: 12 } );

		expect( result[ 0 ].rate ).toBe( 10 );
		expect( result[ 1 ].rate ).toBe( 12 );
	} );

	test( 'is a no-op when no target row exists (ensureRateRows is the upsert path)', () => {
		const rates = [
			{ country: 'US', currency: 'USD', rate: 10, options: {} },
		];

		const result = updateRateRows( rates, [ 'FR' ], { rate: 12 } );

		expect( result ).toEqual( rates );
	} );
} );

describe( 'updateRateRows — optionsPatch', () => {
	test( 'merges options into matching rows without overwriting siblings', () => {
		const rates = [
			{
				country: 'FR',
				currency: 'EUR',
				rate: 8,
				options: { existing_key: 'keep' },
			},
		];

		const result = updateRateRows(
			rates,
			[ 'FR' ],
			{},
			{ free_shipping_threshold: 50 }
		);

		expect( result[ 0 ].options ).toEqual( {
			existing_key: 'keep',
			free_shipping_threshold: 50,
		} );
	} );

	test( 'leaves non-target rows untouched', () => {
		const rates = [
			{ country: 'US', currency: 'USD', rate: 10, options: {} },
			{ country: 'FR', currency: 'EUR', rate: 8, options: {} },
		];

		const result = updateRateRows(
			rates,
			[ 'FR' ],
			{},
			{ free_shipping_threshold: 50 }
		);

		expect( result[ 0 ].options ).toEqual( {} );
	} );
} );

describe( 'updateTimes', () => {
	test( 'patches only rows whose countryCode is in the target list', () => {
		const times = [
			{ countryCode: 'US', time: 3, maxTime: 5 },
			{ countryCode: 'FR', time: 5, maxTime: 7 },
		];

		const result = updateTimes( times, [ 'FR' ], { time: 6 } );

		expect( result[ 0 ].time ).toBe( 3 );
		expect( result[ 1 ].time ).toBe( 6 );
	} );
} );

describe( 'regression: secondary market with no stored rate row', () => {
	// Reproduces the bug that caused Maggie's secondary-market edit to silently
	// drop input. The old code called only `updateRates`, which couldn't add a
	// row, so the form value never changed and saveShippingRates saw no diff.

	test( 'ensureRateRows + updateRates produces a row with the entered rate', () => {
		const targetCountries = getTargetCountries( false, { country: 'FR' } );
		const rates = []; // no FR row stored yet
		const ensured = ensureRateRows( rates, targetCountries, 'EUR' );
		const patched = updateRateRows( ensured, targetCountries, {
			rate: 10,
		} );

		expect( patched ).toEqual( [
			{ country: 'FR', currency: 'EUR', rate: 10, options: {} },
		] );
	} );

	test( 'ensureTimeRows + updateTimes produces a row with the entered min/max time', () => {
		const targetCountries = getTargetCountries( false, { country: 'FR' } );
		const times = [];
		let result = ensureTimeRows( times, targetCountries );
		result = updateTimes( result, targetCountries, { time: 3 } );
		result = updateTimes( result, targetCountries, { maxTime: 5 } );

		expect( result ).toEqual( [
			{ countryCode: 'FR', time: 3, maxTime: 5 },
		] );
	} );
} );
