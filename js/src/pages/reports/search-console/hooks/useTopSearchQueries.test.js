/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import useTopSearchQueries from './useTopSearchQueries';
import useGoogleSearchConsoleAccount from '~/hooks/useGoogleSearchConsoleAccount';

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

jest.mock( '@woocommerce/date', () => ( {
	getCurrentDates: () => ( {
		primary: {
			after: jest.requireActual( 'moment' )( '2026-06-01' ),
			before: jest.requireActual( 'moment' )( '2026-06-28' ),
		},
	} ),
} ) );

const mockGetSearchConsoleQueriesReport = jest.fn();
const mockHasFinishedResolution = jest.fn();

const report = {
	queries: [
		{
			query: 'red shoes',
			clicks: 9,
			impressions: 90,
			ctr: 0.1,
			position: 1.2,
		},
	],
	limit: 100,
};

describe( 'useTopSearchQueries', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		useSelect.mockImplementation( ( cb ) =>
			cb( () => ( {
				getSearchConsoleQueriesReport:
					mockGetSearchConsoleQueriesReport,
				hasFinishedResolution: mockHasFinishedResolution,
			} ) )
		);
	} );

	it( 'requests the report of the selected date range once connected', () => {
		useGoogleSearchConsoleAccount.mockReturnValue( {
			account: {
				status: 'connected',
				site_url: 'https://example.com/',
			},
			hasFinishedResolution: true,
		} );
		mockGetSearchConsoleQueriesReport.mockReturnValue( report );
		mockHasFinishedResolution.mockReturnValue( true );

		const { result } = renderHook( () => useTopSearchQueries() );

		const reportQuery = { after: '2026-06-01', before: '2026-06-28' };
		expect( mockGetSearchConsoleQueriesReport ).toHaveBeenCalledWith(
			reportQuery
		);
		expect( mockHasFinishedResolution ).toHaveBeenCalledWith(
			'getSearchConsoleQueriesReport',
			[ reportQuery ]
		);
		expect( result.current ).toEqual( {
			loaded: true,
			property: 'https://example.com/',
			queries: report.queries,
			limit: 100,
			days: 28,
		} );
	} );

	it( 'is still loading while the report resolves', () => {
		useGoogleSearchConsoleAccount.mockReturnValue( {
			account: { status: 'connected', site_url: 'https://example.com/' },
			hasFinishedResolution: true,
		} );
		mockGetSearchConsoleQueriesReport.mockReturnValue( null );
		mockHasFinishedResolution.mockReturnValue( false );

		const { result } = renderHook( () => useTopSearchQueries() );

		expect( result.current.loaded ).toBe( false );
		expect( result.current.queries ).toEqual( [] );
	} );

	it.each( [ 'disconnected', 'incomplete', 'action-needed', 'reconnect' ] )(
		'does not request the report when the status is %s',
		( status ) => {
			useGoogleSearchConsoleAccount.mockReturnValue( {
				account: { status },
				hasFinishedResolution: true,
			} );

			const { result } = renderHook( () => useTopSearchQueries() );

			expect( mockGetSearchConsoleQueriesReport ).not.toHaveBeenCalled();
			expect( result.current.loaded ).toBe( true );
			expect( result.current.queries ).toEqual( [] );
		}
	);

	it( 'waits for the account before reporting as loaded', () => {
		useGoogleSearchConsoleAccount.mockReturnValue( {
			account: null,
			hasFinishedResolution: false,
		} );

		const { result } = renderHook( () => useTopSearchQueries() );

		expect( mockGetSearchConsoleQueriesReport ).not.toHaveBeenCalled();
		expect( result.current.loaded ).toBe( false );
	} );
} );
