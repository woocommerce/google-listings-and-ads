const mockIssue = ( id, args ) => {
	return {
		product_id: id,
		issue: `#issue-${ id }`,
		code: `#code-${ id }`,
		product: `#product-${ id }`,
		severity: 'warning',
		action: `Action for ${ id }`,
		action_url: `example.com/${ id }`,
		type: 'product',
		...args,
	};
};

jest.mock( '.~/product-feed/issues-table-card/getActiveIssueType', () => ( {
	__esModule: true,
	default: jest.fn().mockName( 'getActiveIssueType' ),
} ) );

jest.mock( '.~/hooks/useMCIssues', () => ( {
	__esModule: true,
	default: jest.fn().mockName( 'useMCIssues' ),
} ) );

/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { ISSUE_TYPE_ACCOUNT, ISSUE_TYPE_PRODUCT } from '.~/constants';
import IssuesTable from '.~/product-feed/issues-table-card/issues-table';
import useMCIssues from '.~/hooks/useMCIssues';
import getActiveIssueType from '.~/product-feed/issues-table-card/getActiveIssueType';

describe( 'Issues Table', () => {
	describe( 'Inactive Issue Table', () => {
		beforeEach( () => {
			useMCIssues.mockReturnValue( {
				data: {
					issues: [ mockIssue( 1 ) ],
					total: 3,
				},
				page: 1,
				hasFinishedResolution: true,
			} );

			getActiveIssueType.mockReturnValue( ISSUE_TYPE_PRODUCT );
		} );

		it( 'Not Rendering', () => {
			useMCIssues.mockReturnValue( {
				data: {
					issues: [ mockIssue( 1 ) ],
					total: 300,
				},
				page: 1,
				hasFinishedResolution: true,
			} );

			render( <IssuesTable issueType={ ISSUE_TYPE_ACCOUNT } /> );

			expect( screen.queryByRole( 'table' ) ).toBeFalsy();
		} );
	} );

	describe( 'Active Issue Table', () => {
		beforeEach( () => {
			getActiveIssueType.mockReturnValue( ISSUE_TYPE_ACCOUNT );
		} );

		it( 'Renders paginator if needed', () => {
			useMCIssues.mockReturnValue( {
				data: {
					issues: [ mockIssue( 1 ) ],
					total: 300,
				},
				page: 1,
				hasFinishedResolution: true,
			} );

			const { getByRole } = render(
				<IssuesTable issueType={ ISSUE_TYPE_ACCOUNT } />
			);

			expect( getByRole( 'button', { name: 'Next Page' } ) ).toBeTruthy();
		} );

		it( 'Does Not render paginator if it is not needed', () => {
			useMCIssues.mockReturnValue( {
				data: {
					issues: [ mockIssue( 1 ), mockIssue( 2 ), mockIssue( 3 ) ],
					total: 1,
				},
				page: 1,
				hasFinishedResolution: true,
			} );

			render( <IssuesTable issueType={ ISSUE_TYPE_ACCOUNT } /> );

			expect( screen.queryByText( 'Next Page' ) ).toBeFalsy();
		} );

		it( 'Renders the Table With Data', () => {
			useMCIssues.mockReturnValue( {
				data: {
					issues: [ mockIssue( 1 ), mockIssue( 2 ), mockIssue( 3 ) ],
					total: 1,
				},
				page: 1,
				hasFinishedResolution: true,
			} );

			const { getAllByRole, getByRole } = render(
				<IssuesTable issueType={ ISSUE_TYPE_ACCOUNT } />
			);

			expect( getByRole( 'table' ) ).toBeTruthy();
			expect( getAllByRole( 'row' ) ).toHaveLength( 4 ); // header + issues
		} );

		it( 'Renders the Placeholder Table on Loading', () => {
			useMCIssues.mockReturnValue( {
				hasFinishedResolution: false,
				page: 1,
			} );

			const { getByText } = render(
				<IssuesTable issueType={ ISSUE_TYPE_ACCOUNT } />
			);

			expect( getByText( 'Loading Issues To Resolve' ) ).toBeTruthy();
		} );
	} );
} );
