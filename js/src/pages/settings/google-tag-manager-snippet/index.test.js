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
import { useAppDispatch } from '~/data';
import useGoogleTagManagerStatus from '~/hooks/useGoogleTagManagerStatus';
import useGoogleTagManagerSettings from '~/hooks/useGoogleTagManagerSettings';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';

jest.mock( '~/data', () => ( {
	...jest.requireActual( '~/data' ),
	useAppDispatch: jest.fn().mockName( 'useAppDispatch' ),
} ) );
jest.mock( '~/hooks/useGoogleTagManagerStatus', () =>
	jest.fn().mockName( 'useGoogleTagManagerStatus' )
);
jest.mock( '~/hooks/useGoogleTagManagerSettings', () =>
	jest.fn().mockName( 'useGoogleTagManagerSettings' )
);
jest.mock( './ads-conversion-conflict-notice', () =>
	jest
		.fn( () => <div>Conflict notice</div> )
		.mockName( 'AdsConversionConflictNotice' )
);
jest.mock( '~/hooks/useDispatchCoreNotices', () =>
	jest.fn().mockName( 'useDispatchCoreNotices' )
);

const CONNECT_PROMPT =
	'Please connect your Google Tag Manager account in order to manage your script.';
const CONNECTED_HELP =
	'Enable the Google Tag Manager snippet to allow for tracking on your store.';

/**
 * Mocks `useGoogleTagManagerStatus`.
 *
 * @param {boolean} isConnected Whether a container is connected.
 */
function mockStatus( isConnected ) {
	useGoogleTagManagerStatus.mockReturnValue( {
		isConnected,
		hasFinishedResolution: true,
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
		name: 'Google Tag Manager snippet',
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
		mockStatus( true );
		mockSettings( { snippetInjectionEnabled: true } );
	} );

	it( 'shows a spinner instead of the toggle while loading', () => {
		mockSettings( null, false );

		render( <GoogleTagManagerSnippet /> );

		expect(
			screen.queryByRole( 'checkbox', {
				name: 'Google Tag Manager snippet',
			} )
		).not.toBeInTheDocument();
	} );

	it( 'disables the toggle and prompts to connect when no container is connected', () => {
		mockStatus( false );
		mockSettings( null );

		render( <GoogleTagManagerSnippet /> );

		expect( getToggle() ).toBeDisabled();
		expect( getToggle() ).not.toBeChecked();
		expect( screen.getByText( CONNECT_PROMPT ) ).toBeInTheDocument();
	} );

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

	it( 'shows the conflict notice above the toggle once loaded', () => {
		render( <GoogleTagManagerSnippet /> );

		expect( screen.getByText( 'Conflict notice' ) ).toBeInTheDocument();
	} );

	it( 'keeps the toggle disabled when the settings could not be loaded', () => {
		mockSettings( null );

		render( <GoogleTagManagerSnippet /> );

		expect( getToggle() ).toBeDisabled();
	} );
} );
