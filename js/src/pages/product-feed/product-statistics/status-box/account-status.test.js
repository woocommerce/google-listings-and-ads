/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { REVIEW_STATUSES } from '../../constants';
import AccountStatus from './account-status';

jest.mock( '~/hooks/useAppSelectDispatch' );
import useAppSelectDispatch from '~/hooks/useAppSelectDispatch';

const WITH_PRODUCTS = {
	active: 3,
	expiring: 0,
	pending: 0,
	disapproved: 0,
	not_synced: 0,
};

const NO_PRODUCTS = {
	active: 0,
	expiring: 0,
	pending: 0,
	disapproved: 0,
	not_synced: 0,
};

function mockStores( { review, statistics } ) {
	useAppSelectDispatch.mockImplementation( ( selector ) => {
		if ( selector === 'getMCProductStatistics' ) {
			return {
				hasFinishedResolution: true,
				data: statistics === undefined ? null : { statistics },
			};
		}
		return review;
	} );
}

describe( 'Account Status', () => {
	it.each( [ 'DISAPPROVED', 'WARNING', 'UNDER_REVIEW' ] )(
		'Renders the %s status directly',
		( status ) => {
			mockStores( {
				review: { hasFinishedResolution: true, data: { status } },
				statistics: NO_PRODUCTS,
			} );

			const { queryByText } = render( <AccountStatus /> );

			expect(
				queryByText( REVIEW_STATUSES[ status ].statusDescription )
			).toBeTruthy();
		}
	);

	it( 'Renders Approved when the account is issue-free and has products', () => {
		mockStores( {
			review: {
				hasFinishedResolution: true,
				data: { status: 'APPROVED' },
			},
			statistics: WITH_PRODUCTS,
		} );

		const { queryByText } = render( <AccountStatus /> );

		expect(
			queryByText( REVIEW_STATUSES.APPROVED.statusDescription )
		).toBeTruthy();
	} );

	it( 'Renders Onboarding when the account is issue-free but has no products', () => {
		mockStores( {
			review: {
				hasFinishedResolution: true,
				data: { status: 'APPROVED' },
			},
			statistics: NO_PRODUCTS,
		} );

		const { queryByText } = render( <AccountStatus /> );

		expect(
			queryByText( REVIEW_STATUSES.ONBOARDING.statusDescription )
		).toBeTruthy();
		expect(
			queryByText( REVIEW_STATUSES.APPROVED.statusDescription )
		).toBeFalsy();
	} );

	it( 'Renders nothing while product statistics are still loading', () => {
		mockStores( {
			review: {
				hasFinishedResolution: true,
				data: { status: 'APPROVED' },
			},
			statistics: undefined,
		} );

		const { queryByText } = render( <AccountStatus /> );

		expect( queryByText( /Account status:/ ) ).toBeFalsy();
	} );

	it( 'Doesnt render unknown statuses', () => {
		mockStores( {
			review: {
				hasFinishedResolution: true,
				data: { status: 'unknown' },
			},
			statistics: WITH_PRODUCTS,
		} );

		const { queryByText } = render( <AccountStatus /> );

		expect( queryByText( /Account status:/ ) ).toBeFalsy();
	} );

	it( 'Doesnt render before the review status resolves', () => {
		mockStores( {
			review: { hasFinishedResolution: false, data: null },
			statistics: WITH_PRODUCTS,
		} );

		const { queryByText } = render( <AccountStatus /> );

		expect( queryByText( /Account status:/ ) ).toBeFalsy();
	} );
} );
