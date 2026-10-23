/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import SearchConsoleReport from './index';
import useGoogleSearchConsoleAccount from '~/hooks/useGoogleSearchConsoleAccount';
import useServiceBasedMerchant from '~/hooks/useServiceBasedMerchant';
import { recordGlaEvent } from '~/utils/tracks';
import { GOOGLE_SEARCH_CONSOLE_ACCOUNT_STATUS, glaData } from '~/constants';

jest.mock( '~/hooks/useGoogleSearchConsoleAccount', () =>
	jest.fn().mockName( 'useGoogleSearchConsoleAccount' )
);

jest.mock( '~/hooks/useServiceBasedMerchant', () =>
	jest.fn().mockName( 'useServiceBasedMerchant' )
);

jest.mock( '~/utils/tracks', () => ( {
	recordGlaEvent: jest.fn().mockName( 'recordGlaEvent' ),
} ) );

jest.mock( '~/components/main-tab-nav', () => () => null );
jest.mock( '../reports-navigation', () => () => null );
jest.mock( '~/components/app-spinner', () => () => <div>Loading</div> );
jest.mock( './search-console-report-filters', () => () => (
	<div>Date filters</div>
) );
jest.mock( './organic-overview', () => ( { trackEventId } ) => (
	<div>Organic overview { trackEventId }</div>
) );
jest.mock( './top-search-queries', () => () => <div>Top search queries</div> );
jest.mock( './top-product-pages', () => () => <div>Top product pages</div> );
jest.mock( './data-freshness-notice', () => ( { lastUpdated } ) => (
	<div>Freshness { String( lastUpdated ) }</div>
) );
jest.mock( './connect-prompt', () => ( { needsAttention } ) => (
	<div>{ needsAttention ? 'Needs attention prompt' : 'Connect prompt' }</div>
) );

jest.mock( '@woocommerce/navigation', () => ( {
	getQuery: () => ( {} ),
} ) );

const mockAccount = ( account, overrides = {} ) => {
	useGoogleSearchConsoleAccount.mockReturnValue( {
		account,
		isConnected:
			account?.status === GOOGLE_SEARCH_CONSOLE_ACCOUNT_STATUS.CONNECTED,
		hasFinishedResolution: true,
		...overrides,
	} );
};

