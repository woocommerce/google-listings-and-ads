/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import ConfirmModal from './confirm-modal';
import { useAppDispatch } from '~/data';
import useGoogleMCAccount from '~/hooks/useGoogleMCAccount';
import { ALL_ACCOUNTS, ADS_ACCOUNT, ADS_ONLY } from './constants';

jest.mock( '~/data', () => ( {
	useAppDispatch: jest.fn(),
} ) );

jest.mock( '~/hooks/useGoogleMCAccount', () =>
	jest.fn().mockName( 'useGoogleMCAccount' )
);

describe( 'ConfirmModal', () => {
	beforeEach( () => {
		useAppDispatch.mockReturnValue( {
			disconnectAllAccounts: jest.fn(),
			disconnectGoogleAdsAccount: jest.fn(),
		} );
	} );

	describe( `when disconnectTarget is "${ ADS_ACCOUNT }"`, () => {
		it( 'should show the Google Ads-specific modal title', () => {
			useGoogleMCAccount.mockReturnValue( { hasGoogleMCConnection: true } );

			render(
				<ConfirmModal
					disconnectTarget={ ADS_ACCOUNT }
					onRequestClose={ jest.fn() }
					onDisconnected={ jest.fn() }
				/>
			);

			expect(
				screen.getByText( 'Disconnect Google Ads account' )
			).toBeInTheDocument();
		} );

		it( 'should not show the "disconnect all accounts" title', () => {
			useGoogleMCAccount.mockReturnValue( { hasGoogleMCConnection: true } );

			render(
				<ConfirmModal
					disconnectTarget={ ADS_ACCOUNT }
					onRequestClose={ jest.fn() }
					onDisconnected={ jest.fn() }
				/>
			);

			expect(
				screen.queryByText( 'Disconnect all accounts' )
			).not.toBeInTheDocument();
		} );

		it( 'should show the Google Ads-specific confirmation checkbox text', () => {
			useGoogleMCAccount.mockReturnValue( { hasGoogleMCConnection: true } );

			render(
				<ConfirmModal
					disconnectTarget={ ADS_ACCOUNT }
					onRequestClose={ jest.fn() }
					onDisconnected={ jest.fn() }
				/>
			);

			expect(
				screen.getByText(
					'Yes, I want to disconnect my Google Ads account.'
				)
			).toBeInTheDocument();
		} );
	} );

	describe( `when disconnectTarget is "${ ALL_ACCOUNTS }" with MC connected`, () => {
		it( 'should show the "disconnect all accounts" modal title', () => {
			useGoogleMCAccount.mockReturnValue( { hasGoogleMCConnection: true } );

			render(
				<ConfirmModal
					disconnectTarget={ ALL_ACCOUNTS }
					onRequestClose={ jest.fn() }
					onDisconnected={ jest.fn() }
				/>
			);

			expect(
				screen.getByRole( 'heading', { name: /Disconnect all accounts/ } )
			).toBeInTheDocument();
		} );
	} );

	describe( `when disconnectTarget is "${ ALL_ACCOUNTS }" without MC connected`, () => {
		it( 'should show the ads-only variant of the modal', () => {
			useGoogleMCAccount.mockReturnValue( {
				hasGoogleMCConnection: false,
			} );

			render(
				<ConfirmModal
					disconnectTarget={ ALL_ACCOUNTS }
					onRequestClose={ jest.fn() }
					onDisconnected={ jest.fn() }
				/>
			);

			// ADS_ONLY variant still says "Disconnect all accounts" in the title
			// but only mentions Google Ads in the body content (no MC mention)
			expect(
				screen.queryByText( /Google Merchant Center/ )
			).not.toBeInTheDocument();
		} );
	} );
} );
