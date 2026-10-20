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

jest.mock( '~/hooks/useGoogleSearchConsoleAccount', () =>
	jest.fn().mockName( 'useGoogleSearchConsoleAccount' )
);

jest.mock( '~/hooks/useServiceBasedMerchant', () =>
	jest.fn().mockName( 'useServiceBasedMerchant' )
);

jest.mock( '~/components/main-tab-nav', () => () => null );
jest.mock( '../reports-navigation', () => () => null );
jest.mock( './search-console-report-filters', () => () => (
	<div>Date filters</div>
) );
jest.mock( './top-search-queries', () => () => <div>Top search queries</div> );
jest.mock( './top-product-pages', () => () => <div>Top product pages</div> );
jest.mock( './data-freshness-notice', () => ( { lastUpdated } ) => (
	<div>Freshness { String( lastUpdated ) }</div>
) );

jest.mock( '@woocommerce/navigation', () => ( {
	getQuery: () => ( {} ),
} ) );

describe( 'SearchConsoleReport', () => {
	beforeEach( () => {
		useServiceBasedMerchant.mockReturnValue( false );
	} );

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
		expect( screen.getByText( 'Top search queries' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Top product pages' ) ).toBeInTheDocument();
	} );

	it( 'should not render the top product pages for a service-based merchant', () => {
		useServiceBasedMerchant.mockReturnValue( true );
		useGoogleSearchConsoleAccount.mockReturnValue( {
			account: { status: 'connected', site_url: 'sc-domain:example.com' },
		} );

		render( <SearchConsoleReport /> );

		expect( screen.getByText( 'Top search queries' ) ).toBeInTheDocument();
		expect(
			screen.queryByText( 'Top product pages' )
		).not.toBeInTheDocument();
	} );

	it( 'should pass no last updated date while the account is loading', () => {
		useGoogleSearchConsoleAccount.mockReturnValue( { account: null } );

		render( <SearchConsoleReport /> );

		expect( screen.getByText( 'Freshness null' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Top search queries' ) ).toBeInTheDocument();
	} );
} );
