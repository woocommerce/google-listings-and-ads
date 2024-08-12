/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import useGoogleMCAccount from '.~/hooks/useGoogleMCAccount';
import EnableNewProductSyncNotice from './enable-new-product-sync-notice';

jest.mock( '.~/hooks/useGoogleMCAccount' );

describe( 'Enable New Product Sync Notice', () => {
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

		const { getByText, getByRole } = render(
			<EnableNewProductSyncNotice />
		);

		expect(
			getByText(
				'We will soon transition to a new and improved method for synchronizing product data with Google.'
			)
		).toBeInTheDocument();

		const button = getByRole( 'button', {
			name: /Get early access/i,
		} );

		expect( button ).toBeEnabled();
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

		const { queryByText, queryByRole } = render(
			<EnableNewProductSyncNotice />
		);

		expect(
			queryByText(
				'We will soon transition to a new and improved method for synchronizing product data with Google.'
			)
		).not.toBeInTheDocument();

		expect(
			queryByRole( 'button', { name: /Get early access/i } )
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

		const { queryByText, queryByRole } = render(
			<EnableNewProductSyncNotice />
		);

		expect(
			queryByText(
				'We will soon transition to a new and improved method for synchronizing product data with Google.'
			)
		).not.toBeInTheDocument();

		expect(
			queryByRole( 'button', { name: /Get early access/i } )
		).not.toBeInTheDocument();
	} );

	it( 'should not render the notice if googleMCAccount is undefined because the google account is not connected or missing scopes', () => {
		useGoogleMCAccount.mockImplementation( () => {
			return {
				hasFinishedResolution: true,
				googleMCAccount: undefined,
			};
		} );

		const { queryByText, queryByRole } = render(
			<EnableNewProductSyncNotice />
		);

		expect(
			queryByText(
				'We will soon transition to a new and improved method for synchronizing product data with Google.'
			)
		).not.toBeInTheDocument();

		expect(
			queryByRole( 'button', { name: /Get early access/i } )
		).not.toBeInTheDocument();
	} );
} );
