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
import { recordGlaEvent } from '~/utils/tracks';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';
import { YOUTUBE_MERCHANT_TERMS_URL } from './youtube-merchant-terms-link';

jest.mock( '~/utils/tracks', () => ( {
	recordGlaEvent: jest.fn().mockName( 'recordGlaEvent' ),
} ) );

jest.mock( '~/hooks/useDispatchCoreNotices', () =>
	jest.fn().mockName( 'useDispatchCoreNotices' )
);

jest.mock( '~/hooks/useApiFetchCallback', () =>
	jest.fn().mockName( 'useApiFetchCallback' )
);

describe( 'ConnectYouTubeAccountCard', () => {
	beforeEach( () => {
		jest.clearAllMocks();

		useDispatchCoreNotices.mockReturnValue( {
			createNotice: jest.fn(),
		} );

		useApiFetchCallback.mockReturnValue( [
			jest.fn(),
			{ loading: false, data: undefined },
		] );
	} );

	it( 'should show the YouTube merchant terms link when the account is not connected', () => {
		render( <ConnectYouTubeAccountCard /> );

		expect(
			screen.getByText( 'Sign in to view your channels.' )
		).toBeInTheDocument();

		expect(
			screen.getByRole( 'link', {
				name: /YouTube Merchant Terms/i,
			} )
		).toHaveAttribute( 'href', YOUTUBE_MERCHANT_TERMS_URL );
	} );

	it( 'should record a documentation event when the YouTube merchant terms link is clicked', async () => {
		const user = userEvent.setup();

		render( <ConnectYouTubeAccountCard /> );

		await user.click(
			screen.getByRole( 'link', {
				name: /YouTube Merchant Terms/i,
			} )
		);

		expect( recordGlaEvent ).toHaveBeenCalledWith(
			'gla_documentation_link_click',
			{
				context: 'settings-connect-youtube-account-card',
				link_id: 'youtube-merchant-terms',
				href: YOUTUBE_MERCHANT_TERMS_URL,
			}
		);
	} );
} );
