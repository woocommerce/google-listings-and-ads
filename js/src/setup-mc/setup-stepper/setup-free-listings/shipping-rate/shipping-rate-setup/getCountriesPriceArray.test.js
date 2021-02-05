/**
 * Internal dependencies
 */
import getCountriesPriceArray from './getCountriesPriceArray';

describe( 'getCountriesPriceArray', () => {
	it( 'should group the shipping rates based on price', () => {
		const shippingRates = [
			{
				countryCode: 'US',
				currency: 'USD',
				rate: 20,
			},
			{
				countryCode: 'AU',
				currency: 'USD',
				rate: 20,
			},
			{
				countryCode: 'CN',
				currency: 'USD',
				rate: 25,
			},
		];

		const result = getCountriesPriceArray( shippingRates );

		expect( result.length ).toEqual( 2 );
		expect( result[ 0 ] ).toStrictEqual( {
			countries: [ 'US', 'AU' ],
			price: 20,
			currency: 'USD',
		} );
		expect( result[ 1 ] ).toStrictEqual( {
			countries: [ 'CN' ],
			price: 25,
			currency: 'USD',
		} );
	} );
} );
