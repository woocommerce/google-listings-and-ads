/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import REVIEW_STATUSES from '.~/product-feed/review-request/review-request-statuses';
import AccountStatus from '.~/product-feed/product-statistics/status-box/account-status';

jest.mock( '.~/hooks/useAppSelectDispatch' );
import useAppSelectDispatch from '.~/hooks/useAppSelectDispatch';

describe( 'Account Status', () => {
	it.each( Object.keys( REVIEW_STATUSES ) )(
		'Renders %s status',
		( status ) => {
			useAppSelectDispatch.mockReturnValue( {
				hasFinishedResolution: true,
				data: { status },
			} );

			const { queryByText } = render( <AccountStatus /> );

			expect(
				queryByText( REVIEW_STATUSES[ status ].statusDescription )
			).toBeTruthy();
		}
	);
} );
