jest.mock( '.~/hooks/useAppSelectDispatch' );
jest.mock( '.~/hooks/useActiveIssueType' );

/**
 * External dependencies
 */
import { fireEvent, render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import useAppSelectDispatch from '.~/hooks/useAppSelectDispatch';
import useActiveIssueType from '.~/hooks/useActiveIssueType';
import ReviewRequest from '.~/product-feed/review-request/index';

describe( 'Request Review Component', () => {
	it.each( [ 'DISAPPROVED', 'WARNING', 'BLOCKED', 'UNDER_REVIEW' ] )(
		'Status %s renders the component on account issues',
		( status ) => {
			useAppSelectDispatch.mockReturnValue( {
				hasFinishedResolution: true,
				data: { status },
			} );
			useActiveIssueType.mockReturnValue( 'account' );
			const { queryByTestId } = render( <ReviewRequest /> );
			expect( queryByTestId( 'gla-review-request-notice' ) ).toBeTruthy();
		}
	);

	it.each( [ 'DISAPPROVED', 'WARNING' ] )(
		'Status %s opens the modal on click',
		( status ) => {
			useAppSelectDispatch.mockReturnValue( {
				hasFinishedResolution: true,
				data: { status, issues: [ '#1', '#2' ] },
			} );
			useActiveIssueType.mockReturnValue( 'account' );
			const { queryByRole } = render( <ReviewRequest /> );
			expect( queryByRole( 'dialog' ) ).toBeFalsy();
			const button = queryByRole( 'button' );
			expect( button ).toBeTruthy();

			fireEvent.click( button );
			expect( queryByRole( 'dialog' ) ).toBeTruthy();
		}
	);

	// eslint-disable-next-line jest/expect-expect
	it( "Doesn't render on Status APPROVED", () => {
		useAppSelectDispatch.mockReturnValue( {
			hasFinishedResolution: true,
			data: { status: 'APPROVED' },
		} );
		useActiveIssueType.mockReturnValue( 'account' );
		isNotRendering();
	} );

	// eslint-disable-next-line jest/expect-expect
	it( "Doesn't render if it is resolving", () => {
		useAppSelectDispatch.mockReturnValue( {
			hasFinishedResolution: false,
			data: { status: 'WARNING' },
		} );
		useActiveIssueType.mockReturnValue( 'account' );
		isNotRendering();
	} );

	// eslint-disable-next-line jest/expect-expect
	it( "Doesn't render if it is on Product Issues", () => {
		useAppSelectDispatch.mockReturnValue( {
			hasFinishedResolution: true,
			data: { status: 'WARNING' },
		} );
		useActiveIssueType.mockReturnValue( 'product' );
		isNotRendering();
	} );
} );

function isNotRendering() {
	const { queryByTestId, queryByRole } = render( <ReviewRequest /> );
	expect( queryByTestId( 'gla-review-request-notice' ) ).toBeFalsy();
	expect( queryByRole( 'dialog' ) ).toBeFalsy();
}
