jest.mock( '~/hooks/useMCIssuesTypeFilter', () => ( {
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
import { ISSUE_TYPE_ACCOUNT } from '~/constants';
import IssuesTable from './issues-table';
import useMCIssuesTypeFilter from '~/hooks/useMCIssuesTypeFilter';
import mockIssue from '~/tests/mock-issue';

describe( 'Issues Table', () => {
	it( 'Requests account issues', () => {
		useMCIssuesTypeFilter.mockReturnValue( {
			data: {
				issues: [ mockIssue( 1, { type: ISSUE_TYPE_ACCOUNT } ) ],
				total: 1,
			},
			page: 1,
			hasFinishedResolution: true,
		} );

		render( <IssuesTable /> );

		expect( useMCIssuesTypeFilter ).toHaveBeenCalledWith(
			ISSUE_TYPE_ACCOUNT,
			1
		);
	} );

	describe( 'Table behaviour', () => {
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
