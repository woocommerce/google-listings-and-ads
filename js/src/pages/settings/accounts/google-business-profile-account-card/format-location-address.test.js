/**
 * Internal dependencies
 */
import formatLocationAddress from './format-location-address';

describe( 'formatLocationAddress', () => {
	it( 'formats a full storefront address on one line', () => {
		expect(
			formatLocationAddress( {
				title: "Jane's Bakery",
				storefrontAddress: {
					addressLines: [ '2423 1st Ave' ],
					locality: 'Seattle',
					administrativeArea: 'WA',
					postalCode: '98121',
					regionCode: 'US',
				},
			} )
		).toBe( '2423 1st Ave, Seattle, WA 98121, US' );
	} );

	it( 'skips missing address parts', () => {
		expect(
			formatLocationAddress( {
				title: "Jane's Bakery",
				storefrontAddress: {
					addressLines: [ '123 Market St' ],
					locality: 'San Francisco',
					postalCode: '94103',
					regionCode: 'US',
				},
			} )
		).toBe( '123 Market St, San Francisco, 94103, US' );
	} );

	it( 'falls back to the business name when there is no storefront address', () => {
		expect( formatLocationAddress( { title: "Jane's Bakery" } ) ).toBe(
			"Jane's Bakery"
		);
	} );
} );
