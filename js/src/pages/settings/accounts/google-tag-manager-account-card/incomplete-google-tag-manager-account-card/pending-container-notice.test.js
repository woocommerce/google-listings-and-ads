/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import PendingContainerNotice from './pending-container-notice';
import { useAppDispatch } from '~/data';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import { handleApiError } from '~/utils/handleError';
import { recordGlaEvent } from '~/utils/tracks';

jest.mock( '~/data', () => ( {
	...jest.requireActual( '~/data' ),
	useAppDispatch: jest.fn().mockName( 'useAppDispatch' ),
} ) );
jest.mock( '~/hooks/useApiFetchCallback' );
jest.mock( '~/utils/handleError', () => ( {
	...jest.requireActual( '~/utils/handleError' ),
	handleApiError: jest.fn().mockName( 'handleApiError' ),
} ) );
jest.mock( '~/utils/tracks', () => ( {
	...jest.requireActual( '~/utils/tracks' ),
	recordGlaEvent: jest.fn().mockName( 'recordGlaEvent' ),
} ) );
jest.mock( '~/hooks/useDispatchCoreNotices', () =>
	jest.fn().mockName( 'useDispatchCoreNotices' )
);

describe( 'PendingContainerNotice', () => {
	let fetchConnectContainer;
	let fetchGoogleTagManagerAccount;
	let fetchGoogleTagManagerSettings;

	beforeEach( () => {
		jest.clearAllMocks();

		useDispatchCoreNotices.mockReturnValue( {
			createNotice: jest.fn().mockName( 'createNotice' ),
		} );

		fetchConnectContainer = jest
			.fn()
			.mockName( 'fetchConnectContainer' )
			.mockResolvedValue();
		useApiFetchCallback.mockReturnValue( [
			fetchConnectContainer,
			{ loading: false },
		] );

		fetchGoogleTagManagerAccount = jest
			.fn()
			.mockName( 'fetchGoogleTagManagerAccount' )
			.mockResolvedValue( {
				account: { status: 'connected', id: '6002847391' },
			} );
		fetchGoogleTagManagerSettings = jest
			.fn()
			.mockName( 'fetchGoogleTagManagerSettings' )
			.mockResolvedValue( {
				settings: { snippetInjectionEnabled: true },
			} );
		useAppDispatch.mockReturnValue( {
			fetchGoogleTagManagerAccount,
			fetchGoogleTagManagerSettings,
		} );
	} );

	it( 'explains that the container was created but not connected, with only a "Try again" action', () => {
		render( <PendingContainerNotice containerId="98765433" /> );

		expect(
			screen.getByText(
				"The container was created, but its connection couldn't be confirmed. Please try again.",
				{ selector: 'p' }
			)
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'button', { name: 'Try again' } )
		).toBeInTheDocument();
		expect(
			screen.queryByRole( 'button', { name: 'Save' } )
		).not.toBeInTheDocument();
		expect( screen.queryByRole( 'combobox' ) ).not.toBeInTheDocument();
	} );

	it( 'selects the pending container by its ID, then refreshes the connection', async () => {
		const user = userEvent.setup();

		render( <PendingContainerNotice containerId="98765433" /> );
		await user.click( screen.getByRole( 'button', { name: 'Try again' } ) );

		expect( useApiFetchCallback ).toHaveBeenCalledWith(
			expect.objectContaining( {
				method: 'POST',
				data: { id: '98765433' },
			} )
		);
		expect( fetchConnectContainer ).toHaveBeenCalledTimes( 1 );
		expect( screen.queryByRole( 'dialog' ) ).not.toBeInTheDocument();
		await waitFor( () =>
			expect( fetchGoogleTagManagerAccount ).toHaveBeenCalledTimes( 1 )
		);
		expect( fetchGoogleTagManagerSettings ).toHaveBeenCalledTimes( 1 );
		expect( recordGlaEvent ).toHaveBeenCalledWith(
			'gla_google_tag_manager_container_connected',
			{ context: 'settings-tag-manager', gtm_account_id: '6002847391' }
		);
	} );

	it( 'reports the error and does not refresh when connecting fails', async () => {
		const user = userEvent.setup();
		const error = new Error( 'Request failed' );
		fetchConnectContainer.mockRejectedValue( error );

		render( <PendingContainerNotice containerId="98765433" /> );
		await user.click( screen.getByRole( 'button', { name: 'Try again' } ) );

		await waitFor( () =>
			expect( handleApiError ).toHaveBeenCalledWith(
				error,
				'There was an error connecting your Google Tag Manager container.'
			)
		);
		expect( fetchGoogleTagManagerAccount ).not.toHaveBeenCalled();
		expect(
			screen.getByRole( 'button', { name: 'Try again' } )
		).toBeEnabled();
	} );

	it( 'disables "Try again" while the container is being connected', async () => {
		useApiFetchCallback.mockReturnValue( [
			fetchConnectContainer,
			{ loading: true },
		] );

		render( <PendingContainerNotice containerId="98765433" /> );

		expect(
			screen.getByRole( 'button', { name: 'Try again' } )
		).toBeDisabled();
	} );

	it( 'disables "Try again" while the connection is being refreshed', async () => {
		const user = userEvent.setup();
		let resolveAccount;
		fetchGoogleTagManagerAccount.mockReturnValue(
			new Promise( ( resolve ) => {
				resolveAccount = resolve;
			} )
		);

		render( <PendingContainerNotice containerId="98765433" /> );
		const button = screen.getByRole( 'button', { name: 'Try again' } );
		await user.click( button );

		await waitFor( () => expect( button ).toBeDisabled() );

		resolveAccount( { account: { status: 'connected' } } );

		await waitFor( () => expect( button ).toBeEnabled() );
	} );
} );
