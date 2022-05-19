jest.mock( '.~/hooks/useActiveIssueType' );
jest.mock( '@woocommerce/tracks', () => {
	return {
		recordEvent: jest.fn(),
	};
} );
/**
 * External dependencies
 */
import { render, fireEvent } from '@testing-library/react';
import { recordEvent } from '@woocommerce/tracks';
import '@testing-library/jest-dom';
/**
 * Internal dependencies
 */
import useActiveIssueType from '.~/hooks/useActiveIssueType';
import mockIssue from '.~/tests/mock-issue';
import IssuesTableData from './issues-table-data';
import { ISSUE_TYPE_ACCOUNT, ISSUE_TYPE_PRODUCT } from '.~/constants';

describe( 'Issues Table data', () => {
	test( 'Render error if no data', () => {
		const { queryByText } = render( <IssuesTableData /> );
		expect(
			queryByText(
				'An error occurred while retrieving issues. Please try again later.'
			)
		).toBeTruthy();
	} );

	test( 'Render Issues Solved when no issues', () => {
		useActiveIssueType.mockReturnValue( ISSUE_TYPE_PRODUCT );

		const { queryByText } = render(
			<IssuesTableData data={ { issues: [] } } />
		);
		expect( queryByText( /All product issues resolved/ ) ).toBeTruthy();
	} );

	test( 'Render Headers', () => {
		const { queryByText } = render(
			<IssuesTableData
				data={ {
					issues: [ mockIssue( 1, { type: 'account' } ) ],
				} }
			/>
		);

		expect( queryByText( 'Type' ) ).toBeTruthy();
		expect( queryByText( 'Affected product' ) ).toBeTruthy();
		expect( queryByText( 'Issue' ) ).toBeTruthy();
		expect( queryByText( 'Suggested action' ) ).toBeTruthy();
	} );

	test( 'Render Account Issues', () => {
		useActiveIssueType.mockReturnValue( ISSUE_TYPE_ACCOUNT );

		const { queryByText } = render(
			<IssuesTableData
				data={ {
					issues: [ mockIssue( 1, { type: 'account', product: 0 } ) ],
				} }
			/>
		);
		expect( queryByText( '#issue-1' ) ).toBeTruthy();
		expect( queryByText( 'Read more about this issue' ) ).toBeTruthy();
	} );

	test( 'Render Product Issues', () => {
		useActiveIssueType.mockReturnValue( ISSUE_TYPE_PRODUCT );

		const { queryByText } = render(
			<IssuesTableData
				data={ {
					issues: [ mockIssue( 1 ) ],
				} }
			/>
		);
		expect( queryByText( '#product-1' ) ).toBeTruthy();
		expect( queryByText( '#issue-1' ) ).toBeTruthy();
		expect( queryByText( 'Read more about this issue' ) ).toBeTruthy();
	} );

	test( 'Open modal when clicking on Action link', () => {
		useActiveIssueType.mockReturnValue( ISSUE_TYPE_ACCOUNT );

		const { queryByText, queryByRole } = render(
			<IssuesTableData
				data={ {
					issues: [ mockIssue( 1, { type: 'account', product: 0 } ) ],
				} }
			/>
		);
		const link = queryByText( 'Read more about this issue' );
		fireEvent.click( link );
		expect( queryByRole( 'dialog' ) ).toBeTruthy();
		expect( recordEvent ).toHaveBeenCalledWith(
			'gla_click_read_more_about_issue',
			{
				context: 'issues-to-resolve',
				issue: '#code-1',
			}
		);
	} );

	test( 'Link instead of modal when action is null', () => {
		jest.clearAllMocks();

		const { queryByText } = render(
			<IssuesTableData
				data={ {
					issues: [
						mockIssue( 1, {
							type: 'account',
							action: null,
							product: 0,
						} ),
					],
				} }
			/>
		);
		const link = queryByText( 'Read more about this issue' );
		expect( link ).toHaveAttribute( 'href', 'example.com/1' );
		fireEvent.click( link );
		expect( recordEvent ).toHaveBeenCalledWith(
			'gla_documentation_link_click',
			{
				context: 'issues-to-resolve',
				link_id: '#code-1',
				href: 'example.com/1',
			}
		);
	} );
} );
