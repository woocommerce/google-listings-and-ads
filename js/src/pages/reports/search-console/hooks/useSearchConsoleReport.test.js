/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import useSearchConsoleReport from './useSearchConsoleReport';
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

const mockGetReport = jest.fn();
const mockHasFinishedResolution = jest.fn();

const connectedAccount = {
	account: { status: 'connected', site_url: 'https://example.com/' },
	isReportAvailable: true,
	hasFinishedResolution: true,
};

describe( 'useSearchConsoleReport', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		useSelect.mockImplementation( ( cb ) =>
			cb( () => ( {
				getReportOfSomething: mockGetReport,
				hasFinishedResolution: mockHasFinishedResolution,
			} ) )
		);
	} );

	it( 'requests the report named by the selector for the selected date range', () => {
		const report = { limit: 100 };
		useGoogleSearchConsoleAccount.mockReturnValue( connectedAccount );
		mockGetReport.mockReturnValue( report );
		mockHasFinishedResolution.mockReturnValue( true );

		const { result } = renderHook( () =>
			useSearchConsoleReport( 'getReportOfSomething' )
		);

		const reportQuery = { after: '2026-06-01', before: '2026-06-28' };
		expect( mockGetReport ).toHaveBeenCalledWith( reportQuery );
		expect( mockHasFinishedResolution ).toHaveBeenCalledWith(
			'getReportOfSomething',
			[ reportQuery ]
		);
		expect( result.current ).toEqual( {
			hasFinishedResolution: true,
			property: 'https://example.com/',
			report,
			days: 28,
		} );
	} );

	it( 'is still loading while the report resolves', () => {
		useGoogleSearchConsoleAccount.mockReturnValue( connectedAccount );
		mockGetReport.mockReturnValue( null );
		mockHasFinishedResolution.mockReturnValue( false );

		const { result } = renderHook( () =>
			useSearchConsoleReport( 'getReportOfSomething' )
		);

		expect( result.current.hasFinishedResolution ).toBe( false );
		expect( result.current.report ).toBeNull();
	} );

	it( 'does not request the report when Search Console is not connected', () => {
		useGoogleSearchConsoleAccount.mockReturnValue( {
			account: { status: 'disconnected' },
			isReportAvailable: false,
			hasFinishedResolution: true,
		} );

		const { result } = renderHook( () =>
			useSearchConsoleReport( 'getReportOfSomething' )
		);

		expect( mockGetReport ).not.toHaveBeenCalled();
		expect( result.current ).toEqual( {
			hasFinishedResolution: true,
			property: null,
			report: null,
			days: 28,
		} );
	} );

	it( 'still requests the report when the status check fails transiently but the stored connection is connected', () => {
		const report = { limit: 100 };
		useGoogleSearchConsoleAccount.mockReturnValue( {
			account: { status: 'transient-error' },
			isConnected: false,
			isReportAvailable: true,
			hasFinishedResolution: true,
		} );
		mockGetReport.mockReturnValue( report );
		mockHasFinishedResolution.mockReturnValue( true );

		const { result } = renderHook( () =>
			useSearchConsoleReport( 'getReportOfSomething' )
		);

		expect( mockGetReport ).toHaveBeenCalledWith( {
			after: '2026-06-01',
			before: '2026-06-28',
		} );
		expect( result.current.report ).toBe( report );
	} );

	it( 'waits for the account before reporting as finished', () => {
		useGoogleSearchConsoleAccount.mockReturnValue( {
			account: null,
			isReportAvailable: false,
			hasFinishedResolution: false,
		} );

		const { result } = renderHook( () =>
			useSearchConsoleReport( 'getReportOfSomething' )
		);

		expect( mockGetReport ).not.toHaveBeenCalled();
		expect( result.current.hasFinishedResolution ).toBe( false );
	} );
} );
