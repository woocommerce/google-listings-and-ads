jest.mock( '.~/hooks/useMCIssuesTypeFilter' );
jest.mock( '.~/hooks/useActiveIssueType' );

/**
 * External dependencies
 */
import { render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import useMCIssuesTypeFilter from '.~/hooks/useMCIssuesTypeFilter';
import useActiveIssueType from '.~/hooks/useActiveIssueType';
import IssuesSolved from '.~/product-feed/issues-table-card/issues-solved';

describe( 'Issues Solved Message', () => {
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

	it.each( [ 'account', 'product' ] )(
		'It renders when there are not issues of type %s',
		( issueType ) => {
			useActiveIssueType.mockReturnValue( issueType );

			const { queryByText } = render( <IssuesSolved /> );

			expect(
				queryByText( `All ${ issueType } issues resolved` )
			).toBeTruthy();
		}
	);
} );
