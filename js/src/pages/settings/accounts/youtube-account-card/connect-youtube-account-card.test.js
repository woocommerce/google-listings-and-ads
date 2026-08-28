/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import ConnectYouTubeAccountCard from './connect-youtube-account-card';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import { recordGlaEvent } from '~/utils/tracks';

jest.mock( '~/hooks/useApiFetchCallback' );
jest.mock( '~/hooks/useDispatchCoreNotices' );
jest.mock( '~/utils/tracks', () => ( {
	recordGlaEvent: jest.fn().mockName( 'recordGlaEvent' ),
} ) );

describe( 'ConnectYouTubeAccountCard', () => {
	const originalLocation = window.location;
	let fetchYouTubeConnect;
	let createNotice;

	beforeEach( () => {
		jest.clearAllMocks();

		// The component navigates via `window.location.href = url` on a
		// successful connect; jsdom doesn't implement real navigation.
		Object.defineProperty( window, 'location', {
			configurable: true,
			value: { href: '' },
		} );

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
	} );

	afterAll( () => {
		Object.defineProperty( window, 'location', {
			configurable: true,
			value: originalLocation,
		} );
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
	} );

	it( 'disables the connection when Merchant Center is not connected', async () => {
		const user = userEvent.setup();

		render( <ConnectYouTubeAccountCard disabled /> );

		const connectButton = screen.getByRole( 'button', { name: 'Connect' } );
		expect( connectButton ).toBeDisabled();

		await user.click( connectButton );

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
