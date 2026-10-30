/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import ViewAllInSearchConsoleLink from './view-all-in-search-console-link';
import { recordGlaEvent } from '~/utils/tracks';

jest.mock( '~/utils/tracks', () => ( {
	__esModule: true,
	recordGlaEvent: jest.fn(),
} ) );

jest.mock( '~/hooks/useSearchConsoleAccountAwareUrl', () => ( {
	__esModule: true,
	default: ( siteUrl, getUrl ) => ( siteUrl ? getUrl( siteUrl ) : null ),
} ) );

describe( 'ViewAllInSearchConsoleLink', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'links to the query report of the property for the number of days', () => {
		render(
			<ViewAllInSearchConsoleLink
				property="https://example.com/"
				days={ 28 }
			/>
		);

		const link = screen.getByRole( 'link', {
			name: /View all in Search Console/,
		} );

		expect( link ).toHaveAttribute(
			'href',
			'https://search.google.com/search-console/performance/search-analytics?resource_id=https%3A%2F%2Fexample.com%2F&breakdown=query&num_of_days=28'
		);
		expect( link ).toHaveAttribute( 'target', '_blank' );
		expect( link ).toHaveAttribute( 'rel', 'noreferrer noopener' );
	} );

	it( 'records an event when clicked', async () => {
		const user = userEvent.setup();
		const { container } = render(
			<ViewAllInSearchConsoleLink
				property="https://example.com/"
				days={ 28 }
			/>
		);
		// jsdom can't navigate; the click only needs to reach the handlers.
		container.addEventListener( 'click', ( e ) => e.preventDefault() );

		await user.click( screen.getByRole( 'link' ) );

		expect( recordGlaEvent ).toHaveBeenCalledTimes( 1 );
		expect( recordGlaEvent ).toHaveBeenCalledWith(
			'gla_google_search_console_view_all_queries_link_click',
			{ context: 'reports-search-console' }
		);
	} );

	it( 'renders nothing without a property', () => {
		const { container } = render(
			<ViewAllInSearchConsoleLink property={ null } days={ 28 } />
		);

		expect( container ).toBeEmptyDOMElement();
	} );
} );
