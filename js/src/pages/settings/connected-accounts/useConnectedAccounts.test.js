/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { YOUTUBE_ACCOUNT_STATUS } from '~/constants';
import useJetpackAccount from '~/hooks/useJetpackAccount';
import useGoogleAccount from '~/hooks/useGoogleAccount';
import useGoogleMCAccount from '~/hooks/useGoogleMCAccount';
import useGoogleAdsAccount from '~/hooks/useGoogleAdsAccount';
import useYouTubeAccount from '~/hooks/useYouTubeAccount';
import useServiceBasedMerchant from '~/hooks/useServiceBasedMerchant';
import useConnectedAccounts from './useConnectedAccounts';

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
jest.mock( '~/hooks/useServiceBasedMerchant', () =>
	jest.fn().mockName( 'useServiceBasedMerchant' )
);

describe( 'useConnectedAccounts', () => {
	beforeEach( () => {
		useJetpackAccount.mockReturnValue( {
			jetpack: {
				active: 'yes',
				owner: 'yes',
				email: 'store@example.com',
			},
			hasFinishedResolution: true,
		} );
		useGoogleAccount.mockReturnValue( {
			google: {
				email: 'merchant@example.com',
			},
			hasFinishedResolution: true,
		} );
		useGoogleAdsAccount.mockReturnValue( {
			googleAdsAccount: {
				status: 'connected',
				id: 1234567890,
				ocid: 9876543210,
			},
			hasFinishedResolution: true,
		} );
		useYouTubeAccount.mockReturnValue( {
			youTubeAccount: {
				status: YOUTUBE_ACCOUNT_STATUS.DISCONNECTED,
			},
			hasFinishedResolution: true,
		} );
		useServiceBasedMerchant.mockReturnValue( false );
	} );

	it( 'shows the Merchant Center setup action when Merchant Center is disconnected', () => {
		useGoogleMCAccount.mockReturnValue( {
			googleMCAccount: {
				id: 0,
				status: 'disconnected',
			},
			hasFinishedResolution: true,
			hasGoogleMCConnection: false,
		} );

		const { result } = renderHook( () => useConnectedAccounts() );
		const merchantCenterAccount = result.current.accounts.find(
			( account ) => account.id === 'merchant-center'
		);

		expect( merchantCenterAccount ).toMatchObject( {
			connected: false,
			canConnect: true,
		} );
	} );

	it( 'hides the YouTube row until Merchant Center is connected', () => {
		useGoogleMCAccount.mockReturnValue( {
			googleMCAccount: {
				id: 0,
				status: 'disconnected',
			},
			hasFinishedResolution: true,
			hasGoogleMCConnection: false,
		} );

		const { result } = renderHook( () => useConnectedAccounts() );
		const youTubeAccount = result.current.accounts.find(
			( account ) => account.id === 'youtube'
		);

		expect( youTubeAccount ).toMatchObject( {
			connected: false,
			canConnect: false,
			isVisible: false,
		} );
	} );

	it( 'shows the YouTube connect action once Merchant Center is connected', () => {
		useGoogleMCAccount.mockReturnValue( {
			googleMCAccount: {
				id: 1234,
				status: 'connected',
			},
			hasFinishedResolution: true,
			hasGoogleMCConnection: true,
		} );

		const { result } = renderHook( () => useConnectedAccounts() );
		const youTubeAccount = result.current.accounts.find(
			( account ) => account.id === 'youtube'
		);

		expect( youTubeAccount ).toMatchObject( {
			connected: false,
			canConnect: true,
			isVisible: true,
		} );
	} );

	it( 'adds a YouTube channel link for the connected account row', () => {
		useGoogleMCAccount.mockReturnValue( {
			googleMCAccount: {
				id: 1234,
				status: 'connected',
			},
			hasFinishedResolution: true,
			hasGoogleMCConnection: true,
		} );
		useYouTubeAccount.mockReturnValue( {
			youTubeAccount: {
				status: YOUTUBE_ACCOUNT_STATUS.CONNECTED,
				channel: {
					id: 'UC1234567890abcdef',
					label: 'My YouTube Channel',
				},
			},
			hasFinishedResolution: true,
		} );

		const { result } = renderHook( () => useConnectedAccounts() );
		const youTubeAccount = result.current.accounts.find(
			( account ) => account.id === 'youtube'
		);

		expect( youTubeAccount ).toMatchObject( {
			connected: true,
			detail: 'My YouTube Channel',
			detailUrl: 'https://www.youtube.com/channel/UC1234567890abcdef',
			canDisconnect: true,
		} );
	} );

	it( 'adds a Merchant Center account link for the connected account row', () => {
		useGoogleMCAccount.mockReturnValue( {
			googleMCAccount: {
				id: 1234,
				status: 'connected',
			},
			hasFinishedResolution: true,
			hasGoogleMCConnection: true,
		} );

		const { result } = renderHook( () => useConnectedAccounts() );
		const merchantCenterAccount = result.current.accounts.find(
			( account ) => account.id === 'merchant-center'
		);

		expect( merchantCenterAccount ).toMatchObject( {
			connected: true,
			detail: '1234',
			detailUrl: 'https://merchants.google.com/mc/overview?a=1234',
			canDisconnect: false,
		} );
	} );

	it( 'uses the trunk Google Ads overview URL and formats the displayed account ID', () => {
		useGoogleMCAccount.mockReturnValue( {
			googleMCAccount: {
				id: 1234,
				status: 'connected',
			},
			hasFinishedResolution: true,
			hasGoogleMCConnection: true,
		} );

		const { result } = renderHook( () => useConnectedAccounts() );
		const googleAdsAccount = result.current.accounts.find(
			( account ) => account.id === 'google-ads'
		);

		expect( googleAdsAccount ).toMatchObject( {
			connected: true,
			detail: '123-456-7890',
			detailUrl: 'https://ads.google.com/aw/overview',
			canDisconnect: false,
		} );
	} );
} );
