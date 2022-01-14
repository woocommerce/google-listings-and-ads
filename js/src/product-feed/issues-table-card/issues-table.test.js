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

jest.mock( '.~/hooks/useActiveIssueType' );
jest.mock( '.~/hooks/useMCIssuesTypeFilter', () => ( {
	__esModule: true,
	default: jest.fn().mockName( 'useMCIssuesTypeFilter' ),
} ) );

/**
 * External dependencies
 */
import { render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { ISSUE_TYPE_ACCOUNT, ISSUE_TYPE_PRODUCT } from '.~/constants';
import IssuesTable from '.~/product-feed/issues-table-card/issues-table';
import useMCIssuesTypeFilter from '.~/hooks/useMCIssuesTypeFilter';
import useActiveIssueType from '.~/hooks/useActiveIssueType';

describe( 'Issues Table', () => {
	describe( 'Rendering correctly the table', () => {
		beforeEach( () => {
			useMCIssuesTypeFilter.mockImplementation( ( type ) => {
				return {
					data: {
						issues: [ mockIssue( 1, { type } ) ],
						total: 3,
					},
					page: 1,
					hasFinishedResolution: true,
				};
			} );
		} );

		it( 'Rendering Product table if issue type is product', () => {
			useActiveIssueType.mockReturnValue( ISSUE_TYPE_PRODUCT );

			const { queryByText } = render( <IssuesTable /> );

			expect( queryByText( 'Edit' ) ).toBeTruthy();
		} );

		it( 'Rendering Account table if issue type is account', () => {
			useActiveIssueType.mockReturnValue( ISSUE_TYPE_ACCOUNT );

			const { queryByText } = render( <IssuesTable /> );

			expect( queryByText( 'Edit' ) ).toBeFalsy();
		} );
	} );

	describe( 'Table behaviour', () => {
		beforeEach( () => {
			useActiveIssueType.mockReturnValue( ISSUE_TYPE_ACCOUNT );
		} );

		it( 'Renders paginator if needed', () => {
			useMCIssuesTypeFilter.mockReturnValue( {
				data: {
					issues: [ mockIssue( 1 ) ],
					total: 300,
				},
				page: 1,
				hasFinishedResolution: true,
			} );

			const { getByRole } = render( <IssuesTable /> );

			expect( getByRole( 'button', { name: 'Next Page' } ) ).toBeTruthy();
		} );

		it( 'Does Not render paginator if it is not needed', () => {
			useMCIssuesTypeFilter.mockReturnValue( {
				data: {
					issues: [ mockIssue( 1 ), mockIssue( 2 ), mockIssue( 3 ) ],
					total: 1,
				},
				page: 1,
				hasFinishedResolution: true,
			} );

			const { queryByText } = render( <IssuesTable /> );

			expect( queryByText( 'Next Page' ) ).toBeFalsy();
		} );

		it( 'Renders the Table With Data', () => {
			useMCIssuesTypeFilter.mockReturnValue( {
				data: {
					issues: [ mockIssue( 1 ), mockIssue( 2 ), mockIssue( 3 ) ],
					total: 1,
				},
				page: 1,
				hasFinishedResolution: true,
			} );

			const { getAllByRole, getByRole } = render( <IssuesTable /> );

			expect( getByRole( 'table' ) ).toBeTruthy();
			expect( getAllByRole( 'row' ) ).toHaveLength( 4 ); // header + issues
		} );

		it( 'Renders the Placeholder Table on Loading', () => {
			useMCIssuesTypeFilter.mockReturnValue( {
				hasFinishedResolution: false,
				page: 1,
			} );

			const { getByText } = render( <IssuesTable /> );

			expect( getByText( 'Loading Issues To Resolve' ) ).toBeTruthy();
		} );
	} );
} );
