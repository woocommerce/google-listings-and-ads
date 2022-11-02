/**
 * Internal dependencies
 */
import checkErrors from './checkErrors';

function toRates( ...tuples ) {
	return tuples.map( ( [ country, rate, threshold ] ) => ( {
		country,
		currency: 'USD',
		rate,
		options: {
			free_shipping_threshold: threshold,
		},
	} ) );
}

function toTimes( ...tuples ) {
	return tuples.map( ( [ countryCode, time ] ) => ( {
		countryCode,
		time,
	} ) );
}

const defaultFormValues = {
	shipping_country_rates: [],
};

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
			location: 'selected',
			countries: [ 'US', 'JP' ],
			shipping_rate: 'flat',
			shipping_time: 'flat',
			tax_rate: 'manual',
			shipping_country_rates: toRates( [ 'US', 10 ], [ 'JP', 30, 88 ] ),
			offer_free_shipping: true,
		};
		const times = toTimes( [ 'US', 3 ], [ 'JP', 10 ] );
		const codes = [ 'US', 'JP' ];

		const errors = checkErrors( values, times, codes );

		expect( errors ).toStrictEqual( {} );
	} );

	it( 'should indicate multiple unpassed checks by setting properties in the returned object', () => {
		const errors = checkErrors( defaultFormValues, [], [] );

		expect( errors ).toHaveProperty( 'shipping_rate' );
		expect( errors ).toHaveProperty( 'shipping_time' );
	} );

	describe( 'Audience', () => {
		it( 'When the audience location option is an invalid value or missing, should not pass', () => {
			// Not set yet
			let errors = checkErrors( {}, [], [] );

			expect( errors ).toHaveProperty( 'location' );
			expect( errors.location ).toMatchSnapshot();

			// Invalid value
			errors = checkErrors( { location: true }, [], [] );

			expect( errors ).toHaveProperty( 'location' );
			expect( errors.location ).toMatchSnapshot();
		} );

		it( 'When the audience location option is a valid value, should pass', () => {
			// Selected all countries
			let errors = checkErrors( { location: 'all' }, [], [] );

			expect( errors ).not.toHaveProperty( 'location' );

			// Selected "selected countries only"
			const values = {
				location: 'selected',
				countries: [],
			};
			errors = checkErrors( values, [], [] );

			expect( errors ).not.toHaveProperty( 'location' );
		} );

		it( `When the audience countries array is empty and the value of audience location option is 'selected', should not pass`, () => {
			const values = {
				location: 'selected',
				countries: [],
			};
			const errors = checkErrors( values, [], [] );

			expect( errors ).toHaveProperty( 'countries' );
			expect( errors.countries ).toMatchSnapshot();
		} );

		it( `When the audience countries array is not empty and the value of audience location option is 'selected', should pass`, () => {
			const values = {
				location: 'selected',
				countries: [ '' ],
			};
			const errors = checkErrors( values, [], [] );

			expect( errors ).not.toHaveProperty( 'countries' );
		} );
	} );

	describe( 'Shipping rates', () => {
		let automaticShipping;
		let flatShipping;
		let manualShipping;

		beforeEach( () => {
			automaticShipping = {
				...defaultFormValues,
				shipping_rate: 'automatic',
			};
			flatShipping = {
				...defaultFormValues,
				shipping_rate: 'flat',
			};
			manualShipping = { ...defaultFormValues, shipping_rate: 'manual' };
		} );

		it( 'When the type of shipping rate is an invalid value or missing, should not pass', () => {
			// Not set yet
			let errors = checkErrors( defaultFormValues, [], [] );

			expect( errors ).toHaveProperty( 'shipping_rate' );
			expect( errors.shipping_rate ).toMatchSnapshot();

			// Invalid value
			errors = checkErrors(
				{ ...defaultFormValues, shipping_rate: true },
				[],
				[]
			);

			expect( errors ).toHaveProperty( 'shipping_rate' );
			expect( errors.shipping_rate ).toMatchSnapshot();

			// Invalid value
			errors = checkErrors(
				{ ...defaultFormValues, shipping_rate: 'invalid' },
				[],
				[]
			);

			expect( errors ).toHaveProperty( 'shipping_rate' );
			expect( errors.shipping_rate ).toMatchSnapshot();
		} );

		it( 'When the type of shipping rate is a valid value, should pass', () => {
			// Selected automatic
			let errors = checkErrors( automaticShipping, [], [] );

			expect( errors ).not.toHaveProperty( 'shipping_rate' );

			// Selected flat
			errors = checkErrors( flatShipping, [], [] );

			expect( errors ).not.toHaveProperty( 'shipping_rate' );

			// Selected manual
			errors = checkErrors( manualShipping, [], [] );

			expect( errors ).not.toHaveProperty( 'shipping_rate' );
		} );

		describe( 'For flat type', () => {
			it( `When there are any selected countries with shipping rates not set, should not pass`, () => {
				const values = {
					...flatShipping,
					shipping_country_rates: toRates(
						[ 'US', 10.5 ],
						[ 'FR', 12.8 ]
					),
				};
				const codes = [ 'US', 'JP', 'FR' ];

				const errors = checkErrors( values, [], codes );

				expect( errors ).toHaveProperty( 'shipping_rate' );
				expect( errors.shipping_rate ).toMatchSnapshot();
			} );

			it( `When all selected countries' shipping rates are set, should pass`, () => {
				const values = {
					...flatShipping,
					shipping_country_rates: toRates(
						[ 'US', 10.5 ],
						[ 'FR', 12.8 ]
					),
				};
				const codes = [ 'US', 'FR' ];

				const errors = checkErrors( values, [], codes );

				expect( errors ).not.toHaveProperty( 'shipping_rate' );
			} );

			it( `When there are any shipping rates is < 0, should not pass`, () => {
				const values = {
					...flatShipping,
					shipping_country_rates: toRates(
						[ 'US', 10.5 ],
						[ 'JP', -0.01 ]
					),
				};
				const codes = [ 'US', 'JP' ];

				const errors = checkErrors( values, [], codes );

				expect( errors ).toHaveProperty( 'shipping_rate' );
				expect( errors.shipping_rate ).toMatchSnapshot();
			} );

			it( `When all shipping rates are ≥ 0, should pass`, () => {
				const values = {
					...flatShipping,
					shipping_country_rates: toRates( [ 'US', 1 ], [ 'JP', 0 ] ),
				};
				const codes = [ 'US', 'JP' ];

				const errors = checkErrors( values, [], codes );

				expect( errors ).not.toHaveProperty( 'shipping_rate' );
			} );
		} );
	} );

	describe( 'Offer free shipping', () => {
		describe( 'With flat shipping rate option', () => {
			it( 'When all shipping rates are free, and offer free shipping is undefined, should pass', () => {
				const values = {
					...defaultFormValues,
					shipping_rate: 'flat',
					shipping_country_rates: toRates( [ 'US', 0 ], [ 'JP', 0 ] ),
					offer_free_shipping: undefined,
				};
				const codes = [ 'US', 'JP' ];

				const errors = checkErrors( values, [], codes );

				expect( errors ).not.toHaveProperty( 'offer_free_shipping' );
			} );

			it( 'When there are some non-free shipping rates, and offer free shipping is unchecked, should not pass', () => {
				const values = {
					...defaultFormValues,
					shipping_rate: 'flat',
					shipping_country_rates: toRates( [ 'US', 0 ], [ 'JP', 1 ] ),
					offer_free_shipping: undefined,
				};
				const codes = [ 'US', 'JP' ];

				const errors = checkErrors( values, [], codes );

				expect( errors ).toHaveProperty( 'offer_free_shipping' );
			} );

			it( 'When there are some non-free shipping rates, and offer free shipping is checked, and there is minimum order amount for non-free shipping rates, should pass', () => {
				const values = {
					...defaultFormValues,
					shipping_rate: 'flat',
					shipping_country_rates: toRates(
						[ 'US', 0 ],
						[ 'JP', 1, 88 ]
					),
					offer_free_shipping: true,
				};
				const codes = [ 'US', 'JP' ];

				const errors = checkErrors( values, [], codes );

				expect( errors ).not.toHaveProperty( 'offer_free_shipping' );
			} );

			it( 'When there are some non-free shipping rates, and offer free shipping is checked, and there is no minimum order amount for non-free shipping rates, should not pass', () => {
				const values = {
					...defaultFormValues,
					shipping_rate: 'flat',
					shipping_country_rates: toRates( [ 'US', 0 ], [ 'JP', 1 ] ),
					offer_free_shipping: true,
				};
				const codes = [ 'US', 'JP' ];

				const errors = checkErrors( values, [], codes );

				expect( errors ).toHaveProperty( 'offer_free_shipping' );
			} );
		} );

		describe( 'With non-flat shipping rate option', () => {
			it( 'When there are some non-free shipping rates, and offer free shipping is unchecked, should pass', () => {
				const values = {
					...defaultFormValues,
					shipping_rate: 'automatic',
					shipping_country_rates: toRates( [ 'US', 0 ], [ 'JP', 1 ] ),
					offer_free_shipping: undefined,
				};
				const codes = [ 'US', 'JP' ];

				const errors = checkErrors( values, [], codes );

				expect( errors ).not.toHaveProperty( 'offer_free_shipping' );
			} );

			it( 'When there are some non-free shipping rates, and offer free shipping is checked, and there is no minimum order amount for non-free shipping rates, should pass', () => {
				const values = {
					...defaultFormValues,
					shipping_rate: 'manual',
					shipping_country_rates: toRates( [ 'US', 0 ], [ 'JP', 1 ] ),
					offer_free_shipping: true,
				};
				const codes = [ 'US', 'JP' ];

				const errors = checkErrors( values, [], codes );

				expect( errors ).not.toHaveProperty( 'offer_free_shipping' );
			} );
		} );
	} );

	describe( 'Shipping times', () => {
		let flatShipping;
		let manualShipping;

		beforeEach( () => {
			flatShipping = { ...defaultFormValues, shipping_time: 'flat' };
			manualShipping = { ...defaultFormValues, shipping_time: 'manual' };
		} );

		it( 'When the type of shipping time is an invalid value or missing, should not pass', () => {
			// Not set yet
			let errors = checkErrors( defaultFormValues, [], [] );

			expect( errors ).toHaveProperty( 'shipping_time' );
			expect( errors.shipping_time ).toMatchSnapshot();

			// Invalid value
			errors = checkErrors(
				{ ...defaultFormValues, shipping_time: true },
				[],
				[]
			);

			expect( errors ).toHaveProperty( 'shipping_time' );
			expect( errors.shipping_time ).toMatchSnapshot();

			// Invalid value
			errors = checkErrors(
				{ ...defaultFormValues, shipping_time: 'invalid' },
				[],
				[]
			);

			expect( errors ).toHaveProperty( 'shipping_time' );
			expect( errors.shipping_time ).toMatchSnapshot();
		} );

		it( 'When the type of shipping time is a valid value, should pass', () => {
			// Selected flat
			let errors = checkErrors( flatShipping, [], [] );

			expect( errors ).not.toHaveProperty( 'shipping_time' );

			// Selected manual
			errors = checkErrors( manualShipping, [], [] );

			expect( errors ).not.toHaveProperty( 'shipping_time' );
		} );

		describe( 'For flat type', () => {
			it( `When there are any selected countries' shipping times is not set, should not pass`, () => {
				const times = toTimes( [ 'US', 7 ], [ 'FR', 16 ] );
				const codes = [ 'US', 'JP', 'FR' ];

				const errors = checkErrors( flatShipping, times, codes );

				expect( errors ).toHaveProperty( 'shipping_time' );
				expect( errors.shipping_time ).toMatchSnapshot();
			} );

			it( `When all selected countries' shipping times are set, should pass`, () => {
				const times = toTimes( [ 'US', 7 ], [ 'FR', 16 ] );
				const codes = [ 'US', 'FR' ];

				const errors = checkErrors( flatShipping, times, codes );

				expect( errors ).not.toHaveProperty( 'shipping_time' );
			} );

			it( `When there are any shipping times is < 0, should not pass`, () => {
				const times = toTimes( [ 'US', 10 ], [ 'JP', -1 ] );
				const codes = [ 'US', 'JP' ];

				const errors = checkErrors( flatShipping, times, codes );

				expect( errors ).toHaveProperty( 'shipping_time' );
				expect( errors.shipping_time ).toMatchSnapshot();
			} );

			it( `When all shipping times are ≥ 0, should pass`, () => {
				const times = toTimes( [ 'US', 1 ], [ 'JP', 0 ] );
				const codes = [ 'US', 'JP' ];

				const errors = checkErrors( flatShipping, times, codes );

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
			let errors = checkErrors( defaultFormValues, [], codes );

			expect( errors ).toHaveProperty( 'tax_rate' );
			expect( errors.tax_rate ).toMatchSnapshot();

			// Invalid value
			errors = checkErrors(
				{ ...defaultFormValues, tax_rate: true },
				[],
				codes
			);

			expect( errors ).toHaveProperty( 'tax_rate' );
			expect( errors.tax_rate ).toMatchSnapshot();

			// Invalid value
			errors = checkErrors(
				{ ...defaultFormValues, tax_rate: 'invalid' },
				[],
				codes
			);

			expect( errors ).toHaveProperty( 'tax_rate' );
			expect( errors.tax_rate ).toMatchSnapshot();
		} );

		it( 'When the tax rate option is a valid value, should pass', () => {
			// Selected destination
			const destinationTaxRate = {
				...defaultFormValues,
				tax_rate: 'destination',
			};

			let errors = checkErrors( destinationTaxRate, [], codes );

			expect( errors ).not.toHaveProperty( 'tax_rate' );

			// Selected manual
			const manualTaxRate = { ...defaultFormValues, tax_rate: 'manual' };

			errors = checkErrors( manualTaxRate, [], codes );

			expect( errors ).not.toHaveProperty( 'tax_rate' );
		} );
	} );
} );
