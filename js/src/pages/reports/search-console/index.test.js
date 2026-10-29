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

jest.mock( '~/utils/tracks', () => ( {
	recordGlaEvent: jest.fn().mockName( 'recordGlaEvent' ),
} ) );

jest.mock( '~/components/main-tab-nav', () => () => <div>Main tabs</div> );
jest.mock( '../reports-navigation', () => () => <div>Reports tabs</div> );
jest.mock( './search-console-report-content', () => () => (
	<div>Report content</div>
) );

describe( 'SearchConsoleReport', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'should render the navigation and the report content', () => {
		render( <SearchConsoleReport /> );

		expect( screen.getByText( 'Main tabs' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Reports tabs' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Report content' ) ).toBeInTheDocument();
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
