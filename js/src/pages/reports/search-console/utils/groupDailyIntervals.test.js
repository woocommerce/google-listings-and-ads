/**
 * Internal dependencies
 */
import groupDailyIntervals from './groupDailyIntervals';

const day = ( interval, clicks, impressions, position ) => {
	return {
		interval,
		subtotals: {
			clicks,
			impressions,
			ctr: impressions ? clicks / impressions : null,
			position: impressions ? position : null,
		},
	};
};

const noData = { clicks: 0, impressions: 0, ctr: null, position: null };

describe( 'groupDailyIntervals', () => {
	it( 'returns no intervals when there is no data', () => {
		expect(
			groupDailyIntervals( [], { after: '2026-10-01', interval: 'day' } )
		).toEqual( [] );
	} );

	it( 'fills the missing days from the start of the period with zeros and keeps the days with data as returned', () => {
		const thirdDay = day( '2026-10-03', 5, 50, 3.2 );

		expect(
			groupDailyIntervals( [ thirdDay ], {
				after: '2026-10-01',
				interval: 'day',
			} )
		).toEqual( [
			{ interval: '2026-10-01', subtotals: noData },
			{ interval: '2026-10-02', subtotals: noData },
			thirdDay,
		] );
	} );

	it( 'leaves out the days after the last one with data', () => {
		const result = groupDailyIntervals(
			[ day( '2026-10-01', 1, 10, 2 ), day( '2026-10-02', 1, 10, 2 ) ],
			{ after: '2026-10-01', interval: 'day' }
		);

		expect( result.map( ( { interval } ) => interval ) ).toEqual( [
			'2026-10-01',
			'2026-10-02',
		] );
	} );

	it( 'groups days into weeks starting on the given day, with the first week starting on the first day of the period', () => {
		// 2026-10-01 is a Thursday; weeks start on Monday.
		const result = groupDailyIntervals(
			[
				day( '2026-10-01', 2, 100, 4 ),
				day( '2026-10-04', 3, 300, 8 ),
				day( '2026-10-05', 1, 50, 1 ),
			],
			{ after: '2026-10-01', interval: 'week', startOfWeek: 1 }
		);

		expect( result ).toEqual( [
			{
				interval: '2026-10-01',
				subtotals: {
					clicks: 5,
					impressions: 400,
					ctr: 5 / 400,
					// Weighted by impressions: ( 4 * 100 + 8 * 300 ) / 400.
					position: 7,
				},
			},
			{
				interval: '2026-10-05',
				subtotals: {
					clicks: 1,
					impressions: 50,
					ctr: 1 / 50,
					position: 1,
				},
			},
		] );
	} );

	it.each( [
		[ 'month', [ '2026-01-15', '2026-02-01', '2026-03-01', '2026-04-01' ] ],
		[ 'quarter', [ '2026-01-15', '2026-04-01' ] ],
		[ 'year', [ '2026-01-15' ] ],
	] )( 'groups days by %s', ( interval, expectedIntervals ) => {
		const result = groupDailyIntervals(
			[
				day( '2026-01-20', 1, 10, 2 ),
				day( '2026-02-10', 1, 10, 2 ),
				day( '2026-04-02', 1, 10, 2 ),
			],
			{ after: '2026-01-15', interval }
		);

		expect( result.map( ( { interval: start } ) => start ) ).toEqual(
			expectedIntervals
		);
		expect(
			result.reduce( ( sum, { subtotals } ) => sum + subtotals.clicks, 0 )
		).toBe( 3 );
	} );

	it( 'reports null CTR and position, not zero, for an interval without impressions', () => {
		// A real 0 would misread as "rank 0" or "0% CTR" -- both better-looking than no data.
		const result = groupDailyIntervals( [ day( '2026-10-08', 1, 10, 2 ) ], {
			after: '2026-10-01',
			interval: 'week',
			startOfWeek: 1,
		} );

		expect( result[ 0 ] ).toEqual( {
			interval: '2026-10-01',
			subtotals: noData,
		} );
	} );
} );
