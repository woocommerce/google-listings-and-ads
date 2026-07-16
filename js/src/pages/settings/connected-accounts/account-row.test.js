/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import AccountRow from './account-row';
import { APPEARANCE } from '~/components/account-card';

describe( 'AccountRow', () => {
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
			screen.queryByRole( 'button', { name: 'Account actions' } )
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
						'Promote your products on YouTube via Shopping ads.',
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
} );
