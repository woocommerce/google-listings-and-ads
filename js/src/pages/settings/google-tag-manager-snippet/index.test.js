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
import useGoogleTagManagerSettings from '~/hooks/useGoogleTagManagerSettings';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';

jest.mock( '~/data', () => ( {
	...jest.requireActual( '~/data' ),
	useAppDispatch: jest.fn().mockName( 'useAppDispatch' ),
} ) );
jest.mock( '~/hooks/useGoogleTagManagerAccount', () =>
	jest.fn().mockName( 'useGoogleTagManagerAccount' )
);
jest.mock( '~/hooks/useGoogleTagManagerSettings', () =>
	jest.fn().mockName( 'useGoogleTagManagerSettings' )
);
jest.mock( '~/hooks/useDispatchCoreNotices', () =>
	jest.fn().mockName( 'useDispatchCoreNotices' )
);

const CONNECT_PROMPT =
	'Please connect your Google Tag Manager account in order to manage your script.';
const CONNECTED_HELP =
	'Enable the Google Tag Manager snippet to allow for tracking on your store.';

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

/**
 * Mocks `useGoogleTagManagerSettings`.
 *
 * @param {Object|null} settings The settings payload to mock.
 * @param {boolean} [hasFinishedResolution] Whether the resolver has finished.
 */
function mockSettings( settings, hasFinishedResolution = true ) {
	useGoogleTagManagerSettings.mockReturnValue( {
		settings,
		hasFinishedResolution,
	} );
}

/**
 * @return {HTMLElement} The snippet toggle.
 */
function getToggle() {
	return screen.getByRole( 'checkbox', {
		name: 'Google Tag Manager Snippet',
	} );
}

describe( 'GoogleTagManagerSnippet', () => {
	let updateGoogleTagManagerSettings;
	let createNotice;

	beforeEach( () => {
		updateGoogleTagManagerSettings = jest.fn().mockResolvedValue( {} );
		createNotice = jest.fn();

		useAppDispatch.mockReturnValue( { updateGoogleTagManagerSettings } );
		useDispatchCoreNotices.mockReturnValue( { createNotice } );
		mockAccount( { status: GOOGLE_TAG_MANAGER_ACCOUNT_STATUS.CONNECTED } );
		mockSettings( { snippetInjectionEnabled: true } );
	} );

	it.each( [
		[ 'the connection', () => mockAccount( null, false ) ],
		[ 'the settings', () => mockSettings( null, false ) ],
	] )(
		'shows a spinner instead of the toggle while %s is loading',
		( _, mock ) => {
			mock();

			render( <GoogleTagManagerSnippet /> );

			expect(
				screen.queryByRole( 'checkbox', {
					name: 'Google Tag Manager Snippet',
				} )
			).not.toBeInTheDocument();
		}
	);

	it.each( [
		GOOGLE_TAG_MANAGER_ACCOUNT_STATUS.DISCONNECTED,
		GOOGLE_TAG_MANAGER_ACCOUNT_STATUS.INCOMPLETE,
	] )(
		'disables the toggle and prompts to connect when the connection is %s',
		( status ) => {
			mockAccount( { status } );

			render( <GoogleTagManagerSnippet /> );

			expect( getToggle() ).toBeDisabled();
			expect( getToggle() ).not.toBeChecked();
			expect( screen.getByText( CONNECT_PROMPT ) ).toBeInTheDocument();
		}
	);

	it( 'reflects the stored setting and shows the snippet help text when connected', () => {
		mockSettings( { snippetInjectionEnabled: false } );

		render( <GoogleTagManagerSnippet /> );

		expect( getToggle() ).toBeEnabled();
		expect( getToggle() ).not.toBeChecked();
		expect( screen.getByText( CONNECTED_HELP ) ).toBeInTheDocument();
	} );

	it( 'saves the new value and shows a success notice when toggled', async () => {
		render( <GoogleTagManagerSnippet /> );

		await userEvent.click( getToggle() );

		expect( updateGoogleTagManagerSettings ).toHaveBeenCalledWith( {
			snippet_injection_enabled: false,
		} );
		await waitFor( () => {
			expect( createNotice ).toHaveBeenCalledWith(
				'success',
				'Google Tag Manager snippet setting updated successfully.'
			);
		} );
	} );

	it( 'does not show a success notice and re-enables the toggle when saving fails', async () => {
		updateGoogleTagManagerSettings.mockRejectedValue(
			new Error( 'Request failed' )
		);

		render( <GoogleTagManagerSnippet /> );

		await userEvent.click( getToggle() );

		await waitFor( () => {
			expect( getToggle() ).toBeEnabled();
		} );
		expect( createNotice ).not.toHaveBeenCalled();
	} );
} );
