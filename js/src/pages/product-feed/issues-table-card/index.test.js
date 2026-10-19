jest.mock( '~/hooks/useMCIssuesTypeFilter', () => ( {
	__esModule: true,
	default: jest.fn().mockName( 'useMCIssuesTypeFilter' ),
} ) );

jest.mock( '~/hooks/useAppSelectDispatch', () =>
	jest.fn().mockName( 'useAppSelectDispatch' )
);

/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import IssuesTableCard from './';
import useMCIssuesTypeFilter from '~/hooks/useMCIssuesTypeFilter';
import useAppSelectDispatch from '~/hooks/useAppSelectDispatch';

const mockIssueTotals = ( { account, product } ) =>
	useMCIssuesTypeFilter.mockImplementation( ( issueType ) => ( {
		data: {
			issues: [],
			total: issueType === 'product' ? product : account,
		},
		page: 1,
	} ) );

const mockAccountStatus = ( status ) =>
	useAppSelectDispatch.mockReturnValue( {
		hasFinishedResolution: true,
		data: { status, issues: [], reviewAction: null },
	} );

const queryHeading = () =>
	screen.queryByRole( 'heading', { name: 'Account issues' } );

describe( 'Issues Table Card', () => {
	beforeEach( () => {
		mockAccountStatus( 'APPROVED' );
	} );

	it( 'Rendering when there are account issues, as the target of the overview link', () => {
		mockIssueTotals( { account: 2, product: 0 } );
		render( <IssuesTableCard /> );

		expect( queryHeading().closest( '#gla-account-issues' ) ).toBeTruthy();
		expect( screen.queryByRole( 'tablist' ) ).not.toBeInTheDocument();
	} );

	it( 'Not rendering when there are only product issues', () => {
		mockIssueTotals( { account: 0, product: 5 } );
		render( <IssuesTableCard /> );

		expect( queryHeading() ).not.toBeInTheDocument();
	} );

	it.each( [ 'DISAPPROVED', 'WARNING' ] )(
		'Rendering with the review request when the account is %s, even without account issues',
		( status ) => {
			mockIssueTotals( { account: 0, product: 0 } );
			mockAccountStatus( status );
			render( <IssuesTableCard /> );

			expect( queryHeading() ).toBeInTheDocument();
			expect(
				screen.getByTestId( 'gla-review-request-notice' )
			).toBeInTheDocument();
		}
	);
} );
