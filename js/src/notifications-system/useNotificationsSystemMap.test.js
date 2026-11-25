/**
 * External dependencies
 */
import { act, renderHook } from '@testing-library/react';
import { getHistory } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import useNotificationsSystemMap from './useNotificationsSystemMap';
import useGoogleMCAccount from '~/hooks/useGoogleMCAccount';
import useSettings from '~/hooks/useSettings';
import { handleApiError } from '~/utils/handleError';
import { getAccountsSettingsUrl } from '~/utils/urls';
import { useAppDispatch } from '~/data';

jest.mock( '~/data', () => ( { useAppDispatch: jest.fn() } ) );

jest.mock( '~/hooks/useGoogleMCAccount', () =>
	jest.fn().mockName( 'useGoogleMCAccount' )
);

jest.mock( '~/hooks/useSettings', () => jest.fn().mockName( 'useSettings' ) );

jest.mock( '~/utils/handleError', () => ( {
	handleApiError: jest.fn(),
} ) );

const saveSettings = jest.fn();
const push = jest.fn();
const dismissNotification = jest.fn();
const persistDismissal = jest.fn();

jest.mock( '@woocommerce/navigation', () => ( {
	...jest.requireActual( '@woocommerce/navigation' ),
	getHistory: jest.fn(),
} ) );

const createMockEvent = () => ( {
	preventDefault: jest.fn(),
	currentTarget: {
		getAttribute: jest.fn().mockReturnValue( '/settings-href' ),
	},
} );

