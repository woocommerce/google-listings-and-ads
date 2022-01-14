jest.mock( '.~/hooks/useMCIssuesTypeFilter' );
jest.mock( '.~/hooks/useActiveIssueType' );

/**
 * External dependencies
 */
import { render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { ISSUE_TYPE_ACCOUNT, ISSUE_TYPE_PRODUCT } from '.~/constants';
import useMCIssuesTypeFilter from '.~/hooks/useMCIssuesTypeFilter';
import useActiveIssueType from '.~/hooks/useActiveIssueType';
import IssuesSolved from '.~/product-feed/issues-table-card/issues-solved';

describe( 'Issues Solved Message', () => {
	describe( 'Renders when no issues', () => {
		beforeEach( () => {
			useMCIssuesTypeFilter.mockReturnValue( {
				data: {
					issues: [],
					total: 0,
				},
				page: 1,
				hasFinishedResolution: true,
			} );
		} );

		it( 'When Issue Type Product', () => {
			useActiveIssueType.mockReturnValue( ISSUE_TYPE_PRODUCT );

			const { queryByText } = render( <IssuesSolved /> );

			expect( queryByText( 'All product issues resolved' ) ).toBeTruthy();
		} );

		it( 'When Issue Type Account', () => {
			useActiveIssueType.mockReturnValue( ISSUE_TYPE_ACCOUNT );

			const { queryByText } = render( <IssuesSolved /> );

			expect( queryByText( 'All account issues resolved' ) ).toBeTruthy();
		} );
	} );
} );
