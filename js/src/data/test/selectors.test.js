/**
 * Internal dependencies
 */
import {
	getNotifications,
	getSearchConsoleQueriesReport,
	getSearchConsoleOverviewReport,
	getSearchConsoleTopProductPages,
	getSearchConsoleTopStorePages,
} from '../selectors';
import { getReportKey } from '../utils';

describe( 'getNotifications', () => {
	it( 'returns state.notifications when present', () => {
		const notifications = [
			{ id: 'notif-1', triggered_at: 1000 },
			{ id: 'notif-2', triggered_at: 2000 },
		];

		expect( getNotifications( { notifications } ) ).toEqual(
			notifications
		);
	} );

	it( 'returns empty array when state.notifications is undefined', () => {
		expect( getNotifications( {} ) ).toEqual( [] );
	} );

	it( 'returns empty array when state.notifications is null', () => {
		expect( getNotifications( { notifications: null } ) ).toEqual( [] );
	} );
} );

describe( 'getSearchConsoleQueriesReport', () => {
	const query = { after: '2026-06-01', before: '2026-06-28' };

	it( 'returns the report cached for the date range', () => {
		const report = { queries: [], limit: 100 };
		const state = {
			report: {
				[ getReportKey( 'search-console', 'queries', query ) ]: report,
			},
		};

		expect( getSearchConsoleQueriesReport( state, query ) ).toBe( report );
	} );

	it( 'returns null before the report of that date range is fetched', () => {
		const state = {
			report: {
				[ getReportKey( 'search-console', 'queries', {
					after: '2026-05-01',
					before: '2026-05-28',
				} ) ]: { queries: [], limit: 100 },
			},
		};

		expect( getSearchConsoleQueriesReport( state, query ) ).toBeNull();
	} );
} );

describe( 'getSearchConsoleOverviewReport', () => {
	const query = {
		after: '2026-06-01',
		before: '2026-06-28',
		interval: 'day',
	};

	it( 'returns the report cached for the date range and interval', () => {
		const report = {
			totals: { clicks: 1, impressions: 10, ctr: 0.1, position: 2.5 },
			intervals: [],
		};
		const state = {
			report: {
				[ getReportKey( 'search-console', 'overview', query ) ]: report,
			},
		};

		expect( getSearchConsoleOverviewReport( state, query ) ).toBe( report );
	} );

	it( 'returns null before the report of that interval is fetched', () => {
		const state = {
			report: {
				[ getReportKey( 'search-console', 'overview', {
					...query,
					interval: 'week',
				} ) ]: { totals: {}, intervals: [] },
			},
		};

		expect( getSearchConsoleOverviewReport( state, query ) ).toBeNull();
	} );
} );

describe( 'getSearchConsoleTopProductPages', () => {
	const query = { after: '2026-06-01', before: '2026-06-28' };

	it( 'returns the report cached for the date range', () => {
		const report = { products: [], limit: 100 };
		const state = {
			report: {
				[ getReportKey(
					'search-console',
					'top-product-pages',
					query
				) ]: report,
			},
		};

		expect( getSearchConsoleTopProductPages( state, query ) ).toBe(
			report
		);
	} );

	it( 'returns null before the report of that date range is fetched', () => {
		const state = {
			report: {
				[ getReportKey( 'search-console', 'queries', query ) ]: {
					queries: [],
					limit: 100,
				},
			},
		};

		expect( getSearchConsoleTopProductPages( state, query ) ).toBeNull();
	} );
} );

describe( 'getSearchConsoleTopStorePages', () => {
	const query = { after: '2026-06-01', before: '2026-06-28' };

	it( 'returns the report cached for the date range', () => {
		const report = { pages: [], limit: 100 };
		const state = {
			report: {
				[ getReportKey( 'search-console', 'top-store-pages', query ) ]:
					report,
			},
		};

		expect( getSearchConsoleTopStorePages( state, query ) ).toBe( report );
	} );

	it( 'returns null before the report of that date range is fetched', () => {
		const state = {
			report: {
				[ getReportKey(
					'search-console',
					'top-product-pages',
					query
				) ]: { products: [], limit: 100 },
			},
		};

		expect( getSearchConsoleTopStorePages( state, query ) ).toBeNull();
	} );
} );
