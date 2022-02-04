jest.mock( '.~/hooks/useAppSelectDispatch' );

/**
 * External dependencies
 */
import { fireEvent, render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import useAppSelectDispatch from '.~/hooks/useAppSelectDispatch';
import ReviewRequest from '.~/product-feed/review-request/index';

describe( 'Request Review Component', () => {
	it( 'Status DISAPPROVED shows Request Button and opens the modal on click', () => {
		useAppSelectDispatch.mockReturnValue( {
			hasFinishedResolution: true,
			data: { status: 'DISAPPROVED' },
		} );
		const { queryByText, queryByRole } = render( <ReviewRequest /> );
		expect(
			queryByText( 'We’ve found unresolved issues in your account.' )
		).toBeTruthy();

		const button = queryByRole( 'button' );

		expect( button ).toBeTruthy();

		fireEvent.click( button );
		expect( screen.queryByRole( 'dialog' ) ).toBeTruthy();
	} );

	it( 'Status BLOCKED shows Request Button but is disabled', () => {
		useAppSelectDispatch.mockReturnValue( {
			hasFinishedResolution: true,
			data: { status: 'BLOCKED' },
		} );
		const { queryByText, queryByRole } = render( <ReviewRequest /> );
		expect(
			queryByText( 'We’ve found unresolved issues in your account.' )
		).toBeTruthy();

		const button = queryByRole( 'button' );

		expect( button ).toBeTruthy();

		fireEvent.click( button );
		expect( screen.queryByRole( 'dialog' ) ).toBeFalsy();
	} );

	it( "Status UNDER_REVIEW doesn't have Request button", () => {
		useAppSelectDispatch.mockReturnValue( {
			hasFinishedResolution: true,
			data: { status: 'UNDER_REVIEW' },
		} );
		const { queryByText, queryByRole } = render( <ReviewRequest /> );
		expect( queryByText( 'Account review in progress.' ) ).toBeTruthy();
		expect( queryByRole( 'button' ) ).toBeFalsy();
	} );
} );
