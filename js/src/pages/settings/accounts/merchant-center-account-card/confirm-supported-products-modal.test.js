/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import {
	act,
	fireEvent,
	render,
	screen,
	waitFor,
} from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import ConfirmSupportedProductsModal from './confirm-supported-products-modal';
import useAdminUrl from '~/hooks/useAdminUrl';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import { recordGlaEvent } from '~/utils/tracks';
import { getAccountsSettingsUrl } from '~/utils/urls';
import { SUPPORTED_PRODUCTS_CONTEXT } from './constants';

jest.mock( '~/hooks/useAdminUrl' );
jest.mock( '~/hooks/useApiFetchCallback' );
jest.mock( '~/hooks/useDispatchCoreNotices' );
jest.mock( '~/utils/tracks', () => ( {
	recordGlaEvent: jest.fn().mockName( 'recordGlaEvent' ),
} ) );
jest.mock( '~/utils/urls', () => ( {
	getAccountsSettingsUrl: jest.fn().mockName( 'getAccountsSettingsUrl' ),
} ) );

describe( 'ConfirmSupportedProductsModal', () => {
	const originalLocation = window.location;
	let confirmSupportedProducts;
	let createNotice;
	let onRequestClose;

	beforeEach( () => {
		jest.clearAllMocks();

		Object.defineProperty( window, 'location', {
			configurable: true,
			value: { href: '' },
		} );

		confirmSupportedProducts = jest.fn().mockResolvedValue( {
			confirmed: true,
			service_based_merchant: false,
		} );
		createNotice = jest.fn();
		onRequestClose = jest.fn();

		useAdminUrl.mockReturnValue( 'https://example.com/wp-admin/' );
		getAccountsSettingsUrl.mockReturnValue(
			'admin.php?page=wc-admin&path=%2Fgoogle%2Fsettings&section=accounts'
		);
		useApiFetchCallback.mockReturnValue( [
			confirmSupportedProducts,
			{ loading: false },
		] );
		useDispatchCoreNotices.mockReturnValue( { createNotice } );
	} );

	afterAll( () => {
		Object.defineProperty( window, 'location', {
			configurable: true,
			value: originalLocation,
		} );
	} );

	it( 'confirms supported products and reloads the Accounts page', async () => {
		const user = userEvent.setup();
		render(
			<ConfirmSupportedProductsModal onRequestClose={ onRequestClose } />
		);
		expect( useApiFetchCallback ).toHaveBeenCalledWith( {
			path: '/wc/gla/mc/supported-products',
			method: 'POST',
			data: { confirmed: true },
		} );

		await user.click( screen.getByRole( 'button', { name: 'Confirm' } ) );

		expect( confirmSupportedProducts ).toHaveBeenCalledTimes( 1 );
		expect( recordGlaEvent ).toHaveBeenCalledWith(
			'gla_supported_products_confirmation',
			{
				action: 'success',
				context: 'settings-merchant-center-supported-products',
			}
		);
		expect( window.location.href ).toBe(
			'https://example.com/wp-admin/admin.php?page=wc-admin&path=%2Fgoogle%2Fsettings&section=accounts'
		);
	} );

	it( 'closes without confirming', async () => {
		const user = userEvent.setup();
		render(
			<ConfirmSupportedProductsModal onRequestClose={ onRequestClose } />
		);

		await user.click( screen.getByRole( 'button', { name: 'Cancel' } ) );

		expect( onRequestClose ).toHaveBeenCalledTimes( 1 );
		expect( confirmSupportedProducts ).not.toHaveBeenCalled();
		expect( recordGlaEvent ).toHaveBeenCalledWith(
			'gla_supported_products_confirmation',
			{
				action: 'cancel',
				context: SUPPORTED_PRODUCTS_CONTEXT,
			}
		);
	} );

	it( 'shows an error notice and allows retrying when confirmation fails', async () => {
		const user = userEvent.setup();
		confirmSupportedProducts.mockRejectedValueOnce(
			new Error( 'Request failed' )
		);
		render(
			<ConfirmSupportedProductsModal onRequestClose={ onRequestClose } />
		);

		await user.click( screen.getByRole( 'button', { name: 'Confirm' } ) );

		expect( createNotice ).toHaveBeenCalledWith(
			'error',
			'Unable to enable the Google Merchant Center connection. Please try again.'
		);
		expect( recordGlaEvent ).toHaveBeenCalledWith(
			'gla_supported_products_confirmation',
			{
				action: 'error',
				context: SUPPORTED_PRODUCTS_CONTEXT,
			}
		);
		expect( window.location.href ).toBe( '' );
		expect(
			screen.getByRole( 'button', { name: 'Confirm' } )
		).toBeEnabled();
		expect(
			screen.getByRole( 'button', { name: 'Cancel' } )
		).toBeEnabled();

		await user.click( screen.getByRole( 'button', { name: 'Confirm' } ) );

		expect( confirmSupportedProducts ).toHaveBeenCalledTimes( 2 );
		expect( window.location.href ).toBe(
			'https://example.com/wp-admin/admin.php?page=wc-admin&path=%2Fgoogle%2Fsettings&section=accounts'
		);
	} );

	it( 'locks submission and dismissal during the request and after success', async () => {
		const user = userEvent.setup();
		let resolveConfirmation;

		confirmSupportedProducts.mockReturnValue(
			new Promise( ( resolve ) => {
				resolveConfirmation = resolve;
			} )
		);

		render(
			<ConfirmSupportedProductsModal onRequestClose={ onRequestClose } />
		);

		const confirmButton = screen.getByRole( 'button', {
			name: 'Confirm',
		} );
		const cancelButton = screen.getByRole( 'button', { name: 'Cancel' } );

		await user.click( confirmButton );

		expect( confirmButton ).toBeDisabled();
		expect( cancelButton ).toBeDisabled();
		expect(
			screen.queryByRole( 'button', { name: 'Close' } )
		).not.toBeInTheDocument();

		fireEvent.keyDown( screen.getByRole( 'dialog' ), {
			key: 'Escape',
			code: 'Escape',
		} );

		// Bypass the native disabled behavior to verify that the handler guards
		// also reject programmatic attempts to submit or dismiss the modal.
		cancelButton.disabled = false;
		confirmButton.disabled = false;
		fireEvent.click( cancelButton );
		fireEvent.click( confirmButton );
		cancelButton.disabled = true;
		confirmButton.disabled = true;

		expect( onRequestClose ).not.toHaveBeenCalled();
		expect( confirmSupportedProducts ).toHaveBeenCalledTimes( 1 );

		await act( async () => {
			resolveConfirmation( {
				confirmed: true,
				service_based_merchant: false,
			} );
		} );

		await waitFor( () => {
			expect( window.location.href ).toBe(
				'https://example.com/wp-admin/admin.php?page=wc-admin&path=%2Fgoogle%2Fsettings&section=accounts'
			);
		} );
		expect( confirmButton ).toBeDisabled();
		expect( cancelButton ).toBeDisabled();

		fireEvent.keyDown( screen.getByRole( 'dialog' ), {
			key: 'Escape',
			code: 'Escape',
		} );
		fireEvent.click( confirmButton );

		expect( onRequestClose ).not.toHaveBeenCalled();
		expect( confirmSupportedProducts ).toHaveBeenCalledTimes( 1 );
	} );
} );
