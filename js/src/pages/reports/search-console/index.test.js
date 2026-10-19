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

jest.mock( '~/hooks/useGoogleSearchConsoleAccount', () =>
	jest.fn().mockName( 'useGoogleSearchConsoleAccount' )
);

jest.mock( '~/components/main-tab-nav', () => () => null );
jest.mock( '../reports-navigation', () => () => null );
jest.mock( './search-console-report-filters', () => () => (
	<div>Date filters</div>
) );
jest.mock( './organic-overview', () => ( { trackEventId } ) => (
	<div>Organic overview { trackEventId }</div>
) );
jest.mock( './top-search-queries', () => () => <div>Top search queries</div> );
jest.mock( './data-freshness-notice', () => ( { lastUpdated } ) => (
	<div>Freshness { String( lastUpdated ) }</div>
) );

jest.mock( '@woocommerce/navigation', () => ( {
	getQuery: () => ( {} ),
} ) );

describe( 'SearchConsoleReport', () => {
	it( 'should show the date filters, freshness notice and report data', () => {
		useGoogleSearchConsoleAccount.mockReturnValue( {
			account: {
				status: 'connected',
				site_url: 'sc-domain:example.com',
				data_last_updated: 1792108800,
			},
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
	} );

	it( 'should pass no last updated date while the account is loading', () => {
		useGoogleSearchConsoleAccount.mockReturnValue( { account: null } );

		render( <SearchConsoleReport /> );

		expect( screen.getByText( 'Freshness null' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Top search queries' ) ).toBeInTheDocument();
	} );
} );
