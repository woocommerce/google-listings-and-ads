jest.mock( '~/utils/tracks', () => {
	return {
		recordGlaEvent: jest.fn(),
	};
} );

/**
 * External dependencies
 */
import { fireEvent, screen, render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import ReviewRequest from './';
import { recordGlaEvent } from '~/utils/tracks';

function isNotRendering( accountState ) {
	const { queryByTestId, queryByRole } = render(
		<ReviewRequest account={ accountState } />
	);
	expect( queryByTestId( 'gla-review-request-notice' ) ).toBeFalsy();
	expect( queryByRole( 'dialog' ) ).toBeFalsy();
}

describe( 'Request Review Component', () => {
	it.each( [ 'DISAPPROVED', 'WARNING' ] )(
		'Status %s renders the component',
		( status ) => {
			const { queryByTestId } = render(
				<ReviewRequest
					account={ {
						hasFinishedResolution: true,
						data: { status, issues: [], reviewAction: null },
					} }
				/>
			);
			expect( queryByTestId( 'gla-review-request-notice' ) ).toBeTruthy();
		}
	);

	it.each( [ 'DISAPPROVED', 'WARNING' ] )(
		'Status %s opens the modal on click',
		( status ) => {
			const { queryByRole } = render(
				<ReviewRequest
					account={ {
						hasFinishedResolution: true,
						data: {
							status,
							issues: [ '#1', '#2' ],
							reviewAction: {
								type: 'in_app',
								isAvailable: true,
							},
						},
					} }
				/>
			);
			expect( queryByRole( 'dialog' ) ).toBeFalsy();
			const button = queryByRole( 'button' );
			expect( button ).toBeTruthy();
			fireEvent.click( button );
			expect( queryByRole( 'dialog' ) ).toBeTruthy();
			expect( recordGlaEvent ).toHaveBeenCalledWith( 'gla_modal_open', {
				context: 'request-review',
			} );

			fireEvent.click( screen.queryByText( 'Cancel' ) );
			expect( queryByRole( 'dialog' ) ).toBeFalsy();
			expect( recordGlaEvent ).toHaveBeenCalledWith( 'gla_modal_closed', {
				context: 'request-review',
				action: 'maybe-later',
			} );
		}
	);

	// eslint-disable-next-line jest/expect-expect
	it.each( [ 'APPROVED', 'ONBOARDING', 'UNDER_REVIEW', 'UNKNOWN' ] )(
		'Status %s not rendering the notice',
		( status ) => {
			isNotRendering( {
				hasFinishedResolution: true,
				data: { status },
			} );
		}
	);

	// eslint-disable-next-line jest/expect-expect
	it( "Doesn't render if it is resolving", () => {
		isNotRendering( {
			hasFinishedResolution: false,
			data: { status: 'WARNING' },
		} );
	} );
} );
