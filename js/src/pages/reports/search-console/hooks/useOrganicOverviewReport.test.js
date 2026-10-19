/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import useOrganicOverviewReport from './useOrganicOverviewReport';
import useGoogleSearchConsoleAccount from '~/hooks/useGoogleSearchConsoleAccount';
import { MISSING_FREE_LISTINGS_DATA } from '~/data/utils';

jest.mock( '@wordpress/data', () => ( {
	__esModule: true,
	useSelect: jest.fn(),
} ) );

jest.mock( '~/hooks/useGoogleSearchConsoleAccount', () => ( {
	__esModule: true,
	default: jest.fn(),
} ) );

jest.mock( '~/hooks/useUrlQuery', () => ( {
	__esModule: true,
	default: () => ( {} ),
} ) );

jest.mock( '@woocommerce/date', () => {
	const moment = jest.requireActual( 'moment' );

	return {
		getCurrentDates: () => ( {
			primary: {
				label: 'Month to date',
				after: moment( '2026-10-01' ),
				before: moment( '2026-10-16' ),
			},
			secondary: {
				label: 'Previous year',
				after: moment( '2025-10-01' ),
				before: moment( '2025-10-16' ),
			},
		} ),
		getIntervalForQuery: () => 'day',
		getAllowedIntervalsForQuery: () => [ 'day', 'week' ],
	};
} );

const mockGetSearchConsoleOverviewReport = jest.fn();
const mockHasFinishedResolution = jest.fn();

const primaryQuery = {
	after: '2026-10-01',
	before: '2026-10-16',
	interval: 'day',
};
const secondaryQuery = {
	after: '2025-10-01',
	before: '2025-10-16',
	interval: 'day',
};

const primaryReport = {
	totals: { clicks: 120, impressions: 4000, ctr: 0.03, position: 12.5 },
	intervals: [
		{
			interval: '2026-10-01',
			subtotals: {
				clicks: 10,
				impressions: 300,
				ctr: 0.033,
				position: 12,
			},
		},
	],
};
const secondaryReport = {
	totals: { clicks: 100, impressions: 5000, ctr: 0.02, position: 15 },
	intervals: [
		{
			interval: '2025-10-01',
			subtotals: {
				clicks: 8,
				impressions: 320,
				ctr: 0.025,
				position: 15,
			},
		},
	],
};

describe( 'useOrganicOverviewReport', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		useSelect.mockImplementation( ( callback ) => {
			return callback( () => ( {
				getSearchConsoleOverviewReport:
					mockGetSearchConsoleOverviewReport,
				hasFinishedResolution: mockHasFinishedResolution,
			} ) );
		} );
		useGoogleSearchConsoleAccount.mockReturnValue( {
			isConnected: true,
			hasFinishedResolution: true,
		} );
	} );

	it( 'requests the selected and comparison periods for the interval in use', () => {
		mockGetSearchConsoleOverviewReport.mockImplementation( ( query ) => {
			return query.after === primaryQuery.after
				? primaryReport
				: secondaryReport;
		} );
		mockHasFinishedResolution.mockReturnValue( true );

		const { result } = renderHook( () => useOrganicOverviewReport() );

		expect( mockGetSearchConsoleOverviewReport ).toHaveBeenCalledWith(
			primaryQuery
		);
		expect( mockGetSearchConsoleOverviewReport ).toHaveBeenCalledWith(
			secondaryQuery
		);
		expect( result.current.loaded ).toBe( true );
		expect( result.current.intervals ).toBe( primaryReport.intervals );
		expect( result.current.comparisonIntervals ).toBe(
			secondaryReport.intervals
		);
		expect( result.current.interval ).toBe( 'day' );
		expect( result.current.allowedIntervals ).toEqual( [ 'day', 'week' ] );
		expect( result.current.labels ).toEqual( {
			primary: 'Month to date',
			secondary: 'Previous year',
		} );
	} );

	it( 'compares the totals of the selected period to the comparison period', () => {
		mockGetSearchConsoleOverviewReport.mockImplementation( ( query ) => {
			return query.after === primaryQuery.after
				? primaryReport
				: secondaryReport;
		} );
		mockHasFinishedResolution.mockReturnValue( true );

		const { result } = renderHook( () => useOrganicOverviewReport() );

		expect( result.current.totals ).toEqual( {
			clicks: expect.objectContaining( {
				value: 120,
				prevValue: 100,
				delta: 20,
			} ),
			impressions: expect.objectContaining( {
				value: 4000,
				prevValue: 5000,
				delta: -20,
			} ),
			ctr: expect.objectContaining( {
				value: 0.03,
				prevValue: 0.02,
				delta: 50,
			} ),
			position: expect.objectContaining( {
				value: 12.5,
				prevValue: 15,
				delta: -16.67,
			} ),
		} );
		expect( result.current.totals.clicks.missingFreeListingsData ).toBe(
			MISSING_FREE_LISTINGS_DATA.NONE
		);
	} );

	it( 'is still loading until both periods have resolved', () => {
		mockGetSearchConsoleOverviewReport.mockReturnValue( null );
		mockHasFinishedResolution.mockImplementation( ( _, [ query ] ) => {
			return query.after === primaryQuery.after;
		} );

		const { result } = renderHook( () => useOrganicOverviewReport() );

		expect( result.current.loaded ).toBe( false );
		expect( result.current.intervals ).toEqual( [] );
		expect( result.current.comparisonIntervals ).toEqual( [] );
	} );

	it( 'does not request anything until Search Console is connected', () => {
		useGoogleSearchConsoleAccount.mockReturnValue( {
			isConnected: false,
			hasFinishedResolution: true,
		} );

		const { result } = renderHook( () => useOrganicOverviewReport() );

		expect( mockGetSearchConsoleOverviewReport ).not.toHaveBeenCalled();
		expect( result.current.loaded ).toBe( true );
		expect( result.current.totals.clicks.value ).toBeUndefined();
	} );
} );
