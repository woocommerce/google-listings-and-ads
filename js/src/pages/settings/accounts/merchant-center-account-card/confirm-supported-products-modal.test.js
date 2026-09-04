/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';
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
	} );

	it( 'shows an error notice when confirmation fails', async () => {
		const user = userEvent.setup();
		confirmSupportedProducts.mockRejectedValue(
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
		expect( window.location.href ).toBe( '' );
	} );
} );
