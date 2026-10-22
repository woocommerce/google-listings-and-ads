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
import { queueRecordGlaEvent } from '~/utils/tracks';
import { getGetStartedUrl, redirectTo } from '~/utils/urls';
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
jest.mock( '~/utils/tracks', () => ( {
	queueRecordGlaEvent: jest.fn().mockName( 'queueRecordGlaEvent' ),
} ) );
jest.mock( '~/utils/urls', () => ( {
	getGetStartedUrl: jest.fn().mockName( 'getGetStartedUrl' ),
	redirectTo: jest.fn().mockName( 'redirectTo' ),
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
	'./google-business-profile-account-card',
	() =>
		function MockGoogleBusinessProfileAccountCard( { onDisconnect } ) {
			return (
				<>
					<div>Google Business Profile account</div>
					<button onClick={ onDisconnect }>
						Disconnect Google Business Profile
					</button>
				</>
			);
		}
);
jest.mock( '../disconnect-modal', () => ( {
	__esModule: true,
	ALL_ACCOUNTS: 'all-accounts',
	YOUTUBE_ACCOUNT: 'youtube-account',
	GOOGLE_BUSINESS_PROFILE_ACCOUNT: 'google-business-profile-account',
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
	beforeEach( () => {
		jest.clearAllMocks();

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
	} );

	it( 'shows a loading spinner until every account has resolved', () => {
		useYouTubeAccount.mockReturnValue( { hasFinishedResolution: false } );

		render( <Accounts /> );

		expect( screen.queryByText( 'WPCom account' ) ).not.toBeInTheDocument();
	} );

	it( 'shows YouTube and Google Business Profile in "Grow your reach" with a Google Merchant Center connection', () => {
		render( <Accounts /> );

		expect( screen.getByText( 'Grow your reach' ) ).toBeInTheDocument();
		expect(
			screen.getByRole( 'button', {
				name: 'Disconnect YouTube account',
			} )
		).toBeInTheDocument();
		expect(
			screen.getByText( 'Google Business Profile account' )
		).toBeInTheDocument();
	} );

	it( 'still shows the Merchant Center, YouTube, and Google Business Profile cards without a Google Merchant Center connection', () => {
		useGoogleMCAccount.mockReturnValue( {
			hasGoogleMCConnection: false,
			hasFinishedResolution: true,
		} );

		render( <Accounts /> );

		expect(
			screen.getByText( 'Merchant Center account' )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'heading', { name: 'Grow your reach' } )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'button', {
				name: 'Disconnect YouTube account',
			} )
		).toBeInTheDocument();
		expect(
			screen.getByText( 'Google Business Profile account' )
		).toBeInTheDocument();
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
		expect( redirectTo ).not.toHaveBeenCalled();
	} );

	it( 'opens the Google Business Profile disconnect modal and tracks it without redirecting', async () => {
		const user = userEvent.setup();

		render( <Accounts /> );

		await user.click(
			screen.getByRole( 'button', {
				name: 'Disconnect Google Business Profile',
			} )
		);

		expect(
			screen.getByText( 'google-business-profile-account' )
		).toBeInTheDocument();

		await user.click(
			screen.getByRole( 'button', { name: 'Confirm disconnect' } )
		);

		expect( queueRecordGlaEvent ).toHaveBeenCalledWith(
			'gla_disconnected_accounts',
			{ context: 'google-business-profile-account' }
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
		expect( redirectTo ).toHaveBeenCalledWith(
			'https://example.com/wp-admin/admin.php?page=wc-admin&path=%2Fgoogle%2Fstart'
		);
	} );
} );
