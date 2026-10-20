/**
 * Internal dependencies
 */
import {
	getNotifications,
	getSearchConsoleQueriesReport,
	getSearchConsoleTopProductPages,
	getLoadedGoogleSearchConsoleStatus,
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

describe( 'getLoadedGoogleSearchConsoleStatus', () => {
	it( 'returns the status of the Search Console account already in the store', () => {
		const state = {
			accounts: { google_search_console: { status: 'connected' } },
		};

		expect( getLoadedGoogleSearchConsoleStatus( state ) ).toBe(
			'connected'
		);
	} );

	it( 'returns null before the Search Console account is fetched', () => {
		const state = { accounts: { google_search_console: null } };

		expect( getLoadedGoogleSearchConsoleStatus( state ) ).toBeNull();
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
