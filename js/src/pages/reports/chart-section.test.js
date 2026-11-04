/**
 * Internal dependencies
 */
import { generateDateRange } from './chart-section';

describe( 'generateDateRange', () => {
	it( 'returns every day between start and end, inclusive', () => {
		expect( generateDateRange( '2026-10-30', '2026-11-02' ) ).toEqual( [
			'2026-10-30',
			'2026-10-31',
			'2026-11-01',
			'2026-11-02',
		] );
	} );

	it( 'returns a single day when start and end are equal', () => {
		expect( generateDateRange( '2026-11-01', '2026-11-01' ) ).toEqual( [
			'2026-11-01',
		] );
	} );
} );
