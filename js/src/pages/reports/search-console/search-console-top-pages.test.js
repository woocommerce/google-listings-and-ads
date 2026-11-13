/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import SearchConsoleTopPages from './search-console-top-pages';
import useGoogleMCAccount from '~/hooks/useGoogleMCAccount';

jest.mock( '~/hooks/useGoogleMCAccount', () =>
	jest.fn().mockName( 'useGoogleMCAccount' )
);

jest.mock( '~/components/app-spinner', () => () => <div>Loading</div> );
jest.mock( './top-product-pages', () => () => <div>Top product pages</div> );
jest.mock( './top-store-pages', () => () => <div>Top store pages</div> );

describe( 'SearchConsoleTopPages', () => {
	it( 'shows a spinner and no tables while the Merchant Center account is loading', () => {
		useGoogleMCAccount.mockReturnValue( {
			hasGoogleMCConnection: false,
			hasFinishedResolution: false,
		} );

		render( <SearchConsoleTopPages /> );

		expect( screen.getByText( 'Loading' ) ).toBeInTheDocument();
		expect(
			screen.queryByText( 'Top product pages' )
		).not.toBeInTheDocument();
		expect(
			screen.queryByText( 'Top store pages' )
		).not.toBeInTheDocument();
	} );

	it( 'shows both tables when a Merchant Center account is connected', () => {
		useGoogleMCAccount.mockReturnValue( {
			hasGoogleMCConnection: true,
			hasFinishedResolution: true,
		} );

		render( <SearchConsoleTopPages /> );

		expect( screen.getByText( 'Top product pages' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Top store pages' ) ).toBeInTheDocument();
		expect( screen.queryByText( 'Loading' ) ).not.toBeInTheDocument();
	} );

	it( 'leaves out the top product pages without a Merchant Center connection', () => {
		useGoogleMCAccount.mockReturnValue( {
			hasGoogleMCConnection: false,
			hasFinishedResolution: true,
		} );

		render( <SearchConsoleTopPages /> );

		expect(
			screen.queryByText( 'Top product pages' )
		).not.toBeInTheDocument();
		expect( screen.getByText( 'Top store pages' ) ).toBeInTheDocument();
	} );
} );
