/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { getQuery, getHistory } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import ConnectedYouTubeAccountCard from './connected-youtube-account-card';
import useYouTubeSetupCompleteCallback from '~/hooks/useYouTubeSetupCompleteCallback';
import { recordGlaEvent } from '~/utils/tracks';
import { YOUTUBE_ACCOUNT_STATUS } from '~/constants';

jest.mock( '@woocommerce/navigation' );
jest.mock( '~/hooks/useYouTubeSetupCompleteCallback' );
jest.mock( '~/utils/tracks', () => ( {
	recordGlaEvent: jest.fn().mockName( 'recordGlaEvent' ),
} ) );

describe( 'ConnectedYouTubeAccountCard', () => {
	let handleFinishSetup;

	beforeEach( () => {
		jest.clearAllMocks();

		getQuery.mockReturnValue( {} );
		getHistory.mockReturnValue( { replace: jest.fn() } );

		handleFinishSetup = jest
			.fn()
			.mockName( 'handleFinishSetup' )
			.mockResolvedValue();
		useYouTubeSetupCompleteCallback.mockReturnValue( [
			handleFinishSetup,
			{ loading: false, error: undefined },
		] );
	} );

	it( 'tracks the "Complete setup" button click for an incomplete account', async () => {
		const user = userEvent.setup();

		render(
			<ConnectedYouTubeAccountCard
				youTubeAccount={ {
					status: YOUTUBE_ACCOUNT_STATUS.INCOMPLETE,
					channel: { id: 'UC123', label: 'My channel' },
				} }
				onDisconnect={ jest.fn() }
			/>
		);

		await user.click(
			screen.getByRole( 'button', { name: 'Complete setup' } )
		);

		expect( recordGlaEvent ).toHaveBeenCalledWith(
			'gla_link_youtube_account_button_click',
			{ context: 'settings-youtube' }
		);
		expect( handleFinishSetup ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'does not render the "Complete setup" CTA for a connected account', () => {
		render(
			<ConnectedYouTubeAccountCard
				youTubeAccount={ {
					status: YOUTUBE_ACCOUNT_STATUS.CONNECTED,
					channel: { id: 'UC123', label: 'My channel' },
				} }
				onDisconnect={ jest.fn() }
			/>
		);

		expect(
			screen.queryByRole( 'button', { name: 'Complete setup' } )
		).not.toBeInTheDocument();
		expect( recordGlaEvent ).not.toHaveBeenCalled();
	} );

	it( 'shows the channel error instead of the channel link when the channel lookup failed', () => {
		render(
			<ConnectedYouTubeAccountCard
				youTubeAccount={ {
					status: YOUTUBE_ACCOUNT_STATUS.CONNECTED,
					channel: {},
					error: 'Error retrieving channels',
				} }
				onDisconnect={ jest.fn() }
			/>
		);

		expect(
			screen.getByText( 'Error retrieving channels', {
				selector: '.components-notice__content',
			} )
		).toBeInTheDocument();
		expect( screen.queryByRole( 'link' ) ).not.toBeInTheDocument();
	} );
} );
