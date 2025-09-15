/**
 * Internal dependencies
 */
import formatNumber from './formatNumber';

describe( 'formatNumber', () => {
	it( 'formats numbers less than 1000 as-is', () => {
		expect( formatNumber( 0 ) ).toBe( '0' );
		expect( formatNumber( 5 ) ).toBe( '5' );
		expect( formatNumber( 999 ) ).toBe( '999' );
	} );

	it( 'formats numbers in thousands with "K"', () => {
		expect( formatNumber( 1000 ) ).toMatch( /1(\.0)?K/ );
		expect( formatNumber( 1500 ) ).toMatch( /1\.5K/ );
		expect( formatNumber( 999999 ) ).toMatch( /1M/ );
	} );

	it( 'formats numbers in millions with "M"', () => {
		expect( formatNumber( 1000000 ) ).toMatch( /1(\.0)?M/ );
		expect( formatNumber( 2500000 ) ).toMatch( /2\.5M/ );
		expect( formatNumber( 9999999 ) ).toMatch( /10M|9\.9M/ );
	} );

	it( 'formats negative numbers correctly', () => {
		expect( formatNumber( -500 ) ).toBe( '-500' );
		expect( formatNumber( -1500 ) ).toMatch( /-1\.5K/ );
		expect( formatNumber( -2000000 ) ).toMatch( /-2M/ );
	} );

	it( 'formats decimal numbers correctly', () => {
		expect( formatNumber( 1234.56 ) ).toMatch( /1\.2K/ );
		expect( formatNumber( 1000000.5 ) ).toMatch( /1M|1\.0M/ );
	} );

	it( 'falls back to manual formatting if Intl.NumberFormat is unavailable', () => {
		const originalIntl = global.Intl;
		global.Intl = undefined;
		expect( formatNumber( 1500 ) ).toBe( '1.5k' );
		expect( formatNumber( 2000000 ) ).toBe( '2M' );
		global.Intl = originalIntl;
	} );

	it( 'handles edge cases', () => {
		expect( formatNumber( NaN ) ).toBe( 'NaN' );
		expect( formatNumber( Infinity ) ).toBe( '∞' );
		expect( formatNumber( -Infinity ) ).toBe( '-∞' );
	} );
} );
