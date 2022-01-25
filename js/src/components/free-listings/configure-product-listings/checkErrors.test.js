/**
 * Internal dependencies
 */
import checkErrors from './checkErrors';

function toRates( ...tuples ) {
	return tuples.map( ( [ countryCode, rate ] ) => ( {
		countryCode,
		rate,
	} ) );
}

function toTimes( ...tuples ) {
	return tuples.map( ( [ countryCode, time ] ) => ( {
		countryCode,
		time,
	} ) );
}

/**
 * checkErrors function returns an object, and if any checks are not passed,
 * set properties respectively with an error message to indicate it.
 *
 * To test each cases separately, the meaning of "should (not) pass" in test descriptions are:
 * should pass - returned object should not contain respective property.
 * should not pass - returned object should contain respective property with an error message.
 */
describe( 'checkErrors', () => {
	it( 'When all checks are passed, should return an empty object', () => {
		const values = {
			shipping_rate: 'flat',
			shipping_time: 'flat',
			offers_free_shipping: true,
			free_shipping_threshold: 100,
			tax_rate: 'manual',
		};
		const rates = toRates( [ 'US', 10 ], [ 'JP', 30 ] );
		const times = toTimes( [ 'US', 3 ], [ 'JP', 10 ] );
		const codes = [ 'US', 'JP' ];

		const errors = checkErrors( values, rates, times, codes );

		expect( errors ).toStrictEqual( {} );
	} );

	it( 'should indicate multiple unpassed checks by setting properties in the returned object', () => {
		const errors = checkErrors( {}, [], [], [] );

		expect( errors ).toHaveProperty( 'shipping_rate' );
		expect( errors ).toHaveProperty( 'shipping_time' );
	} );

	describe( 'Shipping rates', () => {
		let automaticShipping;
		let flatShipping;
		let manualShipping;

		beforeEach( () => {
			automaticShipping = { shipping_rate: 'automatic' };
			flatShipping = { shipping_rate: 'flat' };
			manualShipping = { shipping_rate: 'manual' };
		} );

		it( 'When the type of shipping rate is an invalid value or missing, should not pass', () => {
			// Not set yet
			let errors = checkErrors( {}, [], [], [] );

			expect( errors ).toHaveProperty( 'shipping_rate' );
			expect( errors.shipping_rate ).toMatchSnapshot();

			// Invalid value
			errors = checkErrors( { shipping_rate: true }, [], [], [] );

			expect( errors ).toHaveProperty( 'shipping_rate' );
			expect( errors.shipping_rate ).toMatchSnapshot();

			// Invalid value
			errors = checkErrors( { shipping_rate: 'invalid' }, [], [], [] );

			expect( errors ).toHaveProperty( 'shipping_rate' );
			expect( errors.shipping_rate ).toMatchSnapshot();
		} );

		it( 'When the type of shipping rate is a valid value, should pass', () => {
			// Selected automatic
			let errors = checkErrors( automaticShipping, [], [], [] );

			expect( errors ).not.toHaveProperty( 'shipping_rate' );

			// Selected flat
			errors = checkErrors( flatShipping, [], [], [] );

			expect( errors ).not.toHaveProperty( 'shipping_rate' );

			// Selected manual
			errors = checkErrors( manualShipping, [], [], [] );

			expect( errors ).not.toHaveProperty( 'shipping_rate' );
		} );

		describe( 'For flat type', () => {
			function createFreeShipping( threshold ) {
				return {
					...flatShipping,
					offers_free_shipping: true,
					free_shipping_threshold: threshold,
				};
			}

			it( `When there are any selected countries with shipping rates not set, should not pass`, () => {
				const rates = toRates( [ 'US', 10.5 ], [ 'FR', 12.8 ] );
				const codes = [ 'US', 'JP', 'FR' ];

				const errors = checkErrors( flatShipping, rates, [], codes );

				expect( errors ).toHaveProperty( 'shipping_rate' );
				expect( errors.shipping_rate ).toMatchSnapshot();
			} );

			it( `When all selected countries' shipping rates are set, should pass`, () => {
				const rates = toRates( [ 'US', 10.5 ], [ 'FR', 12.8 ] );
				const codes = [ 'US', 'FR' ];

				const errors = checkErrors( flatShipping, rates, [], codes );

				expect( errors ).not.toHaveProperty( 'shipping_rate' );
			} );

			it( `When there are any shipping rates is < 0, should not pass`, () => {
				const rates = toRates( [ 'US', 10.5 ], [ 'JP', -0.01 ] );
				const codes = [ 'US', 'JP' ];

				const errors = checkErrors( flatShipping, rates, [], codes );

				expect( errors ).toHaveProperty( 'shipping_rate' );
				expect( errors.shipping_rate ).toMatchSnapshot();
			} );

			it( `When all shipping rates are ≥ 0, should pass`, () => {
				const rates = toRates( [ 'US', 1 ], [ 'JP', 0 ] );
				const codes = [ 'US', 'JP' ];

				const errors = checkErrors( flatShipping, rates, [], codes );

				expect( errors ).not.toHaveProperty( 'shipping_rate' );
			} );

			it( 'When the free shipping threshold is an invalid value or missing, should not pass', () => {
				// Not set yet
				// When set up from scratch, the initial value of `free_shipping_threshold` is null.
				let freeShipping = createFreeShipping( null );
				const rates = toRates( [ 'JP', 2 ] );
				const codes = [ 'JP' ];

				let errors = checkErrors( freeShipping, rates, [], codes );

				expect( errors ).toHaveProperty( 'free_shipping_threshold' );
				expect( errors.free_shipping_threshold ).toMatchSnapshot();

				// Invalid value
				freeShipping = createFreeShipping( true );

				errors = checkErrors( freeShipping, rates, [], codes );

				expect( errors ).toHaveProperty( 'free_shipping_threshold' );
				expect( errors.free_shipping_threshold ).toMatchSnapshot();

				// Invalid range
				freeShipping = createFreeShipping( -0.01 );

				errors = checkErrors( freeShipping, rates, [], codes );

				expect( errors ).toHaveProperty( 'free_shipping_threshold' );
				expect( errors.free_shipping_threshold ).toMatchSnapshot();
			} );

			it( 'When the free shipping threshold ≥ 0, should pass', () => {
				// Free threshold is 0
				let freeShipping = createFreeShipping( 0 );
				const rates = toRates( [ 'JP', 2 ] );
				const codes = [ 'JP' ];

				let errors = checkErrors( freeShipping, rates, [], codes );

				expect( errors ).not.toHaveProperty(
					'free_shipping_threshold'
				);

				// Free threshold is a positive number
				freeShipping = createFreeShipping( 0.01 );

				errors = checkErrors( freeShipping, rates, [], codes );

				expect( errors ).not.toHaveProperty(
					'free_shipping_threshold'
				);
			} );
		} );
	} );

	describe( 'Shipping times', () => {
		let flatShipping;
		let manualShipping;

		beforeEach( () => {
			flatShipping = { shipping_time: 'flat' };
			manualShipping = { shipping_time: 'manual' };
		} );

		it( 'When the type of shipping time is an invalid value or missing, should not pass', () => {
			// Not set yet
			let errors = checkErrors( {}, [], [], [] );

			expect( errors ).toHaveProperty( 'shipping_time' );
			expect( errors.shipping_time ).toMatchSnapshot();

			// Invalid value
			errors = checkErrors( { shipping_time: true }, [], [], [] );

			expect( errors ).toHaveProperty( 'shipping_time' );
			expect( errors.shipping_time ).toMatchSnapshot();

			// Invalid value
			errors = checkErrors( { shipping_time: 'invalid' }, [], [], [] );

			expect( errors ).toHaveProperty( 'shipping_time' );
			expect( errors.shipping_time ).toMatchSnapshot();
		} );

		it( 'When the type of shipping time is a valid value, should pass', () => {
			// Selected flat
			let errors = checkErrors( flatShipping, [], [], [] );

			expect( errors ).not.toHaveProperty( 'shipping_time' );

			// Selected manual
			errors = checkErrors( manualShipping, [], [], [] );

			expect( errors ).not.toHaveProperty( 'shipping_time' );
		} );

		describe( 'For flat type', () => {
			it( `When there are any selected countries' shipping times is not set, should not pass`, () => {
				const times = toTimes( [ 'US', 7 ], [ 'FR', 16 ] );
				const codes = [ 'US', 'JP', 'FR' ];

				const errors = checkErrors( flatShipping, [], times, codes );

				expect( errors ).toHaveProperty( 'shipping_time' );
				expect( errors.shipping_time ).toMatchSnapshot();
			} );

			it( `When all selected countries' shipping times are set, should pass`, () => {
				const times = toTimes( [ 'US', 7 ], [ 'FR', 16 ] );
				const codes = [ 'US', 'FR' ];

				const errors = checkErrors( flatShipping, [], times, codes );

				expect( errors ).not.toHaveProperty( 'shipping_time' );
			} );

			it( `When there are any shipping times is < 0, should not pass`, () => {
				const times = toTimes( [ 'US', 10 ], [ 'JP', -1 ] );
				const codes = [ 'US', 'JP' ];

				const errors = checkErrors( flatShipping, [], times, codes );

				expect( errors ).toHaveProperty( 'shipping_time' );
				expect( errors.shipping_time ).toMatchSnapshot();
			} );

			it( `When all shipping times are ≥ 0, should pass`, () => {
				const times = toTimes( [ 'US', 1 ], [ 'JP', 0 ] );
				const codes = [ 'US', 'JP' ];

				const errors = checkErrors( flatShipping, [], times, codes );

				expect( errors ).not.toHaveProperty( 'shipping_time' );
			} );
		} );
	} );

	describe( `For tax rate, if selected country codes include 'US'`, () => {
		let codes;

		beforeEach( () => {
			codes = [ 'US' ];
		} );

		it( `When the tax rate option is an invalid value or missing, should not pass`, () => {
			// Not set yet
			let errors = checkErrors( {}, [], [], codes );

			expect( errors ).toHaveProperty( 'tax_rate' );
			expect( errors.tax_rate ).toMatchSnapshot();

			// Invalid value
			errors = checkErrors( { tax_rate: true }, [], [], codes );

			expect( errors ).toHaveProperty( 'tax_rate' );
			expect( errors.tax_rate ).toMatchSnapshot();

			// Invalid value
			errors = checkErrors( { tax_rate: 'invalid' }, [], [], codes );

			expect( errors ).toHaveProperty( 'tax_rate' );
			expect( errors.tax_rate ).toMatchSnapshot();
		} );

		it( 'When the tax rate option is a valid value, should pass', () => {
			// Selected destination
			const destinationTaxRate = { tax_rate: 'destination' };

			let errors = checkErrors( destinationTaxRate, [], [], codes );

			expect( errors ).not.toHaveProperty( 'tax_rate' );

			// Selected manual
			const manualTaxRate = { tax_rate: 'manual' };

			errors = checkErrors( manualTaxRate, [], [], codes );

			expect( errors ).not.toHaveProperty( 'tax_rate' );
		} );
	} );
} );
