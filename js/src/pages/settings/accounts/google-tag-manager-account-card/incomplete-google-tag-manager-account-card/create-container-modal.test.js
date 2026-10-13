/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { getSetting } from '@woocommerce/settings'; // eslint-disable-line import/no-unresolved

/**
 * Internal dependencies
 */
import CreateContainerModal from './create-container-modal';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';
import { logError } from '~/utils/console';

jest.mock( '@woocommerce/settings', () => ( {
	...jest.requireActual( '@woocommerce/settings' ),
	getSetting: jest.fn().mockName( 'getSetting' ),
} ) );
jest.mock( '~/hooks/useApiFetchCallback' );
jest.mock( '~/utils/console' );

describe( 'CreateContainerModal', () => {
	let fetchCreateContainer;
	let onContainerCreated;
	let onCreated;
	let onRequestClose;

	beforeEach( () => {
		jest.clearAllMocks();

		getSetting.mockReturnValue( 'https://shop.example.com/store/' );

		fetchCreateContainer = jest
			.fn()
			.mockName( 'fetchCreateContainer' )
			.mockResolvedValue( { status: 'success' } );
		useApiFetchCallback.mockReturnValue( [
			fetchCreateContainer,
			{ loading: false },
		] );

		onContainerCreated = jest.fn().mockName( 'onContainerCreated' );
		onCreated = jest.fn().mockName( 'onCreated' ).mockResolvedValue();
		onRequestClose = jest.fn().mockName( 'onRequestClose' );
	} );

	function renderModal() {
		return render(
			<CreateContainerModal
				onContainerCreated={ onContainerCreated }
				onCreated={ onCreated }
				onRequestClose={ onRequestClose }
			/>
		);
	}

	function getNameField() {
		return screen.getByRole( 'textbox', { name: 'Container name' } );
	}

	function getCreateButton() {
		return screen.getByRole( 'button', { name: 'Create container' } );
	}

	/**
	 * Makes the create request fail with the response body the backend sends.
	 *
	 * @param {string} [reason] The failure reason, when the backend reports one.
	 * @param {string} [message] The API's own message.
	 */
	function mockCreateFailure( reason, message = 'Raw API message.' ) {
		fetchCreateContainer.mockRejectedValueOnce( {
			code: 'API_ERROR',
			message,
			data: { message, reason },
		} );
	}

	it( 'pre-fills the name with the store domain and keeps it editable', async () => {
		const user = userEvent.setup();

		renderModal();

		expect( getNameField() ).toHaveValue( 'shop.example.com' );

		await user.clear( getNameField() );
		await user.type( getNameField(), 'My store' );

		expect( getNameField() ).toHaveValue( 'My store' );
	} );

	it( 'does not let the merchant pick a container type', () => {
		renderModal();

		expect( screen.queryByRole( 'combobox' ) ).not.toBeInTheDocument();
		expect( screen.queryByRole( 'radio' ) ).not.toBeInTheDocument();
	} );

	it( 'disables the create action while the name is empty', async () => {
		const user = userEvent.setup();

		renderModal();
		await user.clear( getNameField() );

		expect( getCreateButton() ).toBeDisabled();
	} );

	it( 'creates the container with the entered name, then reports it as created', async () => {
		const user = userEvent.setup();

		renderModal();
		await user.clear( getNameField() );
		await user.type( getNameField(), 'My store' );
		await user.click( getCreateButton() );

		expect( fetchCreateContainer ).toHaveBeenCalledTimes( 1 );
		expect( fetchCreateContainer ).toHaveBeenCalledWith( {
			data: { name: 'My store' },
		} );
		expect( onCreated ).toHaveBeenCalledTimes( 1 );
		expect( screen.queryByRole( 'alert' ) ).not.toBeInTheDocument();
	} );

	it( 'reports the container as created once, as soon as the create request succeeds', async () => {
		const user = userEvent.setup();
		onCreated.mockRejectedValueOnce( new Error( 'Refresh failed.' ) );

		renderModal();
		await user.click( getCreateButton() );
		await screen.findByText( 'Refresh failed.', { selector: 'p' } );

		expect( onContainerCreated ).toHaveBeenCalledTimes( 1 );

		await user.click( screen.getByRole( 'button', { name: 'Try again' } ) );
		await waitFor( () => expect( onCreated ).toHaveBeenCalledTimes( 2 ) );

		expect( onContainerCreated ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'does not report the container as created when the create request fails', async () => {
		const user = userEvent.setup();
		mockCreateFailure( 'api_error' );

		renderModal();
		await user.click( getCreateButton() );
		await screen.findByText( 'Raw API message.', { selector: 'p' } );

		expect( onContainerCreated ).not.toHaveBeenCalled();
	} );

	it( 'closes on Escape when nothing is being created', async () => {
		const user = userEvent.setup();

		renderModal();
		await user.keyboard( '{Escape}' );

		expect( onRequestClose ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'ignores Escape while the container is being created', async () => {
		const user = userEvent.setup();
		fetchCreateContainer.mockReturnValue( new Promise( () => {} ) );

		renderModal();
		await user.click( getCreateButton() );
		// The create button is disabled while creating, so focus leaves the modal; send the key
		// to the modal itself.
		fireEvent.keyDown( screen.getByRole( 'dialog' ), {
			key: 'Escape',
			code: 'Escape',
		} );

		expect( onRequestClose ).not.toHaveBeenCalled();
		expect( screen.getByRole( 'dialog' ) ).toBeInTheDocument();
	} );

	it( 'calls onRequestClose when cancelled', async () => {
		const user = userEvent.setup();

		renderModal();
		await user.click( screen.getByRole( 'button', { name: 'Cancel' } ) );

		expect( onRequestClose ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'cannot be cancelled while the container is being created', async () => {
		const user = userEvent.setup();
		fetchCreateContainer.mockReturnValue( new Promise( () => {} ) );

		renderModal();
		await user.click( getCreateButton() );

		expect(
			screen.getByRole( 'button', { name: 'Cancel' } )
		).toBeDisabled();
	} );

	it.each( [
		[
			'insufficient_scope',
			"Your Google connection doesn't allow creating Google Tag Manager containers.",
		],
		[
			'permission_denied',
			"Your Google account doesn't have permission to create containers in this Google Tag Manager account.",
		],
		[
			'quota_exceeded',
			'Google Tag Manager has reached its request limit. Please try again later.',
		],
		[ 'api_error', 'Raw API message.' ],
		[ undefined, 'Raw API message.' ],
	] )(
		'names the cause inside the modal when creation fails with reason %s',
		async ( reason, expectedMessage ) => {
			const user = userEvent.setup();
			mockCreateFailure( reason );

			renderModal();
			await user.click( getCreateButton() );

			expect(
				await screen.findByText( expectedMessage, { selector: 'p' } )
			).toBeInTheDocument();
			expect( onCreated ).not.toHaveBeenCalled();
			expect( onRequestClose ).not.toHaveBeenCalled();
			expect( logError ).toHaveBeenCalledTimes( 1 );
		}
	);

	it( 'keeps the entered name and retries with it through "Try again"', async () => {
		const user = userEvent.setup();
		mockCreateFailure( 'quota_exceeded' );

		renderModal();
		await user.clear( getNameField() );
		await user.type( getNameField(), 'My store' );
		await user.click( getCreateButton() );

		await screen.findByText( /request limit/, { selector: 'p' } );
		expect( getNameField() ).toHaveValue( 'My store' );

		await user.click( screen.getByRole( 'button', { name: 'Try again' } ) );

		await waitFor( () => expect( onCreated ).toHaveBeenCalledTimes( 1 ) );
		expect( fetchCreateContainer ).toHaveBeenCalledTimes( 2 );
		expect( fetchCreateContainer ).toHaveBeenLastCalledWith( {
			data: { name: 'My store' },
		} );
		expect(
			screen.queryByText( /request limit/, { selector: 'p' } )
		).not.toBeInTheDocument();
	} );

	it( 'does not create a second container when retrying after the refresh that follows the creation fails', async () => {
		const user = userEvent.setup();
		onCreated.mockRejectedValueOnce( new Error( 'Refresh failed.' ) );

		renderModal();
		await user.click( getCreateButton() );

		expect(
			await screen.findByText( 'Refresh failed.', { selector: 'p' } )
		).toBeInTheDocument();

		await user.click( screen.getByRole( 'button', { name: 'Try again' } ) );

		await waitFor( () => expect( onCreated ).toHaveBeenCalledTimes( 2 ) );
		expect( fetchCreateContainer ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'creates the container again when retrying after the creation itself failed', async () => {
		const user = userEvent.setup();
		mockCreateFailure( 'api_error' );

		renderModal();
		await user.click( getCreateButton() );
		await screen.findByText( 'Raw API message.', { selector: 'p' } );
		await user.click( screen.getByRole( 'button', { name: 'Try again' } ) );

		await waitFor( () => expect( onCreated ).toHaveBeenCalledTimes( 1 ) );
		expect( fetchCreateContainer ).toHaveBeenCalledTimes( 2 );
	} );

	it.each( [
		[ 'insufficient_scope', false ],
		[ 'already_connected', false ],
		[ 'permission_denied', true ],
		[ 'quota_exceeded', true ],
		[ 'api_error', true ],
		[ undefined, true ],
	] )(
		'offers "Try again" only when a retry can help, reason %s',
		async ( reason, isRetryOffered ) => {
			const user = userEvent.setup();
			mockCreateFailure( reason );

			renderModal();
			await user.click( getCreateButton() );
			await screen.findByText( /.+/, {
				selector: '.components-notice p',
			} );

			expect(
				screen.queryAllByRole( 'button', { name: 'Try again' } )
			).toHaveLength( isRetryOffered ? 1 : 0 );
		}
	);
} );
