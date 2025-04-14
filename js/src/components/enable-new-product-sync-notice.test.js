/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import useGoogleMCAccount from '~/hooks/useGoogleMCAccount';
import EnableNewProductSyncNotice from './enable-new-product-sync-notice';

jest.mock( '~/hooks/useGoogleMCAccount' );

describe( 'Enable New Product Sync Notice', () => {
	const noticeText =
		'Start using the new and improved method for synchronizing product data with Google.';

	it( 'should render the notice if the account has not switched to new product sync', () => {
		useGoogleMCAccount.mockImplementation( () => {
			return {
				hasFinishedResolution: true,
				googleMCAccount: {
					notification_service_enabled: true,
					wpcom_rest_api_status: null,
				},
			};
		} );

		render( <EnableNewProductSyncNotice /> );

		expect( screen.getByText( noticeText ) ).toBeInTheDocument();
		expect(
			screen.queryByRole( 'button', { name: 'Grant access' } )
		).toBeEnabled();
	} );
	it( 'should not render the notice if the account has switched to new product sync', () => {
		useGoogleMCAccount.mockImplementation( () => {
			return {
				hasFinishedResolution: true,
				googleMCAccount: {
					notification_service_enabled: true,
					wpcom_rest_api_status: 'approved',
				},
			};
		} );

		render( <EnableNewProductSyncNotice /> );

		expect( screen.queryByText( noticeText ) ).not.toBeInTheDocument();
		expect(
			screen.queryByRole( 'button', { name: 'Grant access' } )
		).not.toBeInTheDocument();
	} );
	it( 'should not render the notice if the notification service is not enabled', () => {
		useGoogleMCAccount.mockImplementation( () => {
			return {
				hasFinishedResolution: true,
				googleMCAccount: {
					notification_service_enabled: false,
					wpcom_rest_api_status: 'approved',
				},
			};
		} );

		render( <EnableNewProductSyncNotice /> );

		expect( screen.queryByText( noticeText ) ).not.toBeInTheDocument();
		expect(
			screen.queryByRole( 'button', { name: 'Grant access' } )
		).not.toBeInTheDocument();
	} );

	it( 'should not render the notice if googleMCAccount is undefined because the google account is not connected or missing scopes', () => {
		useGoogleMCAccount.mockImplementation( () => {
			return {
				hasFinishedResolution: true,
				googleMCAccount: undefined,
			};
		} );

		render( <EnableNewProductSyncNotice /> );

		expect( screen.queryByText( noticeText ) ).not.toBeInTheDocument();
		expect(
			screen.queryByRole( 'button', { name: 'Grant access' } )
		).not.toBeInTheDocument();
	} );
} );
