/**
 * Internal dependencies
 */
import { generateDateRange, getChartDates } from './chart-section';

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

describe( 'getChartDates', () => {
	const query = {
		period: 'custom',
		compare: 'previous_year',
		after: '2026-10-01',
		before: '2026-10-04',
	};

	it( 'covers the whole selected range, including days without data at either end', () => {
		const intervals = [
			{ interval: '2026-10-02' },
			{ interval: '2026-10-03' },
		];

		expect( getChartDates( intervals, query ) ).toEqual( [
			'2026-10-01',
			'2026-10-02',
			'2026-10-03',
			'2026-10-04',
		] );
	} );

	it( 'widens the range when an interval falls outside it', () => {
		const intervals = [
			{ interval: '2026-09-30' },
			{ interval: '2026-10-05' },
		];

		expect( getChartDates( intervals, query ) ).toEqual( [
			'2026-09-30',
			'2026-10-01',
			'2026-10-02',
			'2026-10-03',
			'2026-10-04',
			'2026-10-05',
		] );
	} );
} );
