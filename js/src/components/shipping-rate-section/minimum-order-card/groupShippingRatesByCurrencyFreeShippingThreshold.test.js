/**
 * Internal dependencies
 */
import groupShippingRatesByCurrencyFreeShippingThreshold from './groupShippingRatesByCurrencyFreeShippingThreshold';

describe( 'groupShippingRatesByCurrencyFreeShippingThreshold', () => {
	it( 'should group the shipping rates based on currency and rate', () => {
		const shippingRates = [
			{
				id: '1',
				country: 'US',
				currency: 'USD',
				rate: 20,
				options: {},
			},
			{
				id: '2',
				country: 'AU',
				currency: 'USD',
				rate: 20,
				options: {
					free_shipping_threshold: 100,
				},
			},
			{
				id: '3',
				country: 'CN',
				currency: 'USD',
				rate: 25,
				options: {
					free_shipping_threshold: 100,
				},
			},
			{
				id: '4',
				country: 'BR',
				currency: 'USD',
				rate: 20,
				options: {
					free_shipping_threshold: 250,
				},
			},
		];

		const result = groupShippingRatesByCurrencyFreeShippingThreshold(
			shippingRates
		);

		expect( result.length ).toEqual( 3 );
		expect( result[ 0 ] ).toStrictEqual( {
			countries: [ 'US' ],
			currency: 'USD',
			threshold: undefined,
		} );
		expect( result[ 1 ] ).toStrictEqual( {
			countries: [ 'AU', 'CN' ],
			currency: 'USD',
			threshold: 100,
		} );
		expect( result[ 2 ] ).toStrictEqual( {
			countries: [ 'BR' ],
			currency: 'USD',
			threshold: 250,
		} );
	} );

	it( 'should group the shipping rates based on currency and rate - rare edge case with different currencies', () => {
		const shippingRates = [
			{
				id: '1',
				country: 'US',
				currency: 'USD',
				rate: 20,
				options: {},
			},
			{
				id: '2',
				country: 'AU',
				currency: 'USD',
				rate: 20,
				options: {
					free_shipping_threshold: 100,
				},
			},
			{
				id: '3',
				country: 'CN',
				currency: 'MYR',
				rate: 25,
				options: {
					free_shipping_threshold: 100,
				},
			},
			{
				id: '4',
				country: 'BR',
				currency: 'MYR',
				rate: 20,
				options: {
					free_shipping_threshold: 100,
				},
			},
		];

		const result = groupShippingRatesByCurrencyFreeShippingThreshold(
			shippingRates
		);

		expect( result.length ).toEqual( 3 );
		expect( result[ 0 ] ).toStrictEqual( {
			countries: [ 'US' ],
			currency: 'USD',
			threshold: undefined,
		} );
		expect( result[ 1 ] ).toStrictEqual( {
			countries: [ 'AU' ],
			currency: 'USD',
			threshold: 100,
		} );
		expect( result[ 2 ] ).toStrictEqual( {
			countries: [ 'CN', 'BR' ],
			currency: 'MYR',
			threshold: 100,
		} );
	} );
} );
