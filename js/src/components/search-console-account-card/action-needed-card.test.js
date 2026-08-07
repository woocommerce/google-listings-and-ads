/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import ActionNeededCard from './action-needed-card';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import { useAppDispatch } from '~/data';

jest.mock( '~/hooks/useApiFetchCallback', () =>
	jest.fn().mockName( 'useApiFetchCallback' )
);

jest.mock( '~/hooks/useDispatchCoreNotices', () =>
	jest.fn().mockName( 'useDispatchCoreNotices' )
);

jest.mock( '~/data', () => ( {
	...jest.requireActual( '~/data' ),
	useAppDispatch: jest.fn(),
} ) );

describe( 'ActionNeededCard', () => {
	let fetchVerify;
	let createNotice;
	let invalidateResolution;

	beforeEach( () => {
		fetchVerify = jest.fn().mockName( 'fetchVerify' );
		useApiFetchCallback.mockReturnValue( [
			fetchVerify,
			{ loading: false },
		] );

		createNotice = jest.fn().mockName( 'createNotice' );
		useDispatchCoreNotices.mockReturnValue( { createNotice } );

		invalidateResolution = jest.fn().mockName( 'invalidateResolution' );
		useAppDispatch.mockReturnValue( { invalidateResolution } );
	} );

	it( 'renders an "Action needed" message and a destructive re-verify button', () => {
		render( <ActionNeededCard /> );

		expect( screen.getByText( /Action needed:/ ) ).toBeInTheDocument();
		expect(
			screen.getByRole( 'button', { name: 'Verify site' } )
		).toBeInTheDocument();
	} );

	it( 'invalidates resolution after successfully re-verifying', async () => {
		const user = userEvent.setup();
		fetchVerify.mockResolvedValue( {} );

		render( <ActionNeededCard /> );

		await user.click(
			screen.getByRole( 'button', { name: 'Verify site' } )
		);

		expect( invalidateResolution ).toHaveBeenCalledWith(
			'getSearchConsoleAccount',
			[]
		);
	} );

	it( 'shows an error notice when re-verification fails', async () => {
		const user = userEvent.setup();
		fetchVerify.mockRejectedValue( new Error( 'failed' ) );

		render( <ActionNeededCard /> );

		await user.click(
			screen.getByRole( 'button', { name: 'Verify site' } )
		);

		expect( createNotice ).toHaveBeenCalledWith(
			'error',
			expect.stringContaining( 'Unable to verify' )
		);
	} );
} );
