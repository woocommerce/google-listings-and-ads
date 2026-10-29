/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import SearchConsoleReportContent from './search-console-report-content';
import useGoogleSearchConsoleAccount from '~/hooks/useGoogleSearchConsoleAccount';
import { GOOGLE_SEARCH_CONSOLE_ACCOUNT_STATUS } from '~/constants';

jest.mock( '~/hooks/useGoogleSearchConsoleAccount', () =>
	jest.fn().mockName( 'useGoogleSearchConsoleAccount' )
);

jest.mock( '~/components/app-spinner', () => () => <div>Loading</div> );
jest.mock( './search-console-report-filters', () => () => (
	<div>Date filters</div>
) );
jest.mock( './organic-overview', () => () => <div>Organic overview</div> );
jest.mock( './top-search-queries', () => () => <div>Top search queries</div> );
jest.mock( './search-console-top-pages', () => () => (
	<div>Top pages tables</div>
) );
jest.mock( './data-freshness-notice', () => ( { lastUpdated } ) => (
	<div>Freshness { String( lastUpdated ) }</div>
) );
jest.mock( './connect-prompt', () => () => <div>Connect prompt</div> );

const mockAccount = ( account, overrides = {} ) => {
	useGoogleSearchConsoleAccount.mockReturnValue( {
		account,
		isReportAvailable:
			account?.status === GOOGLE_SEARCH_CONSOLE_ACCOUNT_STATUS.CONNECTED,
		hasFinishedResolution: true,
		...overrides,
	} );
};

describe( 'SearchConsoleReportContent', () => {
	it( 'should show a spinner while the connection is loading', () => {
		mockAccount( null, { hasFinishedResolution: false } );

		render( <SearchConsoleReportContent /> );

		expect( screen.getByText( 'Loading' ) ).toBeInTheDocument();
		expect(
			screen.queryByText( 'Connect prompt' )
		).not.toBeInTheDocument();
	} );

	it( 'should show the connect prompt and no report data when Search Console is disconnected', () => {
		mockAccount( {
			status: GOOGLE_SEARCH_CONSOLE_ACCOUNT_STATUS.DISCONNECTED,
		} );

		render( <SearchConsoleReportContent /> );

		expect( screen.getByText( 'Connect prompt' ) ).toBeInTheDocument();
		expect( screen.queryByText( 'Date filters' ) ).not.toBeInTheDocument();
		expect(
			screen.queryByText( 'Organic overview' )
		).not.toBeInTheDocument();
		expect(
			screen.queryByText( 'Top search queries' )
		).not.toBeInTheDocument();
		expect(
			screen.queryByText( 'Top pages tables' )
		).not.toBeInTheDocument();
	} );

	it( 'should show the connect prompt when the report is not available', () => {
		mockAccount( {
			status: GOOGLE_SEARCH_CONSOLE_ACCOUNT_STATUS.ACTION_NEEDED,
		} );

		render( <SearchConsoleReportContent /> );

		expect( screen.getByText( 'Connect prompt' ) ).toBeInTheDocument();
		expect(
			screen.queryByText( 'Top search queries' )
		).not.toBeInTheDocument();
	} );

	it( 'should show the report when the hook says it is available, even if the status is not connected', () => {
		mockAccount(
			{ status: GOOGLE_SEARCH_CONSOLE_ACCOUNT_STATUS.TRANSIENT_ERROR },
			{ isReportAvailable: true }
		);

		render( <SearchConsoleReportContent /> );

		expect( screen.getByText( 'Top search queries' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Freshness null' ) ).toBeInTheDocument();
		expect(
			screen.queryByText( 'Connect prompt' )
		).not.toBeInTheDocument();
	} );

	it( 'should show the date filters, freshness notice and report data once connected', () => {
		mockAccount( {
			status: GOOGLE_SEARCH_CONSOLE_ACCOUNT_STATUS.CONNECTED,
			site_url: 'sc-domain:example.com',
			data_last_updated: '2026-06-24',
		} );

		render( <SearchConsoleReportContent /> );

		expect( screen.getByText( 'Date filters' ) ).toBeInTheDocument();
		expect(
			screen.getByText( 'Freshness 2026-06-24' )
		).toBeInTheDocument();
		expect( screen.getByText( 'Organic overview' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Top search queries' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Top pages tables' ) ).toBeInTheDocument();
		expect(
			screen.queryByText( 'Connect prompt' )
		).not.toBeInTheDocument();
	} );
} );
