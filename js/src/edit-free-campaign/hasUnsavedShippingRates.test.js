/**
 * External dependencies
 */
import { cloneDeep, set } from 'lodash';

/**
 * Internal dependencies
 */
import { SHIPPING_RATE_METHOD } from '.~/constants';
import hasUnsavedShippingRates from './hasUnsavedShippingRates';

const method = SHIPPING_RATE_METHOD.FLAT_RATE;

describe( 'hasUnsavedShippingRates', () => {
	let savedRates;
	let rates;

	beforeEach( () => {
		savedRates = [
			{
				id: '1',
				country: 'US',
				rate: 12.34,
				currency: 'USD',
				options: { free_shipping_threshold: 1000 },
				method,
			},
			{
				id: '2',
				country: 'JP',
				rate: 4900,
				currency: 'JPY',
				options: { free_shipping_threshold: 2000 },
				method,
			},
		];
		rates = cloneDeep( savedRates );
	} );

	it( 'when both shipping rates are the same, should return false', () => {
		expect( hasUnsavedShippingRates( rates, savedRates ) ).toBe( false );
	} );

	it( 'when the `id` of shipping rates are different, should still return false', () => {
		delete rates[ 0 ].id;
		delete savedRates[ 1 ].id;

		expect( hasUnsavedShippingRates( rates, savedRates ) ).toBe( false );
	} );

	it( 'when the `options.free_shipping_threshold` property of one of the shipping rates does not exist and the other is `undefined`, should still return false', () => {
		rates[ 0 ].options.free_shipping_threshold = undefined;
		savedRates[ 0 ].options = {};

		expect( hasUnsavedShippingRates( rates, savedRates ) ).toBe( false );
	} );

	it( 'the comparison is not sequential, two same sets of shipping rates should return false', () => {
		rates.reverse();

		expect( hasUnsavedShippingRates( rates, savedRates ) ).toBe( false );
	} );

	it( 'when there are deleted shipping rates, should return true', () => {
		rates.pop();

		expect( hasUnsavedShippingRates( rates, savedRates ) ).toBe( true );
	} );

	it( 'when there are new shipping rates, should return true', () => {
		rates.push( {} );

		expect( hasUnsavedShippingRates( rates, savedRates ) ).toBe( true );
	} );

	// (Test each property in an atomical way.)
	it.each( [
		[ 'currency', 'AUD' ],
		[ 'rate', 50.88 ],
		[ 'country', 'AU' ],
		[ 'options', {} ],
		[ 'options.free_shipping_threshold', undefined ],
		[ 'options.free_shipping_threshold', 5000 ],
	] )(
		'when the property `%s` is edited, for example to %s, should return true',
		( path, value ) => {
			set( rates[ 0 ], path, value );

			expect( hasUnsavedShippingRates( rates, savedRates ) ).toBe( true );
		}
	);
} );
