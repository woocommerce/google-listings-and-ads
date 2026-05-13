/**
 * Internal dependencies
 */
import { SHIPPING_RATE_METHOD } from '~/constants';
import checkErrors from './checkErrors';

const PRIMARY_MARKET_ID = 'primary';

describe( 'checkErrors', () => {
	describe( 'primary market', () => {
		it( 'returns a countries error when countries is empty', () => {
			const errors = checkErrors( {
				id: PRIMARY_MARKET_ID,
				countries: [],
			} );

			expect( errors.countries ).toBeDefined();
		} );

		it( 'returns a countries error when countries is missing', () => {
			const errors = checkErrors( { id: PRIMARY_MARKET_ID } );

			expect( errors.countries ).toBeDefined();
		} );

		it( 'returns no errors when countries has at least one entry', () => {
			const errors = checkErrors( {
				id: PRIMARY_MARKET_ID,
				countries: [ 'US' ],
			} );

			expect( errors ).toEqual( {} );
		} );

		it( 'returns only country-level errors and skips shipping validation', () => {
			const errors = checkErrors( {
				id: PRIMARY_MARKET_ID,
				countries: [],
				shipping_rate: SHIPPING_RATE_METHOD.FLAT,
				offer_free_shipping: true,
				free_shipping_threshold: null,
			} );

			expect( errors.countries ).toBeDefined();
			expect( errors.free_shipping_threshold ).toBeUndefined();
		} );
	} );

	describe( 'non-primary market — country field', () => {
		it( 'returns a country error when country is falsy', () => {
			const errors = checkErrors( { country: null } );

			expect( errors.country ).toBeDefined();
		} );

		it( 'returns a country error when country is undefined', () => {
			const errors = checkErrors( {} );

			expect( errors.country ).toBeDefined();
		} );

		it( 'returns no country error when country is set', () => {
			const errors = checkErrors( {
				country: 'US',
				shipping_rate: SHIPPING_RATE_METHOD.FLAT,
				offer_free_shipping: false,
				shipping_time: 'flat',
				flat_shipping_min_time: 1,
				flat_shipping_max_time: 3,
			} );

			expect( errors.country ).toBeUndefined();
		} );
	} );

	describe( 'flat shipping rate validation', () => {
		const base = { country: 'US', shipping_rate: SHIPPING_RATE_METHOD.FLAT };

		it( 'returns an error when offer_free_shipping is true but threshold is missing', () => {
			const errors = checkErrors( {
				...base,
				offer_free_shipping: true,
				free_shipping_threshold: null,
			} );

			expect( errors.free_shipping_threshold ).toBeDefined();
		} );

		it( 'returns an error when offer_free_shipping is true and threshold is 0', () => {
			const errors = checkErrors( {
				...base,
				offer_free_shipping: true,
				free_shipping_threshold: 0,
			} );

			expect( errors.free_shipping_threshold ).toBeDefined();
		} );

		it( 'returns no free_shipping_threshold error when offer_free_shipping is true and threshold is set', () => {
			const errors = checkErrors( {
				...base,
				offer_free_shipping: true,
				free_shipping_threshold: 10,
			} );

			expect( errors.free_shipping_threshold ).toBeUndefined();
		} );

		it( 'returns no free_shipping_threshold error when offer_free_shipping is false', () => {
			const errors = checkErrors( {
				...base,
				offer_free_shipping: false,
				free_shipping_threshold: null,
			} );

			expect( errors.free_shipping_threshold ).toBeUndefined();
		} );
	} );

	describe( 'flat shipping time validation', () => {
		const base = {
			country: 'US',
			shipping_rate: SHIPPING_RATE_METHOD.FLAT,
			offer_free_shipping: false,
			shipping_time: 'flat',
		};

		it( 'returns an error when flat_shipping_min_time is null', () => {
			const errors = checkErrors( {
				...base,
				flat_shipping_min_time: null,
				flat_shipping_max_time: 5,
			} );

			expect( errors.flat_shipping_times ).toBeDefined();
		} );

		it( 'returns an error when flat_shipping_min_time is undefined', () => {
			const errors = checkErrors( {
				...base,
				flat_shipping_max_time: 5,
			} );

			expect( errors.flat_shipping_times ).toBeDefined();
		} );

		it( 'returns an error when flat_shipping_max_time is null', () => {
			const errors = checkErrors( {
				...base,
				flat_shipping_min_time: 1,
				flat_shipping_max_time: null,
			} );

			expect( errors.flat_shipping_times ).toBeDefined();
		} );

		it( 'returns an error when flat_shipping_min_time is negative', () => {
			const errors = checkErrors( {
				...base,
				flat_shipping_min_time: -1,
				flat_shipping_max_time: 5,
			} );

			expect( errors.flat_shipping_times ).toBeDefined();
		} );

		it( 'returns an error when flat_shipping_max_time is negative', () => {
			const errors = checkErrors( {
				...base,
				flat_shipping_min_time: 1,
				flat_shipping_max_time: -1,
			} );

			expect( errors.flat_shipping_times ).toBeDefined();
		} );

		it( 'returns an error when min is greater than max', () => {
			const errors = checkErrors( {
				...base,
				flat_shipping_min_time: 5,
				flat_shipping_max_time: 3,
			} );

			expect( errors.flat_shipping_times ).toBeDefined();
		} );

		it( 'returns no error when min equals max', () => {
			const errors = checkErrors( {
				...base,
				flat_shipping_min_time: 3,
				flat_shipping_max_time: 3,
			} );

			expect( errors.flat_shipping_times ).toBeUndefined();
		} );

		it( 'returns no error for valid min and max', () => {
			const errors = checkErrors( {
				...base,
				flat_shipping_min_time: 1,
				flat_shipping_max_time: 5,
			} );

			expect( errors.flat_shipping_times ).toBeUndefined();
		} );

		it( 'skips time validation when shipping_time is not flat', () => {
			const errors = checkErrors( {
				...base,
				shipping_time: 'manual',
				flat_shipping_min_time: null,
				flat_shipping_max_time: null,
			} );

			expect( errors.flat_shipping_times ).toBeUndefined();
		} );
	} );

	describe( 'valid non-primary market', () => {
		it( 'returns an empty errors object for a fully valid submission', () => {
			const errors = checkErrors( {
				country: 'US',
				shipping_rate: SHIPPING_RATE_METHOD.FLAT,
				offer_free_shipping: true,
				free_shipping_threshold: 50,
				shipping_time: 'flat',
				flat_shipping_min_time: 2,
				flat_shipping_max_time: 7,
			} );

			expect( errors ).toEqual( {} );
		} );
	} );
} );
