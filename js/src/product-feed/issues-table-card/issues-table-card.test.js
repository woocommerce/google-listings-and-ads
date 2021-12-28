jest.mock( '.~/hooks/useMCIssuesTypeFilter', () => ( {
	__esModule: true,
	default: jest
		.fn()
		.mockName( 'useMCIssuesTypeFilter' )
		.mockImplementation( ( issueType ) => {
			return {
				data: {
					issues: issueType,
					total: 2,
				},
				page: 1,
				setPage: jest.fn(),
			};
		} ),
} ) );

/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import IssuesTableCard from '.~/product-feed/issues-table-card/index';
import useMCIssuesTypeFilter from '.~/hooks/useMCIssuesTypeFIlter';

describe( 'Issues Table Card', () => {
	it( 'Rendering when there are issues', () => {
		const { getByText } = render( <IssuesTableCard /> );
		expect( getByText( 'Issues to resolve' ) ).toBeTruthy();
	} );

	it( 'Rendering the Issue Type Filter Navigation', () => {
		const { getByRole } = render( <IssuesTableCard /> );
		expect( getByRole( 'tablist' ) ).toBeTruthy();
	} );

	it( 'Rendering only one Issue table', () => {
		const { getAllByText } = render( <IssuesTableCard /> );
		expect( getAllByText( 'Type' ) ).toHaveLength( 1 );
	} );

	it( 'Not rendering when there are no issues', () => {
		useMCIssuesTypeFilter.mockImplementation( ( issueType ) => {
			return {
				data: {
					issues: issueType,
					total: 0,
				},
				page: 1,
				setPage: jest.fn(),
			};
		} );

		render( <IssuesTableCard /> );
		expect( screen.queryByText( 'Issues to resolve' ) ).toBeFalsy();
	} );
} );
