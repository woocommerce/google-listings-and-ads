/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import ConnectExistingAccount from './connect-existing-account';
import { useAppDispatch } from '~/data';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';
import useGoogleAdsAccount from '~/hooks/useGoogleAdsAccount';
import useGoogleAdsAccountReady from '~/hooks/useGoogleAdsAccountReady';

jest.mock( '~/data', () => ( {
	...jest.requireActual( '~/data' ),
	useAppDispatch: jest.fn(),
} ) );

jest.mock( '~/hooks/useApiFetchCallback', () =>
	jest
		.fn()
		.mockName( 'useApiFetchCallback' )
		.mockReturnValue( [ jest.fn().mockName( 'connectGoogleAdsAccount' ) ] )
);

jest.mock( '~/hooks/useGoogleAdsAccount', () =>
	jest.fn().mockName( 'useGoogleAdsAccount' )
);

jest.mock( '~/hooks/useGoogleAdsAccountReady', () =>
	jest.fn().mockName( 'useGoogleAdsAccountReady' )
);

jest.mock( '~/hooks/useExistingGoogleAdsAccounts', () =>
	jest
		.fn()
		.mockName( 'useExistingGoogleAdsAccounts' )
		.mockReturnValue( {
			existingAccounts: [
				{ id: 123, name: 'Account one' },
				{ id: 456, name: 'Account two' },
			],
		} )
);

jest.mock( '~/hooks/useGoogleAdsAccountStatus', () =>
	jest
		.fn()
		.mockName( 'useGoogleAdsAccountStatus' )
		.mockReturnValue( { hasAccess: true } )
);

describe( 'ConnectExistingAccount', () => {
	let connectGoogleAdsAccount;
	let fetchGoogleAdsAccountStatus;
	let disconnectGoogleAdsAccount;

	beforeEach( () => {
		connectGoogleAdsAccount = jest
			.fn()
			.mockName( 'fetchConnectAdsAccount' );

		fetchGoogleAdsAccountStatus = jest
			.fn()
			.mockName( 'fetchGoogleAdsAccountStatus' );

		disconnectGoogleAdsAccount = jest
			.fn()
			.mockName( 'disconnectGoogleAdsAccount' )
			.mockReturnValue( Promise.resolve() );

		useApiFetchCallback.mockReturnValue( [ connectGoogleAdsAccount ] );
		useAppDispatch.mockReturnValue( {
			fetchGoogleAdsAccountStatus,
			disconnectGoogleAdsAccount,
		} );
	} );

	describe( 'Initial with the connected state', () => {
		let refetchGoogleAdsAccount;

		beforeEach( () => {
			refetchGoogleAdsAccount = jest
				.fn()
				.mockName( 'refetchGoogleAdsAccount' );

			useGoogleAdsAccount.mockReturnValue( {
				hasFinishedResolution: true,
				hasGoogleAdsConnection: true,
				googleAdsAccount: { id: 123 },
				refetchGoogleAdsAccount,
			} );

			useGoogleAdsAccountReady.mockReturnValue( {
				isGoogleAdsReady: true,
				isLinkedToMerchantCenter: true,
			} );
		} );

		it( 'Should render the state in connected', () => {
			render( <ConnectExistingAccount /> );

			expect( screen.getByRole( 'combobox' ) ).toHaveAttribute(
				'readonly'
			);
			expect( screen.getByText( 'Connected' ) ).toBeInTheDocument();
			expect(
				screen.getByRole( 'button', {
					name: 'Or, connect to a different Google Ads account',
				} )
			).toBeInTheDocument();

			expect(
				screen.queryByRole( 'button', { name: 'Connect' } )
			).not.toBeInTheDocument();
		} );

		it( 'Should be able to reconnect to the auto-selected account after disconnecting', async () => {
			const user = userEvent.setup();

			disconnectGoogleAdsAccount.mockReturnValue(
				Promise.resolve().then( () => {
					useGoogleAdsAccount.mockReturnValue( {
						hasFinishedResolution: true,
						hasGoogleAdsConnection: false,
						googleAdsAccount: { id: 0 },
						refetchGoogleAdsAccount,
					} );

					useGoogleAdsAccountReady.mockReturnValue( {
						isGoogleAdsReady: false,
						isLinkedToMerchantCenter: false,
					} );
				} )
			);

			render( <ConnectExistingAccount /> );

			await user.click(
				screen.getByRole( 'button', {
					name: 'Or, connect to a different Google Ads account',
				} )
			);

			expect( disconnectGoogleAdsAccount ).toHaveBeenCalledTimes( 1 );
			expect( connectGoogleAdsAccount ).toHaveBeenCalledTimes( 0 );
			expect( fetchGoogleAdsAccountStatus ).toHaveBeenCalledTimes( 0 );
			expect( refetchGoogleAdsAccount ).toHaveBeenCalledTimes( 0 );
			expect( useApiFetchCallback ).toHaveBeenLastCalledWith( {
				path: '/wc/gla/ads/accounts',
				method: 'POST',
				data: { id: 123 },
			} );

			await user.click(
				screen.getByRole( 'button', { name: 'Connect' } )
			);

			expect( connectGoogleAdsAccount ).toHaveBeenCalledTimes( 1 );
			expect( fetchGoogleAdsAccountStatus ).toHaveBeenCalledTimes( 1 );
			expect( refetchGoogleAdsAccount ).toHaveBeenCalledTimes( 1 );
		} );
	} );
} );
