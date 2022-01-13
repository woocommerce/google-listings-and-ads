/**
 * Internal dependencies
 */
import groupShippingRatesByPriceCurrency from './groupShippingRatesByPriceCurrency';

describe( 'groupShippingRatesByPriceCurrency', () => {
	it( 'should group the shipping rates based on price and currency', () => {
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
			{
				countryCode: 'BR',
				currency: 'BRL',
				rate: 20,
			},
		];

		const result = groupShippingRatesByPriceCurrency( shippingRates );

		expect( result.length ).toEqual( 3 );
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
		expect( result[ 2 ] ).toStrictEqual( {
			countries: [ 'BR' ],
			price: 20,
			currency: 'BRL',
		} );
	} );
} );
