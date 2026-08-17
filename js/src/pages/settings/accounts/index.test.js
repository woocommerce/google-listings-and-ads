/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import Accounts from './index';
import useAdminUrl from '~/hooks/useAdminUrl';
import useJetpackAccount from '~/hooks/useJetpackAccount';
import useGoogleAccount from '~/hooks/useGoogleAccount';
import useGoogleMCAccount from '~/hooks/useGoogleMCAccount';
import useGoogleAdsAccount from '~/hooks/useGoogleAdsAccount';
import useYouTubeAccount from '~/hooks/useYouTubeAccount';
import useSearchConsoleAccount from '~/hooks/useSearchConsoleAccount';
import { queueRecordGlaEvent } from '~/utils/tracks';
import { getGetStartedUrl } from '~/utils/urls';
import { ALL_ACCOUNTS, YOUTUBE_ACCOUNT } from '../disconnect-modal';

jest.mock( '~/hooks/useAdminUrl', () => jest.fn().mockName( 'useAdminUrl' ) );
jest.mock( '~/hooks/useJetpackAccount', () =>
	jest.fn().mockName( 'useJetpackAccount' )
);
jest.mock( '~/hooks/useGoogleAccount', () =>
	jest.fn().mockName( 'useGoogleAccount' )
);
jest.mock( '~/hooks/useGoogleMCAccount', () =>
	jest.fn().mockName( 'useGoogleMCAccount' )
);
jest.mock( '~/hooks/useGoogleAdsAccount', () =>
	jest.fn().mockName( 'useGoogleAdsAccount' )
);
jest.mock( '~/hooks/useYouTubeAccount', () =>
	jest.fn().mockName( 'useYouTubeAccount' )
);
jest.mock( '~/hooks/useSearchConsoleAccount', () =>
	jest.fn().mockName( 'useSearchConsoleAccount' )
);
jest.mock( '~/utils/tracks', () => ( {
	queueRecordGlaEvent: jest.fn().mockName( 'queueRecordGlaEvent' ),
} ) );
jest.mock( '~/utils/urls', () => ( {
	getGetStartedUrl: jest.fn().mockName( 'getGetStartedUrl' ),
} ) );
jest.mock(
	'./wpcom-account-card',
	() =>
		function MockWPComAccountCard() {
			return <div>WPCom account</div>;
		}
);
jest.mock(
	'./google-account-card',
	() =>
		function MockGoogleAccountCard() {
			return <div>Google account</div>;
		}
);
jest.mock(
	'./merchant-center-account-card',
	() =>
		function MockMerchantCenterAccountCard() {
			return <div>Merchant Center account</div>;
		}
);
jest.mock(
	'./google-ads-account-card',
	() =>
		function MockGoogleAdsAccountCard() {
			return <div>Google Ads account</div>;
		}
);
jest.mock(
	'./youtube-account-card',
	() =>
		function MockYouTubeAccountCard( { onDisconnect } ) {
			return (
				<button onClick={ onDisconnect }>
					Disconnect YouTube account
				</button>
			);
		}
);
jest.mock(
	'./search-console-account-card',
	() =>
		function MockSearchConsoleAccountCard() {
			return <div>Search Console account</div>;
		}
);
jest.mock( '../disconnect-modal', () => ( {
	__esModule: true,
	ALL_ACCOUNTS: 'all-accounts',
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

describe( 'Accounts', () => {
	const originalLocation = window.location;

	beforeEach( () => {
		Object.defineProperty( window, 'location', {
			configurable: true,
			value: { href: '' },
		} );

		useAdminUrl.mockReturnValue( 'https://example.com/wp-admin/' );
		getGetStartedUrl.mockReturnValue(
			'admin.php?page=wc-admin&path=%2Fgoogle%2Fstart'
		);
		useJetpackAccount.mockReturnValue( { hasFinishedResolution: true } );
		useGoogleAccount.mockReturnValue( { hasFinishedResolution: true } );
		useGoogleMCAccount.mockReturnValue( {
			hasGoogleMCConnection: true,
			hasFinishedResolution: true,
		} );
		useGoogleAdsAccount.mockReturnValue( { hasFinishedResolution: true } );
		useYouTubeAccount.mockReturnValue( { hasFinishedResolution: true } );
		useSearchConsoleAccount.mockReturnValue( {
			hasFinishedResolution: true,
		} );
	} );

	afterAll( () => {
		Object.defineProperty( window, 'location', {
			configurable: true,
			value: originalLocation,
		} );
	} );

	it( 'always renders the Tracking and Site tools group', () => {
		render( <Accounts /> );

		expect(
			screen.getByText( 'Search Console account' )
		).toBeInTheDocument();
	} );

	it( 'shows a loading spinner until every account has resolved', () => {
		useYouTubeAccount.mockReturnValue( { hasFinishedResolution: false } );

		render( <Accounts /> );

		expect( screen.queryByText( 'WPCom account' ) ).not.toBeInTheDocument();
	} );

	it( 'does not render the YouTube group without a Google Merchant Center connection', () => {
		useGoogleMCAccount.mockReturnValue( {
			hasGoogleMCConnection: false,
			hasFinishedResolution: true,
		} );

		render( <Accounts /> );

		expect(
			screen.queryByRole( 'button', {
				name: 'Disconnect YouTube account',
			} )
		).not.toBeInTheDocument();
	} );

	it( 'tracks disconnecting the YouTube account without redirecting', async () => {
		const user = userEvent.setup();

		render( <Accounts /> );

		await user.click(
			screen.getByRole( 'button', {
				name: 'Disconnect YouTube account',
			} )
		);
		await user.click(
			screen.getByRole( 'button', { name: 'Confirm disconnect' } )
		);

		expect( queueRecordGlaEvent ).toHaveBeenCalledWith(
			'gla_disconnected_accounts',
			{ context: YOUTUBE_ACCOUNT }
		);
		expect( window.location.href ).toBe( '' );
	} );

	it( 'tracks disconnecting all accounts and redirects to Get Started', async () => {
		const user = userEvent.setup();

		render( <Accounts /> );

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
			{ context: ALL_ACCOUNTS }
		);
		expect( window.location.href ).toBe(
			'https://example.com/wp-admin/admin.php?page=wc-admin&path=%2Fgoogle%2Fstart'
		);
	} );
} );
