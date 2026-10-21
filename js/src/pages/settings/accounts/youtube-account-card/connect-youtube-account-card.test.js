/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import ConnectYouTubeAccountCard from './connect-youtube-account-card';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import useGoogleMCAccount from '~/hooks/useGoogleMCAccount';
import { recordGlaEvent } from '~/utils/tracks';
import { redirectTo } from '~/utils/urls';

jest.mock( '~/hooks/useApiFetchCallback' );
jest.mock( '~/hooks/useDispatchCoreNotices' );
jest.mock( '~/hooks/useGoogleMCAccount' );
jest.mock( '~/utils/tracks', () => ( {
	recordGlaEvent: jest.fn().mockName( 'recordGlaEvent' ),
} ) );
jest.mock( '~/utils/urls', () => ( {
	redirectTo: jest.fn().mockName( 'redirectTo' ),
} ) );

describe( 'ConnectYouTubeAccountCard', () => {
	let fetchYouTubeConnect;
	let createNotice;

	beforeEach( () => {
		jest.clearAllMocks();

		fetchYouTubeConnect = jest
			.fn()
			.mockName( 'fetchYouTubeConnect' )
			.mockResolvedValue( { url: 'https://accounts.google.com/oauth' } );
		createNotice = jest.fn().mockName( 'createNotice' );

		useApiFetchCallback.mockReturnValue( [
			fetchYouTubeConnect,
			{ loading: false, data: undefined },
		] );
		useDispatchCoreNotices.mockReturnValue( { createNotice } );
		useGoogleMCAccount.mockReturnValue( { hasGoogleMCConnection: true } );
	} );

	it( 'tracks the connect button click and starts the OAuth flow', async () => {
		const user = userEvent.setup();

		render( <ConnectYouTubeAccountCard /> );

		await user.click( screen.getByRole( 'button', { name: 'Connect' } ) );

		expect( recordGlaEvent ).toHaveBeenCalledWith(
			'gla_youtube_account_connect_button_click',
			{ context: 'settings-youtube' }
		);
		expect( fetchYouTubeConnect ).toHaveBeenCalledTimes( 1 );
		expect( redirectTo ).toHaveBeenCalledWith(
			'https://accounts.google.com/oauth'
		);
	} );

	it( 'disables the connection when Merchant Center is not connected', async () => {
		const user = userEvent.setup();
		const disabledReason =
			'Connect a Google Merchant Center account before connecting YouTube.';
		useGoogleMCAccount.mockReturnValue( {
			hasGoogleMCConnection: false,
		} );

		const { container } = render( <ConnectYouTubeAccountCard /> );
		const connectButton = screen.getByRole( 'button', { name: 'Connect' } );
		const helper = within(
			container.querySelector( '.gla-account-card__helper' )
		).getByText( disabledReason );
		const accountCard = container.querySelector( '.gla-account-card' );

		expect( connectButton ).toBeEnabled();
		expect( connectButton ).toHaveAttribute( 'aria-disabled', 'true' );
		expect( helper ).toBeVisible();
		expect( connectButton ).toHaveAccessibleDescription( disabledReason );
		expect( accountCard ).not.toHaveClass(
			'gla-account-card--is-disabled'
		);

		await user.tab();
		await user.tab();
		expect( connectButton ).toHaveFocus();
		expect(
			await screen.findByRole( 'tooltip', { name: disabledReason } )
		).toBeVisible();

		await user.click( connectButton );
		expect( recordGlaEvent ).not.toHaveBeenCalledWith(
			'gla_youtube_account_connect_button_click',
			{ context: 'settings-youtube' }
		);
		expect( fetchYouTubeConnect ).not.toHaveBeenCalled();
	} );

	it( 'tracks the YouTube Merchant Terms documentation link click', async () => {
		const user = userEvent.setup();

		render( <ConnectYouTubeAccountCard /> );

		await user.click(
			screen.getByRole( 'link', {
				name: 'YouTube Merchant Terms (opens in a new tab)',
			} )
		);

		expect( recordGlaEvent ).toHaveBeenCalledWith(
			'gla_documentation_link_click',
			{
				context: 'settings-connect-youtube-account-card',
				link_id: 'youtube-merchant-terms',
				href: 'https://www.youtube.com/t/merchant_terms',
			}
		);
	} );
} );
