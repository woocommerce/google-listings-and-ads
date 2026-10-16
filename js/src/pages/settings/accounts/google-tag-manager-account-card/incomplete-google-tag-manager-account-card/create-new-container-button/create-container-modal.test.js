/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { fireEvent, render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { getSetting } from '@woocommerce/settings'; // eslint-disable-line import/no-unresolved

/**
 * Internal dependencies
 */
import CreateContainerModal from './create-container-modal';
import { useAppDispatch } from '~/data';
import useCreateGoogleTagManagerContainer from '../../hooks/useCreateGoogleTagManagerContainer';

jest.mock( '@woocommerce/settings', () => ( {
	...jest.requireActual( '@woocommerce/settings' ),
	getSetting: jest.fn().mockName( 'getSetting' ),
} ) );
jest.mock( '~/data', () => ( {
	...jest.requireActual( '~/data' ),
	useAppDispatch: jest.fn().mockName( 'useAppDispatch' ),
} ) );
jest.mock( '../../hooks/useCreateGoogleTagManagerContainer', () =>
	jest.fn().mockName( 'useCreateGoogleTagManagerContainer' )
);

describe( 'CreateContainerModal', () => {
	let createContainer;
	let fetchGoogleTagManagerAccount;
	let onRequestClose;

	/**
	 * Mocks the state of `useCreateGoogleTagManagerContainer`.
	 *
	 * @param {Object} [state] The hook state.
	 * @param {boolean} [state.loading] Whether a create is in progress.
	 * @param {Object|null} [state.error] The error from the last attempt.
	 */
	function mockCreateState( { loading = false, error = null } = {} ) {
		useCreateGoogleTagManagerContainer.mockReturnValue( [
			createContainer,
			{ loading, error },
		] );
	}

	/**
	 * Mocks the last create attempt as failed.
	 *
	 * @param {boolean} [canRetry] Whether trying again can help.
	 * @param {string} [message] The message to show.
	 */
	function mockCreateFailure( canRetry = true, message = 'Create failed.' ) {
		mockCreateState( { error: { message, canRetry } } );
	}

	beforeEach( () => {
		jest.clearAllMocks();

		getSetting.mockReturnValue( 'https://shop.example.com/store/' );

		createContainer = jest.fn().mockName( 'createContainer' );
		mockCreateState();

		fetchGoogleTagManagerAccount = jest
			.fn()
			.mockName( 'fetchGoogleTagManagerAccount' );
		useAppDispatch.mockReturnValue( { fetchGoogleTagManagerAccount } );

		onRequestClose = jest.fn().mockName( 'onRequestClose' );
	} );

	function renderModal() {
		return render(
			<CreateContainerModal onRequestClose={ onRequestClose } />
		);
	}

	function getNameField() {
		return screen.getByRole( 'textbox', { name: 'Container name' } );
	}

	function getCreateButton() {
		return screen.getByRole( 'button', { name: 'Create container' } );
	}

	function getCancelButton() {
		return screen.getByRole( 'button', { name: 'Cancel' } );
	}

	function pressEscape() {
		// A disabled button doesn't keep focus, so send the key to the modal itself.
		fireEvent.keyDown( screen.getByRole( 'dialog' ), {
			key: 'Escape',
			code: 'Escape',
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

	it( 'creates the container with the entered name', async () => {
		const user = userEvent.setup();

		renderModal();
		await user.clear( getNameField() );
		await user.type( getNameField(), 'My store' );
		await user.click( getCreateButton() );

		expect( createContainer ).toHaveBeenCalledTimes( 1 );
		expect( createContainer ).toHaveBeenCalledWith( 'My store' );
	} );

	it( 'closes on Escape or Cancel when nothing is being created', async () => {
		const user = userEvent.setup();

		renderModal();
		await user.keyboard( '{Escape}' );
		await user.click( getCancelButton() );

		expect( onRequestClose ).toHaveBeenCalledTimes( 2 );
		expect( fetchGoogleTagManagerAccount ).not.toHaveBeenCalled();
	} );

	it( 'cannot be closed or edited while the container is being created', () => {
		mockCreateState( { loading: true } );

		renderModal();
		pressEscape();

		expect( onRequestClose ).not.toHaveBeenCalled();
		expect( getCancelButton() ).toBeDisabled();
		expect( getCreateButton() ).toBeDisabled();
		expect( getNameField() ).toBeDisabled();
	} );

	it( 'shows the error message inside the modal', () => {
		mockCreateFailure( true, 'Create failed.' );

		renderModal();

		expect(
			screen.getByText( 'Create failed.', { selector: 'p' } )
		).toBeInTheDocument();
	} );

	it( 'retries with the entered name through "Try again"', async () => {
		const user = userEvent.setup();
		mockCreateFailure();

		renderModal();
		await user.clear( getNameField() );
		await user.type( getNameField(), 'My store' );
		await user.click( screen.getByRole( 'button', { name: 'Try again' } ) );

		expect( createContainer ).toHaveBeenCalledWith( 'My store' );
	} );

	it.each( [ true, false ] )(
		'turns the create action into "Try again" only when a retry can help, canRetry %s',
		( canRetry ) => {
			mockCreateFailure( canRetry );

			renderModal();

			expect(
				screen.queryAllByRole( 'button', { name: 'Try again' } )
			).toHaveLength( canRetry ? 1 : 0 );
			expect(
				screen.queryAllByRole( 'button', { name: 'Create container' } )
			).toHaveLength( canRetry ? 0 : 1 );
			expect(
				screen.getByRole( 'button', {
					name: canRetry ? 'Try again' : 'Create container',
				} ).disabled
			).toBe( ! canRetry );
		}
	);

	it.each( [ false, true ] )(
		'refreshes the account on Cancel after a failure, canRetry %s',
		async ( canRetry ) => {
			const user = userEvent.setup();
			mockCreateFailure( canRetry );

			renderModal();

			expect( fetchGoogleTagManagerAccount ).not.toHaveBeenCalled();

			await user.click( getCancelButton() );

			expect( fetchGoogleTagManagerAccount ).toHaveBeenCalledTimes( 1 );
			expect( onRequestClose ).toHaveBeenCalledTimes( 1 );
		}
	);

	it( 'refreshes the account when the modal is closed with Escape after a failure', () => {
		mockCreateFailure( false );

		renderModal();
		pressEscape();

		expect( fetchGoogleTagManagerAccount ).toHaveBeenCalledTimes( 1 );
		expect( onRequestClose ).toHaveBeenCalledTimes( 1 );
	} );
} );
