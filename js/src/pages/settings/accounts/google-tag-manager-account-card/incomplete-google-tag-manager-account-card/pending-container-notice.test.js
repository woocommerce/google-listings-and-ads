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
		render( <PendingContainerNotice /> );

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

	it( 'connects the pending container without opening a modal or sending a name, then refreshes the connection', async () => {
		const user = userEvent.setup();

		render( <PendingContainerNotice /> );
		await user.click( screen.getByRole( 'button', { name: 'Try again' } ) );

		expect( fetchConnectContainer ).toHaveBeenCalledTimes( 1 );
		expect( fetchConnectContainer ).toHaveBeenCalledWith();
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

	it( 'only refreshes the card, without reporting an error, when the backend answers that a container is already connected', async () => {
		const user = userEvent.setup();
		fetchConnectContainer.mockRejectedValue( {
			code: 'API_ERROR',
			message: 'A Tag Manager container is already connected.',
			data: { reason: 'already_connected' },
		} );

		render( <PendingContainerNotice /> );
		await user.click( screen.getByRole( 'button', { name: 'Try again' } ) );

		await waitFor( () =>
			expect( fetchGoogleTagManagerAccount ).toHaveBeenCalledTimes( 1 )
		);
		expect( handleApiError ).not.toHaveBeenCalled();
	} );

	it( 'reports the error and does not refresh when connecting fails', async () => {
		const user = userEvent.setup();
		const error = new Error( 'Request failed' );
		fetchConnectContainer.mockRejectedValue( error );

		render( <PendingContainerNotice /> );
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

	it( 'disables "Try again" while the request is in flight', async () => {
		const user = userEvent.setup();
		let resolveConnect;
		fetchConnectContainer.mockReturnValue(
			new Promise( ( resolve ) => {
				resolveConnect = resolve;
			} )
		);

		render( <PendingContainerNotice /> );
		const button = screen.getByRole( 'button', { name: 'Try again' } );
		await user.click( button );

		expect( button ).toBeDisabled();

		resolveConnect();

		await waitFor( () => expect( button ).toBeEnabled() );
	} );
} );
