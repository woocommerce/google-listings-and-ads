/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import ConfirmModal from './confirm-modal';
import {
	ALL_ACCOUNTS,
	YOUTUBE_ACCOUNT,
	GOOGLE_BUSINESS_PROFILE_ACCOUNT,
} from './constants';
import { useAppDispatch } from '~/data';
import useGoogleMCAccount from '~/hooks/useGoogleMCAccount';
import { recordGlaEvent } from '~/utils/tracks';

jest.mock( '~/data', () => ( {
	...jest.requireActual( '~/data' ),
	useAppDispatch: jest.fn(),
} ) );
jest.mock( '~/hooks/useGoogleMCAccount', () =>
	jest.fn().mockName( 'useGoogleMCAccount' )
);
jest.mock( '~/utils/tracks', () => ( {
	recordGlaEvent: jest.fn().mockName( 'recordGlaEvent' ),
} ) );

describe( 'ConfirmModal', () => {
	let disconnectAllAccounts;
	let disconnectYouTubeAccount;
	let disconnectGoogleBusinessProfileAccount;

	beforeEach( () => {
		jest.clearAllMocks();

		disconnectAllAccounts = jest
			.fn()
			.mockName( 'disconnectAllAccounts' )
			.mockResolvedValue();
		disconnectYouTubeAccount = jest
			.fn()
			.mockName( 'disconnectYouTubeAccount' )
			.mockResolvedValue();

		disconnectGoogleBusinessProfileAccount = jest
			.fn()
			.mockName( 'disconnectGoogleBusinessProfileAccount' )
			.mockResolvedValue();

		useAppDispatch.mockReturnValue( {
			disconnectAllAccounts,
			disconnectYouTubeAccount,
			disconnectGoogleBusinessProfileAccount,
		} );
		useGoogleMCAccount.mockReturnValue( { hasGoogleMCConnection: true } );
	} );

	const renderModal = ( disconnectTarget ) =>
		render(
			<ConfirmModal
				disconnectTarget={ disconnectTarget }
				onRequestClose={ jest.fn() }
				onDisconnected={ jest.fn() }
			/>
		);

	it( 'tracks the YouTube disconnection only when the modal is confirmed', async () => {
		const user = userEvent.setup();

		renderModal( YOUTUBE_ACCOUNT );

		expect( recordGlaEvent ).not.toHaveBeenCalled();

		await user.click(
			screen.getByRole( 'checkbox', {
				name: 'Yes, I want to disconnect my YouTube account.',
			} )
		);
		await user.click(
			screen.getByRole( 'button', { name: 'Disconnect YouTube account' } )
		);

		expect( recordGlaEvent ).toHaveBeenCalledTimes( 1 );
		expect( recordGlaEvent ).toHaveBeenCalledWith(
			'gla_youtube_account_disconnect_button_click',
			{ context: 'settings-youtube' }
		);
		expect( disconnectYouTubeAccount ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'does not track the YouTube event when the modal is dismissed', async () => {
		const user = userEvent.setup();

		renderModal( YOUTUBE_ACCOUNT );

		await user.click( screen.getByRole( 'button', { name: 'Cancel' } ) );

		expect( recordGlaEvent ).not.toHaveBeenCalled();
		expect( disconnectYouTubeAccount ).not.toHaveBeenCalled();
	} );

	it( 'does not track the YouTube event for other disconnect targets', async () => {
		const user = userEvent.setup();

		renderModal( ALL_ACCOUNTS );

		await user.click(
			screen.getByRole( 'checkbox', {
				name: 'Yes, I want to disconnect all my accounts.',
			} )
		);
		await user.click(
			screen.getByRole( 'button', { name: 'Disconnect all accounts' } )
		);

		expect( recordGlaEvent ).not.toHaveBeenCalled();
		expect( disconnectAllAccounts ).toHaveBeenCalledTimes( 1 );
	} );
	describe( 'Google Business Profile', () => {
		it( 'shows the Google Business Profile copy', () => {
			renderModal( GOOGLE_BUSINESS_PROFILE_ACCOUNT );

			expect(
				screen.getByText( 'Disconnect Google Business Profile?' )
			).toBeInTheDocument();
			expect(
				screen.getByText(
					'Posts you already shared stay on Google. Your other Google accounts stay connected.'
				)
			).toBeInTheDocument();
		} );

		it( 'disconnects Google Business Profile and tracks it only once confirmed', async () => {
			const user = userEvent.setup();

			renderModal( GOOGLE_BUSINESS_PROFILE_ACCOUNT );

			expect(
				screen.getByRole( 'button', { name: 'Disconnect' } )
			).toBeDisabled();

			await user.click(
				screen.getByRole( 'checkbox', {
					name: 'Yes, I want to disconnect my Google Business Profile.',
				} )
			);
			await user.click(
				screen.getByRole( 'button', { name: 'Disconnect' } )
			);

			expect(
				disconnectGoogleBusinessProfileAccount
			).toHaveBeenCalledTimes( 1 );
			expect( disconnectAllAccounts ).not.toHaveBeenCalled();
			expect( disconnectYouTubeAccount ).not.toHaveBeenCalled();
			expect( recordGlaEvent ).toHaveBeenCalledWith(
				'gla_google_business_profile_account_disconnect_button_click',
				{ context: 'settings-business-profile' }
			);
		} );

		it( 'does not disconnect when the modal is cancelled', async () => {
			const user = userEvent.setup();

			renderModal( GOOGLE_BUSINESS_PROFILE_ACCOUNT );

			await user.click(
				screen.getByRole( 'button', { name: 'Cancel' } )
			);

			expect(
				disconnectGoogleBusinessProfileAccount
			).not.toHaveBeenCalled();
			expect( recordGlaEvent ).not.toHaveBeenCalled();
		} );
	} );
} );
