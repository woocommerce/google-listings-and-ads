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
import { redirectTo } from '~/utils/urls';

jest.mock( '~/hooks/useApiFetchCallback' );
jest.mock( '~/hooks/useDispatchCoreNotices' );
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
