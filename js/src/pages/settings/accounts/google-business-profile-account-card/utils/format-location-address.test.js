/**
 * Internal dependencies
 */
import formatLocationAddress from './format-location-address';

describe( 'formatLocationAddress', () => {
	it( 'returns the address', () => {
		expect(
			formatLocationAddress( {
				title: "Jane's Bakery",
				address: '2423 1st Ave, Seattle, WA, 98121',
			} )
		).toBe( '2423 1st Ave, Seattle, WA, 98121' );
	} );

	it( 'falls back to the business name when there is no address', () => {
		expect(
			formatLocationAddress( { title: "Jane's Bakery", address: '' } )
		).toBe( "Jane's Bakery" );
	} );
} );
