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
				collect_reviews_after_purchase: false,
				badge_widget_enabled: false,
			},
			saveSettings,
		} );
		getHistory.mockReturnValue( { push } );
		dismissNotification.mockResolvedValue( {} );
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

	it( "saves collect_reviews_after_purchase, blocks default navigation, dismisses the notification, and navigates to the anchor's href once the save succeeds", async () => {
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
				collect_reviews_after_purchase: true,
			} )
		);
		expect( dismissNotification ).toHaveBeenCalledWith(
			'google-customer-reviews-collect-reviews'
		);
		expect( push ).toHaveBeenCalledWith( '/settings-href' );
	} );

	it( "saves badge_widget_enabled, blocks default navigation, dismisses the notification, and navigates to the anchor's href once the save succeeds", async () => {
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
				badge_widget_enabled: true,
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

	it( 'still navigates when the setting saves but dismissing the notification fails', async () => {
		saveSettings.mockResolvedValue( {} );
		dismissNotification.mockRejectedValue(
			new Error( 'Something went wrong' )
		);

		const { result } = renderHook( () => useNotificationsSystemMap() );
		const action =
			result.current[ 'google-customer-reviews-collect-reviews' ]
				.actions[ 0 ];

		await act( async () => {
			await action.onClick( createMockEvent(), dismissNotification );
		} );

		expect( push ).toHaveBeenCalledWith( '/settings-href' );
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
} );
