/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import userEvent from '@testing-library/user-event';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import AccountRow from './account-row';
import { APPEARANCE } from '~/components/account-card';
import { YOUTUBE_MERCHANT_TERMS_URL } from '~/components/youtube-merchant-terms-link';
import { recordGlaEvent } from '~/utils/tracks';

jest.mock( '~/utils/tracks', () => ( {
	recordGlaEvent: jest.fn().mockName( 'recordGlaEvent' ),
} ) );

describe( 'AccountRow', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'renders the Merchant Center account detail as an external link', () => {
		render(
			<AccountRow
				account={ {
					id: 'merchant-center',
					appearance: APPEARANCE.GOOGLE_MERCHANT_CENTER,
					title: 'Merchant Center',
					description:
						'Where your product catalog is synced to appear on Google.',
					connected: true,
					detail: '123456789',
					detailUrl:
						'https://merchants.google.com/mc/overview?a=123456789',
					canDisconnect: false,
				} }
				onDisconnect={ jest.fn() }
			/>
		);

		expect(
			screen.getByRole( 'link', {
				name: /123456789/i,
			} )
		).toHaveAttribute(
			'href',
			'https://merchants.google.com/mc/overview?a=123456789'
		);
	} );

	it( 'renders the Google Ads account detail as an external link without individual disconnect actions', () => {
		render(
			<AccountRow
				account={ {
					id: 'google-ads',
					appearance: APPEARANCE.GOOGLE_ADS,
					title: 'Google Ads',
					description:
						'Where your ad campaigns and conversion tracking are managed.',
					connected: true,
					detail: '123-456-7890',
					detailUrl: 'https://ads.google.com/aw/overview',
					canDisconnect: false,
				} }
				onDisconnect={ jest.fn() }
			/>
		);

		expect(
			screen.getByRole( 'link', {
				name: /123-456-7890/i,
			} )
		).toHaveAttribute( 'href', 'https://ads.google.com/aw/overview' );
		expect(
			screen.queryByRole( 'button', {
				name: 'Account actions for Google Ads',
			} )
		).not.toBeInTheDocument();
	} );

	it( 'renders the YouTube channel label as an external link', () => {
		render(
			<AccountRow
				account={ {
					id: 'youtube',
					appearance: APPEARANCE.YOUTUBE,
					title: 'YouTube',
					description:
						'List your products on YouTube and track sales from your videos.',
					connected: true,
					detail: 'My YouTube Channel',
					detailUrl:
						'https://www.youtube.com/channel/UC1234567890abcdef',
					canDisconnect: true,
					disconnectTarget: 'youtube-account',
				} }
				onDisconnect={ jest.fn() }
			/>
		);

		expect(
			screen.getByRole( 'link', {
				name: /My YouTube Channel/i,
			} )
		).toHaveAttribute(
			'href',
			'https://www.youtube.com/channel/UC1234567890abcdef'
		);
	} );

	it( 'renders the YouTube merchant terms link when the YouTube account is not connected', () => {
		render(
			<AccountRow
				account={ {
					id: 'youtube',
					appearance: APPEARANCE.YOUTUBE,
					title: 'YouTube',
					description:
						'List your products on YouTube and track sales from your videos.',
					connected: false,
					canDisconnect: false,
				} }
				onDisconnect={ jest.fn() }
			/>
		);

		expect(
			screen.getByRole( 'link', {
				name: /YouTube Merchant Terms/i,
			} )
		).toHaveAttribute( 'href', YOUTUBE_MERCHANT_TERMS_URL );
	} );

	it( 'tracks the YouTube-specific disconnect click before opening the modal flow', async () => {
		const user = userEvent.setup();
		const onDisconnect = jest.fn();

		render(
			<AccountRow
				account={ {
					id: 'youtube',
					appearance: APPEARANCE.YOUTUBE,
					title: 'YouTube',
					description:
						'List your products on YouTube and track sales from your videos.',
					connected: true,
					detail: 'My YouTube Channel',
					detailUrl:
						'https://www.youtube.com/channel/UC1234567890abcdef',
					canDisconnect: true,
					disconnectTarget: 'youtube-account',
				} }
				onDisconnect={ onDisconnect }
			/>
		);

		await user.click(
			screen.getByRole( 'button', {
				name: 'Account actions for YouTube',
			} )
		);
		await user.click(
			screen.getByRole( 'menuitem', { name: 'Disconnect YouTube' } )
		);

		expect( recordGlaEvent ).toHaveBeenCalledWith(
			'gla_youtube_account_disconnect_button_click',
			{
				context: 'settings-youtube',
			}
		);
		expect( onDisconnect ).toHaveBeenCalledWith( 'youtube-account' );
	} );
} );
