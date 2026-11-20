/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import SearchConsoleTopTable from './search-console-top-table';
import useUrlQuery from '~/hooks/useUrlQuery';

jest.mock( '~/utils/tracks', () => ( {
	__esModule: true,
	recordGlaEvent: jest.fn(),
} ) );

jest.mock( '~/hooks/useUrlQuery', () => ( {
	__esModule: true,
	default: jest.fn(),
} ) );

jest.mock( '~/hooks/useStoreCurrency', () => ( {
	__esModule: true,
	default: () => ( {
		formatNumber: ( value, precision = 2 ) =>
			Number( value ).toFixed( precision ),
	} ),
} ) );

const mockUpdateQueryString = jest.fn();
jest.mock( '@woocommerce/navigation', () => ( {
	...jest.requireActual( '@woocommerce/navigation' ),
	onQueryChange: () => jest.fn(),
	updateQueryString: ( ...args ) => mockUpdateQueryString( ...args ),
} ) );

const NAME_COLUMN = { key: 'thing', label: 'Thing' };

const getName = ( item ) => item.title;

const buildItems = ( count ) =>
	Array.from( { length: count }, ( _, i ) => ( {
		title: `Item ${ i + 1 }`,
		clicks: 1000 - i,
		impressions: 5000 - i,
		ctr: 0.2,
		position: 2.5,
	} ) );

const renderTable = ( props ) =>
	render(
		<SearchConsoleTopTable
			className="custom-class"
			trackEventReportId="reports-things"
			title="Top things"
			actions={ <a href="https://example.com/">View all</a> }
			nameColumn={ NAME_COLUMN }
			items={ buildItems( 25 ) }
			getName={ getName }
			pagedParam="things_paged"
			limit={ 100 }
			getSummaryLabel={ ( { count, limit } ) =>
				`${ count } of ${ limit }`
			}
			hasFinishedResolution
			{ ...props }
		/>
	);

describe( 'SearchConsoleTopTable', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		useUrlQuery.mockReturnValue( {} );
	} );

	afterEach( () => {
		// eslint-disable-next-line no-console
		const warnings = console.warn.mock.calls.filter(
			( [ message ] ) =>
				! /wp\.components\.\w+ is deprecated/.test( message )
		);
		// eslint-disable-next-line no-console
		console.warn.mockClear();
		warnings.forEach( ( args ) => {
			// eslint-disable-next-line no-console
			console.warn( ...args );
		} );
	} );

	it( 'names the first column after the given column and lists the shared columns after it', () => {
		renderTable();

		expect(
			screen
				.getAllByRole( 'columnheader' )
				.map( ( header ) => header.textContent )
		).toEqual( [
			'Thing',
			'Clicks',
			'Impressions',
			'CTR',
			'Average position',
		] );
	} );

	it( 'shows the text the given function returns for each item in the first column', () => {
		renderTable();

		const firstRow = within( screen.getAllByRole( 'row' )[ 1 ] );
		expect( firstRow.getByText( 'Item 1' ) ).toBeInTheDocument();
		expect( firstRow.getByText( '1000' ) ).toBeInTheDocument();
		expect( firstRow.getByText( '5000' ) ).toBeInTheDocument();
		expect( firstRow.getByText( '20.0%' ) ).toBeInTheDocument();
		expect( firstRow.getByText( '2.5' ) ).toBeInTheDocument();
	} );

	it( 'keeps the shared class and adds the given one', () => {
		const { container } = renderTable();

		expect(
			container.querySelector( '.gla-search-console-table.custom-class' )
		).toBeInTheDocument();
	} );

	it( 'works without an extra class', () => {
		const { container } = renderTable( { className: undefined } );

		expect(
			container.querySelector( '.gla-search-console-table' )
		).toBeInTheDocument();
	} );

	it( 'pages through its own URL param', async () => {
		const user = userEvent.setup();
		useUrlQuery.mockReturnValue( { things_paged: '2', paged: '3' } );
		renderTable();

		expect( screen.getByText( 'Item 11' ) ).toBeInTheDocument();
		expect( screen.queryByText( 'Item 10' ) ).not.toBeInTheDocument();

		await user.click( screen.getByRole( 'button', { name: 'Next Page' } ) );

		expect( mockUpdateQueryString ).toHaveBeenCalledWith( {
			things_paged: '3',
		} );
	} );

	it( 'shows the footer label from the number of items and the limit', () => {
		renderTable();

		expect( screen.getByText( '25 of 100' ) ).toBeInTheDocument();
	} );

	it( 'shows no footer label while the limit is not known', () => {
		renderTable( { limit: null, hasFinishedResolution: false } );

		expect( screen.queryByText( /of 100/ ) ).not.toBeInTheDocument();
		expect(
			screen.getByText( 'Your requested data is loading' )
		).toBeInTheDocument();
	} );

	it( 'shows no footer label when the function returns none', () => {
		renderTable( { getSummaryLabel: () => null } );

		expect( screen.queryByText( /of 100/ ) ).not.toBeInTheDocument();
	} );
} );
