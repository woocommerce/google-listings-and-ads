/**
 * Internal dependencies
 */
import canViewReports from './canViewReports';

describe( 'canViewReports', () => {
	it.each( [
		[ 'Merchant Center only', true, false, true, true ],
		[ 'Ads only', true, true, false, true ],
		[ 'Merchant Center and Ads', true, true, true, true ],
		[ 'neither', true, false, false, false ],
		[ 'reports disabled', false, true, true, false ],
	] )(
		'%s: returns the expected value',
		( name, enableReports, adsSetupComplete, hasMC, expected ) => {
			expect(
				canViewReports( { enableReports, adsSetupComplete, hasMC } )
			).toBe( expected );
		}
	);

	it( 'returns false when the values are missing', () => {
		expect( canViewReports( {} ) ).toBe( false );
	} );
} );
