const DAY_IN_MS = 24 * 60 * 60 * 1000;

/**
 * @typedef {import('~/data/types.js').GoogleSearchConsoleOverviewReport} GoogleSearchConsoleOverviewReport
 * @typedef {GoogleSearchConsoleOverviewReport['intervals']} OverviewIntervals
 */

/**
 * @param {string} date Date in `YYYY-MM-DD` format.
 * @return {number} The UTC timestamp of the start of that day.
 */
const toTime = ( date ) => {
	return Date.parse( `${ date }T00:00:00Z` );
};

/**
 * @param {number} time UTC timestamp.
 * @return {string} The date in `YYYY-MM-DD` format.
 */
const toDate = ( time ) => {
	return new Date( time ).toISOString().slice( 0, 10 );
};

/**
 * Get the first day of the interval a day belongs to.
 *
 * @param {string} date Date in `YYYY-MM-DD` format.
 * @param {'day'|'week'|'month'|'quarter'|'year'} interval The interval to group by.
 * @param {number} startOfWeek First day of the week, from 0 (Sunday) to 6 (Saturday).
 * @return {string} The first day of the interval, in `YYYY-MM-DD` format.
 */
const getIntervalStart = ( date, interval, startOfWeek ) => {
	const day = new Date( toTime( date ) );

	switch ( interval ) {
		case 'week': {
			const offset = ( day.getUTCDay() - startOfWeek + 7 ) % 7;
			return toDate( toTime( date ) - offset * DAY_IN_MS );
		}
		case 'month':
			return `${ date.slice( 0, 7 ) }-01`;
		case 'quarter': {
			const month = Math.floor( day.getUTCMonth() / 3 ) * 3 + 1;
			return `${ date.slice( 0, 4 ) }-${ String( month ).padStart(
				2,
				'0'
			) }-01`;
		}
		case 'year':
			return `${ date.slice( 0, 4 ) }-01-01`;
		default:
			return date;
	}
};

/**
 * Group the daily Search Console overview of a period into the given interval.
 *
 * Search Console omits the days without data, so every day from the start of the period to the
 * last day with data is included, with zeros for the missing ones. That way two periods can be
 * compared interval by interval from their first day. Days after the last one with data, which
 * Search Console hasn't processed yet, are left out.
 *
 * Clicks and impressions are summed. CTR is recalculated from them, and average position is
 * weighted by impressions, as Search Console calculates both for a date range.
 *
 * @param {OverviewIntervals} intervals Daily intervals, oldest first.
 * @param {Object} options Options.
 * @param {string} options.after First day of the period, in `YYYY-MM-DD` format.
 * @param {'day'|'week'|'month'|'quarter'|'year'} options.interval The interval to group by.
 * @param {number} [options.startOfWeek=0] First day of the week, from 0 (Sunday) to 6 (Saturday).
 * @return {OverviewIntervals} One entry per interval, each keyed by its first day within the
 *   period, oldest first.
 */
const groupDailyIntervals = (
	intervals,
	{ after, interval, startOfWeek = 0 }
) => {
	if ( ! intervals.length ) {
		return intervals;
	}

	const subtotalsByDate = new Map(
		intervals.map( ( { interval: date, subtotals } ) => {
			return [ date, subtotals ];
		} )
	);
	const lastTime = toTime( intervals[ intervals.length - 1 ].interval );
	const groups = new Map();

	for ( let time = toTime( after ); time <= lastTime; time += DAY_IN_MS ) {
		const date = toDate( time );
		const intervalStart = getIntervalStart( date, interval, startOfWeek );
		const key = intervalStart < after ? after : intervalStart;
		const subtotals = subtotalsByDate.get( date );
		const group = groups.get( key ) ?? {
			clicks: 0,
			impressions: 0,
			positionTotal: 0,
			days: [],
		};

		if ( subtotals ) {
			group.clicks += subtotals.clicks;
			group.impressions += subtotals.impressions;
			group.positionTotal += subtotals.position * subtotals.impressions;
			group.days.push( subtotals );
		}

		groups.set( key, group );
	}

	return Array.from( groups, ( [ key, group ] ) => {
		// A single day keeps Search Console's own figures rather than recalculated ones.
		if ( interval === 'day' && group.days.length ) {
			return { interval: key, subtotals: group.days[ 0 ] };
		}

		const { clicks, impressions, positionTotal } = group;

		return {
			interval: key,
			subtotals: {
				clicks,
				impressions,
				ctr: impressions ? clicks / impressions : 0,
				position: impressions ? positionTotal / impressions : 0,
			},
		};
	} );
};

export default groupDailyIntervals;
