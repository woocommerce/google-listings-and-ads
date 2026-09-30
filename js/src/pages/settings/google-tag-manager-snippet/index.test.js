/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import GoogleTagManagerSnippet from './index';
import { GOOGLE_TAG_MANAGER_ACCOUNT_STATUS } from '~/constants';
import { useAppDispatch } from '~/data';
import useGoogleTagManagerAccount from '~/hooks/useGoogleTagManagerAccount';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';

jest.mock( '~/data', () => ( {
	...jest.requireActual( '~/data' ),
	useAppDispatch: jest.fn().mockName( 'useAppDispatch' ),
} ) );
jest.mock( '~/hooks/useGoogleTagManagerAccount', () =>
	jest.fn().mockName( 'useGoogleTagManagerAccount' )
);
jest.mock( '~/hooks/useDispatchCoreNotices', () =>
	jest.fn().mockName( 'useDispatchCoreNotices' )
);

/**
 * Mocks `useGoogleTagManagerAccount`.
 *
 * @param {Object|null} account The account payload to mock.
 * @param {boolean} [hasFinishedResolution] Whether the resolver has finished.
 */
function mockAccount( account, hasFinishedResolution = true ) {
	useGoogleTagManagerAccount.mockReturnValue( {
		account,
		hasFinishedResolution,
	} );
}

const connectedAccount = {
	status: GOOGLE_TAG_MANAGER_ACCOUNT_STATUS.CONNECTED,
	id: '123',
	containerId: '456',
	containerPublicId: 'GTM-ABCDEFG',
	snippetInjectionEnabled: true,
};

describe( 'GoogleTagManagerSnippet', () => {
	let updateGoogleTagManagerSnippetInjection;
	let createNotice;

	beforeEach( () => {
		updateGoogleTagManagerSnippetInjection = jest
			.fn()
			.mockResolvedValue( {} );
		createNotice = jest.fn();

		useAppDispatch.mockReturnValue( {
			updateGoogleTagManagerSnippetInjection,
		} );
		useDispatchCoreNotices.mockReturnValue( { createNotice } );
	} );

	it( 'renders nothing while the connection is still loading', () => {
		mockAccount( null, false );

		const { container } = render( <GoogleTagManagerSnippet /> );

		expect( container ).toBeEmptyDOMElement();
	} );

	it.each( [
		GOOGLE_TAG_MANAGER_ACCOUNT_STATUS.DISCONNECTED,
		GOOGLE_TAG_MANAGER_ACCOUNT_STATUS.INCOMPLETE,
	] )( 'renders nothing when the connection is %s', ( status ) => {
		mockAccount( { status } );

		const { container } = render( <GoogleTagManagerSnippet /> );

		expect( container ).toBeEmptyDOMElement();
	} );

	it( 'reflects the stored snippet injection state when connected', () => {
		mockAccount( { ...connectedAccount, snippetInjectionEnabled: false } );

		render( <GoogleTagManagerSnippet /> );

		expect(
			screen.getByRole( 'checkbox', {
				name: 'Google Tag Manager Snippet',
			} )
		).not.toBeChecked();
	} );

	it( 'saves the new value and shows a success notice when toggled', async () => {
		mockAccount( connectedAccount );

		render( <GoogleTagManagerSnippet /> );

		await userEvent.click(
			screen.getByRole( 'checkbox', {
				name: 'Google Tag Manager Snippet',
			} )
		);

		expect( updateGoogleTagManagerSnippetInjection ).toHaveBeenCalledWith(
			false
		);
		await waitFor( () => {
			expect( createNotice ).toHaveBeenCalledWith(
				'success',
				'Google Tag Manager snippet setting updated successfully.'
			);
		} );
	} );

	it( 'does not show a success notice and re-enables the toggle when saving fails', async () => {
		updateGoogleTagManagerSnippetInjection.mockRejectedValue(
			new Error( 'Request failed' )
		);
		mockAccount( connectedAccount );

		render( <GoogleTagManagerSnippet /> );

		const toggle = screen.getByRole( 'checkbox', {
			name: 'Google Tag Manager Snippet',
		} );
		await userEvent.click( toggle );

		await waitFor( () => {
			expect( toggle ).toBeEnabled();
		} );
		expect( createNotice ).not.toHaveBeenCalled();
	} );
} );
