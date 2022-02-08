jest.mock( '.~/hooks/useActiveIssueType' );

/**
 * External dependencies
 */
import { fireEvent, render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import useActiveIssueType from '.~/hooks/useActiveIssueType';
import ReviewRequest from '.~/product-feed/review-request/index';

describe( 'Request Review Component', () => {
	it.each( [ 'DISAPPROVED', 'WARNING', 'BLOCKED', 'UNDER_REVIEW' ] )(
		'Status %s renders the component on account issues',
		( status ) => {
			useActiveIssueType.mockReturnValue( 'account' );
			const { queryByTestId } = render(
				<ReviewRequest
					account={ {
						hasFinishedResolution: true,
						data: { status },
					} }
				/>
			);
			expect( queryByTestId( 'gla-review-request-notice' ) ).toBeTruthy();
		}
	);

	it.each( [ 'DISAPPROVED', 'WARNING' ] )(
		'Status %s opens the modal on click',
		( status ) => {
			useActiveIssueType.mockReturnValue( 'account' );
			const { queryByRole } = render(
				<ReviewRequest
					account={ {
						hasFinishedResolution: true,
						data: { status, issues: [ '#1', '#2' ] },
					} }
				/>
			);
			expect( queryByRole( 'dialog' ) ).toBeFalsy();
			const button = queryByRole( 'button' );
			expect( button ).toBeTruthy();

			fireEvent.click( button );
			expect( queryByRole( 'dialog' ) ).toBeTruthy();
		}
	);

	// eslint-disable-next-line jest/expect-expect
	it( "Doesn't render on Status APPROVED", () => {
		useActiveIssueType.mockReturnValue( 'account' );
		isNotRendering( {
			hasFinishedResolution: true,
			data: { status: 'APPROVED' },
		} );
	} );

	// eslint-disable-next-line jest/expect-expect
	it( "Doesn't render if it is resolving", () => {
		useActiveIssueType.mockReturnValue( 'account' );
		isNotRendering( {
			hasFinishedResolution: false,
			data: { status: 'WARNING' },
		} );
	} );

	// eslint-disable-next-line jest/expect-expect
	it( "Doesn't render if it is on Product Issues", () => {
		useActiveIssueType.mockReturnValue( 'product' );
		isNotRendering( {
			hasFinishedResolution: true,
			data: { status: 'WARNING' },
		} );
	} );
} );

function isNotRendering( accountState ) {
	const { queryByTestId, queryByRole } = render(
		<ReviewRequest account={ accountState } />
	);
	expect( queryByTestId( 'gla-review-request-notice' ) ).toBeFalsy();
	expect( queryByRole( 'dialog' ) ).toBeFalsy();
}
