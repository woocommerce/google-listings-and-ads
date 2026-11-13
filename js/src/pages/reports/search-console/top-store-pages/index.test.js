/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import TopStorePages from './index';
import useTopStorePages from '../hooks/useTopStorePages';
import { recordGlaEvent } from '~/utils/tracks';
import useUrlQuery from '~/hooks/useUrlQuery';

jest.mock( '~/utils/tracks', () => ( {
	__esModule: true,
	recordGlaEvent: jest.fn(),
} ) );

jest.mock( '../hooks/useTopStorePages', () => ( {
	__esModule: true,
	default: jest.fn(),
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

jest.mock( '~/hooks/useSearchConsoleAccountAwareUrl', () => ( {
	__esModule: true,
	default: ( siteUrl, getUrl ) => ( siteUrl ? getUrl( siteUrl ) : null ),
} ) );

const mockOnQueryChange = jest.fn();
const mockOnQueryChangeFactory = jest.fn( () => mockOnQueryChange );
const mockUpdateQueryString = jest.fn();
jest.mock( '@woocommerce/navigation', () => ( {
	...jest.requireActual( '@woocommerce/navigation' ),
	onQueryChange: ( ...args ) => mockOnQueryChangeFactory( ...args ),
	updateQueryString: ( ...args ) => mockUpdateQueryString( ...args ),
} ) );

const buildPages = ( count ) =>
	Array.from( { length: count }, ( _, i ) => ( {
		post_id: i + 1,
		name: `Page ${ i + 1 }`,
		url: `https://example.com/page-${ i + 1 }/`,
		clicks: 1000 - i,
		impressions: 5000 - i,
		ctr: 0.2,
		position: 2.5,
	} ) );

const mockReport = ( overrides ) =>
	useTopStorePages.mockReturnValue( {
		hasFinishedResolution: true,
		property: 'https://example.com/',
		pages: buildPages( 100 ),
		limit: 100,
		days: 28,
		...overrides,
	} );

describe( 'TopStorePages', () => {
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

	it( 'lists the store pages with their columns, 10 per page, ranked as given', () => {
		mockReport();

		render( <TopStorePages /> );

		[ 'Page', 'Clicks', 'Impressions', 'CTR', 'Average position' ].forEach(
			( header ) =>
				expect(
					screen.getByRole( 'columnheader', { name: header } )
				).toBeInTheDocument()
		);

		const rows = screen.getAllByRole( 'row' ).slice( 1 );
		expect( rows ).toHaveLength( 10 );

		const firstRow = within( rows[ 0 ] );
		expect( firstRow.getByText( 'Page 1' ) ).toBeInTheDocument();
		expect( firstRow.getByText( '1000' ) ).toBeInTheDocument();
		expect( firstRow.getByText( '5000' ) ).toBeInTheDocument();
		expect( firstRow.getByText( '20.0%' ) ).toBeInTheDocument();
		expect( firstRow.getByText( '2.5' ) ).toBeInTheDocument();
		expect( screen.queryByText( 'Page 11' ) ).not.toBeInTheDocument();
	} );

	it( 'lists a page that is not a post by its name like any other', () => {
		mockReport( {
			pages: [
				{
					post_id: null,
					name: '/category/news/',
					url: 'https://example.com/category/news/',
					clicks: 7,
					impressions: 70,
					ctr: 0.1,
					position: 3,
				},
			],
		} );

		render( <TopStorePages /> );

		expect( screen.getByText( '/category/news/' ) ).toBeInTheDocument();
	} );

	it( 'is titled like the design', () => {
		mockReport();

		render( <TopStorePages /> );

		expect(
			screen.getByRole( 'heading', {
				name: 'Top store pages',
			} )
		).toBeInTheDocument();
	} );

	it( 'highlights Clicks as the column the list is ranked by, whatever the URL asks for', () => {
		useUrlQuery.mockReturnValue( { orderby: 'sales', order: 'asc' } );
		mockReport();

		render( <TopStorePages /> );

		expect(
			screen.getByRole( 'columnheader', { name: 'Clicks' } )
		).toHaveClass( 'is-sorted' );
		expect(
			screen.getByRole( 'columnheader', { name: 'Impressions' } )
		).not.toHaveClass( 'is-sorted' );
	} );

	it( 'shows the requested page', () => {
		useUrlQuery.mockReturnValue( { store_pages_paged: '2' } );
		mockReport();

		render( <TopStorePages /> );

		expect( screen.getByText( 'Page 11' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Page 20' ) ).toBeInTheDocument();
		expect( screen.queryByText( 'Page 10' ) ).not.toBeInTheDocument();
		expect( screen.queryByText( 'Page 21' ) ).not.toBeInTheDocument();
	} );

	it( 'falls back to the last page when the requested page is out of range', () => {
		useUrlQuery.mockReturnValue( { store_pages_paged: '9' } );
		mockReport( { pages: buildPages( 12 ) } );

		render( <TopStorePages /> );

		expect( screen.getByText( 'Page 11' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Page 12' ) ).toBeInTheDocument();
		expect( screen.queryByText( 'Page 10' ) ).not.toBeInTheDocument();
		expect(
			screen.queryByText( 'No data to display' )
		).not.toBeInTheDocument();
	} );

	it( 'asks for the next page through its own URL param', async () => {
		const user = userEvent.setup();
		mockReport();

		render( <TopStorePages /> );
		await user.click( screen.getByRole( 'button', { name: 'Next Page' } ) );

		expect( mockUpdateQueryString ).toHaveBeenCalledWith( {
			store_pages_paged: '2',
		} );
		expect( mockOnQueryChange ).not.toHaveBeenCalled();
	} );

	it( 'ignores the shared paged param owned by other tables', () => {
		useUrlQuery.mockReturnValue( { paged: '2' } );
		mockReport();

		render( <TopStorePages /> );

		expect( screen.getByText( 'Page 1' ) ).toBeInTheDocument();
		expect( screen.queryByText( 'Page 11' ) ).not.toBeInTheDocument();
	} );

	it( 'states the cap in the footer', () => {
		mockReport();

		render( <TopStorePages /> );

		expect(
			screen.getByText( 'Showing top 100 pages by clicks' )
		).toBeInTheDocument();
	} );

	it( 'states the raised cap in the footer', () => {
		mockReport( {
			pages: buildPages( 250 ),
			limit: 250,
		} );

		render( <TopStorePages /> );

		expect(
			screen.getByText( 'Showing top 250 pages by clicks' )
		).toBeInTheDocument();
	} );

	it( 'states how many pages are listed when there are fewer than the cap', () => {
		mockReport( { pages: buildPages( 20 ) } );

		render( <TopStorePages /> );

		expect(
			screen.getByText( 'Showing 20 pages by clicks' )
		).toBeInTheDocument();
	} );

	it( 'states one page in the singular', () => {
		mockReport( { pages: buildPages( 1 ) } );

		render( <TopStorePages /> );

		expect(
			screen.getByText( 'Showing 1 page by clicks' )
		).toBeInTheDocument();
	} );

	it( 'states the cap in the singular when it is one', () => {
		mockReport( { pages: buildPages( 1 ), limit: 1 } );

		render( <TopStorePages /> );

		expect(
			screen.getByText( 'Showing top 1 page by clicks' )
		).toBeInTheDocument();
	} );

	it( 'links to the full data in Search Console for the selected date range', () => {
		mockReport();

		render( <TopStorePages /> );

		expect(
			screen.getByRole( 'link', { name: /View all in Search Console/ } )
		).toHaveAttribute(
			'href',
			'https://search.google.com/search-console/performance/search-analytics?resource_id=https%3A%2F%2Fexample.com%2F&breakdown=page&num_of_days=28'
		);
	} );

	it( 'records an event when the link to Search Console is clicked', async () => {
		const user = userEvent.setup();
		mockReport();

		const { container } = render( <TopStorePages /> );
		// jsdom can't navigate; the click only needs to reach the handlers.
		container.addEventListener( 'click', ( e ) => e.preventDefault() );
		const link = screen.getByRole( 'link', {
			name: /View all in Search Console/,
		} );
		await user.click( link );

		expect( link ).toHaveAttribute( 'target', '_blank' );
		expect( link ).toHaveAttribute( 'rel', 'noreferrer noopener' );
		expect( recordGlaEvent ).toHaveBeenCalledTimes( 1 );
		expect( recordGlaEvent ).toHaveBeenCalledWith(
			'gla_google_search_console_view_all_store_pages_link_click',
			{ context: 'reports-search-console' }
		);
	} );

	it( 'lets the merchant hide columns except Page', async () => {
		const user = userEvent.setup();
		mockReport();

		render( <TopStorePages /> );
		await user.click(
			screen.getByRole( 'button', {
				name: 'Choose which values to display',
			} )
		);

		expect(
			screen.getByRole( 'menuitemcheckbox', { name: 'Impressions' } )
		).toBeInTheDocument();
		expect(
			screen.queryByRole( 'menuitemcheckbox', { name: 'Page' } )
		).not.toBeInTheDocument();

		await user.click(
			screen.getByRole( 'menuitemcheckbox', { name: 'Impressions' } )
		);

		expect(
			screen.queryByRole( 'columnheader', { name: 'Impressions' } )
		).not.toBeInTheDocument();
	} );

	it( 'does not write a sort to the URL when the Clicks column is hidden', async () => {
		const user = userEvent.setup();
		mockReport();

		render( <TopStorePages /> );
		await user.click(
			screen.getByRole( 'button', {
				name: 'Choose which values to display',
			} )
		);
		await user.click(
			screen.getByRole( 'menuitemcheckbox', { name: 'Clicks' } )
		);

		expect(
			screen.queryByRole( 'columnheader', { name: 'Clicks' } )
		).not.toBeInTheDocument();
		expect( mockOnQueryChangeFactory ).not.toHaveBeenCalledWith( 'sort' );
		expect( mockOnQueryChange ).not.toHaveBeenCalled();
	} );

	it( 'records the existing column toggle event for this report', async () => {
		const user = userEvent.setup();
		mockReport();

		render( <TopStorePages /> );
		await user.click(
			screen.getByRole( 'button', {
				name: 'Choose which values to display',
			} )
		);
		await user.click(
			screen.getByRole( 'menuitemcheckbox', { name: 'Impressions' } )
		);

		expect( recordGlaEvent ).toHaveBeenCalledWith(
			'gla_table_header_toggle',
			{
				report: 'reports-store-pages',
				column: 'impressions',
				status: 'off',
			}
		);
	} );

	it( 'renders the empty state when there are no store pages', () => {
		mockReport( { pages: [] } );

		render( <TopStorePages /> );

		expect( screen.getByText( 'No data to display' ) ).toBeInTheDocument();
	} );

	it( 'shows a loading state without rows', () => {
		mockReport( {
			hasFinishedResolution: false,
			pages: [],
			limit: null,
		} );

		render( <TopStorePages /> );

		expect(
			screen.getByText( 'Your requested data is loading' )
		).toBeInTheDocument();
		expect( screen.queryByText( /Showing top/ ) ).not.toBeInTheDocument();
	} );
} );
