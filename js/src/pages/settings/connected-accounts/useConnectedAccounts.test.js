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
				id: 12345,
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
} );
