/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import SearchConsoleReport from './index';
import { recordGlaEvent } from '~/utils/tracks';
import useRefreshGoogleSearchConsoleAccount from './hooks/useRefreshGoogleSearchConsoleAccount';

jest.mock( '~/utils/tracks', () => ( {
	recordGlaEvent: jest.fn().mockName( 'recordGlaEvent' ),
} ) );

jest.mock( './hooks/useRefreshGoogleSearchConsoleAccount', () => ( {
	__esModule: true,
	default: jest.fn(),
} ) );

jest.mock( '~/components/app-spinner', () => () => <div>Loading</div> );
jest.mock( '~/components/main-tab-nav', () => () => <div>Main tabs</div> );
jest.mock( '../reports-navigation', () => () => <div>Reports tabs</div> );
jest.mock( './search-console-report-content', () => () => (
	<div>Report content</div>
) );

describe( 'SearchConsoleReport', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		useRefreshGoogleSearchConsoleAccount.mockReturnValue( {
			isRefreshPending: false,
		} );
	} );

	it( 'should render the navigation and the report content', () => {
		render( <SearchConsoleReport /> );

		expect( screen.getByText( 'Main tabs' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Reports tabs' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Report content' ) ).toBeInTheDocument();
	} );

	it( 'should hold the report content back while the stored connection status is being refreshed', () => {
		useRefreshGoogleSearchConsoleAccount.mockReturnValue( {
			isRefreshPending: true,
		} );

		render( <SearchConsoleReport /> );

		expect( screen.getByText( 'Loading' ) ).toBeInTheDocument();
		expect(
			screen.queryByText( 'Report content' )
		).not.toBeInTheDocument();
	} );

	it( 'should record one report view on mount, and none on re-render', () => {
		const { rerender } = render( <SearchConsoleReport /> );
		rerender( <SearchConsoleReport /> );

		expect( recordGlaEvent ).toHaveBeenCalledTimes( 1 );
		expect( recordGlaEvent ).toHaveBeenCalledWith(
			'gla_search_console_report_viewed'
		);
	} );
} );
