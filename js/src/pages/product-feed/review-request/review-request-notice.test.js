/**
 * External dependencies
 */
import { fireEvent, render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import ReviewRequestNotice from './review-request-notice';

describe( 'Request Review Notice', () => {
	it.each( [ 'DISAPPROVED', 'WARNING' ] )(
		'Status %s with an available in-app action shows the button and calls onRequestReviewClick on click',
		( status ) => {
			const onRequestReviewClick = jest
				.fn()
				.mockName( 'onRequestReviewClick' );

			const { queryByText, queryByRole } = render(
				<ReviewRequestNotice
					account={ {
						status,
						reviewAction: { type: 'in_app', isAvailable: true },
					} }
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
			expect( onRequestReviewClick ).toHaveBeenCalledTimes( 1 );
		}
	);

	it( 'Renders a Merchant Center link for a redirect action instead of requesting in-app', () => {
		const onRequestReviewClick = jest
			.fn()
			.mockName( 'onRequestReviewClick' );

		const { queryByRole } = render(
			<ReviewRequestNotice
				account={ {
					status: 'DISAPPROVED',
					reviewAction: {
						type: 'redirect',
						isAvailable: true,
						uri: 'https://merchants.google.com/review',
					},
				} }
				onRequestReviewClick={ onRequestReviewClick }
			/>
		);

		const link = queryByRole( 'link' );
		expect( link ).toBeTruthy();
		expect( link.getAttribute( 'href' ) ).toBe(
			'https://merchants.google.com/review'
		);
		expect( link.getAttribute( 'target' ) ).toBe( '_blank' );
		expect( link.getAttribute( 'rel' ) ).toContain( 'noopener' );

		fireEvent.click( link );
		expect( onRequestReviewClick ).not.toHaveBeenCalled();
	} );

	it( 'Does not render the request button when the review action is unavailable', () => {
		const { queryByText, queryByRole } = render(
			<ReviewRequestNotice
				account={ {
					status: 'DISAPPROVED',
					reviewAction: { type: 'in_app', isAvailable: false },
				} }
			/>
		);

		expect(
			queryByText(
				'Fix all account suspension issues listed below to request a review of your account.'
			)
		).toBeTruthy();

		expect( queryByRole( 'button' ) ).toBeFalsy();
		expect( queryByRole( 'link' ) ).toBeFalsy();
	} );

	it( 'Does not render the request button when there is no review action', () => {
		const { queryByRole } = render(
			<ReviewRequestNotice
				account={ { status: 'DISAPPROVED', reviewAction: null } }
			/>
		);

		expect( queryByRole( 'button' ) ).toBeFalsy();
		expect( queryByRole( 'link' ) ).toBeFalsy();
	} );
} );
