/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';

/**
 * Internal dependencies
 */
import useSearchConsoleReportQuery from './useSearchConsoleReportQuery';
import useGoogleSearchConsoleAccount from '~/hooks/useGoogleSearchConsoleAccount';

jest.mock( '~/hooks/useGoogleSearchConsoleAccount', () => ( {
	__esModule: true,
	default: jest.fn(),
} ) );

const mockUrlQuery = {};
jest.mock( '~/hooks/useUrlQuery', () => ( {
	__esModule: true,
	default: () => mockUrlQuery,
} ) );

jest.mock( '@woocommerce/date', () => {
	const moment = jest.requireActual( 'moment' );

	return {
		getCurrentDates: () => ( {
			primary: {
				label: 'Month to date',
				after: moment( '2026-06-01' ),
				before: moment( '2026-06-28' ),
			},
			secondary: {
				label: 'Previous year',
				after: moment( '2025-06-01' ),
				before: moment( '2025-06-28' ),
			},
		} ),
	};
} );

describe( 'useSearchConsoleReportQuery', () => {
	const account = { status: 'connected', site_url: 'https://example.com/' };

	it( 'returns the date range of both periods as Y-m-d, with their labels and the days of the primary period', () => {
		useGoogleSearchConsoleAccount.mockReturnValue( {
			account,
			isReportAvailable: true,
			hasFinishedResolution: true,
		} );

		const { result } = renderHook( () => useSearchConsoleReportQuery() );

		expect( result.current ).toEqual( {
			account,
			isReportAvailable: true,
			hasFinishedAccount: true,
			primaryQuery: { after: '2026-06-01', before: '2026-06-28' },
			secondaryQuery: { after: '2025-06-01', before: '2025-06-28' },
			labels: { primary: 'Month to date', secondary: 'Previous year' },
			days: 28,
		} );
	} );

	it( 'passes on whether the report is available and the account is still resolving', () => {
		useGoogleSearchConsoleAccount.mockReturnValue( {
			account: null,
			isReportAvailable: false,
			hasFinishedResolution: false,
		} );

		const { result } = renderHook( () => useSearchConsoleReportQuery() );

		expect( result.current.isReportAvailable ).toBe( false );
		expect( result.current.hasFinishedAccount ).toBe( false );
	} );

	it( 'keeps the same query objects between renders', () => {
		useGoogleSearchConsoleAccount.mockReturnValue( {
			account,
			isReportAvailable: true,
			hasFinishedResolution: true,
		} );

		const { result, rerender } = renderHook( () =>
			useSearchConsoleReportQuery()
		);
		const { primaryQuery, secondaryQuery } = result.current;
		rerender();

		expect( result.current.primaryQuery ).toBe( primaryQuery );
		expect( result.current.secondaryQuery ).toBe( secondaryQuery );
	} );
} );
