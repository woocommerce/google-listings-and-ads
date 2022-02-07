/**
 * External dependencies
 */
import { fireEvent, render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import ReviewRequestNotice from '.~/product-feed/review-request/review-request-notice';

describe( 'Request Review Notice', () => {
	it.each( [ 'DISAPPROVED', 'WARNING' ] )(
		'Status %s shows Request Button calls onRequestReviewClick on click',
		( status ) => {
			const onRequestReviewClick = jest
				.fn()
				.mockName( 'onRequestReviewClick' );

			const { queryByText, queryByRole } = render(
				<ReviewRequestNotice
					data={ { status } }
					onRequestReviewClick={ onRequestReviewClick }
				/>
			);
			expect(
				queryByText(
					'Fix all account suspension issues listed below to request a review of your account.'
				)
			).toBeTruthy();

			const button = queryByRole( 'button' );

			expect( button ).toBeTruthy();

			fireEvent.click( button );
			expect( onRequestReviewClick ).toBeCalledTimes( 1 );
		}
	);

	it( 'Status BLOCKED shows Request Button but is disabled', () => {
		const onRequestReviewClick = jest
			.fn()
			.mockName( 'onRequestReviewClick' );

		const { queryByText, queryByRole } = render(
			<ReviewRequestNotice
				data={ { status: 'BLOCKED', nextAttempt: 1644220897 } }
				onRequestReviewClick={ onRequestReviewClick }
			/>
		);

		expect(
			queryByText( /You can request a new review on 07 Feb 2022/ )
		).toBeTruthy();
		expect( queryByText( /Learn more/ ) ).toBeTruthy();

		const button = queryByRole( 'button' );

		expect( button ).toBeTruthy();

		fireEvent.click( button );
		expect( onRequestReviewClick ).not.toHaveBeenCalled();
	} );

	it( "Status UNDER_REVIEW doesn't have Request button", () => {
		const { queryByText, queryByRole } = render(
			<ReviewRequestNotice data={ { status: 'UNDER_REVIEW' } } />
		);
		expect( queryByText( 'Account review in progress.' ) ).toBeTruthy();
		expect( queryByRole( 'button' ) ).toBeFalsy();
	} );
} );
