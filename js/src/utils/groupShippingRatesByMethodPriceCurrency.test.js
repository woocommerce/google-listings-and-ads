/**
 * Internal dependencies
 */
import groupShippingRatesByMethodPriceCurrency from './groupShippingRatesByMethodPriceCurrency';

describe( 'groupShippingRatesByPriceCurrency', () => {
	it( 'should group the shipping rates based on method, price and currency', () => {
		const shippingRates = [
			{
				id: '1',
				country: 'US',
				method: 'flat_rate',
				currency: 'USD',
				rate: 20,
				options: [],
			},
			{
				id: '2',
				country: 'AU',
				method: 'flat_rate',
				currency: 'USD',
				rate: 20,
				options: [],
			},
			{
				id: '3',
				country: 'CN',
				method: 'flat_rate',
				currency: 'USD',
				rate: 25,
				options: [],
			},
			{
				id: '4',
				country: 'BR',
				method: 'flat_rate',
				currency: 'BRL',
				rate: 20,
				options: [],
			},
		];

		const result = groupShippingRatesByMethodPriceCurrency( shippingRates );

		expect( result.length ).toEqual( 3 );
		expect( result[ 0 ] ).toStrictEqual( {
			countries: [ 'US', 'AU' ],
			method: 'flat_rate',
			price: 20,
			currency: 'USD',
		} );
		expect( result[ 1 ] ).toStrictEqual( {
			countries: [ 'CN' ],
			method: 'flat_rate',
			price: 25,
			currency: 'USD',
		} );
		expect( result[ 2 ] ).toStrictEqual( {
			countries: [ 'BR' ],
			method: 'flat_rate',
			price: 20,
			currency: 'BRL',
		} );
	} );
} );
