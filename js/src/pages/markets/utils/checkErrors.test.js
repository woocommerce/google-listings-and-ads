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

		it( 'validates both countries and shipping for primary flat market', () => {
			const errors = checkErrors( {
				id: PRIMARY_MARKET_ID,
				countries: [],
				shipping_rate: SHIPPING_RATE_METHOD.FLAT,
				offer_free_shipping: true,
				free_shipping_threshold: null,
			} );

			expect( errors.countries ).toBeDefined();
			expect( errors.free_shipping_threshold ).toBeDefined();
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
		const base = {
			country: 'US',
			shipping_rate: SHIPPING_RATE_METHOD.FLAT,
			flat_shipping_rate: 10,
		};

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
			flat_shipping_rate: 10,
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
	} );

	describe( 'flat_shipping_rate validation', () => {
		const base = {
			country: 'US',
			shipping_rate: SHIPPING_RATE_METHOD.FLAT,
			offer_free_shipping: false,
			flat_shipping_min_time: 1,
			flat_shipping_max_time: 5,
		};

		it( 'returns an error when flat_shipping_rate is null', () => {
			const errors = checkErrors( {
				...base,
				flat_shipping_rate: null,
			} );

			expect( errors.flat_shipping_rate ).toBeDefined();
		} );

		it( 'returns an error when flat_shipping_rate is undefined', () => {
			const errors = checkErrors( { ...base } );

			expect( errors.flat_shipping_rate ).toBeDefined();
		} );

		it( 'returns an error when flat_shipping_rate is negative', () => {
			const errors = checkErrors( {
				...base,
				flat_shipping_rate: -1,
			} );

			expect( errors.flat_shipping_rate ).toBeDefined();
		} );

		it( 'returns no error when flat_shipping_rate is 0', () => {
			const errors = checkErrors( {
				...base,
				flat_shipping_rate: 0,
			} );

			expect( errors.flat_shipping_rate ).toBeUndefined();
		} );

		it( 'returns no error when flat_shipping_rate is a positive number', () => {
			const errors = checkErrors( {
				...base,
				flat_shipping_rate: 5,
			} );

			expect( errors.flat_shipping_rate ).toBeUndefined();
		} );
	} );

	describe( 'valid non-primary market', () => {
		it( 'returns an empty errors object for a fully valid submission', () => {
			const errors = checkErrors( {
				country: 'US',
				shipping_rate: SHIPPING_RATE_METHOD.FLAT,
				flat_shipping_rate: 5,
				offer_free_shipping: true,
				free_shipping_threshold: 50,
				shipping_time: 'flat',
				flat_shipping_min_time: 2,
				flat_shipping_max_time: 7,
			} );

			expect( errors ).toEqual( {} );
		} );
	} );

	describe( 'non-multilingual non-primary manual market', () => {
		beforeEach( () => {
			global.glaData.isMultiLingualStore = false;
		} );

		afterEach( () => {
			delete global.glaData.isMultiLingualStore;
		} );

		it( 'skips audience validation when country field is not shown', () => {
			const errors = checkErrors( {
				shipping_rate: SHIPPING_RATE_METHOD.MANUAL,
				country: null,
			} );

			expect( errors.country ).toBeUndefined();
		} );
	} );

	describe( 'multilingual store', () => {
		beforeEach( () => {
			global.glaData.isMultiLingualStore = true;
		} );

		afterEach( () => {
			delete global.glaData.isMultiLingualStore;
		} );

		it( 'returns a language error when language is empty for manual shipping', () => {
			const errors = checkErrors( {
				country: 'US',
				shipping_rate: SHIPPING_RATE_METHOD.MANUAL,
				language: [],
				currency: [ 'usd' ],
			} );

			expect( errors.language ).toBeDefined();
		} );

		it( 'returns a currency error when currency is empty for manual shipping', () => {
			const errors = checkErrors( {
				country: 'US',
				shipping_rate: SHIPPING_RATE_METHOD.MANUAL,
				language: [ 'en' ],
				currency: [],
			} );

			expect( errors.currency ).toBeDefined();
		} );

		it( 'returns a language error when language is empty for automatic shipping', () => {
			const errors = checkErrors( {
				country: 'US',
				shipping_rate: SHIPPING_RATE_METHOD.AUTOMATIC,
				language: [],
				currency: [ 'usd' ],
				flat_shipping_min_time: 1,
				flat_shipping_max_time: 5,
			} );

			expect( errors.language ).toBeDefined();
		} );

		it( 'returns a currency error when currency is empty for automatic shipping', () => {
			const errors = checkErrors( {
				country: 'US',
				shipping_rate: SHIPPING_RATE_METHOD.AUTOMATIC,
				language: [ 'en' ],
				currency: [],
				flat_shipping_min_time: 1,
				flat_shipping_max_time: 5,
			} );

			expect( errors.currency ).toBeDefined();
		} );

		it( 'skips locale validation for flat shipping', () => {
			const errors = checkErrors( {
				country: 'US',
				shipping_rate: SHIPPING_RATE_METHOD.FLAT,
				language: [],
				currency: [],
				offer_free_shipping: false,
				flat_shipping_min_time: 1,
				flat_shipping_max_time: 5,
			} );

			expect( errors.language ).toBeUndefined();
			expect( errors.currency ).toBeUndefined();
		} );

		it( 'returns no locale errors when language and currency are both set', () => {
			const errors = checkErrors( {
				country: 'US',
				shipping_rate: SHIPPING_RATE_METHOD.MANUAL,
				language: [ 'en' ],
				currency: [ 'usd' ],
			} );

			expect( errors.language ).toBeUndefined();
			expect( errors.currency ).toBeUndefined();
		} );

		it( 'returns a language error when language is missing (undefined)', () => {
			const errors = checkErrors( {
				country: 'US',
				shipping_rate: SHIPPING_RATE_METHOD.MANUAL,
				currency: [ 'usd' ],
			} );

			expect( errors.language ).toBeDefined();
		} );
	} );

	describe( 'MC supported-language validation', () => {
		beforeEach( () => {
			global.glaData.isMultiLingualStore = true;
		} );

		afterEach( () => {
			delete global.glaData.isMultiLingualStore;
		} );

		it( 'returns no error when all selected languages are supported by MC', () => {
			const errors = checkErrors( {
				country: 'US',
				shipping_rate: SHIPPING_RATE_METHOD.MANUAL,
				language: [ 'en', 'fr' ],
				currency: [ 'usd' ],
			} );

			expect( errors.language ).toBeUndefined();
		} );

		it( 'returns a language error when a selected language is not supported by MC', () => {
			const errors = checkErrors( {
				country: 'US',
				shipping_rate: SHIPPING_RATE_METHOD.MANUAL,
				language: [ 'xx' ],
				currency: [ 'usd' ],
			} );

			expect( errors.language ).toBeDefined();
			expect( errors.language ).toContain( 'xx' );
		} );

		it( 'lists only unsupported codes in the error, not supported ones', () => {
			const errors = checkErrors( {
				country: 'US',
				shipping_rate: SHIPPING_RATE_METHOD.MANUAL,
				language: [ 'en', 'xx', 'yy' ],
				currency: [ 'usd' ],
			} );

			expect( errors.language ).toBeDefined();
			expect( errors.language ).toContain( 'xx' );
			expect( errors.language ).toContain( 'yy' );
			// Use word-boundary regex so "en" in "Center" doesn't trigger a false failure.
			expect( errors.language ).not.toMatch( /\ben\b/ );
		} );

		it( 'also validates MC language support for flat-rate markets', () => {
			const errors = checkErrors( {
				country: 'US',
				shipping_rate: SHIPPING_RATE_METHOD.FLAT,
				language: [ 'xx' ],
				currency: [ 'usd' ],
				flat_shipping_rate: 5,
				offer_free_shipping: false,
				flat_shipping_min_time: 1,
				flat_shipping_max_time: 3,
			} );

			expect( errors.language ).toBeDefined();
			expect( errors.language ).toContain( 'xx' );
		} );

		it( 'skips MC check when language is empty (locale validation error takes precedence)', () => {
			const errors = checkErrors( {
				country: 'US',
				shipping_rate: SHIPPING_RATE_METHOD.MANUAL,
				language: [],
				currency: [ 'usd' ],
			} );

			// Locale error set first; MC check is skipped via the `! errors.language` guard.
			expect( errors.language ).toBeDefined();
			expect( errors.language ).not.toContain( 'not supported' );
		} );
	} );
} );
