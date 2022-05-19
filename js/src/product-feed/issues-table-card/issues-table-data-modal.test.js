jest.mock( '@woocommerce/tracks', () => {
	return {
		recordEvent: jest.fn(),
	};
} );
/**
 * External dependencies
 */
import { render, fireEvent } from '@testing-library/react';
import '@testing-library/jest-dom';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import IssuesTableDataModal from './issues-table-data-modal';
import mockIssue from '.~/tests/mock-issue';

describe( 'Issues Data Table Modal', () => {
	test( 'Render the issue details', () => {
		const { queryByText } = render(
			<IssuesTableDataModal issue={ mockIssue( 1 ) } />
		);
		const link = queryByText( 'Learn more' );

		expect( queryByText( '#issue-1' ) ).toBeTruthy();
		expect( queryByText( 'Action for 1' ) ).toBeTruthy();
		expect( queryByText( 'What to do?' ) ).toBeTruthy();
		expect( link ).toHaveAttribute( 'href', 'example.com/1' );
		fireEvent.click( link );
		expect( recordEvent ).toHaveBeenCalledWith(
			'gla_documentation_link_click',
			{
				context: 'issues-data-table-modal',
				linkId: '#code-1',
				href: 'example.com/1',
			}
		);
	} );
} );
