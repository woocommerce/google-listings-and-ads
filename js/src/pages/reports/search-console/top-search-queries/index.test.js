/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import TopSearchQueries from './index';
import useTopSearchQueries from '../hooks/useTopSearchQueries';
import { recordGlaEvent } from '~/utils/tracks';
import useUrlQuery from '~/hooks/useUrlQuery';

jest.mock( '~/utils/tracks', () => ( {
	__esModule: true,
	recordGlaEvent: jest.fn(),
} ) );

jest.mock( '../hooks/useTopSearchQueries', () => ( {
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
jest.mock( '@woocommerce/navigation', () => ( {
	onQueryChange: () => mockOnQueryChange,
} ) );

const buildQueries = ( count ) =>
	Array.from( { length: count }, ( _, i ) => ( {
		query: `query ${ i + 1 }`,
		clicks: 1000 - i,
		impressions: 5000 - i,
		ctr: 0.2,
		position: 2.5,
	} ) );

const mockReport = ( overrides ) =>
	useTopSearchQueries.mockReturnValue( {
		hasFinishedResolution: true,
		hasError: false,
		property: 'https://example.com/',
		queries: buildQueries( 100 ),
		limit: 100,
		days: 28,
		...overrides,
	} );

describe( 'TopSearchQueries', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		useUrlQuery.mockReturnValue( {} );
	} );

	afterEach( () => {
		// `@woocommerce/components`' `TableCard` still uses `@wordpress/components` props that log
		// these deprecation warnings. Clear only those, so any other warning still fails the test.
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

	it( 'lists the queries with their columns, 10 per page, ranked as given', () => {
		mockReport();

		render( <TopSearchQueries /> );

		[ 'Query', 'Clicks', 'Impressions', 'CTR', 'Average position' ].forEach(
			( header ) =>
				expect(
					screen.getByRole( 'columnheader', { name: header } )
				).toBeInTheDocument()
		);

		const rows = screen.getAllByRole( 'row' ).slice( 1 );
		expect( rows ).toHaveLength( 10 );

		const firstRow = within( rows[ 0 ] );
		expect( firstRow.getByText( 'query 1' ) ).toBeInTheDocument();
		expect( firstRow.getByText( '1000' ) ).toBeInTheDocument();
		expect( firstRow.getByText( '5000' ) ).toBeInTheDocument();
		expect( firstRow.getByText( '20.0%' ) ).toBeInTheDocument();
		expect( firstRow.getByText( '2.5' ) ).toBeInTheDocument();
		expect( screen.queryByText( 'query 11' ) ).not.toBeInTheDocument();
	} );

	it( 'shows the requested page', () => {
		useUrlQuery.mockReturnValue( { paged: '2' } );
		mockReport();

		render( <TopSearchQueries /> );

		expect( screen.getByText( 'query 11' ) ).toBeInTheDocument();
		expect( screen.getByText( 'query 20' ) ).toBeInTheDocument();
		expect( screen.queryByText( 'query 10' ) ).not.toBeInTheDocument();
		expect( screen.queryByText( 'query 21' ) ).not.toBeInTheDocument();
	} );

	it( 'falls back to the last page when the requested page is out of range', () => {
		useUrlQuery.mockReturnValue( { paged: '9' } );
		mockReport( { queries: buildQueries( 12 ) } );

		render( <TopSearchQueries /> );

		expect( screen.getByText( 'query 11' ) ).toBeInTheDocument();
		expect( screen.getByText( 'query 12' ) ).toBeInTheDocument();
		expect( screen.queryByText( 'query 10' ) ).not.toBeInTheDocument();
		expect(
			screen.queryByText( 'No data to display' )
		).not.toBeInTheDocument();
	} );

	it( 'asks for the next page through the URL query', async () => {
		const user = userEvent.setup();
		mockReport();

		render( <TopSearchQueries /> );
		await user.click( screen.getByRole( 'button', { name: 'Next Page' } ) );

		expect( mockOnQueryChange ).toHaveBeenCalledWith( '2', 'next' );
	} );

	it( 'states the cap in the footer', () => {
		mockReport();

		render( <TopSearchQueries /> );

		expect(
			screen.getByText( 'Showing top 100 queries by clicks' )
		).toBeInTheDocument();
	} );

	it( 'states the raised cap in the footer', () => {
		mockReport( { limit: 250 } );

		render( <TopSearchQueries /> );

		expect(
			screen.getByText( 'Showing top 250 queries by clicks' )
		).toBeInTheDocument();
	} );

	it( 'links to the full data in Search Console for the selected date range', () => {
		mockReport();

		render( <TopSearchQueries /> );

		expect(
			screen.getByRole( 'link', { name: /View all in Search Console/ } )
		).toHaveAttribute(
			'href',
			'https://search.google.com/search-console/performance/search-analytics?resource_id=https%3A%2F%2Fexample.com%2F&breakdown=query&num_of_days=28'
		);
	} );

	it( 'records an event when the link to Search Console is clicked', async () => {
		const user = userEvent.setup();
		mockReport();

		const { container } = render( <TopSearchQueries /> );
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
			'gla_google_search_console_view_all_queries_link_click',
			{ context: 'reports-search-console' }
		);
	} );

	it( 'lets the merchant hide columns except Query', async () => {
		const user = userEvent.setup();
		mockReport();

		render( <TopSearchQueries /> );
		await user.click(
			screen.getByRole( 'button', {
				name: 'Choose which values to display',
			} )
		);

		expect(
			screen.getByRole( 'menuitemcheckbox', { name: 'Impressions' } )
		).toBeInTheDocument();
		expect(
			screen.queryByRole( 'menuitemcheckbox', { name: 'Query' } )
		).not.toBeInTheDocument();

		await user.click(
			screen.getByRole( 'menuitemcheckbox', { name: 'Impressions' } )
		);

		expect(
			screen.queryByRole( 'columnheader', { name: 'Impressions' } )
		).not.toBeInTheDocument();
	} );

	it( 'renders the empty state when there are no queries', () => {
		mockReport( { queries: [] } );

		render( <TopSearchQueries /> );

		expect( screen.getByText( 'No data to display' ) ).toBeInTheDocument();
	} );

	it( 'shows an inline message instead of the empty state when the report fails to load', () => {
		mockReport( { hasError: true, queries: [], limit: null } );

		render( <TopSearchQueries /> );

		expect(
			screen.getByText(
				'Unable to load the top search queries. Please try again later.'
			)
		).toBeInTheDocument();
		expect(
			screen.queryByText( 'No data to display' )
		).not.toBeInTheDocument();
	} );

	it( 'shows a loading state without rows', () => {
		mockReport( {
			hasFinishedResolution: false,
			queries: [],
			limit: null,
		} );

		render( <TopSearchQueries /> );

		expect(
			screen.getByText( 'Your requested data is loading' )
		).toBeInTheDocument();
		expect( screen.queryByText( /Showing top/ ) ).not.toBeInTheDocument();
	} );
} );
