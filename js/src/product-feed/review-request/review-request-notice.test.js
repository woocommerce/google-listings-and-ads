/**
 * External dependencies
 */
import { fireEvent, render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import ReviewRequestNotice from '.~/product-feed/review-request/review-request-notice';
import { format as formatDate } from '@wordpress/date';

describe( 'Request Review Notice', () => {
	it.each( [ 'DISAPPROVED', 'WARNING' ] )(
		'Status %s shows Request Button calls onRequestReviewClick on click',
		( status ) => {
			const onRequestReviewClick = jest
				.fn()
				.mockName( 'onRequestReviewClick' );

			const { queryByText, queryByRole } = render(
				<ReviewRequestNotice
					account={ { status } }
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

	it( 'Renders date on cool down period', () => {
		const cooldown = 1651047106000;

		const onRequestReviewClick = jest
			.fn()
			.mockName( 'onRequestReviewClick' );

		const { queryByText, queryByRole } = render(
			<ReviewRequestNotice
				account={ { status: 'DISAPPROVED', cooldown } }
				onRequestReviewClick={ onRequestReviewClick }
			/>
		);

		const dateFormat = formatDate( `F j, Y, g:i a`, new Date( cooldown ) );

		expect(
			queryByText(
				`Your account is under cool down period. You can request a new review on ${ dateFormat }.`
			)
		).toBeTruthy();

		const button = queryByRole( 'button' );

		expect( button ).toBeTruthy();

		fireEvent.click( button );
		expect( onRequestReviewClick ).not.toBeCalled();
	} );
} );
