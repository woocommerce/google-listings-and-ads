/**
 * Internal dependencies
 */
import groupShippingRatesByMethodCurrencyRate from './groupShippingRatesByMethodCurrencyRate';

describe( 'groupShippingRatesByMethodCurrencyRate', () => {
	it( 'should group the shipping rates based on method, currency and rate', () => {
		const shippingRates = [
			{
				id: '1',
				country: 'US',
				method: 'flat_rate',
				currency: 'USD',
				rate: 20,
				options: {},
			},
			{
				id: '2',
				country: 'AU',
				method: 'flat_rate',
				currency: 'USD',
				rate: 20,
				options: {},
			},
			{
				id: '3',
				country: 'CN',
				method: 'flat_rate',
				currency: 'USD',
				rate: 25,
				options: {},
			},
			{
				id: '4',
				country: 'BR',
				method: 'flat_rate',
				currency: 'BRL',
				rate: 20,
				options: {},
			},
		];

		const result = groupShippingRatesByMethodCurrencyRate( shippingRates );

		expect( result.length ).toEqual( 3 );
		expect( result[ 0 ] ).toStrictEqual( {
			countries: [ 'US', 'AU' ],
			currency: 'USD',
			rate: 20,
		} );
		expect( result[ 1 ] ).toStrictEqual( {
			countries: [ 'CN' ],
			currency: 'USD',
			rate: 25,
		} );
		expect( result[ 2 ] ).toStrictEqual( {
			countries: [ 'BR' ],
			currency: 'BRL',
			rate: 20,
		} );
	} );
} );