describe( 'useNotificationsSystemMap', () => {
	beforeEach( () => {
		jest.clearAllMocks();

		useGoogleMCAccount.mockReturnValue( {
			hasGoogleMCConnection: true,
			hasFinishedResolution: true,
		} );
		useSettings.mockReturnValue( {
			settings: {
				gcr_collect_reviews_after_purchase: false,
				gcr_badge_widget_enabled: false,
			},
			saveSettings,
		} );
		getHistory.mockReturnValue( { push } );
		useAppDispatch.mockReturnValue( {
			dismissNotification: persistDismissal,
		} );
	} );

	it( 'defines the google-customer-reviews-collect-reviews notification content and CTA', () => {
		const { result } = renderHook( () => useNotificationsSystemMap() );
		const config =
			result.current[ 'google-customer-reviews-collect-reviews' ];

		expect( config.title ).toBe( 'Collect Google Reviews after purchase' );
		expect( config.description ).toBe(
			'Google Reviews provide free social proof, increased organic visibility, and a boost to advertising performance.'
		);
		expect( config.actions ).toHaveLength( 1 );
		expect( config.actions[ 0 ] ).toEqual(
			expect.objectContaining( {
				children: 'Enable reviews collection',
				onClick: expect.any( Function ),
			} )
		);
	} );

	it( 'defines the google-customer-reviews-badge-widget notification content and CTA', () => {
		const { result } = renderHook( () => useNotificationsSystemMap() );
		const config = result.current[ 'google-customer-reviews-badge-widget' ];

		expect( config.title ).toBe( 'Add your store rating widget' );
		expect( config.description ).toBe(
			'Show Google-verified ratings and reviews on your site and boost shopper trust and conversions.'
		);
		expect( config.actions ).toHaveLength( 1 );
		expect( config.actions[ 0 ] ).toEqual(
			expect.objectContaining( {
				children: 'Add widget',
				onClick: expect.any( Function ),
			} )
		);
	} );

	it( "saves gcr_collect_reviews_after_purchase, blocks default navigation, dismisses the notification, and navigates to the anchor's href once the save succeeds", async () => {
		saveSettings.mockResolvedValue( {} );

		const { result } = renderHook( () => useNotificationsSystemMap() );
		const action =
			result.current[ 'google-customer-reviews-collect-reviews' ]
				.actions[ 0 ];
		const event = createMockEvent();

		await act( async () => {
			await action.onClick( event, dismissNotification );
		} );

		expect( event.preventDefault ).toHaveBeenCalled();
		expect( saveSettings ).toHaveBeenCalledWith(
			expect.objectContaining( {
				gcr_collect_reviews_after_purchase: true,
			} )
		);
		expect( dismissNotification ).toHaveBeenCalledWith(
			'google-customer-reviews-collect-reviews'
		);
		expect( push ).toHaveBeenCalledWith( '/settings-href' );
	} );

	it( "saves gcr_badge_widget_enabled, blocks default navigation, dismisses the notification, and navigates to the anchor's href once the save succeeds", async () => {
		saveSettings.mockResolvedValue( {} );

		const { result } = renderHook( () => useNotificationsSystemMap() );
		const action =
			result.current[ 'google-customer-reviews-badge-widget' ]
				.actions[ 0 ];
		const event = createMockEvent();

		await act( async () => {
			await action.onClick( event, dismissNotification );
		} );

		expect( event.preventDefault ).toHaveBeenCalled();
		expect( saveSettings ).toHaveBeenCalledWith(
			expect.objectContaining( {
				gcr_badge_widget_enabled: true,
			} )
		);
		expect( dismissNotification ).toHaveBeenCalledWith(
			'google-customer-reviews-badge-widget'
		);
		expect( push ).toHaveBeenCalledWith( '/settings-href' );
	} );

	it( 'reports an error and does not dismiss or navigate when saving the setting fails', async () => {
		const error = { message: 'Something went wrong' };
		saveSettings.mockRejectedValue( error );

		const { result } = renderHook( () => useNotificationsSystemMap() );
		const action =
			result.current[ 'google-customer-reviews-collect-reviews' ]
				.actions[ 0 ];

		await act( async () => {
			await action.onClick( createMockEvent(), dismissNotification );
		} );

		expect( handleApiError ).toHaveBeenCalledWith(
			error,
			expect.any( String )
		);
		expect( dismissNotification ).not.toHaveBeenCalled();
		expect( push ).not.toHaveBeenCalled();
	} );

	it( "disables the collect-reviews action's own button while its setting is saving", async () => {
		let resolveSave;
		saveSettings.mockReturnValue(
			new Promise( ( resolve ) => {
				resolveSave = resolve;
			} )
		);

		const { result } = renderHook( () => useNotificationsSystemMap() );
		const action =
			result.current[ 'google-customer-reviews-collect-reviews' ]
				.actions[ 0 ];

		act( () => {
			action.onClick( createMockEvent(), dismissNotification );
		} );

		expect(
			result.current[ 'google-customer-reviews-collect-reviews' ]
				.actions[ 0 ].disabled
		).toBe( true );
		expect(
			result.current[ 'google-customer-reviews-badge-widget' ]
				.actions[ 0 ].disabled
		).toBe( false );

		await act( async () => {
			resolveSave( {} );
		} );

		expect(
			result.current[ 'google-customer-reviews-collect-reviews' ]
				.actions[ 0 ].disabled
		).toBe( false );
	} );

	it( 'defines the Search Console notification content and Accounts CTA', () => {
		const { result } = renderHook( () => useNotificationsSystemMap() );
		expect( Object.keys( result.current ) ).toContain(
			'search-console-not-connected'
		);

		const config = result.current[ 'search-console-not-connected' ];

		expect( config.title ).toBe(
			'Discover how shoppers find you on Google'
		);
		expect( config.description ).toBe(
			'Connect your Search Console profile to see how shoppers find your store in organic Google Search, including clicks, impressions, and top queries.'
		);
		expect( config.actions ).toEqual( [
			{
				id: 'connect-search-console',
				href: getAccountsSettingsUrl( {
					'focus-account-card': 'search-console',
				} ),
				children: 'Connect now',
			},
		] );
	} );

	describe.each( [
		[
			'cyo-survey-spend',
			'How did your Google Ads sign-up offer work for you?',
			'You may have claimed an introductory offer when launching your campaigns. Take a quick 4-minute survey to help WooCommerce and Google improve future merchant incentives.',
			'Share your thoughts',
			'https://forms.gle/RyGfPnPHEshzDvL88',
		],
		[
			'cyo-survey-no-spend',
			'Tell us about your Google Ads sign-up offer',
			'Even if you haven’t started running ads yet, your feedback matters. Take a quick 2-minute survey to help us improve the setup and offer experience for store owners.',
			'Give quick feedback',
			'https://forms.gle/i4qugA7rgm4o4j5K9',
		],
	] )( 'the %s notification', ( id, title, description, label, href ) => {
		const getAction = () => {
			const { result } = renderHook( () => useNotificationsSystemMap() );
			return result.current[ id ].actions[ 0 ];
		};

		it( 'defines the survey copy and a CTA that opens the form in a new tab', () => {
			const { result } = renderHook( () => useNotificationsSystemMap() );
			const config = result.current[ id ];

			expect( config.title ).toBe( title );
			expect( config.description ).toBe( description );
			expect( config.actions ).toHaveLength( 1 );
			expect( config.actions[ 0 ] ).toEqual(
				expect.objectContaining( {
					href,
					target: '_blank',
					rel: 'noopener noreferrer',
					children: label,
					onClick: expect.any( Function ),
				} )
			);
		} );

		it( 'persists the dismissal, then removes the card, without blocking the link from opening', async () => {
			persistDismissal.mockResolvedValue( {} );
			const action = getAction();
			const event = createMockEvent();

			await act( async () => {
				await action.onClick( event, dismissNotification );
			} );

			expect( persistDismissal ).toHaveBeenCalledTimes( 1 );
			expect( persistDismissal ).toHaveBeenCalledWith( id );
			expect( dismissNotification ).toHaveBeenCalledTimes( 1 );
			expect( dismissNotification ).toHaveBeenCalledWith( id );
			expect(
				persistDismissal.mock.invocationCallOrder[ 0 ]
			).toBeLessThan( dismissNotification.mock.invocationCallOrder[ 0 ] );
			expect( event.preventDefault ).not.toHaveBeenCalled();
		} );

		it( 'keeps the card and lets no error escape when persisting the dismissal fails', async () => {
			persistDismissal.mockRejectedValue( new Error( 'Request failed' ) );
			const action = getAction();
			const event = createMockEvent();

			await act( async () => {
				await expect(
					action.onClick( event, dismissNotification )
				).resolves.toBeUndefined();
			} );

			expect( persistDismissal ).toHaveBeenCalledWith( id );
			expect( dismissNotification ).not.toHaveBeenCalled();
			expect( event.preventDefault ).not.toHaveBeenCalled();
		} );
	} );
} );
