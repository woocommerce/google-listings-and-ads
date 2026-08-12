/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import ConnectedAccounts from './index';
import useAdminUrl from '~/hooks/useAdminUrl';
import { queueRecordGlaEvent } from '~/utils/tracks';
import { getGetStartedUrl } from '~/utils/urls';
import useConnectedAccounts, { ACCOUNT_SECTION } from './useConnectedAccounts';
import { ALL_ACCOUNTS, YOUTUBE_ACCOUNT } from '../disconnect-modal';

jest.mock( '~/hooks/useAdminUrl', () => jest.fn().mockName( 'useAdminUrl' ) );
jest.mock( '~/utils/tracks', () => ( {
	queueRecordGlaEvent: jest.fn().mockName( 'queueRecordGlaEvent' ),
} ) );
jest.mock( '~/utils/urls', () => ( {
	getGetStartedUrl: jest.fn().mockName( 'getGetStartedUrl' ),
} ) );
jest.mock( './useConnectedAccounts', () => ( {
	__esModule: true,
	default: jest.fn().mockName( 'useConnectedAccounts' ),
	ACCOUNT_SECTION: {
		REQUIRED: 'required',
		GROW: 'grow',
		TRACKING: 'tracking',
	},
} ) );
jest.mock(
	'./accounts-group-card',
	() =>
		function MockAccountsGroupCard( { accounts, onDisconnect } ) {
			return (
				<button
					onClick={ () =>
						onDisconnect( accounts[ 0 ].disconnectTarget )
					}
				>
					Disconnect
				</button>
			);
		}
);
jest.mock( '../disconnect-modal', () => ( {
	__esModule: true,
	ALL_ACCOUNTS: 'all-accounts',
	ADS_ONLY: 'ads-only',
	YOUTUBE_ACCOUNT: 'youtube-account',
	default: function MockDisconnectModal( {
		disconnectTarget,
		onDisconnected,
		onRequestClose,
	} ) {
		return (
			<div>
				<div>{ disconnectTarget }</div>
				<button
					onClick={ () => {
						onDisconnected();
						onRequestClose();
					} }
				>
					Confirm disconnect
				</button>
			</div>
		);
	},
} ) );

describe( 'ConnectedAccounts', () => {
	const originalLocation = window.location;
	const currentSettingsUrl =
		'https://example.com/wp-admin/admin.php?page=wc-admin&path=%2Fgoogle%2Fsettings&section=accounts';

	beforeEach( () => {
		Object.defineProperty( window, 'location', {
			configurable: true,
			value: { href: currentSettingsUrl },
		} );

		useAdminUrl.mockReturnValue( 'https://example.com/wp-admin/' );
		getGetStartedUrl.mockReturnValue(
			'admin.php?page=wc-admin&path=%2Fgoogle%2Fstart'
		);
		useConnectedAccounts.mockReturnValue( {
			isLoading: false,
			accounts: [],
		} );
	} );

	afterAll( () => {
		Object.defineProperty( window, 'location', {
			configurable: true,
			value: originalLocation,
		} );
	} );

	it( 'updates the YouTube disconnect flow in place', async () => {
		const user = userEvent.setup();

		useConnectedAccounts.mockReturnValue( {
			isLoading: false,
			accounts: [
				{
					id: 'youtube',
					section: ACCOUNT_SECTION.GROW,
					title: 'YouTube',
					connected: true,
					canDisconnect: true,
					disconnectTarget: YOUTUBE_ACCOUNT,
				},
			],
		} );

		render( <ConnectedAccounts /> );

		await user.click(
			screen.getByRole( 'button', { name: 'Disconnect' } )
		);
		await user.click(
			screen.getByRole( 'button', { name: 'Confirm disconnect' } )
		);

		expect( queueRecordGlaEvent ).toHaveBeenCalledWith(
			'gla_disconnected_accounts',
			{
				context: YOUTUBE_ACCOUNT,
			}
		);
		expect( window.location.href ).toBe( currentSettingsUrl );
	} );

	it( 'renders a section for an account that is neither connected nor offers a connect action, but has a specialized row (e.g. an incomplete connect flow)', () => {
		useConnectedAccounts.mockReturnValue( {
			isLoading: false,
			accounts: [
				{
					id: 'search-console',
					section: ACCOUNT_SECTION.TRACKING,
					title: 'Google Search Console',
					connected: false,
					canDisconnect: false,
					RowComponent: () => null,
				},
			],
		} );

		render( <ConnectedAccounts /> );

		expect(
			screen.getByRole( 'button', { name: 'Disconnect' } )
		).toBeInTheDocument();
	} );

	it( 'keeps redirecting after disconnecting all accounts', async () => {
		const user = userEvent.setup();

		render( <ConnectedAccounts /> );

		await user.click(
			screen.getByRole( 'button', {
				name: 'Disconnect from all accounts',
			} )
		);
		await user.click(
			screen.getByRole( 'button', { name: 'Confirm disconnect' } )
		);

		expect( queueRecordGlaEvent ).toHaveBeenCalledWith(
			'gla_disconnected_accounts',
			{
				context: ALL_ACCOUNTS,
			}
		);
		expect( window.location.href ).toBe(
			'https://example.com/wp-admin/admin.php?page=wc-admin&path=%2Fgoogle%2Fstart'
		);
	} );
} );
