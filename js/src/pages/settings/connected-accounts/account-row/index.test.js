/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import userEvent from '@testing-library/user-event';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import AccountRow from './index';
import { APPEARANCE } from '~/components/account-card';

describe( 'AccountRow', () => {
	it( 'renders account detail as an external link when a detail URL is provided', () => {
		render(
			<AccountRow
				account={ {
					id: 'video',
					appearance: APPEARANCE.YOUTUBE,
					title: 'Video account',
					description: 'A connected video account.',
					connected: true,
					detail: 'My Channel',
					detailUrl: 'https://example.com/channel',
					canDisconnect: true,
					disconnectTarget: 'video-account',
				} }
				onDisconnect={ jest.fn() }
			/>
		);

		expect(
			screen.getByRole( 'link', { name: /My Channel/ } )
		).toHaveAttribute( 'href', 'https://example.com/channel' );
	} );

	it( 'renders supplied helper content without knowing the account type', () => {
		render(
			<AccountRow
				account={ {
					id: 'example',
					appearance: APPEARANCE.GOOGLE,
					title: 'Example account',
					description: 'An example account.',
					connected: false,
					helper: <a href="https://example.com/terms">Terms</a>,
					canDisconnect: false,
				} }
				onDisconnect={ jest.fn() }
			/>
		);

		expect( screen.getByRole( 'link', { name: 'Terms' } ) ).toHaveAttribute(
			'href',
			'https://example.com/terms'
		);
	} );

	it( 'renders a supplied connect component for a disconnected account', () => {
		function ConnectComponent() {
			return <button>Connect example account</button>;
		}

		render(
			<AccountRow
				account={ {
					id: 'example',
					appearance: APPEARANCE.GOOGLE,
					title: 'Example account',
					description: 'An example account.',
					connected: false,
					ConnectComponent,
					canDisconnect: false,
				} }
				onDisconnect={ jest.fn() }
			/>
		);

		expect(
			screen.getByRole( 'button', { name: 'Connect example account' } )
		).toBeInTheDocument();
	} );

	it( 'does not render individual disconnect actions when unavailable', () => {
		render(
			<AccountRow
				account={ {
					id: 'ads',
					appearance: APPEARANCE.GOOGLE_ADS,
					title: 'Ads',
					description: 'An ads account.',
					connected: true,
					canDisconnect: false,
				} }
				onDisconnect={ jest.fn() }
			/>
		);

		expect(
			screen.queryByRole( 'button', {
				name: 'Account actions for Ads',
			} )
		).not.toBeInTheDocument();
	} );

	it( 'opens the supplied disconnect flow from the account actions menu', async () => {
		const user = userEvent.setup();
		const onDisconnect = jest.fn();

		render(
			<AccountRow
				account={ {
					id: 'video',
					appearance: APPEARANCE.YOUTUBE,
					title: 'Video account',
					description: 'A connected video account.',
					connected: true,
					canDisconnect: true,
					disconnectTarget: 'video-account',
				} }
				onDisconnect={ onDisconnect }
			/>
		);

		await user.click(
			screen.getByRole( 'button', {
				name: 'Account actions for Video account',
			} )
		);
		await user.click(
			screen.getByRole( 'menuitem', {
				name: 'Disconnect Video account',
			} )
		);

		expect( onDisconnect ).toHaveBeenCalledWith( 'video-account' );
	} );
} );
