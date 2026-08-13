/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import userEvent from '@testing-library/user-event';
import { render, renderHook, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import {
	YOUTUBE_ACCOUNT_STATUS,
	SEARCH_CONSOLE_ACCOUNT_STATUS,
} from '~/constants';
import useJetpackAccount from '~/hooks/useJetpackAccount';
import useGoogleAccount from '~/hooks/useGoogleAccount';
import useGoogleMCAccount from '~/hooks/useGoogleMCAccount';
import useGoogleAdsAccount from '~/hooks/useGoogleAdsAccount';
import useYouTubeAccount from '~/hooks/useYouTubeAccount';
import useSearchConsoleAccount from '~/hooks/useSearchConsoleAccount';
import useServiceBasedMerchant from '~/hooks/useServiceBasedMerchant';
import { recordGlaEvent } from '~/utils/tracks';
import useConnectedAccounts, {
	ACCOUNT_SECTION,
	YOUTUBE_MERCHANT_TERMS_URL,
} from './useConnectedAccounts';
import IncompleteYouTubeAccountRow from './incomplete-youtube-account-row';
import MerchantCenterConnectButton from './merchant-center-connect-button';
import YouTubeConnectButton from './youtube-connect-button';
import SearchConsoleConnectButton from './search-console/components/search-console-connect-button';
import SearchConsoleAccountRow from './search-console/components/search-console-account-row';

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
jest.mock( '~/hooks/useServiceBasedMerchant', () =>
	jest.fn().mockName( 'useServiceBasedMerchant' )
);
jest.mock( '~/utils/tracks', () => ( {
	recordGlaEvent: jest.fn().mockName( 'recordGlaEvent' ),
} ) );

describe( 'useConnectedAccounts', () => {
	beforeEach( () => {
		jest.clearAllMocks();

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
		useSearchConsoleAccount.mockReturnValue( {
			searchConsoleAccount: {
				status: SEARCH_CONSOLE_ACCOUNT_STATUS.DISCONNECTED,
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
		} );
		expect( merchantCenterAccount.ConnectComponent ).toBe(
			MerchantCenterConnectButton
		);
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
			isVisible: false,
		} );
		expect( youTubeAccount.ConnectComponent ).toBeUndefined();
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
			isVisible: true,
		} );
		expect( youTubeAccount.ConnectComponent ).toBe( YouTubeConnectButton );
	} );

	it( 'supplies and tracks the YouTube Merchant Terms helper', async () => {
		const user = userEvent.setup();
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

		render( youTubeAccount.helper );
		const termsLink = screen.getByRole( 'link', {
			name: /YouTube Merchant Terms/,
		} );

		expect( termsLink ).toHaveAttribute(
			'href',
			YOUTUBE_MERCHANT_TERMS_URL
		);

		await user.click( termsLink );

		expect( recordGlaEvent ).toHaveBeenCalledWith(
			'gla_documentation_link_click',
			{
				context: 'settings-connect-youtube-account-card',
				link_id: 'youtube-merchant-terms',
				href: YOUTUBE_MERCHANT_TERMS_URL,
			}
		);
	} );

	it( 'supplies the specialized row for incomplete YouTube setup', () => {
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
				status: YOUTUBE_ACCOUNT_STATUS.INCOMPLETE,
			},
			hasFinishedResolution: true,
		} );

		const { result } = renderHook( () => useConnectedAccounts() );
		const youTubeAccount = result.current.accounts.find(
			( account ) => account.id === 'youtube'
		);

		expect( youTubeAccount.RowComponent ).toBe(
			IncompleteYouTubeAccountRow
		);
		expect( youTubeAccount.helper ).toBeUndefined();
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

	it( 'shows the Search Console connect action when disconnected', () => {
		useGoogleMCAccount.mockReturnValue( {
			googleMCAccount: { id: 0, status: 'disconnected' },
			hasFinishedResolution: true,
			hasGoogleMCConnection: false,
		} );

		const { result } = renderHook( () => useConnectedAccounts() );
		const searchConsoleAccount = result.current.accounts.find(
			( account ) => account.id === 'search-console'
		);

		expect( searchConsoleAccount ).toMatchObject( {
			section: ACCOUNT_SECTION.TRACKING,
			connected: false,
			canDisconnect: false,
		} );
		expect( searchConsoleAccount.ConnectComponent ).toBe(
			SearchConsoleConnectButton
		);
		expect( searchConsoleAccount.RowComponent ).toBeUndefined();
		expect( searchConsoleAccount.description ).toBe(
			'See how your store performs in Google Search.'
		);
	} );

	it( 'supplies the specialized row for an incomplete Search Console connection', () => {
		useSearchConsoleAccount.mockReturnValue( {
			searchConsoleAccount: {
				status: SEARCH_CONSOLE_ACCOUNT_STATUS.INCOMPLETE,
				step: 'verification',
			},
			hasFinishedResolution: true,
		} );

		const { result } = renderHook( () => useConnectedAccounts() );
		const searchConsoleAccount = result.current.accounts.find(
			( account ) => account.id === 'search-console'
		);

		expect( searchConsoleAccount ).toMatchObject( { connected: false } );
		expect( searchConsoleAccount.RowComponent ).toBe(
			SearchConsoleAccountRow
		);
		expect( searchConsoleAccount.ConnectComponent ).toBeUndefined();
	} );

	it( 'adds a Search Console property link for the connected account row', () => {
		useSearchConsoleAccount.mockReturnValue( {
			searchConsoleAccount: {
				status: SEARCH_CONSOLE_ACCOUNT_STATUS.CONNECTED,
				property: { url: 'https://example.com/' },
			},
			hasFinishedResolution: true,
		} );

		const { result } = renderHook( () => useConnectedAccounts() );
		const searchConsoleAccount = result.current.accounts.find(
			( account ) => account.id === 'search-console'
		);

		expect( searchConsoleAccount ).toMatchObject( {
			connected: true,
			detail: 'https://example.com/',
			detailUrl: 'https://example.com/',
			canDisconnect: false,
		} );
		expect( searchConsoleAccount.RowComponent ).toBe(
			SearchConsoleAccountRow
		);
		expect( searchConsoleAccount.ConnectComponent ).toBeUndefined();
	} );
} );
