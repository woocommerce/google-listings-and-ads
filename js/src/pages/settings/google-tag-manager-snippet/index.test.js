/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { getHistory, getQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import GoogleTagManagerSnippet from './index';
import { GOOGLE_TAG_MANAGER_ACCOUNT_STATUS } from '~/constants';
import { useAppDispatch } from '~/data';
import useGoogleTagManagerAccount from '~/hooks/useGoogleTagManagerAccount';
import useGoogleTagManagerSettings from '~/hooks/useGoogleTagManagerSettings';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import useScrollIntoView from '~/hooks/useScrollIntoView';

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
jest.mock( '~/hooks/useScrollIntoView', () =>
	jest.fn().mockName( 'useScrollIntoView' )
);
jest.mock( '@woocommerce/navigation', () => ( {
	...jest.requireActual( '@woocommerce/navigation' ),
	getQuery: jest.fn().mockName( 'getQuery' ),
	getHistory: jest.fn().mockName( 'getHistory' ),
} ) );
jest.mock( './conflict-notice', () =>
	jest
		.fn( ( { containerPublicId } ) => (
			<div>Conflict notice for { containerPublicId }</div>
		) )
		.mockName( 'ConflictNotice' )
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
	let scrollIntoView;
	let replace;

	beforeEach( () => {
		updateGoogleTagManagerSettings = jest.fn().mockResolvedValue( {} );
		createNotice = jest.fn();
		scrollIntoView = jest.fn();
		replace = jest.fn();

		useAppDispatch.mockReturnValue( { updateGoogleTagManagerSettings } );
		useDispatchCoreNotices.mockReturnValue( { createNotice } );
		useScrollIntoView.mockReturnValue( {
			containerRef: { current: null },
			scrollIntoView,
		} );
		getQuery.mockReturnValue( {} );
		getHistory.mockReturnValue( { replace } );
		mockAccount( {
			status: GOOGLE_TAG_MANAGER_ACCOUNT_STATUS.CONNECTED,
			containerPublicId: 'GTM-ABC1234',
		} );
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

	it( 'warns about a Google Ads conversion tag in the connected container', () => {
		mockSettings( {
			snippetInjectionEnabled: false,
			adsConversionConflict: true,
		} );

		render( <GoogleTagManagerSnippet /> );

		expect(
			screen.getByText( 'Conflict notice for GTM-ABC1234' )
		).toBeInTheDocument();
		expect( getToggle() ).not.toBeChecked();
	} );

	it( 'shows no conflict warning when the container has no Google Ads conversion tag', () => {
		mockSettings( {
			snippetInjectionEnabled: true,
			adsConversionConflict: false,
		} );

		render( <GoogleTagManagerSnippet /> );

		expect(
			screen.queryByText( /Conflict notice/ )
		).not.toBeInTheDocument();
	} );

	it( 'shows no conflict warning while no container is connected', () => {
		mockAccount( {
			status: GOOGLE_TAG_MANAGER_ACCOUNT_STATUS.DISCONNECTED,
		} );
		mockSettings( {
			snippetInjectionEnabled: true,
			adsConversionConflict: true,
		} );

		render( <GoogleTagManagerSnippet /> );

		expect(
			screen.queryByText( /Conflict notice/ )
		).not.toBeInTheDocument();
	} );

	it( 'scrolls into view once loaded when opened from the conflict snackbar, then drops the query arg', () => {
		getQuery.mockReturnValue( {
			'scroll-to': 'google-tag-manager-snippet',
		} );

		render( <GoogleTagManagerSnippet /> );

		expect( scrollIntoView ).toHaveBeenCalledTimes( 1 );
		expect( replace ).toHaveBeenCalledTimes( 1 );
		expect( replace.mock.calls[ 0 ][ 0 ] ).not.toContain( 'scroll-to' );
	} );

	it( 'waits for the settings to load before scrolling into view', () => {
		getQuery.mockReturnValue( {
			'scroll-to': 'google-tag-manager-snippet',
		} );
		mockSettings( null, false );

		render( <GoogleTagManagerSnippet /> );

		expect( scrollIntoView ).not.toHaveBeenCalled();
	} );

	it( 'does not scroll into view on a regular visit', () => {
		render( <GoogleTagManagerSnippet /> );

		expect( scrollIntoView ).not.toHaveBeenCalled();
		expect( replace ).not.toHaveBeenCalled();
	} );
} );