describe( 'SearchConsoleReport', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		useServiceBasedMerchant.mockReturnValue( false );
	} );

	afterEach( () => {
		glaData.searchConsoleConnected = false;
	} );

	it.each( [
		[
			'connected',
			{ status: GOOGLE_SEARCH_CONSOLE_ACCOUNT_STATUS.CONNECTED },
		],
		[
			'disconnected',
			{ status: GOOGLE_SEARCH_CONSOLE_ACCOUNT_STATUS.DISCONNECTED },
		],
	] )(
		'should record one report view on mount when %s, and none on re-render',
		( _, account ) => {
			mockAccount( account );

			const { rerender } = render( <SearchConsoleReport /> );
			rerender( <SearchConsoleReport /> );

			expect( recordGlaEvent ).toHaveBeenCalledTimes( 1 );
			expect( recordGlaEvent ).toHaveBeenCalledWith(
				'gla_search_console_report_viewed'
			);
		}
	);

	it( 'should record one report view on mount while the connection is still loading', () => {
		mockAccount( null, { hasFinishedResolution: false } );

		render( <SearchConsoleReport /> );

		expect( recordGlaEvent ).toHaveBeenCalledTimes( 1 );
		expect( recordGlaEvent ).toHaveBeenCalledWith(
			'gla_search_console_report_viewed'
		);
	} );

	it( 'should show a spinner while the connection is loading', () => {
		mockAccount( null, { hasFinishedResolution: false } );

		render( <SearchConsoleReport /> );

		expect( screen.getByText( 'Loading' ) ).toBeInTheDocument();
		expect(
			screen.queryByText( 'Connect prompt' )
		).not.toBeInTheDocument();
	} );

	it( 'should show the connect prompt and no report data when Search Console is disconnected', () => {
		mockAccount( {
			status: GOOGLE_SEARCH_CONSOLE_ACCOUNT_STATUS.DISCONNECTED,
		} );

		render( <SearchConsoleReport /> );

		expect( screen.getByText( 'Connect prompt' ) ).toBeInTheDocument();
		expect( screen.queryByText( 'Date filters' ) ).not.toBeInTheDocument();
		expect(
			screen.queryByText( /Organic overview/ )
		).not.toBeInTheDocument();
		expect(
			screen.queryByText( 'Top search queries' )
		).not.toBeInTheDocument();
	} );

	it.each( [
		GOOGLE_SEARCH_CONSOLE_ACCOUNT_STATUS.ACTION_NEEDED,
		GOOGLE_SEARCH_CONSOLE_ACCOUNT_STATUS.INCOMPLETE,
		GOOGLE_SEARCH_CONSOLE_ACCOUNT_STATUS.RECONNECT,
		GOOGLE_SEARCH_CONSOLE_ACCOUNT_STATUS.CONNECTION_FAILED,
	] )(
		'should show the needs-attention prompt when the connection status is "%s"',
		( status ) => {
			mockAccount( { status } );

			render( <SearchConsoleReport /> );

			expect(
				screen.getByText( 'Needs attention prompt' )
			).toBeInTheDocument();
			expect(
				screen.queryByText( 'Top search queries' )
			).not.toBeInTheDocument();
		}
	);

	it( 'should keep showing the report when the status check fails transiently and the stored connection is connected', () => {
		glaData.searchConsoleConnected = true;
		mockAccount( {
			status: GOOGLE_SEARCH_CONSOLE_ACCOUNT_STATUS.TRANSIENT_ERROR,
		} );

		render( <SearchConsoleReport /> );

		expect( screen.getByText( 'Top search queries' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Freshness null' ) ).toBeInTheDocument();
	} );

	it( 'should show the connect prompt when the status check fails transiently and the stored connection is not connected', () => {
		mockAccount( {
			status: GOOGLE_SEARCH_CONSOLE_ACCOUNT_STATUS.TRANSIENT_ERROR,
		} );

		render( <SearchConsoleReport /> );

		expect( screen.getByText( 'Connect prompt' ) ).toBeInTheDocument();
		expect(
			screen.queryByText( 'Top search queries' )
		).not.toBeInTheDocument();
	} );

	it( 'should show the date filters, freshness notice and report data once connected', () => {
		mockAccount( {
			status: GOOGLE_SEARCH_CONSOLE_ACCOUNT_STATUS.CONNECTED,
			site_url: 'sc-domain:example.com',
			data_last_updated: 1792108800,
		} );

		render( <SearchConsoleReport /> );

		expect( screen.getByText( 'Date filters' ) ).toBeInTheDocument();
		expect(
			screen.getByText( 'Freshness 1792108800' )
		).toBeInTheDocument();
		expect(
			screen.getByText( 'Organic overview reports-search-console' )
		).toBeInTheDocument();
		expect( screen.getByText( 'Top search queries' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Top product pages' ) ).toBeInTheDocument();
		expect(
			screen.queryByText( 'Connect prompt' )
		).not.toBeInTheDocument();
	} );

	it( 'should not render the top product pages for a service-based merchant', () => {
		useServiceBasedMerchant.mockReturnValue( true );
		mockAccount( {
			status: GOOGLE_SEARCH_CONSOLE_ACCOUNT_STATUS.CONNECTED,
			site_url: 'sc-domain:example.com',
		} );

		render( <SearchConsoleReport /> );

		expect( screen.getByText( 'Top search queries' ) ).toBeInTheDocument();
		expect(
			screen.queryByText( 'Top product pages' )
		).not.toBeInTheDocument();
	} );
} );
